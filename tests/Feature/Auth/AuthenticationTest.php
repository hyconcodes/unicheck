<?php

use App\Models\User;
use App\Mail\PasskeyEnrollmentCode;
use App\Services\WebauthnService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt as LivewireVolt;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response
        ->assertStatus(200)
        ->assertSee('Use fingerprint to sign in')
        ->assertSee('verify your student email below to register a passkey');
});

test('fingerprint login requests a discoverable credential without an allow list', function () {
    $options = app(WebauthnService::class)->getDiscoverableAuthenticationOptions();

    expect($options['publicKey'])
        ->not->toHaveKey('allowCredentials')
        ->and($options['publicKey']['userVerification'])->toBe('required');
});

test('fingerprint registration requires a discoverable credential', function () {
    $user = User::factory()->create();

    $options = app(WebauthnService::class)->getRegistrationOptions($user);

    expect($options['publicKey']['authenticatorSelection']['residentKey'])->toBe('required');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login');

    $response->assertHasErrors('email');

    $this->assertGuest();
});

test('users can start fingerprint login without entering an email or password', function () {
    Log::spy();

    $service = Mockery::mock(WebauthnService::class);
    $service->shouldReceive('getDiscoverableAuthenticationOptions')->once()->andReturn([
        'publicKey' => [
            'challenge' => 'challenge',
        ],
    ]);

    $this->app->instance(WebauthnService::class, $service);

    $response = LivewireVolt::test('auth.login')
        ->call('loginWithFingerprint');

    $response->assertDispatched('webauthn-login-start', function (string $event, array $params): bool {
        return $event === 'webauthn-login-start'
            && isset($params['options']['publicKey']['challenge']);
    });
    $this->assertGuest();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) =>
            $message === 'Fingerprint login: sign-in button clicked.'
            && $context['event'] === 'fingerprint_login_started'
            && !empty($context['attempt_id'])
        )
        ->once();
});

test('eligible students can verify email and register a passkey from the login screen', function () {
    Mail::fake();

    $user = User::factory()->withoutTwoFactor()->create();
    $user->assignRole(Role::findOrCreate('student', 'web'));
    $code = null;

    Mail::assertNothingSent();

    $service = Mockery::mock(WebauthnService::class);
    $service->shouldReceive('getRegistrationOptions')
        ->once()
        ->with(Mockery::on(fn (User $candidate) => $candidate->id === $user->id), 'webauthn_enrollment_challenge')
        ->andReturn(['publicKey' => ['challenge' => 'test-challenge']]);
    $service->shouldReceive('verifyRegistration')
        ->once()
        ->with(
            Mockery::on(fn (User $candidate) => $candidate->id === $user->id),
            Mockery::type('object'),
            'webauthn_enrollment_challenge'
        )
        ->andReturn(true);

    $this->app->instance(WebauthnService::class, $service);

    $component = LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->call('requestPasskeyEnrollmentCode')
        ->assertHasNoErrors()
        ->assertSet('passkeyEnrollmentCodeSent', true)
        ->assertSee('If an eligible student account matches this email');

    Mail::assertSent(PasskeyEnrollmentCode::class, function (PasskeyEnrollmentCode $mail) use ($user, &$code): bool {
        $code = $mail->code;

        return $mail->hasTo($user->email);
    });

    expect($code)->toMatch('/^\d{6}$/');

    $component
        ->set('passkeyEnrollmentCode', $code)
        ->call('verifyPasskeyEnrollmentCode')
        ->assertHasNoErrors()
        ->assertSet('passkeyEnrollmentVerified', true)
        ->call('startPasskeyEnrollment')
        ->assertDispatched('webauthn-enrollment-start', function (string $event, array $params): bool {
            return $event === 'webauthn-enrollment-start'
                && $params['options']['publicKey']['challenge'] === 'test-challenge';
        })
        ->call('completePasskeyEnrollment', 'client-data', 'attestation')
        ->assertRedirect(
            Features::canManageTwoFactorAuthentication()
                ? route('two-factor.show')
                : route('dashboard', absolute: false)
        );

    $this->assertAuthenticatedAs($user);
});

test('passkey setup code requests are generic for accounts that cannot enroll', function () {
    Mail::fake();

    $response = LivewireVolt::test('auth.login')
        ->set('email', 'unknown@example.com')
        ->call('requestPasskeyEnrollmentCode')
        ->assertHasNoErrors()
        ->assertSee('If an eligible student account matches this email');

    Mail::assertNothingSent();

    expect(session('passkey_enrollment_pending'))->toBeNull();
});

test('invalid passkey setup codes do not authorize device registration', function () {
    Mail::fake();

    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('student', 'web'));

    LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->call('requestPasskeyEnrollmentCode')
        ->set('passkeyEnrollmentCode', '000000')
        ->call('verifyPasskeyEnrollmentCode')
        ->assertSet('passkeyEnrollmentVerified', false)
        ->assertSet('passkeyEnrollmentError', 'That code is invalid or expired. Request a new code and try again.');

    expect(session('passkey_enrollment_user_id'))->toBeNull();
});

test('fingerprint login identifies and authenticates the account from the verified credential', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $service = Mockery::mock(WebauthnService::class);
    $service->shouldReceive('verifyDiscoverableAuthentication')
        ->once()
        ->andReturn($user);

    $this->app->instance(WebauthnService::class, $service);

    $expectedRedirect = Features::canManageTwoFactorAuthentication()
        ? route('two-factor.show')
        : route('dashboard', absolute: false);

    LivewireVolt::test('auth.login')
        ->call('completeFingerprintLogin', 'client-data', 'authenticator-data', 'signature', 'credential-id', 'user-handle')
        ->assertRedirect($expectedRedirect);

    $this->assertAuthenticatedAs($user);
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
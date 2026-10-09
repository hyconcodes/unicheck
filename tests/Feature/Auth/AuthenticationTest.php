<?php

use App\Models\User;
use App\Services\WebauthnService;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt as LivewireVolt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response
        ->assertStatus(200)
        ->assertSee('Use fingerprint to sign in')
        ->assertSee('No email or password needed.');
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
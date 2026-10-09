<?php

use App\Models\User;
use App\Mail\PasskeyEnrollmentCode;
use App\Services\WebauthnService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public bool $isFingerprintAuthenticating = false;

    public string $fingerprintError = '';

    public string $passkeyEnrollmentCode = '';

    public bool $passkeyEnrollmentCodeSent = false;

    public bool $passkeyEnrollmentVerified = false;

    public bool $isRegisteringPasskey = false;

    public string $passkeyEnrollmentMessage = '';

    public string $passkeyEnrollmentError = '';

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        $user = $this->validateCredentials();

        $this->authenticateUser($user);
    }

    public function loginWithFingerprint(WebauthnService $webauthnService): void
    {
        $this->fingerprintError = '';
        $this->isFingerprintAuthenticating = true;
        $attemptId = (string) Str::uuid();
        Session::put('webauthn_login_attempt_id', $attemptId);

        Log::info('Fingerprint login: sign-in button clicked.', [
            'event' => 'fingerprint_login_started',
            'attempt_id' => $attemptId,
        ]);

        try {
            $options = $webauthnService->getDiscoverableAuthenticationOptions();
            $this->dispatch('webauthn-login-start', options: $options);
        } catch (\Throwable $exception) {
            $this->fingerprintError = 'Could not start fingerprint sign-in. Please try again.';
            $this->isFingerprintAuthenticating = false;
            Session::forget(['webauthn_login_attempt_id', 'webauthn_login_challenge']);

            Log::error('Fingerprint login: challenge could not be started.', [
                'event' => 'fingerprint_login_start_failed',
                'attempt_id' => $attemptId,
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);
        }
    }

    public function fingerprintBrowserPromptStarted(): void
    {
        Log::info('Fingerprint login: browser received the challenge and is requesting a passkey.', [
            'event' => 'fingerprint_login_browser_prompt_started',
            'attempt_id' => Session::get('webauthn_login_attempt_id'),
        ]);
    }

    public function completeFingerprintLogin(
        WebauthnService $webauthnService,
        string $clientDataJSON,
        string $authenticatorData,
        string $signature,
        string $credentialId,
        string $userHandle,
    ): void {
        try {
            $assertionResponse = (object) [
                'clientDataJSON' => $clientDataJSON,
                'authenticatorData' => $authenticatorData,
                'signature' => $signature,
                'id' => $credentialId,
                'userHandle' => $userHandle,
            ];

            $user = $webauthnService->verifyDiscoverableAuthentication($assertionResponse);
            Log::info('Fingerprint login: device verification succeeded; continuing sign-in.', [
                'event' => 'fingerprint_login_user_verified',
                'attempt_id' => Session::get('webauthn_login_attempt_id'),
                'user_id' => $user->id,
            ]);
            $this->authenticateUser($user);
        } catch (\Exception $e) {
            $attemptId = Session::get('webauthn_login_attempt_id');
            $this->fingerprintError = 'Fingerprint login failed: ' . $e->getMessage();
            $this->isFingerprintAuthenticating = false;
            Session::forget(['webauthn_login_attempt_id', 'webauthn_login_challenge']);

            Log::warning('Fingerprint login: device verification failed.', [
                'event' => 'fingerprint_login_verification_failed',
                'attempt_id' => $attemptId,
                'exception' => $e::class,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    public function handleFingerprintLoginFailure(string $error): void
    {
        $attemptId = Session::get('webauthn_login_attempt_id');
        $safeError = Str::limit(str_replace(["\r", "\n"], ' ', $error), 250);

        $this->fingerprintError = $error;
        $this->isFingerprintAuthenticating = false;

        Log::warning('Fingerprint login: browser or authenticator did not complete sign-in.', [
            'event' => 'fingerprint_login_browser_failed',
            'attempt_id' => $attemptId,
            'reason' => $safeError,
        ]);

        Session::forget(['webauthn_login_attempt_id', 'webauthn_login_challenge']);
    }

    public function requestPasskeyEnrollmentCode(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($this->email));
        $attemptId = (string) Str::uuid();
        $emailKey = 'passkey-enrollment:email:' . hash('sha256', $email);
        $ipKey = 'passkey-enrollment:ip:' . hash('sha256', (string) request()->ip());

        $this->passkeyEnrollmentError = '';

        Log::info('Passkey enrollment: setup code requested.', [
            'event' => 'passkey_enrollment_code_requested',
            'attempt_id' => $attemptId,
        ]);

        if (RateLimiter::tooManyAttempts($emailKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            $this->passkeyEnrollmentMessage = 'For your security, wait before requesting another setup code.';

            Log::warning('Passkey enrollment: setup code request was rate limited.', [
                'event' => 'passkey_enrollment_code_rate_limited',
                'attempt_id' => $attemptId,
            ]);

            return;
        }

        $this->passkeyEnrollmentError = '';
        $this->passkeyEnrollmentMessage = '';
        $this->passkeyEnrollmentCodeSent = true;
        $this->passkeyEnrollmentVerified = false;
        $this->passkeyEnrollmentCode = '';
        Session::forget([
            'passkey_enrollment_pending',
            'passkey_enrollment_user_id',
            'passkey_enrollment_authorized_until',
            'webauthn_enrollment_challenge',
        ]);

        RateLimiter::hit($emailKey, 600);
        RateLimiter::hit($ipKey, 3600);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user && $user->email_verified_at && $user->isStudent()) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            Session::put('passkey_enrollment_pending', [
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10)->timestamp,
                'attempt_id' => $attemptId,
            ]);

            try {
                Mail::to($user->email)->send(new PasskeyEnrollmentCode($code));

                Log::info('Passkey enrollment: one-time code sent to verified student email.', [
                    'event' => 'passkey_enrollment_code_sent',
                    'attempt_id' => $attemptId,
                    'user_id' => $user->id,
                ]);
            } catch (\Throwable $exception) {
                Session::forget('passkey_enrollment_pending');
                RateLimiter::clear($emailKey);

                Log::error('Passkey enrollment: one-time code email could not be sent.', [
                    'event' => 'passkey_enrollment_email_failed',
                    'attempt_id' => $attemptId,
                    'user_id' => $user->id,
                    'exception' => $exception::class,
                    'reason' => $exception->getMessage(),
                ]);
            }
        } else {
            Log::info('Passkey enrollment: code requested for an ineligible account.', [
                'event' => 'passkey_enrollment_code_not_sent',
                'attempt_id' => $attemptId,
            ]);
        }

        $this->passkeyEnrollmentMessage = 'If an eligible student account matches this email, a setup code will be sent. Check your inbox and spam folder.';
    }

    public function verifyPasskeyEnrollmentCode(): void
    {
        $this->validate([
            'passkeyEnrollmentCode' => ['required', 'digits:6'],
        ]);

        $pending = Session::get('passkey_enrollment_pending');
        $attemptId = is_array($pending) ? ($pending['attempt_id'] ?? null) : null;
        $verifyKey = 'passkey-enrollment:verify:' . hash('sha256', (string) $attemptId . '|' . (string) request()->ip());

        Log::info('Passkey enrollment: one-time code verification started.', [
            'event' => 'passkey_enrollment_code_verification_started',
            'attempt_id' => $attemptId,
        ]);

        if (RateLimiter::tooManyAttempts($verifyKey, 5)) {
            $this->passkeyEnrollmentError = 'Too many incorrect codes. Request a new code and try again.';
            Session::forget('passkey_enrollment_pending');

            Log::warning('Passkey enrollment: code verification was rate limited.', [
                'event' => 'passkey_enrollment_code_verification_rate_limited',
                'attempt_id' => $attemptId,
            ]);

            return;
        }

        RateLimiter::hit($verifyKey, 600);

        if (
            !is_array($pending)
            || !isset($pending['user_id'], $pending['code_hash'], $pending['expires_at'])
            || $pending['expires_at'] < now()->timestamp
            || !Hash::check($this->passkeyEnrollmentCode, $pending['code_hash'])
        ) {
            $this->passkeyEnrollmentError = 'That code is invalid or expired. Request a new code and try again.';
            $failureReason = !is_array($pending) || !isset($pending['code_hash'])
                ? 'challenge_missing'
                : (!isset($pending['expires_at']) || $pending['expires_at'] < now()->timestamp
                    ? 'code_expired'
                    : 'code_mismatch');

            Log::warning('Passkey enrollment: one-time code verification failed.', [
                'event' => 'passkey_enrollment_code_rejected',
                'attempt_id' => $attemptId,
                'reason' => $failureReason,
            ]);

            if (is_array($pending) && isset($pending['expires_at']) && $pending['expires_at'] < now()->timestamp) {
                Session::forget('passkey_enrollment_pending');
            }

            return;
        }

        $user = User::find($pending['user_id']);
        if (!$user || !$user->email_verified_at || !$user->isStudent()) {
            Session::forget('passkey_enrollment_pending');
            $this->passkeyEnrollmentError = 'This account cannot enroll a passkey. Use the standard sign-in option or contact support.';

            Log::warning('Passkey enrollment: account was no longer eligible after code verification.', [
                'event' => 'passkey_enrollment_account_ineligible',
                'attempt_id' => $attemptId,
                'user_id' => $user?->id,
            ]);

            return;
        }

        RateLimiter::clear($verifyKey);
        Session::forget('passkey_enrollment_pending');
        Session::put([
            'passkey_enrollment_user_id' => $user->id,
            'passkey_enrollment_authorized_until' => now()->addMinutes(10)->timestamp,
        ]);

        $this->passkeyEnrollmentCode = '';
        $this->passkeyEnrollmentVerified = true;
        $this->passkeyEnrollmentError = '';
        $this->passkeyEnrollmentMessage = 'Email verified. Now register a passkey on this device.';

        Log::info('Passkey enrollment: verified student email code accepted.', [
            'event' => 'passkey_enrollment_email_verified',
            'attempt_id' => $attemptId,
            'user_id' => $user->id,
        ]);
    }

    public function startPasskeyEnrollment(WebauthnService $webauthnService): void
    {
        $user = $this->getAuthorizedPasskeyEnrollmentUser();
        if (!$user) {
            $this->passkeyEnrollmentVerified = false;
            $this->passkeyEnrollmentError = 'The setup session expired. Request a new email code and try again.';

            Log::warning('Passkey enrollment: registration requested without valid email verification.', [
                'event' => 'passkey_enrollment_authorization_expired',
            ]);

            return;
        }

        $this->passkeyEnrollmentError = '';
        $this->isRegisteringPasskey = true;

        try {
            $options = $webauthnService->getRegistrationOptions($user, 'webauthn_enrollment_challenge');

            $this->dispatch('webauthn-enrollment-start', options: $options);

            Log::info('Passkey enrollment: device registration challenge generated.', [
                'event' => 'passkey_enrollment_challenge_generated',
                'user_id' => $user->id,
            ]);
        } catch (\Throwable $exception) {
            $this->isRegisteringPasskey = false;
            $this->passkeyEnrollmentError = 'Could not start passkey setup. Please try again.';

            Log::error('Passkey enrollment: device registration could not start.', [
                'event' => 'passkey_enrollment_start_failed',
                'user_id' => $user->id,
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);
        }
    }

    public function passkeyEnrollmentBrowserPromptStarted(): void
    {
        Log::info('Passkey enrollment: browser received the challenge and is requesting a device passkey.', [
            'event' => 'passkey_enrollment_browser_prompt_started',
            'user_id' => Session::get('passkey_enrollment_user_id'),
        ]);
    }

    public function completePasskeyEnrollment(
        WebauthnService $webauthnService,
        string $clientDataJSON,
        string $attestationObject,
    ): void {
        $user = $this->getAuthorizedPasskeyEnrollmentUser();
        if (!$user) {
            Session::forget('webauthn_enrollment_challenge');
            $this->passkeyEnrollmentVerified = false;
            $this->isRegisteringPasskey = false;
            $this->passkeyEnrollmentError = 'The setup session expired. Request a new email code and try again.';

            Log::warning('Passkey enrollment: response received after enrollment authorization expired.', [
                'event' => 'passkey_enrollment_authorization_expired',
            ]);

            return;
        }

        try {
            $webauthnService->verifyRegistration($user, (object) [
                'clientDataJSON' => $clientDataJSON,
                'attestationObject' => $attestationObject,
            ], 'webauthn_enrollment_challenge');

            Session::forget([
                'passkey_enrollment_user_id',
                'passkey_enrollment_authorized_until',
                'webauthn_enrollment_challenge',
            ]);
            $this->isRegisteringPasskey = false;
            $this->passkeyEnrollmentVerified = false;
            $this->passkeyEnrollmentMessage = 'Passkey registered. Signing you in…';

            Log::info('Passkey enrollment: device passkey registered successfully.', [
                'event' => 'passkey_enrollment_succeeded',
                'user_id' => $user->id,
            ]);

            $this->authenticateUser($user);
        } catch (\Throwable $exception) {
            $this->isRegisteringPasskey = false;
            $this->passkeyEnrollmentError = 'Passkey setup failed. Please try again.';

            Log::warning('Passkey enrollment: device registration failed.', [
                'event' => 'passkey_enrollment_failed',
                'user_id' => $user->id,
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);
        }
    }

    public function handlePasskeyEnrollmentFailure(string $error): void
    {
        $this->isRegisteringPasskey = false;
        $safeError = Str::limit(str_replace(["\r", "\n"], ' ', $error), 250);
        $this->passkeyEnrollmentError = $safeError;
        Session::forget('webauthn_enrollment_challenge');

        Log::warning('Passkey enrollment: browser or authenticator did not complete registration.', [
            'event' => 'passkey_enrollment_browser_failed',
            'user_id' => Session::get('passkey_enrollment_user_id'),
            'reason' => $safeError,
        ]);
    }

    protected function getAuthorizedPasskeyEnrollmentUser(): ?User
    {
        $userId = Session::get('passkey_enrollment_user_id');
        $authorizedUntil = Session::get('passkey_enrollment_authorized_until');

        if (!$userId || !$authorizedUntil || $authorizedUntil < now()->timestamp) {
            Session::forget([
                'passkey_enrollment_user_id',
                'passkey_enrollment_authorized_until',
                'webauthn_enrollment_challenge',
            ]);

            return null;
        }

        $user = User::find($userId);

        if (!$user || !$user->email_verified_at || !$user->isStudent()) {
            Session::forget([
                'passkey_enrollment_user_id',
                'passkey_enrollment_authorized_until',
                'webauthn_enrollment_challenge',
            ]);

            return null;
        }

        return $user;
    }

    protected function authenticateUser(User $user): void
    {
        if (Features::canManageTwoFactorAuthentication()) {
            if (! $user->hasEnabledTwoFactorAuthentication()) {
                Session::put([
                    'login.id' => $user->getKey(),
                    'login.remember' => $this->remember,
                    'two_factor_setup_required' => true,
                ]);

                Auth::login($user, $this->remember);
                Log::info('Fingerprint login: user signed in; two-factor setup is required.', [
                    'event' => 'fingerprint_login_two_factor_setup_required',
                    'attempt_id' => Session::pull('webauthn_login_attempt_id'),
                    'user_id' => $user->id,
                ]);

                $this->redirect(route('two-factor.show'), navigate: true);
                return;
            }

            Session::put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->remember,
            ]);

            Log::info('Fingerprint login: credential verified; two-factor challenge is required.', [
                'event' => 'fingerprint_login_two_factor_required',
                'attempt_id' => Session::pull('webauthn_login_attempt_id'),
                'user_id' => $user->id,
            ]);

            $this->redirect(route('two-factor.login'), navigate: true);
            return;
        }

        Auth::login($user, $this->remember);

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        Log::info('Fingerprint login: user signed in successfully.', [
            'event' => 'fingerprint_login_succeeded',
            'attempt_id' => Session::pull('webauthn_login_attempt_id'),
            'user_id' => $user->id,
        ]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Validate the user's credentials.
     */
    protected function validateCredentials(): User
    {
        $user = Auth::getProvider()->retrieveByCredentials(['email' => $this->email, 'password' => $this->password]);

        if (! $user || ! Auth::getProvider()->validateCredentials($user, ['password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $user;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Password -->
        <div class="relative">
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            @if (Route::has('password.request'))
                <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </div>
    </form>

    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase tracking-[0.2em] text-zinc-500">
            <span class="bg-white dark:bg-zinc-900 px-2">Or</span>
        </div>
    </div>

    <div class="flex flex-col gap-3">
        <p class="text-sm text-center text-zinc-600 dark:text-zinc-400">
            {{ __('Sign in with a passkey available on this device, including one synced by your passkey provider. If it is not available, verify your student email below to register a passkey on this device. Password sign-in and attendance-screen setup are also available.') }}
        </p>
        <flux:button
            type="button"
            variant="outline"
            class="w-full"
            wire:click="loginWithFingerprint"
            :disabled="$isFingerprintAuthenticating"
        >
            {{ $isFingerprintAuthenticating ? __('Checking fingerprint…') : __('Use fingerprint to sign in') }}
        </flux:button>

        @if ($fingerprintError)
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
                {{ $fingerprintError }}
            </div>
        @endif

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('No passkey on this device?') }}
            </h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Verify your student email once, then register a passkey on this device. Future sign-ins can use the passkey directly.') }}
            </p>

            @if (!$passkeyEnrollmentVerified)
                @if (!$passkeyEnrollmentCodeSent)
                    <flux:button
                        type="button"
                        variant="outline"
                        class="mt-3 w-full"
                        wire:click="requestPasskeyEnrollmentCode"
                    >
                        {{ __('Email me a passkey setup code') }}
                    </flux:button>
                @else
                    <form wire:submit="verifyPasskeyEnrollmentCode" class="mt-3 flex flex-col gap-3">
                        <flux:input
                            wire:model="passkeyEnrollmentCode"
                            :label="__('Six-digit email code')"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            required
                        />
                        <flux:button type="submit" variant="outline" class="w-full">
                            {{ __('Verify code') }}
                        </flux:button>
                        <flux:button
                            type="button"
                            variant="ghost"
                            class="w-full"
                            wire:click="requestPasskeyEnrollmentCode"
                        >
                            {{ __('Send another code') }}
                        </flux:button>
                    </form>
                @endif
            @else
                <flux:button
                    type="button"
                    variant="primary"
                    class="mt-3 w-full"
                    wire:click="startPasskeyEnrollment"
                    :disabled="$isRegisteringPasskey"
                >
                    {{ $isRegisteringPasskey ? __('Waiting for your device…') : __('Register passkey on this device') }}
                </flux:button>
            @endif

            @if ($passkeyEnrollmentMessage)
                <p class="mt-3 text-sm text-green-700 dark:text-green-300" role="status">
                    {{ $passkeyEnrollmentMessage }}
                </p>
            @endif

            @if ($passkeyEnrollmentError)
                <p class="mt-3 text-sm text-red-700 dark:text-red-300" role="alert">
                    {{ $passkeyEnrollmentError }}
                </p>
            @endif
        </div>
    </div>

    @if (Route::has('register'))
        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate class="text-green-600 hover:text-green-700 font-medium">{{ __('Sign up as Student') }}</flux:link>
            <span>{{ __('or') }}</span>
            <flux:link :href="route('register.lecturer')" wire:navigate class="text-green-600 hover:text-green-700 font-medium">{{ __('Sign up as Lecturer') }}</flux:link>
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('webauthn-login-start', async ({ options }) => {
        try {
            if (!window.PublicKeyCredential || !navigator.credentials?.get) {
                $wire.handleFingerprintLoginFailure('This browser does not support passkey sign-in.');
                return;
            }

            options.publicKey.challenge = base64UrlToArrayBuffer(options.publicKey.challenge);

            if (options.publicKey.allowCredentials) {
                options.publicKey.allowCredentials.forEach(cred => {
                    cred.id = base64UrlToArrayBuffer(cred.id);
                });
            }

            const assertionPromise = navigator.credentials.get({ publicKey: options.publicKey });
            $wire.fingerprintBrowserPromptStarted();
            const assertion = await assertionPromise;

            const clientDataJSON = arrayBufferToBase64(assertion.response.clientDataJSON);
            const authenticatorData = arrayBufferToBase64(assertion.response.authenticatorData);
            const signature = arrayBufferToBase64(assertion.response.signature);
            const credentialId = arrayBufferToBase64(assertion.rawId);
            const userHandle = assertion.response.userHandle
                ? arrayBufferToBase64(assertion.response.userHandle)
                : '';

            await $wire.completeFingerprintLogin(clientDataJSON, authenticatorData, signature, credentialId, userHandle);
        } catch (error) {
            if (error.name === 'NotAllowedError') {
                $wire.handleFingerprintLoginFailure('Chrome did not find or complete a passkey for UniCheck on this device. If you expect it to sync, check that this device uses the same passkey provider and account. Otherwise sign in with your email and password on this device, open a class attendance screen, capture your location, then choose Register passkey on this device.');
            } else if (error.name === 'SecurityError') {
                $wire.handleFingerprintLoginFailure('Security error. Make sure you are using HTTPS or localhost.');
            } else {
                $wire.handleFingerprintLoginFailure('Fingerprint login failed: ' + error.message);
            }
        }
    });

    Livewire.on('webauthn-enrollment-start', async ({ options }) => {
        try {
            if (!window.PublicKeyCredential || !navigator.credentials?.create) {
                $wire.handlePasskeyEnrollmentFailure('This browser does not support passkey registration.');
                return;
            }

            options.publicKey.challenge = base64UrlToArrayBuffer(options.publicKey.challenge);
            options.publicKey.user.id = base64UrlToArrayBuffer(options.publicKey.user.id);

            if (options.publicKey.excludeCredentials) {
                options.publicKey.excludeCredentials.forEach(credential => {
                    credential.id = base64UrlToArrayBuffer(credential.id);
                });
            }

            $wire.passkeyEnrollmentBrowserPromptStarted();
            const credential = await navigator.credentials.create({ publicKey: options.publicKey });

            if (!credential) {
                $wire.handlePasskeyEnrollmentFailure('The authenticator did not return a passkey.');
                return;
            }

            await $wire.completePasskeyEnrollment(
                arrayBufferToBase64(credential.response.clientDataJSON),
                arrayBufferToBase64(credential.response.attestationObject),
            );
        } catch (error) {
            if (error.name === 'NotAllowedError') {
                $wire.handlePasskeyEnrollmentFailure('Passkey setup was cancelled or timed out. Please try again.');
            } else if (error.name === 'SecurityError') {
                $wire.handlePasskeyEnrollmentFailure('Security error. Make sure you are using HTTPS or localhost.');
            } else {
                $wire.handlePasskeyEnrollmentFailure('Passkey setup failed: ' + error.message);
            }
        }
    });

    function base64UrlToArrayBuffer(base64Url) {
        if (!base64Url) return new Uint8Array(0);

        let base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        while (base64.length % 4 !== 0) base64 += '=';

        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);

        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }

        return bytes;
    }

    function arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';

        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }

        return btoa(binary);
    }
</script>
@endscript

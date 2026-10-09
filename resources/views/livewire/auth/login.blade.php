<?php

use App\Models\User;
use App\Services\WebauthnService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            {{ __('Sign in with a fingerprint or passkey registered on your device. No email or password needed.') }}
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
                $wire.handleFingerprintLoginFailure('No passkey was selected. Your attendance credential may not support direct sign-in; use email and password once, then register a discoverable passkey.');
            } else if (error.name === 'SecurityError') {
                $wire.handleFingerprintLoginFailure('Security error. Make sure you are using HTTPS or localhost.');
            } else {
                $wire.handleFingerprintLoginFailure('Fingerprint login failed: ' + error.message);
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

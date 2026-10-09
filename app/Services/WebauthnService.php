<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Support\Facades\Log;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\Binary\ByteBuffer;

class WebauthnService
{
    private WebAuthn $webAuthn;

    public function __construct()
    {
        $rpName = config('app.name', 'BioCheck');
        $rpId = config('webauthn.rp_id', 'localhost');
        $this->webAuthn = new WebAuthn($rpName, $rpId, null, true);
    }

    /**
     * Generate registration options for the browser.
     * Returns a plain array so Livewire can serialize it to the frontend.
     */
    public function getRegistrationOptions(User $user, string $challengeSessionKey = 'webauthn_challenge'): array
    {
        Log::info('Fingerprint registration: generating WebAuthn registration challenge.', [
            'event' => 'fingerprint_registration_challenge_started',
            'user_id' => $user->id,
        ]);

        $existingCredentials = WebauthnCredential::where('user_id', $user->id)
            ->pluck('credential_id')
            ->toArray();

        $excludeIds = array_map(function ($id) {
            return base64_decode($id);
        }, $existingCredentials);

        $options = $this->webAuthn->getCreateArgs(
            (string) $user->id,
            $user->email,
            $user->name,
            60,
            true,
            true,
            null,
            $excludeIds
        );

        // Persist challenge for verification on the next request
        session([$challengeSessionKey => $this->webAuthn->getChallenge()->getHex()]);

        Log::info('Fingerprint registration: challenge generated; waiting for device verification.', [
            'event' => 'fingerprint_registration_challenge_generated',
            'user_id' => $user->id,
            'existing_credentials' => count($existingCredentials),
        ]);

        // Convert stdClass + ByteBuffer graph to a plain JSON-friendly array
        return json_decode(json_encode($options), true);
    }

    /**
     * Verify and store a registered credential.
     */
    public function verifyRegistration(
        User $user,
        object $attestationResponse,
        string $challengeSessionKey = 'webauthn_challenge',
    ): bool
    {
        Log::info('Fingerprint registration: device response received; verifying registration.', [
            'event' => 'fingerprint_registration_verification_started',
            'user_id' => $user->id,
        ]);

        $challengeHex = session()->pull($challengeSessionKey);
        if (!$challengeHex) {
            Log::warning('Fingerprint registration failed: challenge expired or missing.', [
                'event' => 'fingerprint_registration_failed',
                'user_id' => $user->id,
                'reason' => 'challenge_missing',
            ]);

            throw new \Exception('Registration challenge expired. Please try again.');
        }

        $clientDataJSON = base64_decode($attestationResponse->clientDataJSON ?? '');
        $attestationObject = base64_decode($attestationResponse->attestationObject ?? '');

        if ($clientDataJSON === '' || $attestationObject === '') {
            Log::warning('Fingerprint registration failed: invalid response data.', [
                'event' => 'fingerprint_registration_failed',
                'user_id' => $user->id,
                'reason' => 'invalid_response_data',
            ]);

            throw new \Exception('Invalid attestation data received.');
        }

        $challengeBinary = hex2bin($challengeHex);

        // processCreate validates origin, challenge, RP ID and returns credential data
        try {
            $data = $this->webAuthn->processCreate(
                $clientDataJSON,
                $attestationObject,
                $challengeBinary,
                true,  // requireUserVerification
                true   // requireUserPresent
            );
        } catch (\Throwable $exception) {
            Log::warning('Fingerprint registration rejected by WebAuthn validation.', [
                'event' => 'fingerprint_registration_failed',
                'user_id' => $user->id,
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        // credentialId is a ByteBuffer
        $credentialIdBinary = $data->credentialId instanceof ByteBuffer
            ? $data->credentialId->getBinaryString()
            : $data->credentialId;

        $credentialIdBase64 = base64_encode($credentialIdBinary);
        $publicKeyPem = $data->credentialPublicKey; // PEM string

        $existingCredential = WebauthnCredential::where('credential_id', $credentialIdBase64)->first();
        if ($existingCredential) {
            if ((int) $existingCredential->user_id !== (int) $user->id) {
                throw new \Exception('This passkey is already registered to another account.');
            }

            Log::info('Fingerprint registration completed: credential already belongs to an account.', [
                'event' => 'fingerprint_registration_already_registered',
                'user_id' => $user->id,
            ]);

            return true;
        }

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => $credentialIdBase64,
            'public_key' => $publicKeyPem,
            'authenticator_type' => $data->attestationFormat ?? null,
            'is_resident_key' => true,
            'device_name' => $attestationResponse->deviceName ?? null,
            'signature_count' => $data->signatureCounter ?? 0,
        ]);

        Log::info('Fingerprint registration completed successfully.', [
            'event' => 'fingerprint_registration_succeeded',
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Generate authentication options for the browser.
     */
    public function getAuthenticationOptions(User $user): array
    {
        Log::info('Fingerprint authentication: generating account-specific challenge.', [
            'event' => 'fingerprint_authentication_challenge_started',
            'user_id' => $user->id,
        ]);

        $credentials = WebauthnCredential::where('user_id', $user->id)->get();

        $credentialIds = $credentials->map(function ($cred) {
            return base64_decode($cred->credential_id);
        })->toArray();

        $options = $this->webAuthn->getGetArgs(
            $credentialIds,
            60,
            true,
            true,
            true,
            true,
            true,
            true
        );

        session(['webauthn_challenge' => $this->webAuthn->getChallenge()->getHex()]);

        Log::info('Fingerprint authentication: account-specific challenge generated.', [
            'event' => 'fingerprint_authentication_challenge_generated',
            'user_id' => $user->id,
            'credential_count' => $credentials->count(),
        ]);

        return json_decode(json_encode($options), true);
    }

    /**
     * Generate options for signing in without an account identifier.
     */
    public function getDiscoverableAuthenticationOptions(): array
    {
        Log::info('Fingerprint login: generating discoverable sign-in challenge.', [
            'event' => 'fingerprint_login_challenge_started',
            'attempt_id' => session('webauthn_login_attempt_id'),
        ]);

        $options = $this->webAuthn->getGetArgs(
            [],
            60,
            false,
            false,
            false,
            true,
            true,
            true
        );

        session(['webauthn_login_challenge' => $this->webAuthn->getChallenge()->getHex()]);

        Log::info('Fingerprint login: challenge generated; waiting for device response.', [
            'event' => 'fingerprint_login_challenge_generated',
            'attempt_id' => session('webauthn_login_attempt_id'),
        ]);

        return json_decode(json_encode($options), true);
    }

    /**
     * Verify an authentication assertion.
     */
    public function verifyAuthentication(User $user, object $assertionResponse): bool
    {
        Log::info('Fingerprint authentication: verifying device assertion for account.', [
            'event' => 'fingerprint_authentication_verification_started',
            'user_id' => $user->id,
        ]);

        $challengeHex = session()->pull('webauthn_challenge');
        if (!$challengeHex) {
            Log::warning('Fingerprint authentication failed: challenge expired or missing.', [
                'event' => 'fingerprint_authentication_failed',
                'user_id' => $user->id,
                'reason' => 'challenge_missing',
            ]);

            throw new \Exception('Authentication challenge expired. Please try again.');
        }

        $credentialId = $this->decodeAssertionField($assertionResponse, 'id');

        $credential = WebauthnCredential::where('user_id', $user->id)
            ->where('credential_id', base64_encode($credentialId))
            ->first();

        if (!$credential) {
            Log::warning('Fingerprint authentication failed: credential did not match this account.', [
                'event' => 'fingerprint_authentication_failed',
                'user_id' => $user->id,
                'reason' => 'credential_not_found_for_account',
            ]);

            throw new \Exception('Credential not found.');
        }

        $this->verifyAssertion($credential, $assertionResponse, $challengeHex);

        return true;
    }

    /**
     * Verify an account-less assertion and return the account bound to its credential.
     */
    public function verifyDiscoverableAuthentication(object $assertionResponse): User
    {
        $attemptId = session('webauthn_login_attempt_id');

        Log::info('Fingerprint login: device response received; resolving registered credential.', [
            'event' => 'fingerprint_login_assertion_received',
            'attempt_id' => $attemptId,
        ]);

        $challengeHex = session()->pull('webauthn_login_challenge');
        if (!$challengeHex) {
            Log::warning('Fingerprint login failed: challenge expired or missing.', [
                'event' => 'fingerprint_login_failed',
                'attempt_id' => $attemptId,
                'reason' => 'challenge_missing',
            ]);

            throw new \Exception('Authentication challenge expired. Please try again.');
        }

        try {
            $credentialId = $this->decodeAssertionField($assertionResponse, 'id');
            $userHandle = $this->decodeAssertionField($assertionResponse, 'userHandle');

            $credential = WebauthnCredential::where('credential_id', base64_encode($credentialId))->first();
            if (!$credential) {
                throw new \Exception('Credential not found.');
            }

            if (!hash_equals((string) $credential->user_id, $userHandle)) {
                throw new \Exception('Credential does not belong to the identified account.');
            }

            $user = $credential->user;
            if (!$user) {
                throw new \Exception('Credential account not found.');
            }

            Log::info('Fingerprint login: credential matched an account; verifying its signature.', [
                'event' => 'fingerprint_login_credential_matched',
                'attempt_id' => $attemptId,
                'user_id' => $user->id,
            ]);

            $this->verifyAssertion($credential, $assertionResponse, $challengeHex);

            Log::info('Fingerprint login: assertion verified successfully.', [
                'event' => 'fingerprint_login_assertion_verified',
                'attempt_id' => $attemptId,
                'user_id' => $user->id,
            ]);

            return $user;
        } catch (\Throwable $exception) {
            Log::warning('Fingerprint login failed while verifying the device response.', [
                'event' => 'fingerprint_login_failed',
                'attempt_id' => $attemptId,
                'reason' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }

    private function decodeAssertionField(object $assertionResponse, string $field): string
    {
        $encoded = $assertionResponse->{$field} ?? null;
        $decoded = is_string($encoded) ? base64_decode($encoded, true) : false;

        if ($decoded === false || $decoded === '') {
            Log::warning('Fingerprint assertion contains missing or invalid data.', [
                'event' => 'fingerprint_assertion_invalid_data',
                'field' => $field,
                'attempt_id' => session('webauthn_login_attempt_id'),
            ]);

            throw new \Exception('Invalid assertion data received.');
        }

        return $decoded;
    }

    private function verifyAssertion(WebauthnCredential $credential, object $assertionResponse, string $challengeHex): void
    {
        $clientDataJSON = $this->decodeAssertionField($assertionResponse, 'clientDataJSON');
        $authenticatorData = $this->decodeAssertionField($assertionResponse, 'authenticatorData');
        $signature = $this->decodeAssertionField($assertionResponse, 'signature');
        $publicKey = $credential->public_key;
        $challengeBinary = hex2bin($challengeHex);

        try {
            $this->webAuthn->processGet(
                $clientDataJSON,
                $authenticatorData,
                $signature,
                $publicKey,
                $challengeBinary,
                $credential->signature_count,
                true,
                true
            );
        } catch (\Throwable $exception) {
            Log::warning('Fingerprint assertion rejected by WebAuthn validation.', [
                'event' => 'fingerprint_assertion_rejected',
                'user_id' => $credential->user_id,
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $signatureCounter = $this->webAuthn->getSignatureCounter();
        $credential->markUsed($signatureCounter);

        Log::info('Fingerprint assertion verified and credential usage recorded.', [
            'event' => 'fingerprint_assertion_verified',
            'user_id' => $credential->user_id,
        ]);
    }

    public function userHasCredentials(User $user): bool
    {
        return WebauthnCredential::where('user_id', $user->id)->exists();
    }

    public function getWebAuthnInstance(): WebAuthn
    {
        return $this->webAuthn;
    }
}

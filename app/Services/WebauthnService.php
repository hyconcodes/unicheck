<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebauthnCredential;
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
     */
    public function getRegistrationOptions(User $user): object
    {
        $existingCredentials = WebauthnCredential::where('user_id', $user->id)
            ->pluck('credential_id')
            ->toArray();

        $excludeIds = array_map(function ($id) {
            return base64_decode($id);
        }, $existingCredentials);

        return $this->webAuthn->getCreateArgs(
            (string) $user->id,
            $user->email,
            $user->name,
            60,
            false,
            true,
            null,
            $excludeIds
        );
    }

    /**
     * Verify and store a registered credential.
     */
    public function verifyRegistration(User $user, object $attestationResponse): bool
    {
        $challenge = $this->webAuthn->getChallenge()->getHex();
        $clientDataJSON = base64_decode($attestationResponse->clientDataJSON ?? '');
        $attestationObject = base64_decode($attestationResponse->attestationObject ?? '');

        $this->webAuthn->loadObject($clientDataJSON, $attestationObject, $challenge);

        $credentialId = $this->webAuthn->getCredentialId();
        $publicKey = $this->webAuthn->getPublicKey();

        if ($credentialId === null || $publicKey === null) {
            return false;
        }

        $credentialIdBase64 = base64_encode($credentialId);

        // Check for duplicate credential
        if (WebauthnCredential::where('credential_id', $credentialIdBase64)->exists()) {
            return true;
        }

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => $credentialIdBase64,
            'public_key' => $publicKey,
            'authenticator_type' => $this->webAuthn->getAuthenticatorDet()->getAsString(),
            'device_name' => $attestationResponse->deviceName ?? null,
        ]);

        return true;
    }

    /**
     * Generate authentication options for the browser.
     */
    public function getAuthenticationOptions(User $user): object
    {
        $credentials = WebauthnCredential::where('user_id', $user->id)->get();

        $credentialIds = $credentials->map(function ($cred) {
            return base64_decode($cred->credential_id);
        })->toArray();

        return $this->webAuthn->getGetArgs(
            $credentialIds,
            60,
            true,
            true,
            true,
            true,
            true,
            true
        );
    }

    /**
     * Verify an authentication assertion.
     */
    public function verifyAuthentication(User $user, object $assertionResponse): bool
    {
        $challenge = $this->webAuthn->getChallenge()->getHex();
        $clientDataJSON = base64_decode($assertionResponse->clientDataJSON ?? '');
        $authenticatorData = base64_decode($assertionResponse->authenticatorData ?? '');
        $signature = base64_decode($assertionResponse->signature ?? '');
        $credentialId = base64_decode($assertionResponse->id ?? '');

        $credential = WebauthnCredential::where('user_id', $user->id)
            ->where('credential_id', base64_encode($credentialId))
            ->first();

        if (!$credential) {
            return false;
        }

        $publicKey = $credential->public_key;

        $this->webAuthn->loadObject($clientDataJSON, $authenticatorData, $signature, $credentialId, $publicKey, $challenge);

        $signatureCounter = $this->webAuthn->getSignatureCounter();
        $credential->markUsed($signatureCounter);

        return true;
    }

    /**
     * Check if a user has any registered WebAuthn credentials.
     */
    public function userHasCredentials(User $user): bool
    {
        return WebauthnCredential::where('user_id', $user->id)->exists();
    }

    /**
     * Get the WebAuthn instance.
     */
    public function getWebAuthnInstance(): WebAuthn
    {
        return $this->webAuthn;
    }
}

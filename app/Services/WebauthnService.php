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
     * Returns a plain array so Livewire can serialize it to the frontend.
     */
    public function getRegistrationOptions(User $user): array
    {
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
            false,
            true,
            null,
            $excludeIds
        );

        // Persist challenge for verification on the next request
        session(['webauthn_challenge' => $this->webAuthn->getChallenge()->getHex()]);

        // Convert stdClass + ByteBuffer graph to a plain JSON-friendly array
        return json_decode(json_encode($options), true);
    }

    /**
     * Verify and store a registered credential.
     */
    public function verifyRegistration(User $user, object $attestationResponse): bool
    {
        $challengeHex = session()->pull('webauthn_challenge');
        if (!$challengeHex) {
            throw new \Exception('Registration challenge expired. Please try again.');
        }

        $clientDataJSON = base64_decode($attestationResponse->clientDataJSON ?? '');
        $attestationObject = base64_decode($attestationResponse->attestationObject ?? '');

        if ($clientDataJSON === '' || $attestationObject === '') {
            throw new \Exception('Invalid attestation data received.');
        }

        $challengeBinary = hex2bin($challengeHex);

        // processCreate validates origin, challenge, RP ID and returns credential data
        $data = $this->webAuthn->processCreate(
            $clientDataJSON,
            $attestationObject,
            $challengeBinary,
            true,  // requireUserVerification
            true   // requireUserPresent
        );

        // credentialId is a ByteBuffer
        $credentialIdBinary = $data->credentialId instanceof ByteBuffer
            ? $data->credentialId->getBinaryString()
            : $data->credentialId;

        $credentialIdBase64 = base64_encode($credentialIdBinary);
        $publicKeyPem = $data->credentialPublicKey; // PEM string

        if (WebauthnCredential::where('credential_id', $credentialIdBase64)->exists()) {
            return true;
        }

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => $credentialIdBase64,
            'public_key' => $publicKeyPem,
            'authenticator_type' => $data->attestationFormat ?? null,
            'device_name' => $attestationResponse->deviceName ?? null,
            'signature_count' => $data->signatureCounter ?? 0,
        ]);

        return true;
    }

    /**
     * Generate authentication options for the browser.
     */
    public function getAuthenticationOptions(User $user): array
    {
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

        return json_decode(json_encode($options), true);
    }

    /**
     * Verify an authentication assertion.
     */
    public function verifyAuthentication(User $user, object $assertionResponse): bool
    {
        $challengeHex = session()->pull('webauthn_challenge');
        if (!$challengeHex) {
            throw new \Exception('Authentication challenge expired. Please try again.');
        }

        $clientDataJSON = base64_decode($assertionResponse->clientDataJSON ?? '');
        $authenticatorData = base64_decode($assertionResponse->authenticatorData ?? '');
        $signature = base64_decode($assertionResponse->signature ?? '');
        $credentialId = base64_decode($assertionResponse->id ?? '');

        if ($clientDataJSON === '' || $authenticatorData === '' || $signature === '' || $credentialId === '') {
            throw new \Exception('Invalid assertion data received.');
        }

        $credential = WebauthnCredential::where('user_id', $user->id)
            ->where('credential_id', base64_encode($credentialId))
            ->first();

        if (!$credential) {
            throw new \Exception('Credential not found.');
        }

        $publicKey = $credential->public_key;
        $challengeBinary = hex2bin($challengeHex);

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

        $signatureCounter = $this->webAuthn->getSignatureCounter();
        $credential->markUsed($signatureCounter);

        return true;
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

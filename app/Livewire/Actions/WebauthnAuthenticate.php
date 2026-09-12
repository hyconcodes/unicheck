<?php

namespace App\Livewire\Actions;

use App\Models\User;
use App\Services\WebauthnService;
use Livewire\Component;

class WebauthnAuthenticate extends Component
{
    public bool $isAuthenticating = false;
    public bool $authenticationComplete = false;
    public string $authenticationError = '';

    public function startAuthentication(WebauthnService $webauthnService): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->authenticationError = 'Authentication required.';
            return;
        }

        if (!$webauthnService->userHasCredentials($user)) {
            $this->authenticationError = 'No fingerprint registered. Please register first.';
            return;
        }

        $this->isAuthenticating = true;
        $this->authenticationError = '';

        $options = $webauthnService->getAuthenticationOptions($user);

        $this->dispatch('webauthn-authenticate-start', [
            'options' => $options,
        ]);
    }

    public function completeAuthentication(WebauthnService $webauthnService, object $assertionResponse): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->authenticationError = 'Authentication required.';
            $this->isAuthenticating = false;
            return;
        }

        try {
            $result = $webauthnService->verifyAuthentication($user, $assertionResponse);

            if ($result) {
                $this->authenticationComplete = true;
                $this->isAuthenticating = false;
                $this->authenticationError = '';
            } else {
                $this->authenticationError = 'Fingerprint verification failed. Please try again.';
                $this->isAuthenticating = false;
            }
        } catch (\Exception $e) {
            $this->authenticationError = 'Verification failed: ' . $e->getMessage();
            $this->isAuthenticating = false;
        }
    }

    public function authenticationFailed(string $error): void
    {
        $this->authenticationError = $error;
        $this->isAuthenticating = false;
    }

    public function render()
    {
        return '';
    }
}

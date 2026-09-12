<?php

namespace App\Livewire\Actions;

use App\Models\User;
use App\Services\WebauthnService;
use Livewire\Component;

class WebauthnRegister extends Component
{
    public bool $isRegistering = false;
    public bool $registrationComplete = false;
    public string $registrationError = '';

    public function startRegistration(WebauthnService $webauthnService): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->registrationError = 'Authentication required.';
            return;
        }

        $this->isRegistering = true;
        $this->registrationError = '';

        $options = $webauthnService->getRegistrationOptions($user);

        $this->dispatch('webauthn-register-start', [
            'options' => $options,
        ]);
    }

    public function completeRegistration(WebauthnService $webauthnService, object $attestationResponse): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->registrationError = 'Authentication required.';
            $this->isRegistering = false;
            return;
        }

        try {
            $result = $webauthnService->verifyRegistration($user, $attestationResponse);

            if ($result) {
                $this->registrationComplete = true;
                $this->isRegistering = false;
                $this->registrationError = '';
            } else {
                $this->registrationError = 'Fingerprint verification failed. Please try again.';
                $this->isRegistering = false;
            }
        } catch (\Exception $e) {
            $this->registrationError = 'Registration failed: ' . $e->getMessage();
            $this->isRegistering = false;
        }
    }

    public function registrationFailed(string $error): void
    {
        $this->registrationError = $error;
        $this->isRegistering = false;
    }

    public function render()
    {
        return '';
    }
}

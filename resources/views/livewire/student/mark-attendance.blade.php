<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ClassModel;
use App\Models\ClassAttendance;
use App\Models\WebauthnCredential;
use App\Services\WebauthnService;
use App\Services\AttendanceLogService;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app', ['title' => 'Mark Attendance'])] class extends Component {

    public ClassModel $class;
    public string $fullName = '';
    public string $matricNumber = '';
    public ?float $latitude = null;
    public ?float $longitude = null;
    public bool $locationCaptured = false;
    public string $locationError = '';
    public bool $isSubmitting = false;
    public ?float $distance = null;
    public bool $withinRadius = false;

    // Biometric state
    public bool $hasFingerprint = false;
    public bool $biometricVerified = false;
    public bool $isRegistering = false;
    public bool $isAuthenticating = false;
    public string $biometricError = '';
    public bool $biometricSupported = true;

    // Toast
    public bool $showToast = false;
    public string $toastMessage = '';
    public string $toastType = 'success';

    public function showToast(string $message, string $type = 'success'): void
    {
        $this->toastMessage = $message;
        $this->toastType = $type;
        $this->showToast = true;
        $this->dispatch('hide-toast-after-delay');
    }

    public function hideToast(): void
    {
        $this->showToast = false;
        $this->toastMessage = '';
    }

    public function mount($classId): void
    {
        $this->class = ClassModel::with(['lecturer', 'department'])
            ->findOrFail($classId);

        if (!$this->class->isActive() || !$this->class->attendance_open) {
            session()->flash('error', 'This class is not accepting attendance at the moment.');
            $this->redirect(route('student.classes'));
        }

        if (ClassAttendance::where('class_id', $this->class->id)
            ->where('student_id', Auth::id())
            ->exists()) {
            session()->flash('error', 'You have already marked attendance for this class.');
            $this->redirect(route('student.classes'));
        }

        $user = Auth::user();
        if (!$user) {
            session()->flash('error', 'Authentication required. Please log in.');
            $this->redirect(route('login'));
        }

        $this->fullName = $user->name;
        $this->matricNumber = $user->matric_no ?? '';

        // Check if user has a registered fingerprint
        $this->hasFingerprint = WebauthnCredential::where('user_id', $user->id)->exists();
    }

    public function captureLocation(): void
    {
        $this->dispatch('capture-location');
    }

    public function setLocation($latitude, $longitude): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->locationCaptured = true;
        $this->locationError = '';

        $this->distance = $this->class->calculateDistance(
            $this->class->latitude,
            $this->class->longitude,
            $latitude,
            $longitude
        );
        $this->withinRadius = $this->class->isWithinRadius($latitude, $longitude);

        if (!$this->withinRadius) {
            $this->locationError = "You are {$this->distance}m away from the class location. You must be within {$this->class->radius}m to mark attendance.";
        }
    }

    public function setLocationError($error): void
    {
        $this->locationError = $error;
        $this->locationCaptured = false;
    }

    public function clearLocation(): void
    {
        $this->latitude = null;
        $this->longitude = null;
        $this->locationCaptured = false;
        $this->locationError = '';
        $this->distance = null;
        $this->withinRadius = false;
    }

    // ── Biometric: Registration ──

    public function startBiometricRegistration(WebauthnService $webauthnService): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->biometricError = 'Authentication required.';
            return;
        }

        $this->isRegistering = true;
        $this->biometricError = '';

        $options = $webauthnService->getRegistrationOptions($user);

        $this->dispatch('webauthn-register-start', [
            'options' => $options,
        ]);
    }

    public function handleRegistrationComplete(WebauthnService $webauthnService, string $clientDataJSON, string $attestationObject): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->biometricError = 'Authentication required.';
            $this->isRegistering = false;
            return;
        }

        try {
            $attestationResponse = (object) [
                'clientDataJSON' => $clientDataJSON,
                'attestationObject' => $attestationObject,
            ];

            $result = $webauthnService->verifyRegistration($user, $attestationResponse);

            if ($result) {
                $this->hasFingerprint = true;
                $this->biometricVerified = true;
                $this->isRegistering = false;
                $this->biometricError = '';

                AttendanceLogService::logBiometricSuccess($this->class, [
                    'action' => 'registration',
                ]);

                $this->showToast('Fingerprint registered successfully!', 'success');
            } else {
                $this->biometricError = 'Fingerprint verification failed. Please try again.';
                $this->isRegistering = false;

                AttendanceLogService::logBiometricFailure($this->class, 'registration_verification_failed');
            }
        } catch (\Exception $e) {
            $this->biometricError = 'Registration failed: ' . $e->getMessage();
            $this->isRegistering = false;

            AttendanceLogService::logBiometricFailure($this->class, $e->getMessage());
        }
    }

    public function handleRegistrationFailed(string $error): void
    {
        $this->biometricError = $error;
        $this->isRegistering = false;

        $this->dispatch('log-biometric-failure', [
            'reason' => $error,
            'class_id' => $this->class->id,
        ]);
    }

    // ── Biometric: Authentication ──

    public function startBiometricAuthentication(WebauthnService $webauthnService): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->biometricError = 'Authentication required.';
            return;
        }

        if (!$webauthnService->userHasCredentials($user)) {
            $this->biometricError = 'No fingerprint registered. Please register first.';
            return;
        }

        $this->isAuthenticating = true;
        $this->biometricError = '';

        $options = $webauthnService->getAuthenticationOptions($user);

        $this->dispatch('webauthn-authenticate-start', [
            'options' => $options,
        ]);
    }

    public function handleAuthenticationComplete(
        WebauthnService $webauthnService,
        string $clientDataJSON,
        string $authenticatorData,
        string $signature,
        string $credentialId
    ): void {
        $user = Auth::user();
        if (!$user) {
            $this->biometricError = 'Authentication required.';
            $this->isAuthenticating = false;
            return;
        }

        try {
            $assertionResponse = (object) [
                'clientDataJSON' => $clientDataJSON,
                'authenticatorData' => $authenticatorData,
                'signature' => $signature,
                'id' => $credentialId,
            ];

            $result = $webauthnService->verifyAuthentication($user, $assertionResponse);

            if ($result) {
                $this->biometricVerified = true;
                $this->isAuthenticating = false;
                $this->biometricError = '';

                AttendanceLogService::logBiometricSuccess($this->class, [
                    'action' => 'authentication',
                ]);

                $this->showToast('Fingerprint verified!', 'success');
            } else {
                $this->biometricError = 'Fingerprint verification failed. Please try again.';
                $this->isAuthenticating = false;

                AttendanceLogService::logBiometricFailure($this->class, 'authentication_verification_failed');
            }
        } catch (\Exception $e) {
            $this->biometricError = 'Verification failed: ' . $e->getMessage();
            $this->isAuthenticating = false;

            AttendanceLogService::logBiometricFailure($this->class, $e->getMessage());
        }
    }

    public function handleAuthenticationFailed(string $error): void
    {
        $this->biometricError = $error;
        $this->isAuthenticating = false;

        $this->dispatch('log-biometric-failure', [
            'reason' => $error,
            'class_id' => $this->class->id,
        ]);
    }

    // ── Mark Attendance ──

    public function markAttendance(): void
    {
        $user = Auth::user();
        if (!$user || $user->name !== $this->fullName || $user->matric_no !== $this->matricNumber) {
            AttendanceLogService::logSecurityViolation('attempted_identity_fraud', [
                'attempted_name' => $this->fullName,
                'attempted_matric' => $this->matricNumber,
                'actual_name' => $user?->name,
                'actual_matric' => $user?->matric_no
            ]);

            $this->showToast('Hold up! You can only mark your own attendance!', 'error');
            return;
        }

        if (!$this->biometricVerified) {
            $this->showToast('Fingerprint verification is required to mark attendance.', 'error');
            return;
        }

        $this->validate([
            'fullName' => 'required|string|max:255',
            'matricNumber' => 'required|string|max:50',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if (!$this->withinRadius) {
            AttendanceLogService::logError('location_outside_radius', $this->class, [
                'user_latitude' => $this->latitude,
                'user_longitude' => $this->longitude,
                'class_latitude' => $this->class->latitude,
                'class_longitude' => $this->class->longitude,
                'required_radius' => $this->class->radius,
                'calculated_distance' => $this->distance
            ]);

            $this->showToast('You must be within the class radius to mark attendance!', 'error');
            return;
        }

        $this->submitAttendance();
    }

    private function submitAttendance(): void
    {
        $this->isSubmitting = true;

        try {
            ClassAttendance::markAttendance(
                $this->class,
                Auth::user(),
                $this->fullName,
                $this->matricNumber,
                $this->latitude,
                $this->longitude
            );

            AttendanceLogService::logSuccess($this->class, [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'distance_from_class' => $this->distance,
                'within_radius' => $this->withinRadius,
                'biometric_verified' => true,
            ]);

            $this->showToast('Attendance marked successfully!', 'success');

            $this->js('setTimeout(() => { window.location.href = "' . route('student.classes') . '"; }, 2000);');

        } catch (\Exception $e) {
            AttendanceLogService::logError('attendance_submission_failed', $this->class, [
                'exception_message' => $e->getMessage(),
                'exception_code' => $e->getCode(),
                'latitude' => $this->latitude,
                'longitude' => $this->longitude
            ]);

            $this->showToast('Oops! Something went wrong with your attendance!', 'error');
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function goBack(): void
    {
        $this->redirect(route('student.classes'));
    }
}; ?>

<main>
@if($showToast)
<div
    x-data="{ show: @entangle('showToast') }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="transition ease-in duration-200"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    class="fixed top-4 right-4 z-50 max-w-sm w-full"
>
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg p-4 flex items-start gap-3
        @if($toastType === 'success') border-l-4 border-l-green-500 @endif
        @if($toastType === 'error') border-l-4 border-l-red-500 @endif
        @if($toastType === 'warning') border-l-4 border-l-yellow-500 @endif
        @if($toastType === 'info') border-l-4 border-l-blue-500 @endif
    ">
        <div class="flex-shrink-0">
            @if($toastType === 'success')
                <div class="text-green-500 text-xl">&#10003;</div>
            @elseif($toastType === 'error')
                <div class="text-red-500 text-xl">&#10007;</div>
            @elseif($toastType === 'warning')
                <div class="text-yellow-500 text-xl">!</div>
            @else
                <div class="text-blue-500 text-xl">i</div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ $toastMessage }}
            </p>
        </div>
        <button
            wire:click="hideToast"
            class="flex-shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>
@endif

<div class="p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6 sm:mb-8">
        <div class="flex items-center gap-4 mb-4">
            <button
                wire:click="goBack"
                class="inline-flex items-center text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
            >
                <flux:icon.arrow-left class="size-5 mr-2" />
                Back to Classes
            </button>
        </div>

        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100">Mark Attendance</h1>
        <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 mt-1">Mark your attendance for this class</p>
    </div>

    <!-- Class Information -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $class->title }}</h2>
            <flux:badge color="green" size="sm">Active</flux:badge>
            <flux:badge color="blue" size="sm">Attendance Open</flux:badge>
        </div>

        @if($class->description)
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">{{ $class->description }}</p>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-zinc-500 dark:text-zinc-400">
            <div>
                <p><span class="font-medium">Lecturer:</span> {{ $class->lecturer->name }}</p>
                <p><span class="font-medium">Department:</span> {{ $class->department->name }}</p>
            </div>
            <div>
                <p><span class="font-medium">Level:</span> {{ $class->level }}</p>
                <p><span class="font-medium">Started:</span> {{ $class->starts_at->format('M j, Y g:i A') }}</p>
            </div>
            <div>
                <p><span class="font-medium">Required Radius:</span> {{ $class->radius }}m</p>
                <p><span class="font-medium">Location:</span> {{ $class->formatted_coordinates }}</p>
            </div>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="flex items-center justify-center mb-8">
        <div class="flex items-center">
            <!-- Step 1 -->
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $locationCaptured && $withinRadius ? 'bg-green-500 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                    @if($locationCaptured && $withinRadius)
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    @else
                        1
                    @endif
                </div>
                <span class="ml-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hidden sm:inline">Location</span>
            </div>

            <div class="w-8 h-px bg-zinc-300 dark:bg-zinc-600 mx-2"></div>

            <!-- Step 2 -->
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $biometricVerified ? 'bg-green-500 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                    @if($biometricVerified)
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    @else
                        2
                    @endif
                </div>
                <span class="ml-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hidden sm:inline">Fingerprint</span>
            </div>

            <div class="w-8 h-px bg-zinc-300 dark:bg-zinc-600 mx-2"></div>

            <!-- Step 3 -->
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $biometricVerified && $withinRadius && $locationCaptured ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                    3
                </div>
                <span class="ml-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hidden sm:inline">Confirm</span>
            </div>
        </div>
    </div>

    <!-- Step 1: Location Capture -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 mb-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Step 1: Capture Your Location</h3>

        <div class="space-y-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start">
                    <flux:icon.information-circle class="size-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-medium mb-1">Location Required</p>
                        <p>You must be within {{ $class->radius }} meters of the class location. Click below to capture your position.</p>
                    </div>
                </div>
            </div>

            @if($locationError)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <flux:icon.exclamation-triangle class="size-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div class="text-sm text-red-800 dark:text-red-200">
                            <p class="font-medium mb-1">Location Error</p>
                            <p>{{ $locationError }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if($locationCaptured && $withinRadius)
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div class="text-sm text-green-800 dark:text-green-200">
                            <p class="font-medium mb-1">Location Verified</p>
                            <p>You are {{ number_format($distance, 1) }}m from the class location.</p>
                            <p class="mt-1"><span class="font-medium">Your Location:</span> {{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3">
                <button
                    wire:click="captureLocation"
                    class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors"
                    wire:loading.attr="disabled"
                >
                    <flux:icon.map-pin class="size-4 mr-2" />
                    <span wire:loading.remove>Capture Location</span>
                    <span wire:loading>Capturing...</span>
                </button>

                @if($locationCaptured)
                    <button
                        wire:click="clearLocation"
                        class="inline-flex items-center justify-center px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:text-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors"
                    >
                        <flux:icon.x-mark class="size-4 mr-2" />
                        Clear Location
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Step 2: Biometric Fingerprint -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 mb-6
        {{ !$withinRadius ? 'opacity-50 pointer-events-none' : '' }}">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Step 2: Biometric Verification</h3>

        <div class="space-y-4">
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <div class="flex items-start">
                    <flux:icon.finger-print class="size-5 text-purple-600 dark:text-purple-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div class="text-sm text-purple-800 dark:text-purple-200">
                        <p class="font-medium mb-1">Fingerprint Required</p>
                        <p>You must verify your identity with a fingerprint scan each time you mark attendance. This ensures only you can mark your own attendance.</p>
                    </div>
                </div>
            </div>

            @if($biometricError)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <flux:icon.exclamation-triangle class="size-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div class="text-sm text-red-800 dark:text-red-200">
                            <p class="font-medium mb-1">Biometric Error</p>
                            <p>{{ $biometricError }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if($biometricVerified)
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div class="text-sm text-green-800 dark:text-green-200">
                            <p class="font-medium mb-1">Fingerprint Verified</p>
                            <p>Your identity has been confirmed. You can now mark your attendance.</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3">
                @if(!$biometricVerified)
                    @if(!$hasFingerprint)
                        {{-- Registration flow --}}
                        <button
                            wire:click="startBiometricRegistration"
                            class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white text-sm font-medium rounded-lg transition-colors"
                            wire:loading.attr="disabled"
                            {{ !$withinRadius ? 'disabled' : '' }}
                        >
                            <flux:icon.finger-print class="size-4 mr-2" />
                            <span wire:loading.remove wire:target="startBiometricRegistration">Register Fingerprint</span>
                            <span wire:loading wire:target="startBiometricRegistration">Registering...</span>
                        </button>
                    @else
                        {{-- Authentication flow --}}
                        <button
                            wire:click="startBiometricAuthentication"
                            class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white text-sm font-medium rounded-lg transition-colors"
                            wire:loading.attr="disabled"
                            {{ !$withinRadius ? 'disabled' : '' }}
                        >
                            <flux:icon.finger-print class="size-4 mr-2" />
                            <span wire:loading.remove wire:target="startBiometricAuthentication">Scan Fingerprint</span>
                            <span wire:loading wire:target="startBiometricAuthentication">Scanning...</span>
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Step 3: Confirm & Submit -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6
        {{ !$biometricVerified ? 'opacity-50 pointer-events-none' : '' }}">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Step 3: Confirm & Submit</h3>

        <form wire:submit="markAttendance" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <flux:field>
                        <flux:label>Full Name</flux:label>
                        <flux:input
                            wire:model="fullName"
                            placeholder="Enter your full name"
                            :disabled="!$biometricVerified"
                        />
                        <flux:error name="fullName" />
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Matric Number</flux:label>
                        <flux:input
                            wire:model="matricNumber"
                            placeholder="Enter your matric number"
                            :disabled="!$biometricVerified"
                        />
                        <flux:error name="matricNumber" />
                    </flux:field>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white text-sm font-medium rounded-lg transition-colors"
                    :disabled="!$biometricVerified || !$withinRadius || $isSubmitting"
                    wire:loading.attr="disabled"
                >
                    <flux:icon.check class="size-4 mr-2" />
                    <span wire:loading.remove wire:target="markAttendance">Mark Attendance</span>
                    <span wire:loading wire:target="markAttendance">Marking Attendance...</span>
                </button>

                <button
                    type="button"
                    wire:click="goBack"
                    class="inline-flex items-center justify-center px-6 py-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors"
                >
                    <flux:icon.arrow-left class="size-4 mr-2" />
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        // ── Location Capture ──
        Livewire.on('capture-location', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        @this.setLocation(
                            position.coords.latitude,
                            position.coords.longitude
                        );
                    },
                    function(error) {
                        let errorMessage = 'Unable to retrieve your location. ';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'Please allow location access and try again.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Location information is unavailable.';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'Location request timed out.';
                                break;
                            default:
                                errorMessage += 'An unknown error occurred.';
                                break;
                        }
                        @this.setLocationError(errorMessage);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            } else {
                @this.setLocationError('Geolocation is not supported by this browser.');
            }
        });

        // ── WebAuthn Registration ──
        Livewire.on('webauthn-register-start', async (data) => {
            try {
                const options = data.options;

                // Convert challenge and user ID from hex to ArrayBuffer
                options.publicKey.challenge = hexStringToUint8Array(options.publicKey.challenge);
                options.publicKey.user.id = hexStringToUint8Array(options.publicKey.user.id);

                // Convert excludeCredentials IDs
                if (options.publicKey.excludeCredentials) {
                    options.publicKey.excludeCredentials.forEach(cred => {
                        cred.id = hexStringToUint8Array(cred.id);
                    });
                }

                const credential = await navigator.credentials.create(options);

                // Convert the credential to sendable format
                const clientDataJSON = arrayBufferToBase64(credential.response.clientDataJSON);
                const attestationObject = arrayBufferToBase64(credential.response.attestationObject);

                @this.handleRegistrationComplete(clientDataJSON, attestationObject);

            } catch (error) {
                if (error.name === 'NotAllowedError') {
                    @this.handleRegistrationFailed('Fingerprint scan was cancelled. Please try again.');
                } else if (error.name === 'SecurityError') {
                    @this.handleRegistrationFailed('Security error. Make sure you are using HTTPS or localhost.');
                } else {
                    @this.handleRegistrationFailed('Registration failed: ' + error.message);
                }
            }
        });

        // ── WebAuthn Authentication ──
        Livewire.on('webauthn-authenticate-start', async (data) => {
            try {
                const options = data.options;

                // Convert challenge from hex to ArrayBuffer
                options.publicKey.challenge = hexStringToUint8Array(options.publicKey.challenge);

                // Convert allowCredentials IDs
                if (options.publicKey.allowCredentials) {
                    options.publicKey.allowCredentials.forEach(cred => {
                        cred.id = hexStringToUint8Array(cred.id);
                    });
                }

                const assertion = await navigator.credentials.get(options);

                // Convert the assertion to sendable format
                const clientDataJSON = arrayBufferToBase64(assertion.response.clientDataJSON);
                const authenticatorData = arrayBufferToBase64(assertion.response.authenticatorData);
                const signature = arrayBufferToBase64(assertion.response.signature);
                const credentialId = arrayBufferToBase64(assertion.rawId);

                @this.handleAuthenticationComplete(clientDataJSON, authenticatorData, signature, credentialId);

            } catch (error) {
                if (error.name === 'NotAllowedError') {
                    @this.handleAuthenticationFailed('Fingerprint scan was cancelled. Please try again.');
                } else if (error.name === 'SecurityError') {
                    @this.handleAuthenticationFailed('Security error. Make sure you are using HTTPS or localhost.');
                } else {
                    @this.handleAuthenticationFailed('Verification failed: ' + error.message);
                }
            }
        });

        // ── Biometric failure logging ──
        Livewire.on('log-biometric-failure', (data) => {
            fetch('/log-biometric-failure', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
        });
    });

    // ── Utility Functions ──

    function hexStringToUint8Array(hexString) {
        const matches = hexString.match(/.{1,2}/g);
        if (!matches) return new Uint8Array(0);
        return new Uint8Array(matches.map(byte => parseInt(byte, 16)));
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
</main>

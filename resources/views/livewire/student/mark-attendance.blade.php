<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ClassModel;
use App\Models\ClassAttendance;
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
    
    public function mount($classId): void
    {
        $this->class = ClassModel::with(['lecturer', 'department'])
            ->findOrFail($classId);
            
        // Check if class is active and attendance is open
        if (!$this->class->isActive() || !$this->class->attendance_open) {
            session()->flash('error', 'This class is not accepting attendance at the moment.');
            $this->redirect(route('student.classes'));
        }
        
        // Check if student already marked attendance
        if (ClassAttendance::where('class_id', $this->class->id)
            ->where('student_id', Auth::id())
            ->exists()) {
            session()->flash('error', 'You have already marked attendance for this class.');
            $this->redirect(route('student.classes'));
        }
        
        // Pre-fill user data
        $user = Auth::user();
        $this->fullName = $user->name;
        $this->matricNumber = $user->matric_number ?? '';
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
        
        // Calculate distance from class location
        $this->distance = $this->class->calculateDistance($latitude, $longitude);
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
    
    public function markAttendance(): void
    {
        $this->validate([
            'fullName' => 'required|string|max:255',
            'matricNumber' => 'required|string|max:50',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        
        if (!$this->withinRadius) {
            session()->flash('error', 'You must be within the class radius to mark attendance.');
            return;
        }
        
        $this->isSubmitting = true;
        
        try {
            ClassAttendance::markAttendance(
                $this->class->id,
                Auth::id(),
                $this->fullName,
                $this->matricNumber,
                $this->latitude,
                $this->longitude
            );
            
            session()->flash('success', 'Attendance marked successfully!');
            $this->redirect(route('student.classes'));
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
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

    <!-- Location Capture Section -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 mb-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Step 1: Capture Your Location</h3>
        
        <div class="space-y-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start">
                    <flux:icon.information-circle class="size-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-medium mb-1">Location Required</p>
                        <p>You must be within {{ $class->radius }} meters of the class location to mark attendance. Click the button below to capture your current location.</p>
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
                            <p>You are {{ number_format($distance, 1) }}m from the class location. You can now mark your attendance.</p>
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
                        class="inline-flex items-center justify-center px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors"
                    >
                        <flux:icon.x-mark class="size-4 mr-2" />
                        Clear Location
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Attendance Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Step 2: Confirm Your Details</h3>
        
        <form wire:submit="markAttendance" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <flux:field>
                        <flux:label>Full Name</flux:label>
                        <flux:input 
                            wire:model="fullName" 
                            placeholder="Enter your full name"
                            :disabled="!$withinRadius"
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
                            :disabled="!$withinRadius"
                        />
                        <flux:error name="matricNumber" />
                    </flux:field>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <button 
                    type="submit"
                    class="inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white text-sm font-medium rounded-lg transition-colors"
                    :disabled="!$withinRadius || $isSubmitting"
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
    });
</script>
</main>
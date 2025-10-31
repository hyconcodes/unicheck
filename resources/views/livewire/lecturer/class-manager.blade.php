<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ClassModel;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ClassCreatedNotification;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF;
use App\Models\ClassAttendance;

new #[Layout('components.layouts.app', ['title' => 'Class Manager'])] class extends Component {
    
    // Class creation form properties
    public string $title = '';
    public string $description = '';
    public string $level = '';
    public float $latitude = 0;
    public float $longitude = 0;
    public int $radius = 30;
    public string $ends_at = '';
    
    // State properties
    public bool $location_captured = false;
    public bool $location_loading = false;
    public string $error_message = '';
    public ?int $classToDelete = null;
    public ?int $classToToggle = null;
    public string $toggleAction = '';
    
    // Manual attendance properties
    public bool $showManualAttendance = false;
    public ?int $selectedClassId = null;
    public string $studentMatricNumber = '';
    public array $classStudents = [];
    public array $classAttendances = [];
    
    // Available levels
    public array $levels = ['100', '200', '300', '400', '500'];
    
    /**
     * Capture current location using browser geolocation API
     */
    public function captureLocation(): void
    {
        // Check permission
        if (!Auth::user()->hasRole('lecturer')) {
            $this->dispatch('show-toast', message: 'Only lecturers can create classes.', type: 'error');
            return;
        }
        
        $this->location_loading = true;
        $this->error_message = '';
        $this->location_captured = false;
        
        // This will trigger JavaScript geolocation
        $this->dispatch('capture-location');
    }
    
    /**
     * Handle location data received from JavaScript
     */
    public function locationReceived($latitude, $longitude): void
    {
        $this->latitude = (float)$latitude;
        $this->longitude = (float)$longitude;
        $this->location_loading = false;
        $this->location_captured = true;
        $this->error_message = '';
    }
    
    /**
     * Handle location error from JavaScript
     */
    public function locationError($message): void
    {
        $this->error_message = $message;
        $this->location_loading = false;
        $this->location_captured = false;
    }
    
    /**
     * Clear captured location
     */
    public function clearLocation(): void
    {
        $this->reset(['latitude', 'longitude', 'location_captured', 'location_loading', 'error_message']);
    }
    
    /**
     * Create a new class
     */
    public function createClass(): void
    {
        if (!Auth::user()->hasRole('lecturer')) {
            $this->dispatch('show-toast', message: 'Only lecturers can create classes.', type: 'error');
            return;
        }
        
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'level' => 'required|in:100,200,300,400,500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10|max:500',
            'ends_at' => 'nullable|date|after:now',
        ]);
        
        try {
            $class = ClassModel::create([
                'title' => $this->title,
                'description' => $this->description,
                'lecturer_id' => Auth::id(),
                'department_id' => Auth::user()->department_id,
                'level' => $this->level,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'radius' => $this->radius,
                'starts_at' => now(),
                'ends_at' => $this->ends_at ? $this->ends_at : null,
                'status' => 'active',
                'attendance_open' => true,
            ]);
            
            // Send email notifications to students
            $this->sendClassNotifications($class);
            
            $this->reset(['title', 'description', 'level', 'latitude', 'longitude', 'radius', 'ends_at', 'location_captured', 'error_message']);
            
            $this->dispatch('show-toast', message: 'Class created successfully! Students have been notified.', type: 'success');
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Error creating class: ' . $e->getMessage(), type: 'error');
        }
    }
    
    /**
     * Send email notifications to eligible students
     */
    private function sendClassNotifications(ClassModel $class): void
    {
        $students = User::where('department_id', $class->department_id)
            ->where('level', $class->level)
            ->where('role', 'student')
            ->get();
            
        foreach ($students as $student) {
            try {
                Mail::to($student->email)->send(new ClassCreatedNotification($class, $student));
            } catch (\Exception $e) {
                Log::error('Failed to send class notification to ' . $student->email . ': ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Toggle class status (pause/resume)
     */
    public function confirmToggleClass($classId, $action): void
    {
        $this->classToToggle = $classId;
        $this->toggleAction = $action;
        $this->dispatch('show-toggle-modal');
    }
    
    public function toggleClass(): void
    {
        if (!$this->classToToggle) return;
        
        $class = ClassModel::find($this->classToToggle);
        if (!$class || $class->lecturer_id !== Auth::id()) {
            $this->dispatch('show-toast', message: 'Class not found or unauthorized.', type: 'error');
            return;
        }
        
        if ($this->toggleAction === 'pause') {
            $class->update(['status' => 'paused', 'attendance_open' => false]);
            $message = 'Class paused successfully.';
        } elseif ($this->toggleAction === 'resume') {
            $class->update(['status' => 'active', 'attendance_open' => true]);
            $message = 'Class resumed successfully.';
        } elseif ($this->toggleAction === 'end') {
            $class->update(['status' => 'ended', 'attendance_open' => false, 'ends_at' => now()]);
            $message = 'Class ended successfully.';
        }
        
        $this->classToToggle = null;
        $this->toggleAction = '';
        $this->dispatch('show-toast', message: $message, type: 'success');
    }
    
    /**
     * Toggle attendance for a class
     */
    public function toggleAttendance($classId): void
    {
        $class = ClassModel::find($classId);
        if (!$class || $class->lecturer_id !== Auth::id()) {
            $this->dispatch('show-toast', message: 'Class not found or unauthorized.', type: 'error');
            return;
        }
        
        $class->update(['attendance_open' => !$class->attendance_open]);
        $message = $class->attendance_open ? 'Attendance opened.' : 'Attendance closed.';
        $this->dispatch('show-toast', message: $message, type: 'success');
    }
    
    /**
     * Download attendance PDF
     */
    public function downloadAttendance($classId)
    {
        $class = ClassModel::with(['attendances.student', 'department', 'lecturer'])->find($classId);
        if (!$class || $class->lecturer_id !== Auth::id()) {
            $this->dispatch('show-toast', message: 'Class not found or unauthorized.', type: 'error');
            return;
        }
        
        $attendances = $class->attendances()->orderBy('marked_at', 'asc')->get();
        
        $totalStudents = User::role('student')
            ->where('department_id', $class->department_id)
            ->where('level', $class->level)
            ->count();
        
        $pdf = Pdf::loadView('pdf.attendance-report', [
            'class' => $class,
            'attendances' => $attendances,
            'totalStudents' => $totalStudents
        ]);
        
        $filename = 'attendance-' . Str::slug($class->title) . '-' . now()->format('Y-m-d') . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
    
    /**
     * Redirect to manual attendance page
     */
    public function showManualAttendanceModal($classId)
    {
        $class = ClassModel::find($classId);
        if (!$class || $class->lecturer_id !== Auth::id()) {
            $this->dispatch('show-toast', message: 'Class not found or unauthorized.', type: 'error');
            return;
        }
        
        return redirect()->route('lecturer.manual-attendance', ['class' => $classId]);
    }
    
    /**
     * Mark attendance manually by matric number
     */
    public function markAttendanceManually()
    {
        $this->validate([
            'studentMatricNumber' => 'required|string',
        ]);
        
        $class = ClassModel::find($this->selectedClassId);
        if (!$class || $class->lecturer_id !== Auth::id()) {
            $this->dispatch('show-toast', message: 'Class not found or unauthorized.', type: 'error');
            return;
        }
        
        // Find student by matric number
        $student = User::role('student')
            ->where('matric_no', $this->studentMatricNumber)
            ->where('department_id', $class->department_id)
            ->where('level', $class->level)
            ->first();
        
        if (!$student) {
            $this->dispatch('show-toast', message: 'Student not found in this class.', type: 'error');
            return;
        }
        
        // Check if already marked
        $existingAttendance = $class->attendances()->where('student_id', $student->id)->first();
        if ($existingAttendance) {
            $this->dispatch('show-toast', message: 'Student attendance already marked.', type: 'warning');
            return;
        }
        
        // Mark attendance
        $class->attendances()->create([
            'student_id' => $student->id,
            'marked_at' => now(),
            'marked_by_lecturer' => true,
        ]);
        
        $this->dispatch('show-toast', message: "Attendance marked for {$student->name}.", type: 'success');
        
        // Refresh the attendance list
        $this->classAttendances = $class->attendances()
            ->with('student')
            ->orderBy('marked_at', 'desc')
            ->get()
            ->toArray();
        
        $this->studentMatricNumber = '';
    }
    
    /**
     * Remove student attendance
     */
    public function removeAttendance($attendanceId)
    {
        $attendance = ClassAttendance::with(['student', 'class'])->find($attendanceId);
        
        if (!$attendance || $attendance->class->lecturer_id !== Auth::id()) {
            $this->dispatch('show-toast', message: 'Attendance record not found or unauthorized.', type: 'error');
            return;
        }
        
        $studentName = $attendance->student->name;
        $attendance->delete();
        
        $this->dispatch('show-toast', message: "Attendance removed for {$studentName}.", type: 'success');
        
        // Refresh the attendance list
        $class = ClassModel::find($this->selectedClassId);
        $this->classAttendances = $class->attendances()
            ->with('student')
            ->orderBy('marked_at', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Get time remaining for a class in seconds
     */
    public function getTimeRemaining($class): int
    {
        if (!$class->ends_at) {
            return 0;
        }
        
        $now = now();
        $endsAt = $class->ends_at;
        
        if ($now->greaterThan($endsAt)) {
            return 0;
        }
        
        return $now->diffInSeconds($endsAt);
    }
    
    /**
     * Get lecturer's classes with pagination
     */
    public function with(): array
    {
        return [
            'classes' => ClassModel::with(['department', 'attendances'])
                ->where('lecturer_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate(10),
        ];
    }
}; ?>

<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
    <div class="mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100">Class Manager</h1>
        <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 mt-1">Create and manage your classes</p>
    </div>

    <!-- Class Creation Card -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 mb-6">
        <h2 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Create New Class</h2>
        
        <!-- Instructions -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 sm:p-4 mb-4">
            <h3 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Instructions:</h3>
            <ul class="text-xs sm:text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• Stand in your preferred classroom location</li>
                <li>• Click "Capture Current Location" to get GPS coordinates</li>
                <li>• Fill in class details and select student level</li>
                <li>• Students in your department at the selected level will be notified</li>
                <li>• Students can only join within the specified radius</li>
            </ul>
        </div>

        <!-- Location Capture Button -->
        <div class="mb-4">
            <flux:button 
                wire:click="captureLocation"
                variant="primary" 
                size="sm"
                :disabled="$location_captured || $location_loading"
                class="w-full sm:w-auto"
            >
                @if($location_loading)
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    Getting Location...
                @elseif($location_captured)
                    <flux:icon.check class="size-4" />
                    Location Captured
                @else
                    <flux:icon.map-pin class="size-4" />
                    Capture Current Location
                @endif
            </flux:button>
        </div>

        <!-- Error Message -->
        @if($error_message)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <flux:icon.exclamation-triangle class="size-5 text-red-500 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div class="flex-1">
                        <p class="font-medium text-red-900 dark:text-red-100">Location Error</p>
                        <p class="text-sm text-red-800 dark:text-red-200 mt-1">{{ $error_message }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <flux:button 
                                wire:click="captureLocation" 
                                variant="outline" 
                                size="xs"
                                class="text-red-700 border-red-300 hover:bg-red-50"
                            >
                                Try Again
                            </flux:button>
                            <flux:button 
                                wire:click="clearLocation" 
                                variant="ghost" 
                                size="xs"
                                class="text-red-600"
                            >
                                Clear Error
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Location Success Message -->
        @if($location_captured && $latitude && $longitude)
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <flux:icon.check-circle class="size-5 text-green-500 dark:text-green-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div class="flex-1">
                        <p class="font-medium text-green-900 dark:text-green-100">Location Captured Successfully</p>
                        <p class="text-sm text-green-800 dark:text-green-200 mt-1">
                            Coordinates: {{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}
                        </p>
                        <flux:button 
                            wire:click="clearLocation" 
                            variant="ghost" 
                            size="xs"
                            class="text-green-600 mt-2"
                        >
                            Capture New Location
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Class Form -->
        @if($location_captured)
            <form wire:submit="createClass" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:input 
                            wire:model="title" 
                            label="Class Title" 
                            placeholder="e.g., Introduction to Programming"
                            required
                        />
                    </div>
                    
                    <div>
                        <flux:select wire:model="level" label="Student Level" placeholder="Select level" required>
                            @foreach($levels as $levelOption)
                                <option value="{{ $levelOption }}">{{ $levelOption }} Level</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div>
                    <flux:textarea 
                        wire:model="description" 
                        label="Description (Optional)" 
                        placeholder="Brief description of the class..."
                        rows="3"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:input 
                            wire:model="radius" 
                            type="number" 
                            label="Attendance Radius (meters)" 
                            min="10" 
                            max="500"
                            required
                        />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Students must be within this distance to mark attendance</p>
                    </div>
                    
                    <div>
                        <flux:input 
                            wire:model="ends_at" 
                            type="datetime-local" 
                            label="End Time (Optional)"
                        />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Leave empty for manual end</p>
                    </div>
                </div>

                <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                    <flux:icon.plus class="size-4" />
                    Create Class
                </flux:button>
            </form>
        @endif
    </div>

    <!-- My Classes -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6">
        <h2 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">My Classes</h2>

        @if($classes->count() > 0)
            <div class="space-y-4">
                @foreach($classes as $class)
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2 flex-wrap">
                                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $class->title }}</h3>
                                    <flux:badge 
                                        :color="$class->status === 'active' ? 'green' : ($class->status === 'paused' ? 'yellow' : 'red')"
                                        size="sm"
                                    >
                                        {{ ucfirst($class->status) }}
                                    </flux:badge>
                                    @if($class->attendance_open)
                                        <flux:badge color="blue" size="sm">Attendance Open</flux:badge>
                                    @endif
                                </div>
                                
                                @if($class->description)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">{{ $class->description }}</p>
                                @endif
                                
                                <div class="text-xs text-zinc-500 dark:text-zinc-500 space-y-1">
                                    <p>Department: {{ $class->department->name }} • Level: {{ $class->level }}</p>
                                    <p>Location: {{ $class->formatted_coordinates }} • Radius: {{ $class->radius }}m</p>
                                    <p>Created: {{ $class->created_at->format('M j, Y g:i A') }}</p>
                                    @if($class->ends_at)
                                        <p>Ends: {{ $class->ends_at->format('M j, Y g:i A') }}</p>
                                        @if($this->getTimeRemaining($class) > 0 && $class->status === 'active')
                                            <p class="font-medium text-blue-600 dark:text-blue-400">
                                                Time Remaining: 
                                                <span class="countdown-timer" data-end-time="{{ $class->ends_at->timestamp }}">
                                                    {{ gmdate('H:i:s', $this->getTimeRemaining($class)) }}
                                                </span>
                                            </p>
                                        @endif
                                    @endif
                                    <p>Attendees: {{ $class->total_attendees }}</p>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                @if($class->status === 'active')
                                    <flux:button 
                                        wire:click="confirmToggleClass({{ $class->id }}, 'pause')" 
                                        variant="ghost" 
                                        size="sm"
                                    >
                                        <flux:icon.pause class="size-4" />
                                        Pause
                                    </flux:button>
                                @elseif($class->status === 'paused')
                                    <flux:button 
                                        wire:click="confirmToggleClass({{ $class->id }}, 'resume')" 
                                        variant="ghost" 
                                        size="sm"
                                    >
                                        <flux:icon.play class="size-4" />
                                        Resume
                                    </flux:button>
                                @endif
                                
                                @if($class->status !== 'ended')
                                    <flux:button 
                                        wire:click="toggleAttendance({{ $class->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                    >
                                        @if($class->attendance_open)
                                            <flux:icon.lock-closed class="size-4" />
                                            Close Attendance
                                        @else
                                            <flux:icon.lock-open class="size-4" />
                                            Open Attendance
                                        @endif
                                    </flux:button>
                                    
                                    <flux:button 
                                        wire:click="confirmToggleClass({{ $class->id }}, 'end')" 
                                        variant="danger" 
                                        size="sm"
                                    >
                                        <flux:icon.stop class="size-4" />
                                        End Class
                                    </flux:button>
                                @endif
                                
                                <flux:button 
                                    wire:click="downloadAttendance({{ $class->id }})" 
                                    variant="ghost" 
                                    size="sm"
                                >
                                    <flux:icon.document-arrow-down class="size-4" />
                                    Download PDF
                                </flux:button>
                                
                                <flux:button 
                                    wire:click="showManualAttendanceModal({{ $class->id }})" 
                                    variant="ghost" 
                                    size="sm"
                                >
                                    <flux:icon.user-plus class="size-4" />
                                    Manage Attendance
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-6">
                {{ $classes->links() }}
            </div>
        @else
            <div class="text-center py-8">
                <flux:icon.academic-cap class="size-12 text-zinc-400 dark:text-zinc-600 mx-auto mb-4" />
                <h3 class="text-sm sm:text-base font-medium text-zinc-900 dark:text-zinc-100 mb-2">No classes created yet</h3>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">Create your first class to get started.</p>
            </div>
        @endif
    </div>

    <!-- Toggle Class Modal -->
    <flux:modal name="toggle-class-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirm Action</flux:heading>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                    Are you sure you want to {{ $toggleAction }} this class?
                    @if($toggleAction === 'end')
                        This action cannot be undone.
                    @endif
                </p>
            </div>

            <div class="flex space-x-2">
                <flux:button variant="primary" wire:click="toggleClass">
                    {{ ucfirst($toggleAction) }} Class
                </flux:button>

                <flux:button variant="ghost" x-on:click="$dispatch('modal-close', { name: 'toggle-class-modal' })">
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>


</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        // Handle location capture request
        Livewire.on('capture-location', () => {
            if (!navigator.geolocation) {
                @this.call('locationError', 'Geolocation is not supported by your browser.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    @this.call('locationReceived', 
                        position.coords.latitude,
                        position.coords.longitude
                    );
                },
                (error) => {
                    let errorMessage = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = "Location access denied. Please allow location access in your browser settings and try again.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "Location information is unavailable. Please check your device's location settings.";
                            break;
                        case error.TIMEOUT:
                            errorMessage = "Location request timed out. Please try again.";
                            break;
                        default:
                            errorMessage = "An unknown error occurred while retrieving location.";
                            break;
                    }
                    
                    @this.call('locationError', errorMessage);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });

        // Handle toggle modal
        Livewire.on('show-toggle-modal', () => {
            $dispatch('modal-open', { name: 'toggle-class-modal' });
        });

        // Countdown timer functionality
        function updateCountdownTimers() {
            const timers = document.querySelectorAll('.countdown-timer');
            
            timers.forEach(timer => {
                const endTime = parseInt(timer.getAttribute('data-end-time')) * 1000;
                const now = new Date().getTime();
                const timeLeft = endTime - now;
                
                if (timeLeft > 0) {
                    const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                    
                    timer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                } else {
                    timer.textContent = '00:00:00';
                    timer.parentElement.innerHTML = '<span class="font-medium text-red-600 dark:text-red-400">Class Ended</span>';
                }
            });
        }

        // Update timers every second
        setInterval(updateCountdownTimers, 1000);
        
        // Initial update
        updateCountdownTimers();
    });
</script>
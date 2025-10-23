<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ClassModel;
use App\Models\ClassAttendance;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app', ['title' => 'My Classes'])] class extends Component {
    
    public string $activeTab = 'active';
    
    /**
     * Switch between tabs
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
    
    /**
     * Get active classes for the student's department and level
     */
    public function getActiveClasses()
    {
        $user = Auth::user();
        
        return ClassModel::with(['lecturer', 'department', 'attendances'])
            ->where('department_id', $user->department_id)
            ->where('level', $user->level)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Get past classes that the student attended
     */
    public function getPastClasses()
    {
        $user = Auth::user();
        
        return ClassModel::with(['lecturer', 'department'])
            ->whereHas('attendances', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Get all classes for the student's department and level (including past ones)
     */
    public function getAllClasses()
    {
        $user = Auth::user();
        
        return ClassModel::with(['lecturer', 'department', 'attendances'])
            ->where('department_id', $user->department_id)
            ->where('level', $user->level)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Check if student has attended a class
     */
    public function hasAttended($classId): bool
    {
        return ClassAttendance::where('class_id', $classId)
            ->where('student_id', Auth::id())
            ->exists();
    }
    
    /**
     * Get student's attendance record for a class
     */
    public function getAttendanceRecord($classId)
    {
        return ClassAttendance::where('class_id', $classId)
            ->where('student_id', Auth::id())
            ->first();
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
    
    public function with(): array
    {
        $activeClasses = $this->getActiveClasses();
        $pastClasses = $this->getPastClasses();
        $allClasses = $this->getAllClasses();
        
        return [
            'activeClasses' => $activeClasses,
            'pastClasses' => $pastClasses,
            'allClasses' => $allClasses,
        ];
    }
}; ?>

<main>
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
    <div class="mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100">My Classes</h1>
        <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 mt-1">View and join your classes</p>
    </div>

    <!-- Tab Navigation -->
    <div class="mb-6">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button 
                wire:click="setActiveTab('active')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}"
            >
                Active Classes
                <span class="ml-2 bg-{{ $activeTab === 'active' ? 'blue' : 'zinc' }}-100 text-{{ $activeTab === 'active' ? 'blue' : 'zinc' }}-600 py-0.5 px-2.5 rounded-full text-xs font-medium">
                    {{ $activeClasses->count() }}
                </span>
            </button>
            
            <button 
                wire:click="setActiveTab('attended')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'attended' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}"
            >
                Attended Classes
                <span class="ml-2 bg-{{ $activeTab === 'attended' ? 'blue' : 'zinc' }}-100 text-{{ $activeTab === 'attended' ? 'blue' : 'zinc' }}-600 py-0.5 px-2.5 rounded-full text-xs font-medium">
                    {{ $pastClasses->count() }}
                </span>
            </button>
            
            <button 
                wire:click="setActiveTab('all')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}"
            >
                All Classes
                <span class="ml-2 bg-{{ $activeTab === 'all' ? 'blue' : 'zinc' }}-100 text-{{ $activeTab === 'all' ? 'blue' : 'zinc' }}-600 py-0.5 px-2.5 rounded-full text-xs font-medium">
                    {{ $allClasses->count() }}
                </span>
            </button>
        </nav>
    </div>

    <!-- Active Classes Tab -->
    @if($activeTab === 'active')
        <div class="space-y-4">
            @if($activeClasses->count() > 0)
                @foreach($activeClasses as $class)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $class->title }}</h3>
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                    @if($class->attendance_open)
                                        <flux:badge color="blue" size="sm">Attendance Open</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm">Attendance Closed</flux:badge>
                                    @endif
                                    @if($this->hasAttended($class->id))
                                        <flux:badge color="purple" size="sm">Attended</flux:badge>
                                    @endif
                                </div>
                                
                                @if($class->description)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">{{ $class->description }}</p>
                                @endif
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div>
                                        <p><span class="font-medium">Lecturer:</span> {{ $class->lecturer->name }}</p>
                                        <p><span class="font-medium">Department:</span> {{ $class->department->name }}</p>
                                        <p><span class="font-medium">Level:</span> {{ $class->level }}</p>
                                    </div>
                                    <div>
                                        <p><span class="font-medium">Started:</span> {{ $class->starts_at->format('M j, Y g:i A') }}</p>
                                        @if($class->ends_at)
                                            <p><span class="font-medium">Ends:</span> {{ $class->ends_at->format('M j, Y g:i A') }}</p>
                                            @if($this->getTimeRemaining($class) > 0)
                                                <p class="font-medium text-blue-600 dark:text-blue-400">
                                                    <span class="font-medium">Time Remaining:</span> 
                                                    <span class="countdown-timer" data-end-time="{{ $class->ends_at->timestamp }}">
                                                        {{ gmdate('H:i:s', $this->getTimeRemaining($class)) }}
                                                    </span>
                                                </p>
                                            @endif
                                        @endif
                                        <p><span class="font-medium">Radius:</span> {{ $class->radius }}m</p>
                                        <p><span class="font-medium">Attendees:</span> {{ $class->attendances->count() }}</p>
                                    </div>
                                </div>
                                
                                @if($this->hasAttended($class->id))
                                    @php $attendance = $this->getAttendanceRecord($class->id); @endphp
                                    <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                        <p class="text-sm text-green-800 dark:text-green-200">
                                            <span class="font-medium">✓ Attendance Marked</span> on {{ $attendance->marked_at->format('M j, Y g:i A') }}
                                            <br>Distance from class: {{ $attendance->formatted_distance }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-2">
                                @if(!$this->hasAttended($class->id) && $class->attendance_open)
                                    <a href="{{ route('student.mark-attendance', $class->id) }}" 
                                       class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        <flux:icon.map-pin class="size-4 mr-2" />
                                        Mark Attendance
                                    </a>
                                @elseif(!$class->attendance_open)
                                    <div class="inline-flex items-center justify-center px-4 py-2 bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 text-sm font-medium rounded-lg">
                                        <flux:icon.lock-closed class="size-4 mr-2" />
                                        Attendance Closed
                                    </div>
                                @endif
                                
                                <button 
                                    onclick="showClassDetails({{ json_encode($class) }})"
                                    class="inline-flex items-center justify-center px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors"
                                >
                                    <flux:icon.information-circle class="size-4 mr-2" />
                                    Details
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-12">
                    <flux:icon.academic-cap class="size-16 text-zinc-400 dark:text-zinc-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No Active Classes</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">There are no active classes for your level at the moment.</p>
                </div>
            @endif
        </div>
    @endif

    <!-- Attended Classes Tab -->
    @if($activeTab === 'attended')
        <div class="space-y-4">
            @if($pastClasses->count() > 0)
                @foreach($pastClasses as $class)
                    @php $attendance = $this->getAttendanceRecord($class->id); @endphp
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $class->title }}</h3>
                                    <flux:badge 
                                        :color="$class->status === 'active' ? 'green' : ($class->status === 'paused' ? 'yellow' : 'red')"
                                        size="sm"
                                    >
                                        {{ ucfirst($class->status) }}
                                    </flux:badge>
                                    <flux:badge color="purple" size="sm">Attended</flux:badge>
                                </div>
                                
                                @if($class->description)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">{{ $class->description }}</p>
                                @endif
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div>
                                        <p><span class="font-medium">Lecturer:</span> {{ $class->lecturer->name }}</p>
                                        <p><span class="font-medium">Department:</span> {{ $class->department->name }}</p>
                                        <p><span class="font-medium">Level:</span> {{ $class->level }}</p>
                                    </div>
                                    <div>
                                        <p><span class="font-medium">Class Date:</span> {{ $class->starts_at->format('M j, Y g:i A') }}</p>
                                        <p><span class="font-medium">Attendance Marked:</span> {{ $attendance->marked_at->format('M j, Y g:i A') }}</p>
                                        <p><span class="font-medium">Distance:</span> {{ $attendance->formatted_distance }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-2">
                                <button 
                                    onclick="showClassDetails({{ json_encode($class) }})"
                                    class="inline-flex items-center justify-center px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors"
                                >
                                    <flux:icon.information-circle class="size-4 mr-2" />
                                    Details
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-12">
                    <flux:icon.clock class="size-16 text-zinc-400 dark:text-zinc-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No Attended Classes</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">You haven't attended any classes yet.</p>
                </div>
            @endif
        </div>
    @endif

    <!-- All Classes Tab -->
    @if($activeTab === 'all')
        <div class="space-y-4">
            @if($allClasses->count() > 0)
                @foreach($allClasses as $class)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $class->title }}</h3>
                                    <flux:badge 
                                        :color="$class->status === 'active' ? 'green' : ($class->status === 'paused' ? 'yellow' : 'red')"
                                        size="sm"
                                    >
                                        {{ ucfirst($class->status) }}
                                    </flux:badge>
                                    @if($this->hasAttended($class->id))
                                        <flux:badge color="purple" size="sm">Attended</flux:badge>
                                    @endif
                                </div>
                                
                                @if($class->description)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">{{ $class->description }}</p>
                                @endif
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div>
                                        <p><span class="font-medium">Lecturer:</span> {{ $class->lecturer->name }}</p>
                                        <p><span class="font-medium">Department:</span> {{ $class->department->name }}</p>
                                        <p><span class="font-medium">Level:</span> {{ $class->level }}</p>
                                    </div>
                                    <div>
                                        <p><span class="font-medium">Started:</span> {{ $class->starts_at->format('M j, Y g:i A') }}</p>
                                        @if($class->ends_at)
                                            <p><span class="font-medium">Ends:</span> {{ $class->ends_at->format('M j, Y g:i A') }}</p>
                                        @endif
                                        <p><span class="font-medium">Attendees:</span> {{ $class->attendances->count() }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-2">
                                @if(!$this->hasAttended($class->id) && $class->isActive() && $class->attendance_open)
                                    <a href="{{ route('student.mark-attendance', $class->id) }}" 
                                       class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        <flux:icon.map-pin class="size-4 mr-2" />
                                        Mark Attendance
                                    </a>
                                @endif
                                
                                <button 
                                    onclick="showClassDetails({{ json_encode($class) }})"
                                    class="inline-flex items-center justify-center px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors"
                                >
                                    <flux:icon.information-circle class="size-4 mr-2" />
                                    Details
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-12">
                    <flux:icon.academic-cap class="size-16 text-zinc-400 dark:text-zinc-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No Classes Available</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">There are no classes available for your level.</p>
                </div>
            @endif
        </div>
    @endif
</div>

<!-- Class Details Modal -->
<div id="classDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" id="modalTitle">Class Details</h3>
                    <button onclick="closeClassDetails()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="modalContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showClassDetails(classData) {
        const modal = document.getElementById('classDetailsModal');
        const title = document.getElementById('modalTitle');
        const content = document.getElementById('modalContent');
        
        title.textContent = classData.title;
        
        const statusColor = classData.status === 'active' ? 'green' : (classData.status === 'paused' ? 'yellow' : 'red');
        const attendanceStatus = classData.attendance_open ? 'Open' : 'Closed';
        const attendanceColor = classData.attendance_open ? 'blue' : 'red';
        
        content.innerHTML = `
            <div class="space-y-4">
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-${statusColor}-100 text-${statusColor}-800">
                        ${classData.status.charAt(0).toUpperCase() + classData.status.slice(1)}
                    </span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-${attendanceColor}-100 text-${attendanceColor}-800">
                        Attendance ${attendanceStatus}
                    </span>
                </div>
                
                ${classData.description ? `<p class="text-zinc-600 dark:text-zinc-400">${classData.description}</p>` : ''}
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="space-y-2">
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Lecturer:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">${classData.lecturer.name}</span>
                        </div>
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Department:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">${classData.department.name}</span>
                        </div>
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Level:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">${classData.level}</span>
                        </div>
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Radius:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">${classData.radius} meters</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Started:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">${new Date(classData.starts_at).toLocaleString()}</span>
                        </div>
                        ${classData.ends_at ? `
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Ends:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">${new Date(classData.ends_at).toLocaleString()}</span>
                        </div>
                        ` : ''}
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Location:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">${classData.formatted_coordinates}</span>
                        </div>
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Total Attendees:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">${classData.attendances.length}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        modal.classList.remove('hidden');
    }
    
    function closeClassDetails() {
        document.getElementById('classDetailsModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('classDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeClassDetails();
        }
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
</script>
</main>
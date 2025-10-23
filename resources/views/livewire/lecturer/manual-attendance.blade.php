<?php

use Livewire\Volt\Component;
use App\Models\ClassModel;
use App\Models\User;
use App\Models\ClassAttendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $class;
    public $classStudents = [];
    public $classAttendances = [];
    public $studentMatricNumber = '';

    // Toast notification properties
    public bool $showToast = false;
    public string $toastMessage = '';
    public string $toastType = 'success';

    public function showToast(string $message, string $type = 'success'): void
    {
        $this->toastMessage = $message;
        $this->toastType = $type;
        $this->showToast = true;

        // Auto-hide toast after 5 seconds
        $this->dispatch('hide-toast-after-delay');
    }

    public function hideToast(): void
    {
        $this->showToast = false;
        $this->toastMessage = '';
    }

    public function mount($class)
    {
        $this->class = ClassModel::with(['attendances.student', 'department'])->find($class);

        if (!$this->class || $this->class->lecturer_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this class.');
        }

        $this->loadData();
    }

    public function loadData()
    {
        // Get all students in the same department and level
        $this->classStudents = User::role('student')->where('department_id', $this->class->department_id)->where('level', $this->class->level)->orderBy('name')->get()->toArray();

        // Get current attendances for this class
        $attendances = $this->class->attendances()->orderBy('marked_at', 'desc')->get();

        $this->classAttendances = $attendances
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'full_name' => $attendance->full_name,
                    'matric_number' => $attendance->matric_number,
                    'marked_at' => $attendance->marked_at,
                    'marked_by_lecturer' => $attendance->marked_by_lecturer,
                ];
            })
            ->toArray();
    }

    /**
     * Mark attendance manually by matric number
     */
    public function markAttendanceManually()
    {
        $this->validate([
            'studentMatricNumber' => 'required|string',
        ]);

        // Find student by matric number
        $student = User::role('student')->where('matric_no', $this->studentMatricNumber)->where('department_id', $this->class->department_id)->where('level', $this->class->level)->first();

        if (!$student) {
            $this->showToast('Student not found in this department and level.', 'error');
            return;
        }

        // Check if student already marked attendance
        $existingAttendance = ClassAttendance::where('class_id', $this->class->id)->where('student_id', $student->id)->first();

        if ($existingAttendance) {
            $this->showToast('Student has already marked attendance for this class.', 'error');
            return;
        }

        // Create attendance record
        ClassAttendance::create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'full_name' => $student->name,
            'matric_number' => $student->matric_no,
            'latitude' => 0.0, // Default value for manual attendance
            'longitude' => 0.0, // Default value for manual attendance
            'distance' => 0.0, // Default value for manual attendance (lecturer marked)
            'marked_at' => now(),
            'marked_by_lecturer' => true,
        ]);

        $this->studentMatricNumber = '';
        $this->loadData();

        $this->showToast('Student attendance marked successfully.', 'success');
    }

    /**
     * Remove attendance record
     */
    public function removeAttendance($attendanceId)
    {
        $attendance = ClassAttendance::where('id', $attendanceId)->where('class_id', $this->class->id)->first();

        if (!$attendance) {
            $this->showToast('Attendance record not found.', 'error');
            return;
        }

        $attendance->delete();
        $this->loadData();

        $this->showToast('Attendance record removed successfully.', 'success');
    }

    /**
     * Go back to class manager
     */
    public function goBack()
    {
        return redirect()->route('lecturer.classes');
    }
}; ?>

<div class="max-w-6xl mx-auto p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Manual Attendance Management</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-2">{{ $class->class_name }} -
                    {{ $class->department->name }} (Level {{ $class->level }})</p>
            </div>
            <button wire:click="goBack"
                class="inline-flex items-center px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Classes
            </button>
        </div>
    </div>

    <div class="space-y-6">
        <!-- Add Student Form -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Add Student Manually</h2>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">Enter the student's matric number to mark their
                    attendance</p>
            </div>

            <div class="p-6">
                <div class="flex space-x-4">
                    <input wire:model="studentMatricNumber" type="text" placeholder="Enter student matric number"
                        class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-zinc-700 dark:text-white" />
                    <button wire:click="markAttendanceManually"
                        class="inline-flex items-center px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-zinc-400 disabled:cursor-not-allowed text-white rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Student
                    </button>
                </div>
            </div>
        </div>

        <!-- Current Attendance List -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Current Attendance</h2>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">{{ count($classAttendances) }} students have marked
                    attendance</p>
            </div>

            <div class="p-0">
                @if (count($classAttendances) > 0)
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($classAttendances as $attendance)
                            <div class="flex items-center justify-between p-6">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                                {{ $attendance['full_name'] }}
                                            </p>
                                            <div
                                                class="flex items-center space-x-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                <span>{{ $attendance['matric_number'] }}</span>
                                                @if ($attendance['marked_by_lecturer'])
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Manual
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                                Marked at:
                                                {{ \Carbon\Carbon::parse($attendance['marked_at'])->format('M j, Y g:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <button wire:click="removeAttendance({{ $attendance['id'] }})"
                                    class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                            No students have marked attendance yet.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Inline Toast Notification -->
    @if ($showToast)
        <div x-data="{ show: @entangle('showToast') }" x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2"
            class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-zinc-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden
            @if ($toastType === 'success') border-l-4 border-l-green-500 @endif
            @if ($toastType === 'error') border-l-4 border-l-red-500 @endif
            @if ($toastType === 'warning') border-l-4 border-l-yellow-500 @endif
            @if ($toastType === 'info') border-l-4 border-l-green-500 @endif
            ">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        @if ($toastType === 'success')
                            <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif($toastType === 'error')
                            <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif($toastType === 'warning')
                            <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        @else
                            <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $toastMessage }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button wire:click="hideToast"
                            class="inline-flex text-zinc-400 hover:text-zinc-600 focus:outline-none focus:text-zinc-600 transition ease-in-out duration-150">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Auto-hide toast after 5 seconds
            setTimeout(() => {
                @this.call('hideToast');
            }, 5000);
        </script>
    @endif
</div>

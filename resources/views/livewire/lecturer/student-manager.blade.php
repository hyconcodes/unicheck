<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\ClassAttendance;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedClass = '';
    public $selectedLevel = '';

    public function with(): array
    {
        $query = User::role('student')
            ->where('department_id', auth()->user()->department_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('matric_no', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedLevel) {
            $query->where('level', $this->selectedLevel);
        }

        $students = $query->orderBy('name')->paginate(15);

        // Get lecturer's classes for filtering
        $lecturerClasses = ClassModel::where('lecturer_id', auth()->id())
            ->orderBy('title')
            ->get();

        // Get unique levels from students in the same department
        $levels = User::role('student')
            ->where('department_id', auth()->user()->department_id)
            ->distinct()
            ->pluck('level')
            ->filter()
            ->sort()
            ->values();

        return [
            'students' => $students,
            'lecturerClasses' => $lecturerClasses,
            'levels' => $levels,
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedLevel()
    {
        $this->resetPage();
    }

    public function getStudentAttendanceCount($studentId)
    {
        return ClassAttendance::whereHas('class', function($query) {
            $query->where('lecturer_id', auth()->id());
        })->where('student_id', $studentId)->count();
    }
}; ?>

<div class="p-4 sm:p-6 lg:p-8">
    <div class="mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100">Student Manager</h1>
        <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 mt-1">View and manage students in your department</p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Search Students
                </label>
                <input 
                    type="text" 
                    id="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, email, or matric number..."
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-700 dark:text-white"
                >
            </div>

            <!-- Level Filter -->
            <div>
                <label for="level" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Filter by Level
                </label>
                <select 
                    id="level"
                    wire:model.live="selectedLevel"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-700 dark:text-white"
                >
                    <option value="">All Levels</option>
                    @foreach($levels as $level)
                        <option value="{{ $level }}">Level {{ $level }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                <button 
                    wire:click="$set('search', ''); $set('selectedLevel', '')"
                    class="w-full px-4 py-2 bg-zinc-500 hover:bg-zinc-600 text-white rounded-lg font-medium transition-colors"
                >
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Students List -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="p-4 sm:p-6 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                Students in Your Department
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                Total: {{ $students->total() }} students
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100 text-sm">Student</th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100 text-sm">Matric No</th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100 text-sm">Level</th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100 text-sm">Email</th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100 text-sm">Attendance</th>
                        {{-- <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100 text-sm">Actions</th> --}}
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($students as $student)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                        {{ $student->initials() }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $student->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $student->matric_no }}
                            </td>
                            <td class="py-3 px-4 text-sm text-zinc-600 dark:text-zinc-400">
                                Level {{ $student->level }}
                            </td>
                            <td class="py-3 px-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $student->email }}
                            </td>
                            <td class="py-3 px-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $this->getStudentAttendanceCount($student->id) }} classes attended
                            </td>
                            {{-- <td class="py-3 px-4">
                                <a href="{{ route('student.profile', $student) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                    View Profile
                                </a>
                            </td> --}}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center">
                                <div class="text-zinc-500 dark:text-zinc-400">
                                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.25" />
                                    </svg>
                                    <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">No students found</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        @if($search || $selectedLevel)
                                            Try adjusting your search criteria.
                                        @else
                                            No students are enrolled in your department yet.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $students->links() }}
            </div>
        @endif
    </div>

    <!-- Statistics -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Total Students</h3>
            <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $students->total() }}</p>
        </div>
        
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Your Classes</h3>
            <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $lecturerClasses->count() }}</p>
        </div>
        
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Levels Available</h3>
            <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $levels->count() }}</p>
        </div>
    </div>
</div>
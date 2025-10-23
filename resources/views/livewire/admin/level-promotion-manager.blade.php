<?php

use Livewire\WithPagination;
use App\Models\User;
use App\Models\Department;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $department_id = '';
    public $level = '';
    public $selectedUsers = [];
    public $selectAll = false;
    public $showPromotionModal = false;
    public $promotionType = 'individual'; // 'individual' or 'bulk'
    public $userToPromote = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDepartmentId()
    {
        $this->resetPage();
    }

    public function updatingLevel()
    {
        $this->resetPage();
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedUsers = $this->getFilteredStudents()->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function promoteIndividual($userId)
    {
        $user = User::findOrFail($userId);
        
        if (!$user->canBePromoted()) {
            session()->flash('error', "Student {$user->name} cannot be promoted. They are already at the maximum level or not eligible.");
            return;
        }

        $this->userToPromote = $user;
        $this->promotionType = 'individual';
        $this->showPromotionModal = true;
    }

    public function promoteBulk()
    {
        if (empty($this->selectedUsers)) {
            session()->flash('error', 'Please select at least one student to promote.');
            return;
        }

        $this->promotionType = 'bulk';
        $this->showPromotionModal = true;
    }

    public function confirmPromotion()
    {
        if ($this->promotionType === 'individual' && $this->userToPromote) {
            $this->performIndividualPromotion();
        } elseif ($this->promotionType === 'bulk') {
            $this->performBulkPromotion();
        }

        $this->closePromotionModal();
    }

    private function performIndividualPromotion()
    {
        $oldLevel = $this->userToPromote->level;
        $this->userToPromote->promoteToNextLevel();
        
        session()->flash('success', "Student {$this->userToPromote->name} has been promoted from {$oldLevel} Level to {$this->userToPromote->level} Level.");
    }

    private function performBulkPromotion()
    {
        $users = User::whereIn('id', $this->selectedUsers)->get();
        $promoted = 0;
        $failed = [];

        foreach ($users as $user) {
            if ($user->canBePromoted()) {
                $user->promoteToNextLevel();
                $promoted++;
            } else {
                $failed[] = $user->name;
            }
        }

        $message = "Successfully promoted {$promoted} student(s).";
        if (!empty($failed)) {
            $message .= " Failed to promote: " . implode(', ', $failed) . " (already at maximum level or not eligible).";
        }

        session()->flash('success', $message);
        $this->selectedUsers = [];
        $this->selectAll = false;
    }

    public function closePromotionModal()
    {
        $this->showPromotionModal = false;
        $this->userToPromote = null;
        $this->promotionType = 'individual';
    }

    private function getFilteredStudents()
    {
        $query = User::whereHas('roles', function($q) {
            $q->where('name', 'student');
        })->with(['department', 'roles']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('matric_no', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->department_id) {
            $query->where('department_id', $this->department_id);
        }
        
        if ($this->level) {
            $query->where('level', $this->level);
        }
        
        return $query;
    }

    public function with()
    {
        $students = $this->getFilteredStudents()->paginate(15);
        
        return [
            'students' => $students,
            'departments' => Department::active()->orderBy('name')->get(),
            'levels' => [
                '100' => '100 Level',
                '200' => '200 Level', 
                '300' => '300 Level',
                '400' => '400 Level',
                '500' => '500 Level',
                '600' => '600 Level',
            ]
        ];
    }
}; ?>

<div>
    <div class="p-6 bg-white dark:bg-zinc-800 shadow-md rounded-lg">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Level Promotion Management</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Promote students to the next academic level</p>
        </div>

        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:input 
                wire:model.live="search" 
                placeholder="Search by name, email, or matric no"
                type="text"
                label="Search Students"
            />
            
            <flux:select 
                wire:model.live="department_id"
                label="Filter by Department"
                placeholder="All Departments"
            >
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </flux:select>

            <flux:select 
                wire:model.live="level"
                label="Filter by Level"
                placeholder="All Levels"
            >
                <option value="">All Levels</option>
                @foreach($levels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <div class="flex items-end">
                <flux:button 
                    wire:click="promoteBulk"
                    variant="primary"
                    size="sm"
                    :disabled="empty($selectedUsers)"
                    class="w-full"
                >
                    Promote Selected ({{ count($selectedUsers) }})
                </flux:button>
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <flux:checkbox 
                                wire:model.live="selectAll"
                                label="Select All"
                            />
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Matric No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Current Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Next Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-zinc-200 dark:bg-zinc-800 dark:divide-zinc-700">
                    @forelse ($students as $student)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:checkbox 
                                    wire:model.live="selectedUsers"
                                    value="{{ $student->id }}"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-600 mr-3">
                                        <img src="{{ $student->getAvatarUrl() }}" alt="{{ $student->name }}" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $student->name }}</div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $student->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $student->matric_no ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-zinc-900 dark:text-white">
                                    {{ $student->department->name ?? 'No Department' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                    {{ $student->level_display }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($student->canBePromoted())
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                        {{ $student->getNextLevel() }} Level
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                        Max Level
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if($student->canBePromoted())
                                    <flux:button 
                                        wire:click="promoteIndividual({{ $student->id }})"
                                        variant="primary"
                                        size="sm"
                                    >
                                        Promote
                                    </flux:button>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">Cannot Promote</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400 text-center">
                                No students found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-4">
            @forelse ($students as $student)
                <div class="bg-zinc-50 dark:bg-zinc-700 rounded-lg p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center">
                            <flux:checkbox 
                                wire:model.live="selectedUsers"
                                value="{{ $student->id }}"
                                class="mr-3"
                            />
                            <div class="w-12 h-12 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-600">
                                <img src="{{ $student->getAvatarUrl() }}" alt="{{ $student->name }}" class="w-full h-full object-cover">
                            </div>
                        </div>
                        @if($student->canBePromoted())
                            <flux:button 
                                wire:click="promoteIndividual({{ $student->id }})"
                                variant="primary"
                                size="sm"
                            >
                                Promote
                            </flux:button>
                        @endif
                    </div>
                    
                    <div class="ml-15">
                        <h3 class="font-medium text-zinc-900 dark:text-white">{{ $student->name }}</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $student->email }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Matric: {{ $student->matric_no ?? 'N/A' }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Department: {{ $student->department->name ?? 'No Department' }}</p>
                        
                        <div class="flex items-center justify-between mt-2">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                Current: {{ $student->level_display }}
                            </span>
                            @if($student->canBePromoted())
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Next: {{ $student->getNextLevel() }} Level
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    Max Level
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <p class="text-zinc-500 dark:text-zinc-400">No students found</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $students->links() }}
        </div>
    </div>

    <!-- Promotion Confirmation Modal -->
    <flux:modal wire:model="showPromotionModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Confirm Level Promotion</flux:heading>
            
            @if($promotionType === 'individual' && $userToPromote)
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to promote <strong>{{ $userToPromote->name }}</strong> from 
                    <strong>{{ $userToPromote->level_display }}</strong> to 
                    <strong>{{ $userToPromote->getNextLevel() }} Level</strong>?
                </p>
            @elseif($promotionType === 'bulk')
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to promote <strong>{{ count($selectedUsers) }} selected student(s)</strong> to their next level?
                    Students who are already at the maximum level will be skipped.
                </p>
            @endif
        </div>

        <div class="flex space-x-2">
            <flux:button wire:click="confirmPromotion" variant="primary">
                Confirm Promotion
            </flux:button>
            <flux:button wire:click="closePromotionModal" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </flux:modal>
</div>
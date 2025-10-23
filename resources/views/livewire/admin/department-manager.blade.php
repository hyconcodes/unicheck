<?php

use Livewire\WithPagination;
use App\Models\Department;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public $name = '';
    public $code = '';
    public $description = '';
    public $is_active = true;
    public $search = '';
    public $editingId = null;
    public $showDeleteModal = false;
    public $departmentToDelete = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:10|alpha_num',
        'description' => 'nullable|string|max:1000',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'name.required' => 'Department name is required.',
        'name.max' => 'Department name cannot exceed 255 characters.',
        'code.required' => 'Department code is required.',
        'code.max' => 'Department code cannot exceed 10 characters.',
        'code.alpha_num' => 'Department code must contain only letters and numbers.',
        'description.max' => 'Description cannot exceed 1000 characters.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function save()
    {
        $this->validate();

        // Check for unique name and code
        $query = Department::where('name', $this->name)
            ->orWhere('code', $this->code);
        
        if ($this->editingId) {
            $query->where('id', '!=', $this->editingId);
        }

        if ($query->exists()) {
            if (Department::where('name', $this->name)->where('id', '!=', $this->editingId)->exists()) {
                $this->addError('name', 'Department name already exists.');
            }
            if (Department::where('code', $this->code)->where('id', '!=', $this->editingId)->exists()) {
                $this->addError('code', 'Department code already exists.');
            }
            return;
        }

        if ($this->editingId) {
            $department = Department::find($this->editingId);
            $department->update([
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            session()->flash('message', 'Department updated successfully!');
        } else {
            Department::create([
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            session()->flash('message', 'Department created successfully!');
        }

        $this->reset(['name', 'code', 'description', 'is_active', 'editingId']);
        $this->is_active = true;
    }

    public function edit($id)
    {
        $department = Department::find($id);
        $this->editingId = $id;
        $this->name = $department->name;
        $this->code = $department->code;
        $this->description = $department->description;
        $this->is_active = $department->is_active;
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'code', 'description', 'is_active', 'editingId']);
        $this->is_active = true;
    }

    public function confirmDelete($id)
    {
        $this->departmentToDelete = Department::find($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->departmentToDelete) {
            // Check if department has users
            if ($this->departmentToDelete->users()->count() > 0) {
                session()->flash('error', 'Cannot delete department with assigned users. Please reassign users first.');
                $this->showDeleteModal = false;
                $this->departmentToDelete = null;
                return;
            }

            $this->departmentToDelete->delete();
            session()->flash('message', 'Department deleted successfully!');
        }

        $this->showDeleteModal = false;
        $this->departmentToDelete = null;
    }

    public function toggleStatus($id)
    {
        $department = Department::find($id);
        $department->update(['is_active' => !$department->is_active]);
        
        $status = $department->is_active ? 'activated' : 'deactivated';
        session()->flash('message', "Department {$status} successfully!");
    }

    public function with()
    {
        $departments = Department::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->withCount(['users', 'students', 'lecturers'])
            ->orderBy('name')
            ->paginate(10);

        return compact('departments');
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900">Department Management</h1>
            <p class="text-sm sm:text-base text-zinc-600 mt-1">Manage university departments and their settings</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('message') }}
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Department Form -->
    <div class="bg-white rounded-lg shadow-sm border border-zinc-200 p-4 sm:p-6">
        <h2 class="text-lg sm:text-xl font-semibold text-zinc-900 mb-4">
            {{ $editingId ? 'Edit Department' : 'Add New Department' }}
        </h2>
        
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Department Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-zinc-700 mb-1">
                        Department Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name"
                        wire:model="name"
                        class="w-full px-3 py-2 text-sm sm:text-base border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., Computer Science"
                    >
                    @error('name') 
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> 
                    @enderror
                </div>

                <!-- Department Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-zinc-700 mb-1">
                        Department Code <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="code"
                        wire:model="code"
                        class="w-full px-3 py-2 text-sm sm:text-base border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                        placeholder="e.g., CSC"
                        maxlength="10"
                    >
                    @error('code') 
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> 
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-zinc-700 mb-1">
                    Description
                </label>
                <textarea 
                    id="description"
                    wire:model="description"
                    rows="3"
                    class="w-full px-3 py-2 text-sm sm:text-base border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Brief description of the department..."
                ></textarea>
                @error('description') 
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p> 
                @enderror
            </div>

            <!-- Status -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="is_active"
                    wire:model="is_active"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-zinc-300 rounded"
                >
                <label for="is_active" class="ml-2 text-sm text-zinc-700">
                    Active Department
                </label>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <button 
                    type="submit"
                    class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white text-sm sm:text-base font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                >
                    <svg class="h-4 w-4 sm:h-5 sm:w-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $editingId ? 'Update Department' : 'Create Department' }}
                </button>
                
                @if($editingId)
                    <button 
                        type="button"
                        wire:click="cancelEdit"
                        class="w-full sm:w-auto px-4 py-2 bg-zinc-600 text-white text-sm sm:text-base font-medium rounded-lg hover:bg-zinc-700 focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 transition-colors"
                    >
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-zinc-200 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-zinc-700 mb-1">Search Departments</label>
                <input 
                    type="text" 
                    id="search"
                    wire:model.live="search"
                    class="w-full px-3 py-2 text-sm sm:text-base border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Search by name, code, or description..."
                >
            </div>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="bg-white rounded-lg shadow-sm border border-zinc-200 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-zinc-200">
            <h3 class="text-lg font-medium text-zinc-900">Departments ({{ $departments->total() }})</h3>
        </div>

        @if($departments->count() > 0)
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200">
                    <thead class="bg-zinc-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Users</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-zinc-200">
                        @foreach($departments as $department)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-zinc-900">{{ $department->name }}</div>
                                        @if($department->description)
                                            <div class="text-sm text-zinc-500">{{ Str::limit($department->description, 50) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-800">
                                        {{ $department->code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-900">
                                    <div class="space-y-1">
                                        <div>Total: {{ $department->users_count }}</div>
                                        <div class="text-xs text-zinc-500">
                                            Students: {{ $department->students_count }} | Lecturers: {{ $department->lecturers_count }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <button 
                                        wire:click="toggleStatus({{ $department->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}"
                                    >
                                        {{ $department->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium space-x-2">
                                    <button 
                                        wire:click="edit({{ $department->id }})"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        wire:click="confirmDelete({{ $department->id }})"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="sm:hidden divide-y divide-zinc-200">
                @foreach($departments as $department)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-zinc-900">{{ $department->name }}</h4>
                                <p class="text-xs text-zinc-500 mt-1">Code: {{ $department->code }}</p>
                                @if($department->description)
                                    <p class="text-xs text-zinc-600 mt-1">{{ Str::limit($department->description, 60) }}</p>
                                @endif
                            </div>
                            <button 
                                wire:click="toggleStatus({{ $department->id }})"
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}"
                            >
                                {{ $department->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </div>
                        
                        <div class="text-xs text-zinc-500">
                            Total Users: {{ $department->users_count }} (Students: {{ $department->students_count }}, Lecturers: {{ $department->lecturers_count }})
                        </div>
                        
                        <div class="flex space-x-3">
                            <button 
                                wire:click="edit({{ $department->id }})"
                                class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                            >
                                Edit
                            </button>
                            <button 
                                wire:click="confirmDelete({{ $department->id }})"
                                class="text-red-600 hover:text-red-900 text-sm font-medium"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-4 sm:px-6 py-4 border-t border-zinc-200">
                {{ $departments->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-zinc-900">No departments found</h3>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ $search ? 'Try adjusting your search criteria.' : 'Get started by creating your first department.' }}
                </p>
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $departmentToDelete)
        <div class="fixed inset-0 bg-zinc-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-sm sm:max-w-md w-full p-4 sm:p-6">
                <div class="flex items-center mb-4">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-red-100">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                </div>
                
                <div class="text-center mb-6">
                    <h3 class="text-lg font-medium text-zinc-900 mb-2">Delete Department</h3>
                    <p class="text-sm text-zinc-500">
                        Are you sure you want to delete <strong>{{ $departmentToDelete->name }}</strong>? 
                        This action cannot be undone.
                    </p>
                    @if($departmentToDelete->users_count > 0)
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-xs text-yellow-800">
                                <strong>Warning:</strong> This department has {{ $departmentToDelete->users_count }} assigned user(s). 
                                You must reassign them before deletion.
                            </p>
                        </div>
                    @endif
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <button 
                        wire:click="delete"
                        class="w-full sm:w-auto px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        {{ $departmentToDelete->users_count > 0 ? 'disabled' : '' }}
                    >
                        Delete
                    </button>
                    <button 
                        wire:click="$set('showDeleteModal', false)"
                        class="w-full sm:w-auto px-4 py-2 bg-zinc-600 text-white text-sm font-medium rounded-lg hover:bg-zinc-700 focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
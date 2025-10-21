<?php

use Livewire\WithPagination;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $role = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRole()
    {
        $this->resetPage();
    }

    public function viewProfile($userId)
    {
        $user = User::findOrFail($userId);
        $userRole = $user->roles->first()->name ?? 'student';
        
        if ($userRole === 'lecturer') {
            return redirect()->route('lecturer.profile', $user->id);
        } else {
            return redirect()->route('student.profile', $user->id);
        }
    }

    public function with()
    {
        $query = User::query();
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->role) {
            $query->whereHas('roles', function($q) {
                $q->where('name', $this->role);
            });
        }
        
        $users = $query->paginate(10);
        
        return [
            'users' => $users,
        ];
    }
}; ?>

<div>
    <div class="p-6 bg-white dark:bg-zinc-800 shadow-md rounded-lg">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Account Management</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage lecturer and student accounts</p>
        </div>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:input 
                wire:model.live="search" 
                placeholder="Search by name or email"
                type="text"
                label="Search Users"
            />
            <flux:select 
                wire:model.live="role"
                label="Filter by Role"
                placeholder="All Accounts"
            >
                <option value="">All Accounts</option>
                <option value="lecturer">Lecturers Only</option>
                <option value="student">Students Only</option>
            </flux:select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Avatar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Role</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-zinc-200 dark:bg-zinc-800 dark:divide-zinc-700">
                    @forelse ($users as $user)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer transition-colors duration-200" 
                            wire:click="viewProfile({{ $user->id }})">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-600">
                                        <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @foreach($user->roles as $role)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400 text-center">
                                No users found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</div>
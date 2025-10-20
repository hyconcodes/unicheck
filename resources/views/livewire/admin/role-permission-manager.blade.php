<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $showCreateRoleModal = false;
    public $showCreatePermissionModal = false;
    public $showAssignRoleModal = false;
    public $showAssignPermissionModal = false;
    
    public $roleName = '';
    public $permissionName = '';
    public $selectedUserId = '';
    public $selectedRoleId = '';
    public $selectedPermissions = [];
    public $selectedRoles = [];
    
    public $editingRole = null;
    public $editingPermission = null;

    public function createRole()
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name'
        ]);

        Role::create(['name' => $this->roleName]);
        
        $this->reset(['roleName', 'showCreateRoleModal']);
        session()->flash('success', 'Role created successfully!');
    }

    public function createPermission()
    {
        $this->validate([
            'permissionName' => 'required|string|max:255|unique:permissions,name'
        ]);

        Permission::create(['name' => $this->permissionName]);
        
        $this->reset(['permissionName', 'showCreatePermissionModal']);
        session()->flash('success', 'Permission created successfully!');
    }

    public function assignRoleToUser()
    {
        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'selectedRoles' => 'required|array|min:1'
        ]);

        $user = User::find($this->selectedUserId);
        $user->syncRoles($this->selectedRoles);
        
        $this->reset(['selectedUserId', 'selectedRoles', 'showAssignRoleModal']);
        session()->flash('success', 'Roles assigned to user successfully!');
    }

    public function assignPermissionToRole()
    {
        $this->validate([
            'selectedRoleId' => 'required|exists:roles,id',
            'selectedPermissions' => 'required|array|min:1'
        ]);

        $role = Role::find($this->selectedRoleId);
        $role->syncPermissions($this->selectedPermissions);
        
        $this->reset(['selectedRoleId', 'selectedPermissions', 'showAssignPermissionModal']);
        session()->flash('success', 'Permissions assigned to role successfully!');
    }

    public function deleteRole($roleId)
    {
        $role = Role::find($roleId);
        if ($role && !in_array($role->name, ['superadmin', 'student', 'lecturer'])) {
            $role->delete();
            session()->flash('success', 'Role deleted successfully!');
        } else {
            session()->flash('error', 'Cannot delete system roles!');
        }
    }

    public function deletePermission($permissionId)
    {
        $permission = Permission::find($permissionId);
        if ($permission) {
            $permission->delete();
            session()->flash('success', 'Permission deleted successfully!');
        }
    }

    public function with(): array
    {
        return [
            'roles' => Role::with('permissions')->paginate(10, ['*'], 'rolesPage'),
            'permissions' => Permission::with('roles')->paginate(10, ['*'], 'permissionsPage'),
            'users' => User::with('roles')->get(),
            'allRoles' => Role::all(),
            'allPermissions' => Permission::all(),
        ];
    }
}; ?>

<div class="min-h-screen bg-zinc-100 dark:bg-zinc-900">
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 mb-8">
            <h1 class="text-4xl font-extrabold text-zinc-900 dark:text-white mb-2">
                Role & Permission Management
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                Effortlessly manage system roles, permissions, and user access control.
            </p>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-700 flex items-center space-x-3">
                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-700 flex items-center space-x-3">
                <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <flux:button wire:click="$set('showCreateRoleModal', true)" variant="primary" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Create Role
            </flux:button>
            
            <flux:button wire:click="$set('showCreatePermissionModal', true)" variant="primary" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Create Permission
            </flux:button>
            
            <flux:button wire:click="$set('showAssignRoleModal', true)" variant="outline" class="w-full border-green-500 text-green-600 hover:bg-green-50 dark:hover:bg-zinc-700 font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1-857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Assign Roles
            </flux:button>
            
            <flux:button wire:click="$set('showAssignPermissionModal', true)" variant="outline" class="w-full border-yellow-500 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-zinc-700 font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                Assign Permissions
            </flux:button>
        </div>

        <!-- Roles and Permissions Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Roles Table -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mb-4">System Roles</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Permissions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($roles as $role)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $role->name }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($role->permissions->take(3) as $permission)
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    {{ $permission->name }}
                                                </span>
                                            @endforeach
                                            @if($role->permissions->count() > 3)
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300">
                                                    +{{ $role->permissions->count() - 3 }} more
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if(!in_array($role->name, ['superadmin', 'student', 'lecturer']))
                                            <flux:button wire:click="deleteRole({{ $role->id }})" size="sm" variant="danger" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-1 px-3 rounded-lg transition duration-300 ease-in-out">
                                                Delete
                                            </flux:button>
                                        @else
                                            <span class="text-zinc-500 dark:text-zinc-400">System Role</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $roles->links() }}
                    </div>
                </div>
            </div>

            <!-- Permissions Table -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mb-4">System Permissions</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Permission</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Roles</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($permissions as $permission)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $permission->name }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($permission->roles->take(2) as $role)
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                            @if($permission->roles->count() > 2)
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300">
                                                    +{{ $permission->roles->count() - 2 }} more
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <flux:button wire:click="deletePermission({{ $permission->id }})" size="sm" variant="danger" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-1 px-3 rounded-lg transition duration-300 ease-in-out">
                                            Delete
                                        </flux:button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $permissions->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <!-- Create Role Modal -->
        @if($showCreateRoleModal)
        <div class="fixed inset-0 bg-zinc-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-zinc-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">Create New Role</h3>
                    <form wire:submit="createRole">
                        <div class="mb-4">
                            <flux:input wire:model="roleName" label="Role Name" placeholder="Enter role name" required />
                        </div>
                        <div class="flex justify-end space-x-2">
                            <flux:button wire:click="$set('showCreateRoleModal', false)" variant="outline">Cancel</flux:button>
                            <flux:button type="submit" variant="primary" class="bg-green-600 hover:bg-green-700">Create</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Create Permission Modal -->
        @if($showCreatePermissionModal)
        <div class="fixed inset-0 bg-zinc-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-zinc-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">Create New Permission</h3>
                    <form wire:submit="createPermission">
                        <div class="mb-4">
                            <flux:input wire:model="permissionName" label="Permission Name" placeholder="Enter permission name" required />
                        </div>
                        <div class="flex justify-end space-x-2">
                            <flux:button wire:click="$set('showCreatePermissionModal', false)" variant="outline">Cancel</flux:button>
                            <flux:button type="submit" variant="primary" class="bg-yellow-600 hover:bg-yellow-700">Create</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Assign Role Modal -->
        @if($showAssignRoleModal)
        <div class="fixed inset-0 bg-zinc-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-zinc-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">Assign Roles to User</h3>
                    <form wire:submit="assignRoleToUser">
                        <div class="mb-4">
                            <flux:select wire:model="selectedUserId" label="Select User" required>
                                <option value="">Choose a user...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Select Roles</label>
                            @foreach($allRoles as $role)
                                <label class="flex items-center mb-2">
                                    <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}" class="mr-2">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="flex justify-end space-x-2">
                            <flux:button wire:click="$set('showAssignRoleModal', false)" variant="outline">Cancel</flux:button>
                            <flux:button type="submit" variant="primary" class="bg-green-600 hover:bg-green-700">Assign</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Assign Permission Modal -->
        @if($showAssignPermissionModal)
        <div class="fixed inset-0 bg-zinc-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-zinc-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">Assign Permissions to Role</h3>
                    <form wire:submit="assignPermissionToRole">
                        <div class="mb-4">
                            <flux:select wire:model="selectedRoleId" label="Select Role" required>
                                <option value="">Choose a role...</option>
                                @foreach($allRoles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Select Permissions</label>
                            <div class="max-h-40 overflow-y-auto">
                                @foreach($allPermissions as $permission)
                                    <label class="flex items-center mb-2">
                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="mr-2">
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <flux:button wire:click="$set('showAssignPermissionModal', false)" variant="outline">Cancel</flux:button>
                            <flux:button type="submit" variant="primary" class="bg-yellow-600 hover:bg-yellow-700">Assign</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
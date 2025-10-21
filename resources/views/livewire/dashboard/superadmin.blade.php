<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        return [
            'totalUsers' => User::count(),
            'totalStudents' => User::role('student')->count(),
            'totalLecturers' => User::role('lecturer')->count(),
            'totalRoles' => Role::count(),
            'totalPermissions' => Permission::count(),
        ];
    }
}; ?>

<div class="min-h-screen bg-zinc-100 dark:bg-zinc-900">
    <div class="max-w-screen-2xl mx-auto p-3 sm:p-4 md:p-6 lg:p-8">
        <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-lg">
            <div class="p-4 sm:p-6 text-zinc-900 dark:text-zinc-100">
                <!-- Welcome Header -->
                <div class="mb-6 sm:mb-8">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-zinc-800 dark:text-white leading-tight">
                        Welcome, {{ auth()->user()->name }}!
                    </h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-2 text-sm sm:text-base lg:text-lg">
                        Super Administrator Dashboard - System Management & Control
                    </p>
                </div>

                <!-- System Statistics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
                    <!-- Total Users Card -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-4 sm:p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 ease-in-out transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold">Total Users</h3>
                                <p class="text-2xl sm:text-3xl font-bold mt-2">{{ $totalUsers }}</p>
                            </div>
                            <div class="bg-blue-700 p-2 sm:p-3 rounded-full bg-opacity-50">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Students Card -->
                    <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-4 sm:p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 ease-in-out transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold">Students</h3>
                                <p class="text-2xl sm:text-3xl font-bold mt-2">{{ $totalStudents }}</p>
                            </div>
                            <div class="bg-green-700 p-2 sm:p-3 rounded-full bg-opacity-50">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Lecturers Card -->
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-4 sm:p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 ease-in-out transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold">Lecturers</h3>
                                <p class="text-2xl sm:text-3xl font-bold mt-2">{{ $totalLecturers }}</p>
                            </div>
                            <div class="bg-purple-700 p-2 sm:p-3 rounded-full bg-opacity-50">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Roles Card -->
                    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-4 sm:p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 ease-in-out transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold">Roles</h3>
                                <p class="text-2xl sm:text-3xl font-bold mt-2">{{ $totalRoles }}</p>
                            </div>
                            <div class="bg-yellow-700 p-2 sm:p-3 rounded-full bg-opacity-50">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Card -->
                    {{-- <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 ease-in-out transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold">Permissions</h3>
                                <p class="text-3xl font-bold mt-2">{{ $totalPermissions }}</p>
                            </div>
                            <div class="bg-red-700 p-3 rounded-full bg-opacity-50">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                    </div> --}}
                </div>

                <!-- Management Sections -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-8 sm:mb-10">
                    <!-- User Management -->
                    <div class="bg-zinc-50 dark:bg-zinc-800 p-4 sm:p-6 rounded-xl shadow-md">
                        <h2 class="text-xl sm:text-2xl font-bold text-zinc-800 dark:text-white mb-4 sm:mb-5">User Management</h2>
                        <div class="space-y-3 sm:space-y-4">
                            <flux:button variant="primary" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 sm:py-3 rounded-lg text-base sm:text-lg font-medium" href="{{ route('superadmin.account-manager') }}">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Manage All Users
                            </flux:button>
                            
                            
                            <flux:button variant="outline" class="w-full border-purple-500 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-zinc-700 py-2.5 sm:py-3 rounded-lg text-base sm:text-lg font-medium">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                Manage Lecturers
                            </flux:button>
                        </div>
                    </div>

                    <!-- System Management -->
                    <div class="bg-zinc-50 dark:bg-zinc-800 p-4 sm:p-6 rounded-xl shadow-md">
                        <h2 class="text-xl sm:text-2xl font-bold text-zinc-800 dark:text-white mb-4 sm:mb-5">System Management</h2>
                        <div class="space-y-3 sm:space-y-4">
                            <flux:button variant="primary" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white py-2.5 sm:py-3 rounded-lg text-base sm:text-lg font-medium" onclick="window.location.href='{{ route('admin.role-permission-manager') }}'">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                Manage Roles & Permissions
                            </flux:button>
                            
                            <flux:button variant="outline" class="w-full border-yellow-500 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-zinc-700 py-2.5 sm:py-3 rounded-lg text-base sm:text-lg font-medium">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                System Reports
                            </flux:button>
                            
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-zinc-50 dark:bg-zinc-800 p-4 sm:p-6 rounded-xl shadow-md">
                    <h2 class="text-xl sm:text-2xl font-bold text-zinc-800 dark:text-white mb-4 sm:mb-5">Recent System Activity</h2>
                    <div class="space-y-3 sm:space-y-4">
                        <div class="flex items-center justify-between p-3 sm:p-4 bg-white dark:bg-zinc-700 rounded-lg shadow-sm">
                            <div class="flex items-center">
                                <div class="bg-blue-100 dark:bg-blue-800 p-2 sm:p-3 rounded-full mr-3 sm:mr-4 flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-zinc-800 dark:text-white text-sm sm:text-base lg:text-lg">New student registered</p>
                                    <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 mt-1 truncate">john.doe123@bouesti.edu.ng - 2 minutes ago</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-3 sm:p-4 bg-white dark:bg-zinc-700 rounded-lg shadow-sm">
                            <div class="flex items-center">
                                <div class="bg-green-100 dark:bg-green-800 p-2 sm:p-3 rounded-full mr-3 sm:mr-4 flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-zinc-800 dark:text-white text-sm sm:text-base lg:text-lg">New lecturer registered</p>
                                    <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 mt-1 truncate">jane.smith@bouesti.edu.ng - 15 minutes ago</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
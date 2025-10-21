<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(User $user)
    {
        $this->user = $user;
    }
}; ?>

<div>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <a href="{{ route('superadmin.account-manager') }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </a>
                        <h1 class="ml-4 text-2xl font-bold text-zinc-900 dark:text-white">Lecturer Profile</h1>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Horizontal Profile Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden mb-8">
                <div class="flex flex-col md:flex-row">
                    <!-- Avatar Section -->
                    <div class="md:w-1/3 bg-gradient-to-br from-green-500 to-teal-600 p-8 flex items-center justify-center">
                        <div class="text-center">
                            <div class="w-32 h-32 mx-auto mb-4 rounded-full bg-white/20 overflow-hidden">
                                <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                            </div>
                            <h2 class="text-2xl font-bold text-white">{{ $user->name }}</h2>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
                                    </svg>
                                    Lecturer
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Information Section -->
                    <div class="md:w-2/3 p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Contact Information</h3>
                                <div class="mt-3 space-y-3">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-zinc-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                        </svg>
                                        <span class="text-zinc-900 dark:text-white">{{ $user->email }}</span>
                                    </div>
                                    @if($user->matric_no)
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-zinc-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zM8 6V5a2 2 0 114 0v1H8z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-zinc-900 dark:text-white">Staff ID: {{ $user->matric_no }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Account Details</h3>
                                <div class="mt-3 space-y-3">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-zinc-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-zinc-900 dark:text-white">Joined {{ $user->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-zinc-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-zinc-900 dark:text-white">Last active {{ $user->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Teaching Status -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">Teaching Status</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Active Faculty</p>
                        </div>
                    </div>
                </div>

                <!-- Email Verification -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $user->email_verified_at ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }}">
                            <svg class="w-6 h-6 {{ $user->email_verified_at ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                @if($user->email_verified_at)
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                @else
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                @endif
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">Email Status</h3>
                            <p class="text-sm {{ $user->email_verified_at ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $user->email_verified_at ? 'Verified' : 'Not Verified' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Two Factor Authentication -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $user->two_factor_secret ? 'bg-green-100 dark:bg-green-900' : 'bg-zinc-100 dark:bg-zinc-700' }}">
                            <svg class="w-6 h-6 {{ $user->two_factor_secret ? 'text-green-600 dark:text-green-400' : 'text-zinc-600 dark:text-zinc-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">2FA Status</h3>
                            <p class="text-sm {{ $user->two_factor_secret ? 'text-green-600 dark:text-green-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                {{ $user->two_factor_secret ? 'Enabled' : 'Disabled' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Department -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2h6v4H7V6zm8 8v2h1v-2h-1zm-2-2H4v2h9v-2z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">Department</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Computer Science</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="mt-8 bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-4">Academic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Specialization</h4>
                        <p class="text-zinc-900 dark:text-white">Software Engineering & Web Development</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Office Hours</h4>
                        <p class="text-zinc-900 dark:text-white">Monday - Friday, 9:00 AM - 5:00 PM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
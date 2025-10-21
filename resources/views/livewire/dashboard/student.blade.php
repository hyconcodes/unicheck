<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    //
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-zinc-900 dark:text-zinc-100">
                <!-- Welcome Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-green-700 dark:text-green-400">
                        Welcome, {{ auth()->user()->name }}!
                    </h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-2">
                        Student Dashboard - Matriculation Number: {{ auth()->user()->matric_no }}
                    </p>
                </div>

                <!-- Dashboard Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Academic Progress Card -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 p-6 rounded-lg shadow-md border border-green-200 dark:border-green-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Academic Progress</h3>
                                <p class="text-green-600 dark:text-green-400 text-sm mt-1">Track your courses</p>
                            </div>
                            <div class="bg-green-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Assignments Card -->
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900 dark:to-yellow-800 p-6 rounded-lg shadow-md border border-yellow-200 dark:border-yellow-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Assignments</h3>
                                <p class="text-yellow-600 dark:text-yellow-400 text-sm mt-1">Pending submissions</p>
                            </div>
                            <div class="bg-yellow-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Grades Card -->
                    <div class="bg-gradient-to-br from-green-50 to-yellow-50 dark:from-green-900 dark:to-yellow-900 p-6 rounded-lg shadow-md border border-green-200 dark:border-green-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Grades</h3>
                                <p class="text-green-600 dark:text-green-400 text-sm mt-1">View your results</p>
                            </div>
                            <div class="bg-gradient-to-r from-green-500 to-yellow-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-zinc-50 dark:bg-zinc-700 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:button variant="primary" class="bg-green-600 hover:bg-green-700 text-white">
                            View Course Materials
                        </flux:button>
                        <flux:button variant="outline" class="border-yellow-500 text-yellow-600 hover:bg-yellow-50">
                            Submit Assignment
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
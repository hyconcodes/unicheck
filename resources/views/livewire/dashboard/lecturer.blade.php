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
                        Lecturer Dashboard - Teaching & Course Management
                    </p>
                </div>

                <!-- Dashboard Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- My Courses Card -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 p-6 rounded-lg shadow-md border border-green-200 dark:border-green-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">My Courses</h3>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">5</p>
                            </div>
                            <div class="bg-green-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Students Card -->
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900 dark:to-yellow-800 p-6 rounded-lg shadow-md border border-yellow-200 dark:border-yellow-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Students</h3>
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">127</p>
                            </div>
                            <div class="bg-yellow-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Assignments Card -->
                    <div class="bg-gradient-to-br from-green-50 to-yellow-50 dark:from-green-900 dark:to-yellow-900 p-6 rounded-lg shadow-md border border-green-200 dark:border-green-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Assignments</h3>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">12</p>
                            </div>
                            <div class="bg-gradient-to-r from-green-500 to-yellow-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Grades Card -->
                    <div class="bg-gradient-to-br from-yellow-50 to-green-50 dark:from-yellow-900 dark:to-green-900 p-6 rounded-lg shadow-md border border-yellow-200 dark:border-yellow-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Pending Grades</h3>
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">8</p>
                            </div>
                            <div class="bg-yellow-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Course Management -->
                    <div class="bg-zinc-50 dark:bg-zinc-700 p-6 rounded-lg">
                        <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200 mb-4">Course Management</h2>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-600 rounded-lg">
                                <div>
                                    <p class="font-medium text-zinc-800 dark:text-zinc-200">Computer Science 101</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">45 students enrolled</p>
                                </div>
                                <flux:button size="sm" variant="outline" class="border-green-500 text-green-600">
                                    Manage
                                </flux:button>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-600 rounded-lg">
                                <div>
                                    <p class="font-medium text-zinc-800 dark:text-zinc-200">Data Structures</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">38 students enrolled</p>
                                </div>
                                <flux:button size="sm" variant="outline" class="border-green-500 text-green-600">
                                    Manage
                                </flux:button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-zinc-50 dark:bg-zinc-700 p-6 rounded-lg">
                        <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200 mb-4">Quick Actions</h2>
                        <div class="grid grid-cols-1 gap-3">
                            <flux:button variant="primary" class="bg-green-600 hover:bg-green-700 text-white">
                                Create New Assignment
                            </flux:button>
                            <flux:button variant="outline" class="border-yellow-500 text-yellow-600 hover:bg-yellow-50">
                                Grade Submissions
                            </flux:button>
                            <flux:button variant="outline" class="border-green-500 text-green-600 hover:bg-green-50">
                                Manage Students
                            </flux:button>
                            <flux:button variant="outline" class="border-yellow-500 text-yellow-600 hover:bg-yellow-50">
                                Upload Course Materials
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
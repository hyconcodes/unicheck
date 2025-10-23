<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ClassModel;
use App\Models\User;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        $totalClasses = ClassModel::where('lecturer_id', auth()->id())->count();
        
        $totalStudents = ClassModel::where('lecturer_id', auth()->id())
                                 ->withCount(['attendances as students_count'])
                                 ->get()
                                 ->sum('students_count');
        
        $recentClasses = ClassModel::where('lecturer_id', auth()->id())
                                 ->with(['department'])
                                 ->withCount('students')
                                 ->latest()
                                 ->take(3)
                                 ->get();

        return [
            'totalClasses' => $totalClasses,
            'totalStudents' => $totalStudents,
            'recentClasses' => $recentClasses,
        ];
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <!-- Welcome Header -->
        <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-zinc-900 dark:text-white">
                Welcome back, {{ auth()->user()->name }}! 👨‍🏫
            </h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-2 text-sm sm:text-base">
                Lecturer Dashboard • {{ auth()->user()->email }}
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <!-- My Classes Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">My Classes</p>
                        <p class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalClasses }}</p>
                    </div>
                </div>
            </div>

            <!-- Total Students Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Total Students</p>
                        <p class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalStudents }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 hover:shadow-md transition-shadow sm:col-span-2 lg:col-span-1">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Quick Actions</p>
                        <p class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">Available</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 mb-8">
            <!-- Class Management -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-zinc-900 dark:text-white">Class Management</h2>
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <a href="{{ route('lecturer.classes') }}" 
                       class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 px-4 rounded-lg font-medium transition-colors">
                        Manage My Classes
                    </a>
                    <a href="{{ route('lecturer.classes.create') }}" 
                       class="block w-full border border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900 text-center py-3 px-4 rounded-lg font-medium transition-colors">
                        Create New Class
                    </a>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center">
                        Create and manage your classes, track attendance
                    </p>
                </div>
            </div>

            <!-- Student Management -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-zinc-900 dark:text-white">Student Management</h2>
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <a href="{{ route('lecturer.students') }}" 
                       class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-3 px-4 rounded-lg font-medium transition-colors">
                        View All Students
                    </a>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center">
                        View students enrolled in your classes
                    </p>
                </div>
            </div>
        </div>

        <!-- Recent Classes -->
        @if($recentClasses->count() > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 mb-8">
            <h2 class="text-lg sm:text-xl font-semibold text-zinc-900 dark:text-white mb-6">Recent Classes</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($recentClasses as $class)
                <div class="bg-zinc-50 dark:bg-zinc-700 rounded-lg p-4 hover:bg-zinc-100 dark:hover:bg-zinc-600 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-medium text-zinc-900 dark:text-white text-sm sm:text-base">{{ $class->name }}</h3>
                            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $class->code }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">
                                {{ $class->students_count ?? 0 }} students
                            </p>
                        </div>
                        <div class="flex-shrink-0 ml-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                Active
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Getting Started Guide -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg sm:text-xl font-semibold text-zinc-900 dark:text-white mb-4">Getting Started</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="flex items-start space-x-3 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">1</span>
                    </div>
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Create Classes</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Set up your classes with location-based attendance</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">2</span>
                    </div>
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Manage Students</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">View and manage students in your classes</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex-shrink-0 w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-purple-600 dark:text-purple-400">3</span>
                    </div>
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Track Attendance</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Monitor student attendance and participation</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
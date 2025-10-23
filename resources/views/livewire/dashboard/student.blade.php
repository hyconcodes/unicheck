<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Complaint;
use App\Models\ClassModel;

new #[Layout('components.layouts.app')] class extends Component {
    public $showComplaintModal = false;
    public $complaintSubject = '';
    public $complaintMessage = '';
    public $complaintPriority = 'medium';
    public $showToast = false;
    public $toastMessage = '';
    public $toastType = 'success';

    public function with(): array
    {
        $totalClasses = ClassModel::whereHas('attendances', function($query) {
            $query->where('student_id', auth()->id());
        })->count();
        
        $pendingComplaints = Complaint::where('student_id', auth()->id())
                                   ->where('status', 'pending')
                                   ->count();
        
        $recentClasses = ClassModel::whereHas('attendances', function($query) {
            $query->where('student_id', auth()->id());
        })->with(['lecturer', 'department'])
          ->latest()
          ->take(3)
          ->get();

        return [
            'totalClasses' => $totalClasses,
            'pendingComplaints' => $pendingComplaints,
            'recentClasses' => $recentClasses,
        ];
    }

    public function openComplaintModal()
    {
        $this->showComplaintModal = true;
        $this->reset(['complaintSubject', 'complaintMessage', 'complaintPriority']);
    }

    public function closeComplaintModal()
    {
        $this->showComplaintModal = false;
        $this->reset(['complaintSubject', 'complaintMessage', 'complaintPriority']);
    }

    public function submitComplaint()
    {
        $this->validate([
            'complaintSubject' => 'required|string|max:255',
            'complaintMessage' => 'required|string|max:1000',
            'complaintPriority' => 'required|in:low,medium,high,urgent',
        ]);

        try {
            Complaint::create([
                'student_id' => auth()->id(),
                'subject' => $this->complaintSubject,
                'message' => $this->complaintMessage,
                'priority' => $this->complaintPriority,
                'status' => 'pending',
            ]);

            $this->showToast('Complaint submitted successfully! We will review it shortly.', 'success');
            $this->closeComplaintModal();
        } catch (\Exception $e) {
            $this->showToast('Failed to submit complaint. Please try again.', 'error');
        }
    }

    public function showToast($message, $type = 'success')
    {
        $this->toastMessage = $message;
        $this->toastType = $type;
        $this->showToast = true;
    }

    public function hideToast()
    {
        $this->showToast = false;
    }
}; ?>
<main>
<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <!-- Welcome Header -->
        <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-zinc-900 dark:text-white">
                Welcome back, {{ auth()->user()->name }}! 👋
            </h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-2 text-sm sm:text-base">
                Student Dashboard • {{ auth()->user()->matric_no }}
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

            <!-- Pending Complaints Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Pending Complaints</p>
                        <p class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">{{ $pendingComplaints }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 hover:shadow-md transition-shadow sm:col-span-2 lg:col-span-1">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
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
                    <a href="{{ route('student.classes') }}" 
                       class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 px-4 rounded-lg font-medium transition-colors">
                        View My Classes
                    </a>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center">
                        Access your enrolled classes and mark attendance
                    </p>
                </div>
            </div>

            <!-- Support & Complaints -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-zinc-900 dark:text-white">Support & Complaints</h2>
                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <button wire:click="openComplaintModal"
                            class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-3 px-4 rounded-lg font-medium transition-colors">
                        Submit New Complaint
                    </button>
                    <a href="{{ route('student.complaints') }}" 
                       class="block w-full border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-center py-3 px-4 rounded-lg font-medium transition-colors">
                        View My Complaints
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity (if needed in future) -->
        <div class="mt-8 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg sm:text-xl font-semibold text-zinc-900 dark:text-white mb-4">Getting Started</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="flex items-start space-x-3 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">1</span>
                    </div>
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">View Your Classes</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Check your enrolled classes and schedules</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">2</span>
                    </div>
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Mark Attendance</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Use location-based attendance marking</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex-shrink-0 w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">3</span>
                    </div>
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Get Support</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Submit complaints or get help when needed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Complaint Icon -->
    <div class="fixed bottom-6 right-6 z-50">
        <button 
            wire:click="openComplaintModal"
            class="bg-red-500 hover:bg-red-600 text-white p-3 sm:p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-110 group"
            title="Report a Complaint"
        >
            <svg class="w-5 h-5 sm:w-6 sm:h-6 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </button>
    </div>

    <!-- Complaint Modal -->
    @if($showComplaintModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">Submit a Complaint</h3>
                    <button 
                        wire:click="closeComplaintModal"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Form -->
                <form wire:submit.prevent="submitComplaint" class="space-y-4">
                    <!-- Subject Field -->
                    <div>
                        <label for="complaintSubject" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="complaintSubject"
                            wire:model="complaintSubject"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-zinc-700 dark:text-white"
                            placeholder="Brief description of your complaint"
                            maxlength="255"
                        >
                        @error('complaintSubject') 
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Priority Field -->
                    <div>
                        <label for="complaintPriority" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Priority <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="complaintPriority"
                            wire:model="complaintPriority"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-zinc-700 dark:text-white"
                        >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        @error('complaintPriority') 
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Message Field -->
                    <div>
                        <label for="complaintMessage" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Message <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="complaintMessage"
                            wire:model="complaintMessage"
                            rows="4"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-zinc-700 dark:text-white resize-none"
                            placeholder="Describe your complaint in detail..."
                            maxlength="1000"
                        ></textarea>
                        <div class="flex justify-between items-center mt-1">
                            @error('complaintMessage') 
                                <span class="text-red-500 text-sm">{{ $message }}</span> 
                            @else
                                <span></span>
                            @enderror
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ strlen($complaintMessage) }}/1000
                            </span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3 pt-4">
                        <button 
                            type="button"
                            wire:click="closeComplaintModal"
                            class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-500 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                        >
                            Submit Complaint
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Toast Notification -->
    @if($showToast)
    <div class="fixed top-4 right-4 z-50 max-w-sm w-full">
        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    @if($toastType === 'success')
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    @endif
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $toastMessage }}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button wire:click="hideToast" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
          

    <!-- Floating Complaint Icon -->
    <div class="fixed bottom-6 right-6 z-50">
        <button 
            wire:click="openComplaintModal"
            class="bg-red-500 hover:bg-red-600 text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-110 group"
            title="Report a Complaint"
        >
            <svg class="w-6 h-6 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </button>
    </div>

    <!-- Complaint Modal -->
    @if($showComplaintModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">Submit a Complaint</h3>
                    <button 
                        wire:click="closeComplaintModal"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Form -->
                <form wire:submit.prevent="submitComplaint" class="space-y-4">
                    <!-- Subject Field -->
                    <div>
                        <label for="complaintSubject" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="complaintSubject"
                            wire:model="complaintSubject"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-zinc-700 dark:text-white"
                            placeholder="Brief description of your complaint"
                            maxlength="255"
                        >
                        @error('complaintSubject') 
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Priority Field -->
                    <div>
                        <label for="complaintPriority" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Priority <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="complaintPriority"
                            wire:model="complaintPriority"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-zinc-700 dark:text-white"
                        >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        @error('complaintPriority') 
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Message Field -->
                    <div>
                        <label for="complaintMessage" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Message <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="complaintMessage"
                            wire:model="complaintMessage"
                            rows="4"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-zinc-700 dark:text-white resize-none"
                            placeholder="Describe your complaint in detail..."
                            maxlength="1000"
                        ></textarea>
                        @error('complaintMessage') 
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                        @enderror
                        <div class="text-right text-sm text-zinc-500 mt-1">
                            {{ strlen($complaintMessage) }}/1000 characters
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end space-x-3 pt-4">
                        <button 
                            type="button"
                            wire:click="closeComplaintModal"
                            class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-500 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
                        >
                            Submit Complaint
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Toast Notification -->
    @if($showToast)
    <div class="fixed top-4 right-4 z-50 max-w-sm w-full">
        <div class="bg-white dark:bg-zinc-800 border-l-4 {{ $toastType === 'success' ? 'border-green-500' : 'border-red-500' }} rounded-lg shadow-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    @if($toastType === 'success')
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                        {{ $toastMessage }}
                    </p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button 
                        wire:click="hideToast"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-toast', () => {
                setTimeout(() => {
                    @this.hideToast();
                }, 5000);
            });
        });
    </script>
    @endif
</div>
</main>
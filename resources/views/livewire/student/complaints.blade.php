<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Complaint;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $selectedComplaint = null;
    public $showDetailModal = false;

    public function viewComplaint($complaintId)
    {
        $this->selectedComplaint = Complaint::with(['student', 'respondedBy'])->find($complaintId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedComplaint = null;
    }

    public function with()
    {
        return [
            'complaints' => Complaint::where('student_id', auth()->id())
                ->with(['respondedBy'])
                ->orderBy('created_at', 'desc')
                ->paginate(10)
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-zinc-900 dark:text-zinc-100">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
                        My Complaints
                    </h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-2">
                        View your submitted complaints and admin responses
                    </p>
                </div>

                <!-- Complaints List -->
                @if($complaints->count() > 0)
                    <div class="space-y-4">
                        @foreach($complaints as $complaint)
                            <div class="bg-zinc-50 dark:bg-zinc-700 rounded-lg p-6 border border-zinc-200 dark:border-zinc-600">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">
                                            {{ $complaint->subject }}
                                        </h3>
                                        <div class="flex items-center space-x-4 text-sm text-zinc-600 dark:text-zinc-400">
                                            <span>{{ $complaint->created_at->format('M d, Y h:i A') }}</span>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                                @if($complaint->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @elseif($complaint->status === 'in_review') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @elseif($complaint->status === 'resolved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @else bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200
                                                @endif">
                                                {{ ucfirst($complaint->status) }}
                                            </span>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                                @if($complaint->priority === 'low') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($complaint->priority === 'medium') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @elseif($complaint->priority === 'high') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @endif">
                                                {{ ucfirst($complaint->priority) }} Priority
                                            </span>
                                        </div>
                                    </div>
                                    <button 
                                        wire:click="viewComplaint({{ $complaint->id }})"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                                    >
                                        View Details
                                    </button>
                                </div>

                                <p class="text-zinc-700 dark:text-zinc-300 mb-4 line-clamp-3">
                                    {{ Str::limit($complaint->message, 200) }}
                                </p>

                                @if($complaint->admin_response)
                                    <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4">
                                        <div class="flex items-center mb-2">
                                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-green-800 dark:text-green-200">Admin Response</span>
                                        </div>
                                        <p class="text-green-700 dark:text-green-300 text-sm">
                                            {{ Str::limit($complaint->admin_response, 150) }}
                                        </p>
                                        @if($complaint->responded_at)
                                            <p class="text-green-600 dark:text-green-400 text-xs mt-2">
                                                Responded on {{ $complaint->responded_at->format('M d, Y h:i A') }}
                                                @if($complaint->respondedBy)
                                                    by {{ $complaint->respondedBy->name }}
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $complaints->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-zinc-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-2">No complaints submitted</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">You haven't submitted any complaints yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedComplaint)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">Complaint Details</h3>
                    <button 
                        wire:click="closeDetailModal"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Complaint Info -->
                <div class="space-y-6">
                    <div>
                        <h4 class="text-lg font-medium text-zinc-900 dark:text-white mb-2">{{ $selectedComplaint->subject }}</h4>
                        <div class="flex items-center space-x-4 text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                            <span>Submitted: {{ $selectedComplaint->created_at->format('M d, Y h:i A') }}</span>
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                @if($selectedComplaint->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($selectedComplaint->status === 'in_review') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif($selectedComplaint->status === 'resolved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200
                                @endif">
                                {{ ucfirst($selectedComplaint->status) }}
                            </span>
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                @if($selectedComplaint->priority === 'low') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($selectedComplaint->priority === 'medium') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($selectedComplaint->priority === 'high') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ ucfirst($selectedComplaint->priority) }} Priority
                            </span>
                        </div>
                    </div>

                    <div>
                        <h5 class="font-medium text-zinc-900 dark:text-white mb-2">Message</h5>
                        <p class="text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $selectedComplaint->message }}</p>
                    </div>

                    @if($selectedComplaint->admin_response)
                        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-medium text-green-800 dark:text-green-200">Admin Response</span>
                            </div>
                            <p class="text-green-700 dark:text-green-300 whitespace-pre-wrap mb-3">{{ $selectedComplaint->admin_response }}</p>
                            @if($selectedComplaint->responded_at)
                                <p class="text-green-600 dark:text-green-400 text-sm">
                                    Responded on {{ $selectedComplaint->responded_at->format('M d, Y h:i A') }}
                                    @if($selectedComplaint->respondedBy)
                                        by {{ $selectedComplaint->respondedBy->name }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-yellow-800 dark:text-yellow-200">Waiting for admin response...</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Actions -->
                <div class="flex justify-end pt-6">
                    <button 
                        wire:click="closeDetailModal"
                        class="px-4 py-2 bg-zinc-500 hover:bg-zinc-600 text-white rounded-lg transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
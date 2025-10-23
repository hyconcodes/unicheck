<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Complaint;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $selectedComplaint = null;
    public $showResponseModal = false;
    public $adminResponse = '';
    public $newStatus = '';
    public $filterStatus = 'all';
    public $filterPriority = 'all';
    public $showToast = false;
    public $toastMessage = '';
    public $toastType = 'success';

    public function mount()
    {
        $this->filterStatus = 'pending';
    }

    public function respondToComplaint($complaintId)
    {
        $this->selectedComplaint = Complaint::with(['student'])->find($complaintId);
        $this->adminResponse = $this->selectedComplaint->admin_response ?? '';
        $this->newStatus = $this->selectedComplaint->status;
        $this->showResponseModal = true;
    }

    public function closeResponseModal()
    {
        $this->showResponseModal = false;
        $this->selectedComplaint = null;
        $this->adminResponse = '';
        $this->newStatus = '';
    }

    public function submitResponse()
    {
        $this->validate([
            'adminResponse' => 'required|string|max:2000',
            'newStatus' => 'required|in:pending,in_review,resolved,closed',
        ]);

        try {
            $this->selectedComplaint->update([
                'admin_response' => $this->adminResponse,
                'status' => $this->newStatus,
                'responded_by' => auth()->id(),
                'responded_at' => now(),
            ]);

            $this->showToast('Response submitted successfully!', 'success');
            $this->closeResponseModal();
        } catch (\Exception $e) {
            $this->showToast('Failed to submit response. Please try again.', 'error');
        }
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterPriority()
    {
        $this->resetPage();
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

    public function with()
    {
        $query = Complaint::with(['student', 'respondedBy']);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterPriority !== 'all') {
            $query->where('priority', $this->filterPriority);
        }

        return [
            'complaints' => $query->orderBy('created_at', 'desc')->paginate(10),
            'totalComplaints' => Complaint::count(),
            'pendingComplaints' => Complaint::where('status', 'pending')->count(),
            'resolvedComplaints' => Complaint::where('status', 'resolved')->count(),
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
                        Complaint Management
                    </h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-2">
                        Manage and respond to student complaints
                    </p>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-blue-50 dark:bg-blue-900 p-6 rounded-lg border border-blue-200 dark:border-blue-700">
                        <div class="flex items-center">
                            <div class="bg-blue-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">Total Complaints</h3>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $totalComplaints }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900 p-6 rounded-lg border border-yellow-200 dark:border-yellow-700">
                        <div class="flex items-center">
                            <div class="bg-yellow-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Pending</h3>
                                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ $pendingComplaints }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900 p-6 rounded-lg border border-green-200 dark:border-green-700">
                        <div class="flex items-center">
                            <div class="bg-green-500 p-3 rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Resolved</h3>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $resolvedComplaints }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 flex flex-wrap gap-4">
                    <div>
                        <label for="filterStatus" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Filter by Status
                        </label>
                        <select 
                            id="filterStatus"
                            wire:model.live="filterStatus"
                            class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-700 dark:text-white"
                        >
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_review">In Review</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div>
                        <label for="filterPriority" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Filter by Priority
                        </label>
                        <select 
                            id="filterPriority"
                            wire:model.live="filterPriority"
                            class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-700 dark:text-white"
                        >
                            <option value="all">All Priorities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
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
                                        <div class="flex items-center space-x-4 text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                                            <span>By: {{ $complaint->student->name }} ({{ $complaint->student->matric_no }})</span>
                                            <span>{{ $complaint->created_at->format('M d, Y h:i A') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-4 text-sm">
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
                                        wire:click="respondToComplaint({{ $complaint->id }})"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                                    >
                                        {{ $complaint->admin_response ? 'Update Response' : 'Respond' }}
                                    </button>
                                </div>

                                <p class="text-zinc-700 dark:text-zinc-300 mb-4">
                                    {{ Str::limit($complaint->message, 200) }}
                                </p>

                                @if($complaint->admin_response)
                                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                        <div class="flex items-center mb-2">
                                            <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-blue-800 dark:text-blue-200">Your Response</span>
                                        </div>
                                        <p class="text-blue-700 dark:text-blue-300 text-sm mb-2">
                                            {{ Str::limit($complaint->admin_response, 150) }}
                                        </p>
                                        @if($complaint->responded_at)
                                            <p class="text-blue-600 dark:text-blue-400 text-xs">
                                                Responded on {{ $complaint->responded_at->format('M d, Y h:i A') }}
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
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-2">No complaints found</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">No complaints match your current filters.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    @if($showResponseModal && $selectedComplaint)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">Respond to Complaint</h3>
                    <button 
                        wire:click="closeResponseModal"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Complaint Details -->
                <div class="mb-6 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                    <h4 class="font-medium text-zinc-900 dark:text-white mb-2">{{ $selectedComplaint->subject }}</h4>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                        From: {{ $selectedComplaint->student->name }} ({{ $selectedComplaint->student->matric_no }})
                    </p>
                    <p class="text-zinc-700 dark:text-zinc-300 text-sm">{{ $selectedComplaint->message }}</p>
                </div>

                <!-- Response Form -->
                <form wire:submit.prevent="submitResponse" class="space-y-4">
                    <!-- Status Field -->
                    <div>
                        <label for="newStatus" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="newStatus"
                            wire:model="newStatus"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-700 dark:text-white"
                        >
                            <option value="pending">Pending</option>
                            <option value="in_review">In Review</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        @error('newStatus') 
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Response Field -->
                    <div>
                        <label for="adminResponse" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Response <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="adminResponse"
                            wire:model="adminResponse"
                            rows="6"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-700 dark:text-white resize-none"
                            placeholder="Provide your response to the student's complaint..."
                            maxlength="2000"
                        ></textarea>
                        @error('adminResponse') 
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                        @enderror
                        <div class="text-right text-sm text-zinc-500 mt-1">
                            {{ strlen($adminResponse) }}/2000 characters
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end space-x-3 pt-4">
                        <button 
                            type="button"
                            wire:click="closeResponseModal"
                            class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-500 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
                        >
                            Submit Response
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
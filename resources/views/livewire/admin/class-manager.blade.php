<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ClassModel;
use App\Models\Department;
use App\Models\User;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app', ['title' => 'Class Manager'])] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $departmentFilter = 'all';
    public string $levelFilter = 'all';

    /**
     * Handle component mounting and check for download requests
     */
    public function mount()
    {
        if (request()->has('download')) {
            return $this->downloadAttendance(request('download'));
        }
    }

    /**
     * Reset pagination when filters change
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLevelFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Get filtered classes with pagination
     */
    public function getClasses()
    {
        $query = ClassModel::with(['lecturer', 'department', 'attendances'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('lecturer', function ($lecturerQuery) {
                            $lecturerQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->departmentFilter !== 'all', function ($query) {
                $query->where('department_id', $this->departmentFilter);
            })
            ->when($this->levelFilter !== 'all', function ($query) {
                $query->where('level', $this->levelFilter);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate(10);
    }

    /**
     * Get all departments for filter
     */
    public function getDepartments()
    {
        return Department::orderBy('name')->get();
    }

    /**
     * Get available levels for filter
     */
    public function getLevels()
    {
        return [100, 200, 300, 400, 500];
    }

    /**
     * Get class statistics
     */
    public function getStats()
    {
        return [
            'total' => ClassModel::count(),
            'active' => ClassModel::where('status', 'active')->count(),
            'paused' => ClassModel::where('status', 'paused')->count(),
            'ended' => ClassModel::where('status', 'ended')->count(),
            'total_attendances' => \App\Models\ClassAttendance::count(),
        ];
    }

    /**
     * Download attendance PDF for a specific class
     */
    public function downloadAttendance($classId)
    {
        $class = ClassModel::with(['lecturer', 'department', 'attendances.student'])->findOrFail($classId);

        // Get attendance data
        $attendances = $class->attendances()->with('student')->get();
        $totalStudents = User::where('role', 'student')->where('department_id', $class->department_id)->where('level', $class->level)->count();

        $attendanceRate = $totalStudents > 0 ? round(($attendances->count() / $totalStudents) * 100, 2) : 0;

        $pdf = \PDF::loadView('pdf.attendance-report', [
            'class' => $class,
            'attendances' => $attendances,
            'totalStudents' => $totalStudents,
            'attendanceRate' => $attendanceRate,
        ]);

        $filename = Str::slug($class->title) . '-attendance-' . now()->format('Y-m-d') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public function with(): array
    {
        return [
            'classes' => $this->getClasses(),
            'departments' => $this->getDepartments(),
            'levels' => $this->getLevels(),
            'stats' => $this->getStats(),
        ];
    }
}; ?>
<main>

    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
        <div class="mb-6 sm:mb-8">
            <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100">Class Manager</h1>
            <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 mt-1">Manage all classes across the system
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:icon.presentation-chart-bar class="size-8 text-blue-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Classes</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:icon.play class="size-8 text-green-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $stats['active'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:icon.pause class="size-8 text-yellow-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Paused</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $stats['paused'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:icon.stop class="size-8 text-red-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Ended</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $stats['ended'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:icon.users class="size-8 text-purple-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Attendances</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $stats['total_attendances'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <flux:field>
                        <flux:label>Search</flux:label>
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search classes, lecturers..."
                            icon="magnifying-glass" />
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model.live="statusFilter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="ended">Ended</option>
                        </flux:select>
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Department</flux:label>
                        <flux:select wire:model.live="departmentFilter">
                            <option value="all">All Departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Level</flux:label>
                        <flux:select wire:model.live="levelFilter">
                            <option value="all">All Levels</option>
                            @foreach ($levels as $level)
                                <option value="{{ $level }}">{{ $level }} Level</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <div class="flex items-end">
                    <button
                        wire:click="$set('search', ''); $set('statusFilter', 'all'); $set('departmentFilter', 'all'); $set('levelFilter', 'all')"
                        class="w-full px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Classes Table -->
        <div
            class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Class</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Lecturer</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Department</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Level</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Attendees</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Created</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($classes as $class)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $class->title }}</div>
                                        @if ($class->description)
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate max-w-xs">
                                                {{ $class->description }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">{{ $class->lecturer->name }}
                                    </div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $class->lecturer->email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $class->department->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $class->level }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:badge
                                        :color="$class->status === 'active' ? 'green' : ($class->status === 'paused' ? 'yellow' : 'red')"
                                        size="sm">
                                        {{ ucfirst($class->status) }}
                                    </flux:badge>
                                    @if ($class->attendance_open && $class->status === 'active')
                                        <flux:badge color="blue" size="sm" class="ml-1">Attendance Open
                                        </flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $class->attendances->count() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $class->created_at->format('M j, Y') }}
                                    <div class="text-xs">{{ $class->created_at->format('g:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        onclick="showClassDetails({{ json_encode($class->load(['lecturer', 'department', 'attendances'])) }})"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <flux:icon.presentation-chart-bar
                                        class="size-16 text-zinc-400 dark:text-zinc-600 mx-auto mb-4" />
                                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No Classes
                                        Found</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        @if ($search || $statusFilter !== 'all' || $departmentFilter !== 'all' || $levelFilter !== 'all')
                                            No classes match your current filters.
                                        @else
                                            No classes have been created yet.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($classes->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $classes->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Class Details Modal -->
    <div id="classDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" id="modalTitle">Class
                            Details</h3>
                        <button onclick="closeClassDetails()"
                            class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="modalContent">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showClassDetails(classData) {
            const modal = document.getElementById('classDetailsModal');
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');

            title.textContent = classData.title;

            const statusColor = classData.status === 'active' ? 'green' : (classData.status === 'paused' ? 'yellow' :
            'red');
            const attendanceStatus = classData.attendance_open ? 'Open' : 'Closed';
            const attendanceColor = classData.attendance_open ? 'blue' : 'red';

            content.innerHTML = `
            <div class="space-y-6">
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-${statusColor}-100 text-${statusColor}-800">
                        ${classData.status.charAt(0).toUpperCase() + classData.status.slice(1)}
                    </span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-${attendanceColor}-100 text-${attendanceColor}-800">
                        Attendance ${attendanceStatus}
                    </span>
                </div>
                
                ${classData.description ? `<p class="text-zinc-600 dark:text-zinc-400">${classData.description}</p>` : ''}
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">Class Information</h4>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Lecturer:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">${classData.lecturer.name}</span>
                            </div>
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Email:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">${classData.lecturer.email}</span>
                            </div>
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Department:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">${classData.department.name}</span>
                            </div>
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Level:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">${classData.level}</span>
                            </div>
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Radius:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">${classData.radius} meters</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">Schedule & Location</h4>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Started:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">${new Date(classData.starts_at).toLocaleString()}</span>
                            </div>
                            ${classData.ends_at ? `
                                <div>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">Ends:</span>
                                    <span class="text-zinc-600 dark:text-zinc-400">${new Date(classData.ends_at).toLocaleString()}</span>
                                </div>
                                ` : ''}
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Location:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">${classData.formatted_coordinates}</span>
                            </div>
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Total Attendees:</span>
                                <span class="text-zinc-600 dark:text-zinc-400">${classData.attendances.length}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${classData.attendances.length > 0 ? `
                    <div class="space-y-4">
                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">Recent Attendees</h4>
                        <div class="max-h-40 overflow-y-auto">
                            <div class="space-y-2">
                                ${classData.attendances.slice(0, 10).map(attendance => `
                                <div class="flex justify-between items-center text-sm p-2 bg-zinc-50 dark:bg-zinc-700 rounded">
                                    <span class="font-medium">${attendance.full_name}</span>
                                    <span class="text-zinc-500 dark:text-zinc-400">${attendance.matric_number}</span>
                                    <span class="text-xs text-zinc-400">${new Date(attendance.marked_at).toLocaleString()}</span>
                                </div>
                            `).join('')}
                                ${classData.attendances.length > 10 ? `
                                <div class="text-center text-sm text-zinc-500 dark:text-zinc-400 py-2">
                                    And ${classData.attendances.length - 10} more...
                                </div>
                            ` : ''}
                            </div>
                        </div>
                    </div>
                    ` : ''}
                
                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <button 
                        onclick="downloadAttendancePdf(${classData.id})"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Download PDF</span>
                    </button>
                </div>
            </div>
        `;

            modal.classList.remove('hidden');
        }

        function closeClassDetails() {
            document.getElementById('classDetailsModal').classList.add('hidden');
        }

        function downloadAttendancePdf(classId) {
        window.location.href = `{{ route('superadmin.class-manager') }}?download=${classId}`;
    }

        // Close modal when clicking outside
        document.getElementById('classDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeClassDetails();
            }
        });
    </script>
</main>

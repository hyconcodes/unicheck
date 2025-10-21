<?php

use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Location Manager'])] class extends Component {
    use WithPagination;

    public string $latitude = '';
    public string $longitude = '';
    public string $building_block_name = '';
    public string $description = '';
    public bool $capturing = false;
    public bool $location_captured = false;
    public string $error_message = '';
    
    // Delete confirmation modal properties
    public bool $showDeleteModal = false;
    public ?int $locationToDelete = null;

    /**
     * Capture current location using browser geolocation API
     */
    public function captureLocation(): void
    {
        // Check permission
        if (!Auth::user()->can('can.capture.locations')) {
            $this->dispatch('show-toast', message: 'You do not have permission to capture locations.', type: 'error');
            return;
        }

        $this->capturing = true;
        $this->error_message = '';
        $this->location_captured = false;
        
        // This will trigger JavaScript geolocation
        $this->dispatch('capture-location');
    }

    /**
     * Handle location data received from JavaScript
     */
    public function locationReceived($latitude, $longitude): void
    {
        $this->latitude = number_format((float)$latitude, 8, '.', '');
        $this->longitude = number_format((float)$longitude, 8, '.', '');
        $this->capturing = false;
        $this->location_captured = true;
        $this->error_message = '';
    }

    /**
     * Handle location error from JavaScript
     */
    public function locationError($error): void
    {
        $this->capturing = false;
        $this->location_captured = false;
        $this->error_message = $error;
    }

    /**
     * Save the captured location to database
     */
    public function saveLocation(): void
    {
        // Check permission
        if (!Auth::user()->can('can.create.locations')) {
            $this->dispatch('show-toast', message: 'You do not have permission to create locations.', type: 'error');
            return;
        }

        $this->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'building_block_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Location::create([
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'building_block_name' => $this->building_block_name,
            'location_type' => 'building',
            'description' => $this->description,
            'created_by' => Auth::id(),
        ]);

        // Reset form
        $this->reset(['latitude', 'longitude', 'building_block_name', 'description', 'location_captured']);

        // Dispatch toast notification
        $this->dispatch('show-toast', message: 'Location saved successfully!', type: 'success');
    }

    /**
     * Clear captured location
     */
    public function clearLocation(): void
    {
        $this->reset(['latitude', 'longitude', 'location_captured', 'error_message']);
    }

    /**
     * Show delete confirmation modal
     */
    public function confirmDelete($locationId): void
    {
        // Check permission
        if (!Auth::user()->can('can.delete.locations')) {
            $this->dispatch('show-toast', message: 'You do not have permission to delete locations.', type: 'error');
            return;
        }

        $this->locationToDelete = $locationId;
        $this->showDeleteModal = true;
    }

    /**
     * Cancel delete operation
     */
    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->locationToDelete = null;
    }

    /**
     * Delete the location
     */
    public function deleteLocation(): void
    {
        // Double-check permission (additional security)
        if (!Auth::user()->can('can.delete.locations')) {
            $this->dispatch('show-toast', message: 'You do not have permission to delete locations.', type: 'error');
            $this->cancelDelete();
            return;
        }

        if ($this->locationToDelete) {
            $location = Location::find($this->locationToDelete);
            
            if ($location) {
                $locationName = $location->display_name;
                $location->delete();
                
                // Dispatch toast notification
                $this->dispatch('show-toast', message: "Location '{$locationName}' deleted successfully!", type: 'success');
            } else {
                $this->dispatch('show-toast', message: 'Location not found!', type: 'error');
            }
        }

        $this->cancelDelete();
    }

    /**
     * Get all locations with pagination
     */
    public function with(): array
    {
        return [
            'locations' => Location::with('creator')
                ->orderBy('created_at', 'desc')
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Location Manager</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Capture and manage geographical coordinates for attendance tracking</p>
        </div>
    </div>

    <!-- Location Capture Card -->
    @can('can.view.locations')
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Capture New Location</h2>
            
            <!-- Instructions -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <flux:icon name="information-circle" class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-medium mb-1">Instructions:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Click "Capture Current Location" to get your GPS coordinates</li>
                            <li>Allow location access when prompted by your browser</li>
                            <li>Provide the building/block name and optional description</li>
                            <li>Save the location for future attendance tracking</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Location Capture Button -->
            @can('can.capture.locations')
            <div class="mb-6">
                <flux:button 
                    wire:click="captureLocation" 
                    :disabled="$capturing"
                    variant="primary"
                    class="w-full sm:w-auto"
                >
                    @if($capturing)
                        <flux:icon name="arrow-path" class="animate-spin h-4 w-4 mr-2" />
                        Capturing Location...
                    @else
                        <flux:icon name="map-pin" class="h-4 w-4 mr-2" />
                        Capture Current Location
                    @endif
                </flux:button>
            </div>
            @else
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-start">
                    <flux:icon name="exclamation-triangle" class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div class="text-sm text-yellow-800 dark:text-yellow-200">
                        <p class="font-medium">Permission Required:</p>
                        <p>You do not have permission to capture locations. Please contact your administrator.</p>
                    </div>
                </div>
            </div>
            @endcan

            <!-- Error Message -->
            @if($error_message)
                <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <flux:icon name="exclamation-triangle" class="h-5 w-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div class="text-sm text-red-800 dark:text-red-200">
                            <p class="font-medium">Location Error:</p>
                            <p>{{ $error_message }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Captured Coordinates Display -->
            @if($location_captured)
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start">
                            <flux:icon name="check-circle" class="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5 mr-3 flex-shrink-0" />
                            <div class="text-sm text-green-800 dark:text-green-200">
                                <p class="font-medium mb-1">Location Captured Successfully!</p>
                                <p><strong>Latitude:</strong> {{ $latitude }}</p>
                                <p><strong>Longitude:</strong> {{ $longitude }}</p>
                            </div>
                        </div>
                        <flux:button wire:click="clearLocation" variant="ghost" size="sm">
                            <flux:icon name="x-mark" class="h-4 w-4" />
                        </flux:button>
                    </div>
                </div>
            @endif

            <!-- Location Form -->
            @if($location_captured)
                @can('can.create.locations')
                <form wire:submit="saveLocation" class="space-y-4">
                    <!-- Building/Block Name -->
                    <flux:input
                        wire:model="building_block_name"
                        label="Building/Block Name"
                        placeholder="e.g., Science Block, Admin Building, etc."
                        required
                    />

                    <!-- Description -->
                    <flux:textarea
                        wire:model="description"
                        label="Description (Optional)"
                        placeholder="Additional details about this location..."
                        rows="3"
                    />

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">
                            <flux:icon name="check" class="h-4 w-4 mr-2" />
                            Save Location
                        </flux:button>
                    </div>
                </form>
                @else
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <flux:icon name="exclamation-triangle" class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div class="text-sm text-yellow-800 dark:text-yellow-200">
                            <p class="font-medium">Permission Required:</p>
                            <p>You do not have permission to save locations. Please contact your administrator.</p>
                        </div>
                    </div>
                </div>
                @endcan
            @endif
        </div>
    </div>
    @endcan

    <!-- Saved Locations -->
    @can('can.view.locations')
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Saved Locations</h2>
            
            @if($locations->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">Name</th>
                                <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">Type</th>
                                <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">Coordinates</th>
                                <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">Created By</th>
                                <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">Date</th>
                                @can('can.delete.locations')
                                <th class="text-left py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($locations as $location)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="py-3 px-4">
                                        <div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $location->display_name }}
                                            </div>
                                            @if($location->description)
                                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ Str::limit($location->description, 50) }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <flux:badge 
                                            :color="$location->location_type === 'class' ? 'blue' : 'green'"
                                            size="sm"
                                        >
                                            {{ ucfirst($location->location_type) }}
                                        </flux:badge>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm font-mono text-zinc-600 dark:text-zinc-400">
                                            {{ $location->formatted_coordinates }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $location->creator->name }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $location->created_at->format('M j, Y') }}
                                        </div>
                                    </td>
                                    @can('can.delete.locations')
                                    <td class="py-3 px-4">
                                        <flux:button 
                                            wire:click="confirmDelete({{ $location->id }})"
                                            variant="danger"
                                            size="sm"
                                        >
                                            <flux:icon name="trash" class="h-4 w-4" />
                                        </flux:button>
                                    </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $locations->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <flux:icon name="map-pin" class="h-12 w-12 text-zinc-400 mx-auto mb-4" />
                    <p class="text-zinc-500 dark:text-zinc-400">No locations saved yet.</p>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">Capture your first location to get started.</p>
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="p-6">
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-start">
                    <flux:icon name="exclamation-triangle" class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div class="text-sm text-yellow-800 dark:text-yellow-200">
                        <p class="font-medium">Permission Required:</p>
                        <p>You do not have permission to view locations. Please contact your administrator.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <flux:modal wire:model="showDeleteModal" focusable class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Are you sure you want to delete this location?') }}</flux:heading>
                    
                    <flux:subheading>
                        {{ __('Once this location is deleted, all of its data will be permanently removed. This action cannot be undone.') }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:button variant="filled" wire:click="cancelDelete">{{ __('Cancel') }}</flux:button>

                    <flux:button variant="danger" wire:click="deleteLocation" data-test="confirm-delete-location-button">
                        {{ __('Delete Location') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>

@script
<script>
    // Listen for toast events from Livewire
    $wire.on('show-toast', (event) => {
        // Find the toast component and update it
        const toastElement = document.querySelector('[x-data*="show:"]');
        if (toastElement) {
            // Update Alpine.js data
            Alpine.store('toast', {
                show: true,
                message: event.message,
                type: event.type
            });
            
            // Trigger the toast display
            toastElement._x_dataStack[0].show = true;
            toastElement._x_dataStack[0].message = event.message;
            toastElement._x_dataStack[0].type = event.type;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (toastElement._x_dataStack[0]) {
                    toastElement._x_dataStack[0].show = false;
                }
            }, 5000);
        }
    });

    $wire.on('capture-location', () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    $wire.locationReceived(
                        position.coords.latitude,
                        position.coords.longitude
                    );
                },
                function(error) {
                    let errorMessage = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = "Location access denied by user. Please allow location access and try again.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "Location information is unavailable. Please check your device settings.";
                            break;
                        case error.TIMEOUT:
                            errorMessage = "Location request timed out. Please try again.";
                            break;
                        default:
                            errorMessage = "An unknown error occurred while retrieving location.";
                            break;
                    }
                    
                    // Use toast notification for errors
                    $wire.dispatch('show-toast', { message: errorMessage, type: 'error' });
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            $wire.dispatch('show-toast', { message: "Geolocation is not supported by this browser.", type: 'error' });
        }
    });
</script>
@endscript
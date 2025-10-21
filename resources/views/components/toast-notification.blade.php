@props(['type' => 'success', 'message' => '', 'show' => false])

<div 
    x-data="{ 
        show: false,
        message: '',
        type: 'success',
        timeoutId: null,
        init() {
            // Listen for Livewire toast events
            this.$wire.on('show-toast', (event) => {
                this.showToast(event.message, event.type);
            });
            
            // Listen for custom browser events
            window.addEventListener('show-toast', (event) => {
                this.showToast(event.detail.message, event.detail.type);
            });
        },
        showToast(message, type = 'success') {
            this.message = message;
            this.type = type;
            this.show = true;
            
            // Clear existing timeout
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
            }
            
            // Auto-hide after 5 seconds
            this.timeoutId = setTimeout(() => {
                this.show = false;
            }, 5000);
        },
        hideToast() {
            this.show = false;
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
            }
        }
    }"
    x-show="show"
    x-transition:enter="transform ease-out duration-300 transition"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed top-4 right-4 z-50 max-w-sm w-full"
    style="display: none;"
>
    <div class="bg-white rounded-lg shadow-lg border-l-4 p-4" 
         :class="{
             'border-green-500': type === 'success',
             'border-red-500': type === 'error',
             'border-yellow-500': type === 'warning',
             'border-blue-500': type === 'info'
         }">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <!-- Success Icon -->
                <svg x-show="type === 'success'" class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                
                <!-- Error Icon -->
                <svg x-show="type === 'error'" class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                
                <!-- Warning Icon -->
                <svg x-show="type === 'warning'" class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                
                <!-- Info Icon -->
                <svg x-show="type === 'info'" class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-medium" 
                   :class="{
                       'text-green-800': type === 'success',
                       'text-red-800': type === 'error',
                       'text-yellow-800': type === 'warning',
                       'text-blue-800': type === 'info'
                   }"
                   x-text="message">
                </p>
            </div>
            
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="hideToast()" class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Global toast function for JavaScript usage
    window.showToast = function(message, type = 'success') {
        // Dispatch a custom event that Livewire components can listen to
        window.dispatchEvent(new CustomEvent('show-toast', {
            detail: { message, type }
        }));
    };
</script>
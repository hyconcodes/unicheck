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
        },
        getIcon() {
            return {
                'success': '🎉',
                'error': '😱',
                'warning': '⚠️',
                'info': 'ℹ️'
            }[this.type] || '🎉';
        },
        getColors() {
            return {
                'success': 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300',
                'error': 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300',
                'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300',
                'info': 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300'
            }[this.type] || 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300';
        }
    }"
    x-show="show"
    x-transition:enter="transform ease-out duration-300 transition"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed top-4 right-4 z-50 max-w-sm w-full shadow-lg rounded-lg border-2 p-4"
    :class="getColors()"
    style="display: none;"
>
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <span class="text-2xl" x-text="getIcon()"></span>
        </div>
        <div class="ml-3 w-0 flex-1">
            <p class="text-sm font-medium" x-text="message"></p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
            <button @click="hideToast()" class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
    // Fun messages for different scenarios
    window.toastMessages = {
        attendance: {
            success: [
                "🎯 Boom! Attendance marked like a boss! 💪",
                "✨ You're officially here! Time to shine! ⭐",
                "🚀 Attendance locked and loaded! Ready for action! 🎪",
                "🎉 Present and accounted for! You're on fire! 🔥",
                "💫 Attendance captured! You're a superstar! 🌟"
            ],
            error: [
                "🤔 Oops! Something went wonky with your attendance! 🙈",
                "😅 Houston, we have a problem! Attendance failed! 🚀",
                "🎭 Plot twist! Your attendance didn't make it through! 🎪",
                "🤖 Beep boop! Attendance system had a hiccup! 🔧",
                "🎲 Dice rolled badly! Attendance marking failed! 🎯"
            ]
        },
        location: {
            error: [
                "📍 GPS is playing hide and seek! Can't find you! 🕵️",
                "🗺️ Your location is more mysterious than Bermuda Triangle! 🌊",
                "🧭 Compass is spinning! Location detection failed! 🌪️",
                "🛰️ Satellites are on coffee break! Try again! ☕",
                "📡 Location signal went on vacation! Come back later! 🏖️"
            ]
        },
        twoFactor: {
            success: [
                "🔐 2FA verified! You're more secure than Fort Knox! 🏰",
                "🛡️ Identity confirmed! Welcome back, secret agent! 🕵️",
                "🎯 2FA bullseye! You're authenticated and awesome! 🏹",
                "✅ Double-checked and approved! You're legit! 💎",
                "🔑 Access granted! The vault is yours! 💰"
            ],
            error: [
                "🤨 2FA code said 'nope'! Try again, detective! 🔍",
                "🎭 Wrong code! Even Sherlock would be puzzled! 🕵️",
                "🎪 2FA circus act failed! Try another trick! 🤹",
                "🎲 Code didn't roll right! Shake and try again! 🎯",
                "🔐 Access denied! The vault remains locked! 🚪"
            ]
        },
        security: {
            error: [
                "🚨 Hold up! You can't mark attendance for someone else! 👮",
                "🕵️ Nice try, but we caught you red-handed! 🔍",
                "🎭 Identity theft is not a joke! Stay in your lane! 🛣️",
                "🚫 Nope! One person, one attendance! Rules are rules! 📏",
                "🤖 Security bot activated! Unauthorized action blocked! 🛡️"
            ]
        }
    };

    // Helper function to get random message
    window.getRandomToastMessage = function(category, type) {
        const messages = window.toastMessages[category]?.[type] || [];
        return messages[Math.floor(Math.random() * messages.length)] || 'Something happened!';
    };

    // Global toast function for JavaScript usage
    window.showToast = function(message, type = 'success') {
        // Dispatch a custom event that Livewire components can listen to
        window.dispatchEvent(new CustomEvent('show-toast', {
            detail: { message, type }
        }));
    };

    // Convenience functions for fun messages
    window.showAttendanceSuccess = function() {
        const message = window.getRandomToastMessage('attendance', 'success');
        window.showToast(message, 'success');
    };

    window.showAttendanceError = function() {
        const message = window.getRandomToastMessage('attendance', 'error');
        window.showToast(message, 'error');
    };

    window.showLocationError = function() {
        const message = window.getRandomToastMessage('location', 'error');
        window.showToast(message, 'error');
    };

    window.show2FASuccess = function() {
        const message = window.getRandomToastMessage('twoFactor', 'success');
        window.showToast(message, 'success');
    };

    window.show2FAError = function() {
        const message = window.getRandomToastMessage('twoFactor', 'error');
        window.showToast(message, 'error');
    };

    window.showSecurityError = function() {
        const message = window.getRandomToastMessage('security', 'error');
        window.showToast(message, 'error');
    };
</script>
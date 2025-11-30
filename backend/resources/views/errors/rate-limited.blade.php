@extends('layouts.guest')

@section('title', 'Rate Limited')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-lg sm:rounded-lg sm:px-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Too Many Attempts</h2>
                <p class="mt-2 text-gray-600">
                    You've made too many requests in a short period of time.
                </p>
            </div>

            <!-- Rate Limit Details -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Security Protection Active</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ $message ?? 'Too many requests have been made from your location.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wait Time Information -->
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium text-blue-800">
                            Please wait {{ floor($retry_after / 60) }} minutes before trying again
                        </span>
                    </div>
                </div>

                <!-- Countdown Timer -->
                <div class="text-center">
                    <div id="countdown" class="text-2xl font-mono text-gray-900 mb-2">
                        <span id="minutes">{{ floor($retry_after / 60) }}</span>:<span id="seconds">{{ str_pad($retry_after % 60, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <p class="text-sm text-gray-600">Time remaining</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 space-y-3">
                <button 
                    onclick="location.reload()" 
                    id="retry-button"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled
                >
                    <span id="retry-text">Retry in <span id="retry-countdown">{{ floor($retry_after / 60) }}m {{ $retry_after % 60 }}s</span></span>
                </button>

                <a 
                    href="{{ route('dashboard') }}" 
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Return to Dashboard
                </a>
            </div>

            <!-- Help Section -->
            <div class="mt-6 text-center">
                <details class="text-left">
                    <summary class="text-sm text-gray-600 cursor-pointer hover:text-gray-900">
                        Why am I seeing this?
                    </summary>
                    <div class="mt-3 text-sm text-gray-600 space-y-2">
                        <p>This security measure helps protect against:</p>
                        <ul class="list-disc list-inside ml-4 space-y-1">
                            <li>Brute force attacks</li>
                            <li>Automated bot attempts</li>
                            <li>Unauthorized access attempts</li>
                        </ul>
                        <p class="mt-3">
                            If you believe this is an error, please contact your system administrator.
                        </p>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let timeRemaining = {{ $retry_after }};
    const minutesElement = document.getElementById('minutes');
    const secondsElement = document.getElementById('seconds');
    const retryButton = document.getElementById('retry-button');
    const retryText = document.getElementById('retry-text');
    const retryCountdown = document.getElementById('retry-countdown');

    function updateDisplay() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        
        minutesElement.textContent = minutes;
        secondsElement.textContent = seconds.toString().padStart(2, '0');
        
        if (timeRemaining > 0) {
            retryCountdown.textContent = `${minutes}m ${seconds}s`;
        } else {
            retryText.textContent = 'Retry Now';
            retryButton.disabled = false;
            retryButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    const countdown = setInterval(() => {
        timeRemaining--;
        updateDisplay();
        
        if (timeRemaining < 0) {
            clearInterval(countdown);
        }
    }, 1000);

    updateDisplay();
});
</script>
@endsection
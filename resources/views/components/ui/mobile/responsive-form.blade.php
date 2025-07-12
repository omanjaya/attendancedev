@props([
    'title' => null,
    'subtitle' => null,
    'action' => '#',
    'method' => 'POST',
    'multipart' => false,
    'spacing' => 'normal', // tight, normal, relaxed
    'submitText' => 'Submit',
    'cancelUrl' => null,
    'loading' => false
])

@php
    $spacingClasses = [
        'tight' => 'space-y-4',
        'normal' => 'space-y-6',
        'relaxed' => 'space-y-8'
    ];
    
    $spaceClass = $spacingClasses[$spacing] ?? $spacingClasses['normal'];
@endphp

<div class="w-full max-w-lg mx-auto">
    <!-- Form Header -->
    @if($title || $subtitle)
        <div class="mb-6 sm:mb-8 text-center">
            @if($title)
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $title }}
                </h1>
            @endif
            @if($subtitle)
                <p class="mt-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg">
        <form action="{{ $action }}" 
              method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}" 
              @if($multipart) enctype="multipart/form-data" @endif
              class="p-4 sm:p-6 {{ $spaceClass }}"
              x-data="{ loading: {{ $loading ? 'true' : 'false' }} }"
              @submit="loading = true">
            
            @if(strtoupper($method) !== 'GET' && strtoupper($method) !== 'POST')
                @method($method)
            @endif
            
            @if(strtoupper($method) !== 'GET')
                @csrf
            @endif

            <!-- Loading Overlay -->
            <div x-show="loading" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-2 border-emerald-500 border-t-transparent"></div>
                    <span class="text-gray-900 dark:text-white">Processing...</span>
                </div>
            </div>

            <!-- Form Fields -->
            <div class="{{ $spaceClass }}">
                {{ $slot }}
            </div>

            <!-- Form Actions -->
            <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                    @if($cancelUrl)
                        <a href="{{ $cancelUrl }}" 
                           class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200 min-h-[44px]">
                            Cancel
                        </a>
                    @endif
                    
                    <button type="submit" 
                            :disabled="loading"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 min-h-[44px]">
                        <span x-show="!loading">{{ $submitText }}</span>
                        <span x-show="loading" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    /* Enhance mobile form usability */
    @media (max-width: 640px) {
        /* Larger touch targets on mobile */
        input, select, textarea, button {
            min-height: 44px;
        }
        
        /* Better input spacing on mobile */
        input[type="text"], 
        input[type="email"], 
        input[type="password"], 
        input[type="number"], 
        input[type="tel"], 
        textarea, 
        select {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        /* Improve focus visibility on touch devices */
        input:focus, 
        select:focus, 
        textarea:focus {
            outline: 2px solid #10b981;
            outline-offset: 2px;
        }
    }
</style>
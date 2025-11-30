@props([
    'id' => 'modal',
    'title' => 'Modal',
    'size' => 'md', // sm, md, lg, xl, 2xl
    'maxWidth' => null,
    'closable' => true,
    'show' => false
])

@php
$sizeClasses = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl',
    '4xl' => 'max-w-4xl',
    '5xl' => 'max-w-5xl',
    '6xl' => 'max-w-6xl',
    '7xl' => 'max-w-7xl'
];

$maxWidthClass = $maxWidth ? $maxWidth : ($sizeClasses[$size] ?? 'max-w-md');
@endphp

<!-- Modal Backdrop -->
<div 
    id="{{ $id }}" 
    class="fixed inset-0 z-50 overflow-y-auto {{ $show ? '' : 'hidden' }}"
    style="{{ $show ? '' : 'display: none;' }}"
>
    <!-- Background overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModal('{{ $id }}')"></div>
    
    <!-- Modal container -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <!-- Modal panel -->
        <div 
            class="relative transform rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full {{ $maxWidthClass }} sm:p-6"
            onclick="event.stopPropagation()"
        >
            <!-- Modal header -->
            <div class="sm:flex sm:items-start">
                <div class="w-full">
                    @if($title)
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                            {{ $title }}
                        </h3>
                        @if($closable)
                        <button 
                            type="button" 
                            class="rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            @click="show = false"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        @endif
                    </div>
                    @endif
                    
                    <!-- Modal content -->
                    <div class="mt-2">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Global modal helper functions
    if (typeof window.openModal === 'undefined') {
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                // Trigger Alpine.js show if available
                if (typeof Alpine !== 'undefined') {
                    const alpineComponent = Alpine.$data(modal);
                    if (alpineComponent) {
                        alpineComponent.show = true;
                    }
                }
            }
        }
    }
    
    if (typeof window.closeModal === 'undefined') {
        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Trigger Alpine.js hide if available
                if (typeof Alpine !== 'undefined') {
                    const alpineComponent = Alpine.$data(modal);
                    if (alpineComponent) {
                        alpineComponent.show = false;
                        // Hide after transition
                        setTimeout(() => {
                            modal.classList.add('hidden');
                        }, 200);
                        return;
                    }
                }
                // Fallback for non-Alpine.js
                modal.classList.add('hidden');
            }
        }
    }
</script>
@endpush
@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'focusable' => false,
    'title' => null,
    'closable' => true,
    'backdrop' => true, // Allow backdrop click to close
    'persistent' => false, // Don't close on escape or backdrop
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    'full' => 'sm:max-w-full',
][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1 }
    }"
    x-init="$nextTick(() => show && (@js($focusable) ? firstFocusable().focus() : $el.focus()))"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close.stop="show = false"
    @unless($persistent) x-on:keydown.escape.window="show = false" @endunless
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div x-show="show" 
         class="fixed inset-0 transform transition-all" 
         @if($backdrop && !$persistent) x-on:click="show = false" @endif
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    </div>

    {{-- Modal Content --}}
    <div x-show="show" 
         class="mb-6 bg-card border border-border rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        
        {{-- Header --}}
        @if($title || $closable)
        <div class="flex items-center justify-between p-4 border-b border-border">
            @if($title)
            <h3 class="text-lg font-semibold text-foreground">{{ $title }}</h3>
            @endif
            
            @if($closable)
            <button @click="show = false" 
                    class="text-muted-foreground hover:text-foreground transition-colors p-1 rounded-md hover:bg-muted">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            @endif
        </div>
        @endif
        
        {{-- Content --}}
        <div class="@if($title || $closable) @else p-4 @endif">
            {{ $slot }}
        </div>
    </div>
</div>
@props([
    'size' => 'default',    // sm, default, lg
    'showLabel' => false,
])

<div class="relative">
    <x-ui.button 
        variant="ghost" 
        :size="$size" 
        data-theme-toggle
        {{ $attributes->merge(['class' => 'relative theme-toggle-btn']) }}
        title="Toggle theme">
        
        {{-- Theme Icons --}}
        <div class="relative">
            {{-- Light Mode Icon --}}
            <svg class="theme-icon theme-icon-light h-4 w-4 transition-all duration-200" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            
            {{-- Dark Mode Icon --}}
            <svg class="theme-icon theme-icon-dark h-4 w-4 transition-all duration-200 hidden" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        </div>
        
        @if($showLabel)
            <span class="ml-2 text-sm theme-label">Light</span>
        @endif
        
    </x-ui.button>
    
    {{-- Keyboard Shortcut Hint (Optional) --}}
    @if($showLabel)
        <div class="text-xs text-muted-foreground mt-1">
            <kbd class="px-1 py-0.5 text-xs bg-muted rounded">Ctrl</kbd> + 
            <kbd class="px-1 py-0.5 text-xs bg-muted rounded">Shift</kbd> + 
            <kbd class="px-1 py-0.5 text-xs bg-muted rounded">L</kbd>
        </div>
    @endif
</div>


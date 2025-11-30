@props([
    'variant' => 'sidebar', // sidebar, tabs, breadcrumb, pagination
    'items' => [],
    'currentPath' => null,
    'alignment' => 'left', // left, center, right, justified
    'size' => 'md', // sm, md, lg
    'orientation' => 'horizontal', // horizontal, vertical
])

@php
    $currentPath = $currentPath ?? request()->path();
    
    // Base navigation classes
    $baseClasses = [
        'sidebar' => 'nav-sidebar',
        'tabs' => 'nav-tabs',
        'breadcrumb' => 'nav-breadcrumb',
        'pagination' => 'nav-pagination',
    ];
    
    $alignmentClasses = [
        'left' => 'justify-start',
        'center' => 'justify-center', 
        'right' => 'justify-end',
        'justified' => 'justify-between',
    ];
    
    $sizeClasses = [
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
    ];
    
    $orientationClasses = [
        'horizontal' => 'flex-row space-x-1',
        'vertical' => 'flex-col space-y-1',
    ];
@endphp

{{-- Sidebar Navigation --}}
@if($variant === 'sidebar')
<nav class="nav-sidebar {{ $sizeClasses[$size] }}" role="navigation" aria-label="Main navigation">
    <ul class="space-y-1" role="list">
        @foreach($items as $item)
        @php
            $isActive = isset($item['path']) && str_starts_with($currentPath, trim($item['path'], '/'));
            $hasChildren = isset($item['children']) && count($item['children']) > 0;
        @endphp
        
        <li>
            @if($hasChildren)
                {{-- Collapsible Menu Item --}}
                <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="nav-item nav-item--collapsible w-full flex items-center justify-between p-2 rounded-md transition-colors hover:bg-muted focus:bg-muted
                                   {{ $isActive ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                        <div class="flex items-center space-x-3">
                            @if(isset($item['icon']))
                            <span class="nav-item__icon">
                                {!! $item['icon'] !!}
                            </span>
                            @endif
                            <span class="nav-item__label">{{ $item['label'] }}</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" 
                             :class="open ? 'rotate-180' : ''" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="mt-1 ml-6 space-y-1"
                         style="display: none;">
                        @foreach($item['children'] as $child)
                        @php
                            $childActive = isset($child['path']) && str_starts_with($currentPath, trim($child['path'], '/'));
                        @endphp
                        <a href="{{ $child['path'] ?? '#' }}" 
                           class="nav-item nav-item--child block p-2 rounded-md transition-colors hover:bg-muted focus:bg-muted
                                  {{ $childActive ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                            <span class="nav-item__label">{{ $child['label'] }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Simple Menu Item --}}
                <a href="{{ $item['path'] ?? '#' }}" 
                   class="nav-item flex items-center space-x-3 p-2 rounded-md transition-colors hover:bg-muted focus:bg-muted
                          {{ $isActive ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:text-foreground' }}"
                   @if($isActive) aria-current="page" @endif>
                    @if(isset($item['icon']))
                    <span class="nav-item__icon">
                        {!! $item['icon'] !!}
                    </span>
                    @endif
                    <span class="nav-item__label">{{ $item['label'] }}</span>
                    @if(isset($item['badge']))
                    <span class="nav-item__badge ml-auto">
                        <x-ui.badge variant="secondary" size="sm">{{ $item['badge'] }}</x-ui.badge>
                    </span>
                    @endif
                </a>
            @endif
        </li>
        @endforeach
    </ul>
</nav>

{{-- Tab Navigation --}}
@elseif($variant === 'tabs')
<nav class="nav-tabs {{ $alignmentClasses[$alignment] }}" role="tablist">
    <div class="flex {{ $orientationClasses[$orientation] }} border-b border-border">
        @foreach($items as $item)
        @php
            $isActive = isset($item['path']) && str_starts_with($currentPath, trim($item['path'], '/'));
        @endphp
        
        <a href="{{ $item['path'] ?? '#' }}" 
           class="nav-tab px-4 py-2 border-b-2 transition-colors {{ $sizeClasses[$size] }}
                  {{ $isActive 
                     ? 'border-primary text-primary font-medium' 
                     : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted' }}"
           role="tab"
           @if($isActive) aria-current="page" aria-selected="true" @else aria-selected="false" @endif>
            @if(isset($item['icon']))
            <span class="nav-tab__icon mr-2">
                {!! $item['icon'] !!}
            </span>
            @endif
            <span class="nav-tab__label">{{ $item['label'] }}</span>
            @if(isset($item['count']))
            <span class="nav-tab__count ml-2 px-2 py-1 text-xs bg-muted rounded-full">
                {{ $item['count'] }}
            </span>
            @endif
        </a>
        @endforeach
    </div>
</nav>

{{-- Breadcrumb Navigation --}}
@elseif($variant === 'breadcrumb')
<nav class="nav-breadcrumb {{ $sizeClasses[$size] }}" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2" role="list">
        @foreach($items as $index => $item)
        <li class="flex items-center">
            @if($index > 0)
            <svg class="h-4 w-4 text-muted-foreground mx-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>
            @endif
            
            @if(isset($item['path']) && $index < count($items) - 1)
            <a href="{{ $item['path'] }}" 
               class="breadcrumb-item text-muted-foreground hover:text-foreground transition-colors">
                {{ $item['label'] }}
            </a>
            @else
            <span class="breadcrumb-item text-foreground font-medium" aria-current="page">
                {{ $item['label'] }}
            </span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>

{{-- Pagination Navigation --}}
@elseif($variant === 'pagination')
<nav class="nav-pagination {{ $alignmentClasses[$alignment] }}" aria-label="Pagination">
    <div class="flex items-center space-x-1">
        @foreach($items as $item)
        @php
            $isActive = isset($item['active']) && $item['active'];
            $isDisabled = isset($item['disabled']) && $item['disabled'];
        @endphp
        
        @if($item['type'] === 'previous')
        <a href="{{ $item['path'] ?? '#' }}" 
           class="pagination-item pagination-item--previous flex items-center px-3 py-2 rounded-md transition-colors {{ $sizeClasses[$size] }}
                  {{ $isDisabled 
                     ? 'text-muted-foreground cursor-not-allowed' 
                     : 'text-muted-foreground hover:text-foreground hover:bg-muted' }}"
           @if($isDisabled) aria-disabled="true" tabindex="-1" @endif>
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Previous
        </a>
        
        @elseif($item['type'] === 'next')
        <a href="{{ $item['path'] ?? '#' }}" 
           class="pagination-item pagination-item--next flex items-center px-3 py-2 rounded-md transition-colors {{ $sizeClasses[$size] }}
                  {{ $isDisabled 
                     ? 'text-muted-foreground cursor-not-allowed' 
                     : 'text-muted-foreground hover:text-foreground hover:bg-muted' }}"
           @if($isDisabled) aria-disabled="true" tabindex="-1" @endif>
            Next
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        
        @elseif($item['type'] === 'ellipsis')
        <span class="pagination-item pagination-item--ellipsis px-3 py-2 text-muted-foreground {{ $sizeClasses[$size] }}">
            ...
        </span>
        
        @else
        <a href="{{ $item['path'] ?? '#' }}" 
           class="pagination-item px-3 py-2 rounded-md transition-colors {{ $sizeClasses[$size] }}
                  {{ $isActive 
                     ? 'bg-primary text-primary-foreground' 
                     : 'text-muted-foreground hover:text-foreground hover:bg-muted' }}"
           @if($isActive) aria-current="page" @endif>
            {{ $item['label'] }}
        </a>
        @endif
        @endforeach
    </div>
</nav>
@endif

{{-- Enhanced Keyboard Navigation --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced keyboard navigation for all nav patterns
    const navElements = document.querySelectorAll('[role="navigation"], [role="tablist"]');
    
    navElements.forEach(nav => {
        const items = nav.querySelectorAll('a, button');
        
        nav.addEventListener('keydown', function(e) {
            const currentIndex = Array.from(items).indexOf(document.activeElement);
            
            switch(e.key) {
                case 'ArrowDown':
                case 'ArrowRight':
                    e.preventDefault();
                    const nextIndex = (currentIndex + 1) % items.length;
                    items[nextIndex]?.focus();
                    break;
                    
                case 'ArrowUp':
                case 'ArrowLeft':
                    e.preventDefault();
                    const prevIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
                    items[prevIndex]?.focus();
                    break;
                    
                case 'Home':
                    e.preventDefault();
                    items[0]?.focus();
                    break;
                    
                case 'End':
                    e.preventDefault();
                    items[items.length - 1]?.focus();
                    break;
            }
        });
    });
});
</script>
@endpush
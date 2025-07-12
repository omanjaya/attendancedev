@props([
    'icon' => null,
    'title' => 'No data found',
    'description' => null,
    'action' => null,
    'actionText' => 'Add new',
    'actionHref' => null,
    'size' => 'default', // sm, default, lg
])

@php
    $containerClasses = 'flex flex-col items-center justify-center text-center';
    $iconClasses = 'text-muted-foreground mb-4';
    $titleClasses = 'font-semibold text-foreground mb-2';
    $descriptionClasses = 'text-muted-foreground mb-6';
    
    switch ($size) {
        case 'sm':
            $containerClasses .= ' py-8';
            $iconClasses .= ' h-8 w-8';
            $titleClasses .= ' text-lg';
            $descriptionClasses .= ' text-sm';
            break;
        case 'lg':
            $containerClasses .= ' py-16';
            $iconClasses .= ' h-16 w-16';
            $titleClasses .= ' text-2xl';
            $descriptionClasses .= ' text-base';
            break;
        default:
            $containerClasses .= ' py-12';
            $iconClasses .= ' h-12 w-12';
            $titleClasses .= ' text-xl';
            $descriptionClasses .= ' text-sm';
    }
@endphp

<div {{ $attributes->merge(['class' => $containerClasses]) }}>
    @if($icon)
        <div class="{{ $iconClasses }}">
            {!! $icon !!}
        </div>
    @else
        <svg class="{{ $iconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
    @endif
    
    <h3 class="{{ $titleClasses }}">{{ $title }}</h3>
    
    @if($description)
        <p class="{{ $descriptionClasses }}">{{ $description }}</p>
    @endif
    
    @if($action || $actionHref)
        <div class="flex gap-3">
            @if($actionHref)
                <x-ui.button href="{{ $actionHref }}">{{ $actionText }}</x-ui.button>
            @endif
            
            @if($action)
                {{ $action }}
            @endif
        </div>
    @endif
    
    @if($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
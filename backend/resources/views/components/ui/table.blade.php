@props([
    'striped' => false,
    'hover' => true,
    'condensed' => false,
    'responsive' => true,
    'stickyHeader' => false,
    'loading' => false,
    'emptyMessage' => 'No data available',
    'emptyIcon' => null,
])

@php
    // Use design system classes
    $tableClasses = 'table';
    
    if ($striped) {
        $tableClasses .= ' [&>tbody>tr:nth-child(odd)]:bg-muted/50';
    }
    
    if ($hover) {
        $tableClasses .= ' [&>tbody>tr]:transition-colors [&>tbody>tr]:hover:bg-muted/50';
    }
    
    if ($condensed) {
        $tableClasses .= ' [&_th]:py-2 [&_td]:py-2';
    }
    
    if ($stickyHeader) {
        $tableClasses .= ' [&>thead]:sticky [&>thead]:top-0 [&>thead]:z-10 [&>thead]:bg-background';
    }
    
    $wrapperClasses = 'relative rounded-md border';
    
    if ($responsive) {
        $wrapperClasses .= ' overflow-auto';
    }
@endphp

<div class="{{ $wrapperClasses }}">
    @if($loading)
        <div class="absolute inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-20 rounded-md">
            <div class="flex flex-col items-center gap-2">
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent"></div>
                <p class="text-sm text-muted-foreground">Loading...</p>
            </div>
        </div>
    @endif
    
    <table {{ $attributes->merge(['class' => $tableClasses]) }}>
        {{ $slot }}
    </table>
    
    @if($slot->isEmpty() && !$loading)
        <div class="p-8 text-center">
            @if($emptyIcon)
                <div class="mx-auto w-12 h-12 text-muted-foreground mb-4">
                    {!! $emptyIcon !!}
                </div>
            @else
                <svg class="mx-auto h-12 w-12 text-muted-foreground mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            @endif
            <p class="text-muted-foreground">{{ $emptyMessage }}</p>
        </div>
    @endif
</div>
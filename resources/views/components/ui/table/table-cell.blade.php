@props([
    'class' => '',
    'header' => false,
    'align' => 'left', // left, center, right
    'width' => null,
    'sortable' => false,
    'sorted' => null, // 'asc', 'desc', null
])

@php
    $tag = $header ? 'th' : 'td';
    $cellClasses = 'border-b px-4 py-3.5';
    
    if ($header) {
        $cellClasses .= ' font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0';
    } else {
        $cellClasses .= ' text-foreground [&:has([role=checkbox])]:pr-0';
    }
    
    // Alignment
    switch ($align) {
        case 'center':
            $cellClasses .= ' text-center';
            break;
        case 'right':
            $cellClasses .= ' text-right';
            break;
        default:
            $cellClasses .= ' text-left';
    }
    
    if ($sortable) {
        $cellClasses .= ' cursor-pointer hover:bg-muted/50 select-none';
    }
    
    $cellClasses .= " {$class}";
    
    $style = $width ? "width: {$width};" : '';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $cellClasses]) }} @if($style) style="{{ $style }}" @endif>
    @if($sortable)
        <div class="flex items-center gap-2">
            {{ $slot }}
            @if($sorted)
                @if($sorted === 'asc')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                @else
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                @endif
            @else
                <svg class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                </svg>
            @endif
        </div>
    @else
        {{ $slot }}
    @endif
</{{ $tag }}>
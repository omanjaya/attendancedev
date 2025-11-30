@props([
    'title' => '',
    'description' => '',
    'icon' => '',
    'href' => '#',
    'color' => 'primary',    // primary, success, warning, info, etc.
])

@php
    // Color configurations for different action types
    $colorConfig = [
        'primary' => [
            'bg' => 'from-primary/10 to-primary/5',
            'hoverBg' => 'hover:from-primary/20 hover:to-primary/10',
            'iconBg' => 'bg-primary/10 group-hover:bg-primary/20',
            'iconColor' => 'text-primary',
            'titleHover' => 'group-hover:text-primary',
            'border' => 'border-primary/20 hover:border-primary/30',
        ],
        'success' => [
            'bg' => 'from-success/10 to-success/5',
            'hoverBg' => 'hover:from-success/20 hover:to-success/10',
            'iconBg' => 'bg-success/10 group-hover:bg-success/20',
            'iconColor' => 'text-success',
            'titleHover' => 'group-hover:text-success',
            'border' => 'border-success/20 hover:border-success/30',
        ],
        'warning' => [
            'bg' => 'from-warning/10 to-warning/5',
            'hoverBg' => 'hover:from-warning/20 hover:to-warning/10',
            'iconBg' => 'bg-warning/10 group-hover:bg-warning/20',
            'iconColor' => 'text-warning',
            'titleHover' => 'group-hover:text-warning',
            'border' => 'border-warning/20 hover:border-warning/30',
        ],
        'info' => [
            'bg' => 'from-info/10 to-info/5',
            'hoverBg' => 'hover:from-info/20 hover:to-info/10',
            'iconBg' => 'bg-info/10 group-hover:bg-info/20',
            'iconColor' => 'text-info',
            'titleHover' => 'group-hover:text-info',
            'border' => 'border-info/20 hover:border-info/30',
        ],
        'muted' => [
            'bg' => 'from-muted to-muted/50',
            'hoverBg' => 'hover:from-muted hover:to-muted/80',
            'iconBg' => 'bg-muted group-hover:bg-muted/80',
            'iconColor' => 'text-muted-foreground',
            'titleHover' => 'group-hover:text-foreground',
            'border' => 'border-border hover:border-border/80',
        ],
    ];
    
    $config = $colorConfig[$color] ?? $colorConfig['primary'];
@endphp

<a href="{{ $href }}" 
   class="group flex items-center p-4 bg-gradient-to-r {{ $config['bg'] }} {{ $config['hoverBg'] }} rounded-xl transition-all duration-200 border {{ $config['border'] }}"
   {{ $attributes }}>
   
    <div class="bg-gradient-to-br {{ $config['iconBg'] }} rounded-lg p-3 transition-colors">
        @if($icon)
            <i class="{{ $icon }} text-xl {{ $config['iconColor'] }}"></i>
        @else
            <svg class="h-6 w-6 {{ $config['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        @endif
    </div>
    
    <div class="ml-4 flex-1">
        <h4 class="text-sm font-semibold text-foreground {{ $config['titleHover'] }} transition-colors">
            {{ $title }}
        </h4>
        @if($description)
            <p class="text-xs text-muted-foreground mt-1">{{ $description }}</p>
        @endif
    </div>
    
    <svg class="h-5 w-5 text-muted-foreground {{ $config['titleHover'] }} transition-colors" 
         fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
</a>
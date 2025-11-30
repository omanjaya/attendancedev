@props([
    'status' => 'present',    // present, absent, late, early, leave, sick, vacation
    'size' => 'default',      // sm, default, lg
])

@php
    // Status configurations with semantic colors
    $statusConfig = [
        'present' => [
            'label' => 'Present',
            'variant' => 'success',
            'icon' => 'M5 13l4 4L19 7',
        ],
        'absent' => [
            'label' => 'Absent', 
            'variant' => 'destructive',
            'icon' => 'M6 18L18 6M6 6l12 12',
        ],
        'late' => [
            'label' => 'Late',
            'variant' => 'warning',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'early' => [
            'label' => 'Early',
            'variant' => 'info',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'leave' => [
            'label' => 'On Leave',
            'variant' => 'secondary',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'sick' => [
            'label' => 'Sick Leave',
            'variant' => 'warning',
            'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        ],
        'vacation' => [
            'label' => 'Vacation',
            'variant' => 'info',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'pending' => [
            'label' => 'Pending',
            'variant' => 'outline',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'approved' => [
            'label' => 'Approved',
            'variant' => 'success',
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'rejected' => [
            'label' => 'Rejected',
            'variant' => 'destructive',
            'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    ];
    
    $config = $statusConfig[$status] ?? $statusConfig['present'];
    
    // Size configurations
    $iconSizes = [
        'sm' => 'h-3 w-3',
        'default' => 'h-4 w-4',
        'lg' => 'h-5 w-5',
    ];
    
    $iconSize = $iconSizes[$size] ?? $iconSizes['default'];
@endphp

<x-ui.badge :variant="$config['variant']" :size="$size" {{ $attributes }}>
    <svg class="{{ $iconSize }} mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
    </svg>
    {{ $config['label'] }}
</x-ui.badge>
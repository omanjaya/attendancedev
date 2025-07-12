@php
    $status = $value ?? $row['status'] ?? 'unknown';
    
    $badgeClasses = [
        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        'away' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        'offline' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
        'busy' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
    ];
    
    $badgeClass = $badgeClasses[$status] ?? $badgeClasses['offline'];
    $statusLabel = ucfirst($status);
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
    {{ $statusLabel }}
</span>
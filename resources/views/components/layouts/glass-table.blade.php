@props([
    'striped' => true,
    'hover' => true,
])

<div class="overflow-hidden rounded-xl border border-white/20 dark:border-gray-600/30 bg-white/10 dark:bg-gray-800/20 backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'w-full']) }}>
            {{ $slot }}
        </table>
    </div>
</div>

@push('styles')
<style>
    .glass-table {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .dark .glass-table {
        background: rgba(31, 41, 55, 0.2);
        border: 1px solid rgba(75, 85, 99, 0.3);
    }
    
    .glass-table th {
        background: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        color: rgb(31, 41, 55);
        font-weight: 600;
        padding: 0.75rem 1rem;
        text-align: left;
        font-size: 0.875rem;
    }
    
    .dark .glass-table th {
        background: rgba(31, 41, 55, 0.3);
        border-bottom: 1px solid rgba(75, 85, 99, 0.2);
        color: rgb(243, 244, 246);
    }
    
    .glass-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        color: rgb(55, 65, 81);
    }
    
    .dark .glass-table td {
        border-bottom: 1px solid rgba(75, 85, 99, 0.2);
        color: rgb(209, 213, 219);
    }
    
    .glass-table tbody tr:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .dark .glass-table tbody tr:hover {
        background: rgba(31, 41, 55, 0.2);
    }
    
    .glass-table tbody tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.02);
    }
    
    .dark .glass-table tbody tr:nth-child(even) {
        background: rgba(31, 41, 55, 0.1);
    }
</style>
@endpush
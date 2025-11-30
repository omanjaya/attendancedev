@props([
    'stats' => [],
    'columns' => 4,
    'loading' => false,
    'class' => '',
])

@php
    $gridClasses = match($columns) {
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        5 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
        6 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6',
        default => 'grid-cols-1 md:grid-cols-4'
    };
@endphp

<div class="grid {{ $gridClasses }} gap-6 mb-8 {{ $class }}">
    @if($loading)
        @for($i = 0; $i < $columns; $i++)
            <x-ui.card variant="simple" class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <x-ui.skeleton height="h-4" width="w-20" class="mb-2" />
                        <x-ui.skeleton height="h-8" width="w-16" class="mb-2" />
                        <x-ui.skeleton height="h-3" width="w-24" />
                    </div>
                    <x-ui.skeleton shape="circle" height="h-10" width="w-10" />
                </div>
            </x-ui.card>
        @endfor
    @else
        @foreach($stats as $stat)
            <x-layouts.stats-card 
                :title="$stat['label'] ?? $stat['title'] ?? 'N/A'"
                :value="$stat['value']"
                :change="isset($stat['change']) ? (is_array($stat['change']) ? $stat['change']['value'] : $stat['change']) : null"
                :change-type="isset($stat['change_type']) ? $stat['change_type'] : (isset($stat['change']['type']) ? $stat['change']['type'] : 'neutral')"
                :icon="$stat['icon'] ?? null"
                :icon-bg="$stat['iconBg'] ?? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-800 dark:text-emerald-300'"
                :id="$stat['id'] ?? null" />
        @endforeach
    @endif
</div>
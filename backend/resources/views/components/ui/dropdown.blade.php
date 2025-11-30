@props([
    'align' => 'right',
    'width' => '48',
])

@php
    $alignmentClasses = match ($align) {
        'left' => 'origin-top-left left-0',
        'top' => 'origin-top',
        default => 'origin-top-right right-0',
    };

    $width = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        default => $width,
    };
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false" @keydown.escape="open = false">
    <div @click="open = ! open" @keydown.enter="open = ! open" @keydown.space.prevent="open = ! open" role="button" :aria-expanded="open" aria-haspopup="true" tabindex="0">
        {{ $trigger }}
    </div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 mt-2 {{ $width }} rounded-lg shadow-lg {{ $alignmentClasses }}"
         style="display: none;"
         @click="open = false"
         role="menu"
         x-trap.inert.noscroll="open">
        <div class="rounded-lg ring-1 ring-border overflow-hidden bg-popover">
            <div class="py-1">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
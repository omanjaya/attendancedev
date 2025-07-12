@props([
    'id' => null,
    'name' => null,
    'checked' => false,
    'disabled' => false,
    'size' => 'default',    // sm, default, lg
])

@php
    // Size classes
    $sizeClasses = [
        'sm' => [
            'track' => 'h-4 w-7',
            'thumb' => 'h-3 w-3 data-[state=checked]:translate-x-3',
        ],
        'default' => [
            'track' => 'h-5 w-9',
            'thumb' => 'h-4 w-4 data-[state=checked]:translate-x-4',
        ],
        'lg' => [
            'track' => 'h-6 w-11', 
            'thumb' => 'h-5 w-5 data-[state=checked]:translate-x-5',
        ],
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
    
    $trackClasses = "peer inline-flex {$sizeClass['track']} shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input";
    
    $thumbClasses = "pointer-events-none block {$sizeClass['thumb']} rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=unchecked]:translate-x-0";
@endphp

<button type="button"
        role="switch"
        aria-checked="{{ $checked ? 'true' : 'false' }}"
        data-state="{{ $checked ? 'checked' : 'unchecked' }}"
        @if($id) id="{{ $id }}" @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => $trackClasses]) }}
        x-data="{ checked: {{ $checked ? 'true' : 'false' }} }"
        @click="checked = !checked; $dispatch('change', { checked, name: '{{ $name }}' })"
        :data-state="checked ? 'checked' : 'unchecked'"
        :aria-checked="checked">
    <span :class="checked ? '{{ $thumbClasses }} {{ str_replace('data-[state=unchecked]:translate-x-0', '', $thumbClasses) }} {{ $sizeClass['thumb'] }}' : '{{ $thumbClasses }}'"></span>
    @if($name)
        <input type="hidden" name="{{ $name }}" :value="checked ? '1' : '0'" />
    @endif
</button>
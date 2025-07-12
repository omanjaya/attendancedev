@props([
    'for' => null,
    'required' => false,
])

@php
    $classes = 'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70';
@endphp

<label @if($for) for="{{ $for }}" @endif 
       {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
    @if($required)
        <span class="text-destructive ml-1">*</span>
    @endif
</label>
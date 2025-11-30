@props([
    'for' => null,
    'required' => false,
    'optional' => false,
    'value' => null,
])

@php
    // Use design system classes
    $classes = 'form-label';
@endphp

<label @if($for) for="{{ $for }}" @endif 
       {{ $attributes->merge(['class' => $classes]) }}>
    {{ $value ?? $slot }}
    @if($required)
        <span class="text-destructive ml-1" aria-label="required">*</span>
    @elseif($optional)
        <span class="text-muted-foreground ml-1 text-xs">(optional)</span>
    @endif
</label>
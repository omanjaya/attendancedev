@props([
    'type' => 'text',
    'id' => null,
    'name' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
])

@php
    // Use design system classes
    $classes = 'form-input';
    
    if ($error) {
        $classes .= ' border-destructive focus-visible:ring-destructive';
    }
@endphp

<input type="{{ $type }}"
       @if($id) id="{{ $id }}" @endif
       @if($name) name="{{ $name }}" @endif
       @if($value !== null) value="{{ $value }}" @endif
       @if($placeholder) placeholder="{{ $placeholder }}" @endif
       @if($required) required @endif
       @if($disabled) disabled @endif
       @if($readonly) readonly @endif
       {{ $attributes->merge(['class' => $classes]) }} />
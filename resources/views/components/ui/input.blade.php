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
    $classes = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    
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
@props([
    'id' => null,
    'name' => null,
    'value' => null,
    'checked' => false,
    'disabled' => false,
    'required' => false,
])

<input 
    {{ $attributes->merge([
        'type' => 'checkbox',
        'class' => 'h-4 w-4 text-primary focus:ring-primary border-input rounded'
    ]) }}
    @if($id) id="{{ $id }}" @endif
    @if($name) name="{{ $name }}" @endif
    @if($value) value="{{ $value }}" @endif
    @if($checked) checked @endif
    @if($disabled) disabled @endif
    @if($required) required @endif
/>
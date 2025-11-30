@props([
    'disabled' => false,
    'rows' => 3,
    'placeholder' => '',
    'required' => false,
    'readonly' => false,
])

<textarea {{ $disabled ? 'disabled' : '' }} 
          {{ $required ? 'required' : '' }}
          {{ $readonly ? 'readonly' : '' }}
          rows="{{ $rows }}"
          {!! $attributes->merge(['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']) !!}
          placeholder="{{ $placeholder }}">{{ $slot }}</textarea>
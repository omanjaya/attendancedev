@props([
    'disabled' => false,
])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'block w-full border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 rounded-md'
]) !!}>
    {{ $slot }}
</select>
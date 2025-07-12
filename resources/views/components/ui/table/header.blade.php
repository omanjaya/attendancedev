@props([
    'class' => ''
])

<thead {{ $attributes->merge(['class' => 'bg-muted/50 ' . $class]) }}>
    {{ $slot }}
</thead>
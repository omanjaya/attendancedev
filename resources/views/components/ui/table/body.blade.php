@props([
    'class' => ''
])

<tbody {{ $attributes->merge(['class' => 'bg-background divide-y divide-border ' . $class]) }}>
    {{ $slot }}
</tbody>
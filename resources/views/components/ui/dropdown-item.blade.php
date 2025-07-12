@props([
    'href' => null,
])

@if($href)
<a href="{{ $href }}" {{ $attributes->merge([
    'class' => 'block px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground transition-colors'
]) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground transition-colors'
]) }}>
    {{ $slot }}
</button>
@endif
@props([
    'messages' => [],
])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'text-sm text-destructive mt-1']) }}>
        @foreach ((array) $messages as $message)
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ $message }}</span>
            </div>
        @endforeach
    </div>
@endif
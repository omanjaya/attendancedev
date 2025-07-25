@props(['message'])

@if($message)
<div {{ $attributes->merge(['class' => 'rounded-md bg-emerald-50 p-4 mb-4 dark:bg-emerald-900/20']) }}>
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ $message }}</p>
        </div>
    </div>
</div>
@endif
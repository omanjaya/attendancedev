@props([
    'title' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'neutral',
    'icon' => null,
    'iconBg' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-800 dark:text-emerald-300',
    'id' => null,
    'loading' => false,
])

<x-layouts.glass-card 
    variant="default"
    hover="true"
    class="transition-all duration-200 hover:shadow-md"
    {{ $attributes }}>
    
    @if($loading)
        <div class="flex items-center justify-between p-6">
            <div class="flex-1">
                <x-ui.skeleton height="h-4" width="w-20" class="mb-2" />
                <x-ui.skeleton height="h-8" width="w-16" class="mb-2" />
                <x-ui.skeleton height="h-3" width="w-24" />
            </div>
            <x-ui.skeleton shape="circle" height="h-10" width="w-10" />
        </div>
    @else
        <div class="flex items-center justify-between p-6">
            <div class="flex-1">
                <p class="text-base font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide mb-3">
                    {{ $title }}
                </p>
                
                <div class="flex items-baseline gap-2">
                    <p class="text-4xl font-bold text-gray-900 dark:text-gray-100" 
                       @if($id) id="{{ $id }}" @endif>
                        {{ $value }}
                    </p>
                </div>
                
                @if($change)
                    <div class="flex items-center mt-3">
                        @if($changeType === 'increase' || $changeType === 'positive')
                            <svg class="h-4 w-4 text-green-600 dark:text-green-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            <span class="text-sm text-green-600 dark:text-green-400 font-semibold">{{ $change }}</span>
                        @elseif($changeType === 'decrease' || $changeType === 'negative')
                            <svg class="h-4 w-4 text-red-600 dark:text-red-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                            </svg>
                            <span class="text-sm text-red-600 dark:text-red-400 font-semibold">{{ $change }}</span>
                        @else
                            <span class="text-sm text-gray-600 dark:text-gray-400 font-semibold">{{ $change }}</span>
                        @endif
                    </div>
                @endif
            </div>
            
            @if($icon)
                <div class="ml-4">
                    <div class="w-12 h-12 rounded-xl {{ $iconBg }} flex items-center justify-center">
                        {!! $icon !!}
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-layouts.glass-card>
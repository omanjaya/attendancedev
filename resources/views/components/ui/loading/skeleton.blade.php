@props([
    'type' => 'line', // line, circle, card, table, avatar, button, text
    'width' => null,
    'height' => null,
    'lines' => 3,
    'animated' => true,
    'class' => ''
])

@php
    $baseClasses = 'bg-gray-200 dark:bg-gray-700';
    $animatedClasses = $animated ? 'animate-pulse' : '';
    
    $skeletonClass = collect([$baseClasses, $animatedClasses, $class])
        ->filter()
        ->implode(' ');
@endphp

@if($type === 'line')
    <div class="{{ $skeletonClass }} rounded h-4 {{ $width ? "w-{$width}" : 'w-full' }}"></div>

@elseif($type === 'circle')
    @php
        $size = $width ?? $height ?? '12';
        $circleClass = "w-{$size} h-{$size}";
    @endphp
    <div class="{{ $skeletonClass }} rounded-full {{ $circleClass }}"></div>

@elseif($type === 'avatar')
    @php
        $size = $width ?? '10';
        $avatarClass = "w-{$size} h-{$size}";
    @endphp
    <div class="{{ $skeletonClass }} rounded-full {{ $avatarClass }}"></div>

@elseif($type === 'button')
    <div class="{{ $skeletonClass }} rounded-md h-10 {{ $width ? "w-{$width}" : 'w-24' }}"></div>

@elseif($type === 'text')
    <div class="space-y-2">
        @for($i = 0; $i < $lines; $i++)
            @php
                $lineWidth = $i === $lines - 1 ? 'w-3/4' : 'w-full';
            @endphp
            <div class="{{ $skeletonClass }} rounded h-4 {{ $lineWidth }}"></div>
        @endfor
    </div>

@elseif($type === 'card')
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
        <div class="space-y-4">
            <!-- Card header -->
            <div class="flex items-center space-x-4">
                <div class="{{ $skeletonClass }} rounded-full w-12 h-12"></div>
                <div class="flex-1 space-y-2">
                    <div class="{{ $skeletonClass }} rounded h-4 w-3/4"></div>
                    <div class="{{ $skeletonClass }} rounded h-3 w-1/2"></div>
                </div>
            </div>
            
            <!-- Card content -->
            <div class="space-y-2">
                @for($i = 0; $i < ($lines ?? 3); $i++)
                    @php
                        $lineWidth = $i === 0 ? 'w-full' : ($i === $lines - 1 ? 'w-2/3' : 'w-5/6');
                    @endphp
                    <div class="{{ $skeletonClass }} rounded h-4 {{ $lineWidth }}"></div>
                @endfor
            </div>
            
            <!-- Card actions -->
            <div class="flex space-x-2 pt-2">
                <div class="{{ $skeletonClass }} rounded h-8 w-20"></div>
                <div class="{{ $skeletonClass }} rounded h-8 w-16"></div>
            </div>
        </div>
    </div>

@elseif($type === 'table')
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Table header -->
        <div class="border-b border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-4 gap-4">
                @for($i = 0; $i < 4; $i++)
                    <div class="{{ $skeletonClass }} rounded h-4"></div>
                @endfor
            </div>
        </div>
        
        <!-- Table rows -->
        @for($row = 0; $row < ($lines ?? 5); $row++)
            <div class="border-b border-gray-100 dark:border-gray-700 p-4 last:border-b-0">
                <div class="grid grid-cols-4 gap-4 items-center">
                    @for($col = 0; $col < 4; $col++)
                        @if($col === 0)
                            <div class="flex items-center space-x-3">
                                <div class="{{ $skeletonClass }} rounded-full w-8 h-8"></div>
                                <div class="{{ $skeletonClass }} rounded h-4 w-20"></div>
                            </div>
                        @else
                            <div class="{{ $skeletonClass }} rounded h-4 {{ $col === 3 ? 'w-16' : 'w-full' }}"></div>
                        @endif
                    @endfor
                </div>
            </div>
        @endfor
    </div>

@elseif($type === 'stats')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        @for($i = 0; $i < 4; $i++)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                <div class="flex items-center space-x-4">
                    <div class="{{ $skeletonClass }} rounded-lg w-12 h-12"></div>
                    <div class="flex-1 space-y-2">
                        <div class="{{ $skeletonClass }} rounded h-6 w-16"></div>
                        <div class="{{ $skeletonClass }} rounded h-4 w-24"></div>
                    </div>
                </div>
                <div class="mt-4 space-y-2">
                    <div class="{{ $skeletonClass }} rounded h-8 w-20"></div>
                    <div class="{{ $skeletonClass }} rounded h-2 w-full"></div>
                </div>
            </div>
        @endfor
    </div>

@elseif($type === 'form')
    <div class="space-y-6">
        @for($i = 0; $i < ($lines ?? 4); $i++)
            <div class="space-y-2">
                <div class="{{ $skeletonClass }} rounded h-4 w-24"></div>
                <div class="{{ $skeletonClass }} rounded-md h-10 w-full"></div>
            </div>
        @endfor
        
        <div class="flex space-x-4 pt-4">
            <div class="{{ $skeletonClass }} rounded-md h-10 w-24"></div>
            <div class="{{ $skeletonClass }} rounded-md h-10 w-20"></div>
        </div>
    </div>

@elseif($type === 'list')
    <div class="space-y-4">
        @for($i = 0; $i < ($lines ?? 5); $i++)
            <div class="flex items-center space-x-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="{{ $skeletonClass }} rounded-full w-10 h-10"></div>
                <div class="flex-1 space-y-2">
                    <div class="{{ $skeletonClass }} rounded h-4 w-3/4"></div>
                    <div class="{{ $skeletonClass }} rounded h-3 w-1/2"></div>
                </div>
                <div class="{{ $skeletonClass }} rounded h-6 w-16"></div>
            </div>
        @endfor
    </div>

@else
    <!-- Custom skeleton -->
    <div class="{{ $skeletonClass }} rounded {{ $width ? "w-{$width}" : 'w-full' }} {{ $height ? "h-{$height}" : 'h-4' }}"></div>
@endif

<style>
    /* Enhanced pulse animation for better visual feedback */
    @keyframes skeleton-pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    
    .animate-pulse {
        animation: skeleton-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    /* Shimmer effect for premium loading experience */
    .animate-shimmer {
        background: linear-gradient(90deg, 
            theme('colors.gray.200') 25%, 
            theme('colors.gray.100') 50%, 
            theme('colors.gray.200') 75%
        );
        background-size: 200% 100%;
        animation: shimmer 2s infinite;
    }
    
    .dark .animate-shimmer {
        background: linear-gradient(90deg, 
            theme('colors.gray.700') 25%, 
            theme('colors.gray.600') 50%, 
            theme('colors.gray.700') 75%
        );
        background-size: 200% 100%;
    }
    
    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }
</style>
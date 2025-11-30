@props([
    'item' => [],
    'isActive' => false,
    'variant' => 'desktop' // desktop, mobile, mobile-bottom
])

@php
use App\Services\IconService;

$baseClasses = 'transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500';
$href = isset($item['route']) ? route($item['route']) : '#';
$isHighlight = $item['highlight'] ?? false;
$badge = $item['badge'] ?? null;
@endphp

@if($variant === 'desktop')
    <!-- Desktop Navigation Item - Clean Professional Design -->
    <a href="{{ $href }}"
       @if($isActive) aria-current="page" @endif
       class="nav-item group relative
              @if($isActive)
                  nav-item-active
              @elseif($isHighlight)
                  nav-item-highlight
              @else
                  nav-item-inactive
              @endif">
        
        <!-- Icon -->
        <div class="relative mr-3 flex-shrink-0 transition-colors duration-200
                    @if($isHighlight) text-white 
                    @elseif($isActive) text-emerald-600 dark:text-emerald-400
                    @else text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-200
                    @endif">
            {!! IconService::getIcon($item['icon'], $isActive ? 'solid' : 'outline', '5') !!}
        </div>
        
        <!-- Label -->
        <span class="flex-1 truncate font-medium">{{ $item['name'] }}</span>
        
        <!-- Badge -->
        @if($badge)
            <span class="ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-bold
                         @if($badge['type'] === 'danger') 
                             bg-red-500 text-white
                         @elseif($badge['type'] === 'warning') 
                             bg-amber-500 text-white
                         @elseif($badge['type'] === 'info') 
                             bg-blue-500 text-white
                         @else 
                             bg-gray-500 text-white
                         @endif">
                {{ $badge['count'] > 99 ? '99+' : $badge['count'] }}
            </span>
        @endif
    </a>

@elseif($variant === 'mobile')
    <!-- Mobile Navigation Item - Clean Professional Design -->
    <a href="{{ $href }}"
       @if($isActive) aria-current="page" @endif
       @click="mobileNavOpen = false"
       class="nav-item group relative text-base
              @if($isActive)
                  nav-item-active
              @elseif($isHighlight)
                  nav-item-highlight
              @else
                  nav-item-inactive
              @endif">
        
        <!-- Icon -->
        <div class="w-6 h-6 mr-4 flex-shrink-0
                    @if($isActive) text-emerald-600 dark:text-emerald-300
                    @else text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300
                    @endif">
            {!! IconService::getIcon($item['icon'], $isActive ? 'solid' : 'outline', '6') !!}
        </div>
        
        <!-- Label -->
        <span class="flex-1 truncate font-medium">{{ $item['name'] }}</span>
        
        <!-- Badge -->
        @if($badge)
            <span class="ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-bold
                         @if($badge['type'] === 'danger') 
                             bg-red-500 text-white
                         @elseif($badge['type'] === 'warning') 
                             bg-amber-500 text-white
                         @elseif($badge['type'] === 'info') 
                             bg-blue-500 text-white
                         @else 
                             bg-gray-500 text-white
                         @endif">
                {{ $badge['count'] > 99 ? '99+' : $badge['count'] }}
            </span>
        @endif
    </a>

@elseif($variant === 'mobile-bottom')
    <!-- Mobile Bottom Navigation Item - Clean Professional Design -->
    <a href="{{ $href }}"
       @if($isActive) aria-current="page" @endif
       class="{{ $baseClasses }} group relative flex flex-col items-center justify-center p-2 text-xs font-medium transition-colors duration-200
              @if($isActive)
                  text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 rounded-md
              @elseif($isHighlight)
                  text-white bg-emerald-600 rounded-md
              @else
                  text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md
              @endif">
        
        <!-- Icon with Badge -->
        <div class="relative mb-1.5 z-10">
            <div class="transition-colors duration-200
                        @if($isHighlight) text-white 
                        @elseif($isActive) text-emerald-600 dark:text-emerald-400
                        @else text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300
                        @endif">
                {!! IconService::getIcon($item['icon'], $isActive ? 'solid' : 'outline', '6') !!}
            </div>
            
            @if($badge)
                <span class="absolute -top-2 -right-2 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-xs font-bold
                             @if($badge['type'] === 'danger') 
                                 bg-red-500 text-white
                             @elseif($badge['type'] === 'warning') 
                                 bg-amber-500 text-white
                             @elseif($badge['type'] === 'info') 
                                 bg-blue-500 text-white
                             @else 
                                 bg-gray-500 text-white
                             @endif
                             min-w-[1.25rem] h-5">
                    {{ $badge['count'] > 99 ? '99+' : $badge['count'] }}
                </span>
            @endif
        </div>
        
        <!-- Label -->
        <span class="relative z-10 truncate max-w-full font-medium">{{ $item['name'] }}</span>
        
        <!-- Active indicator dot -->
        @if($isActive && !$isHighlight)
            <div class="absolute bottom-0.5 w-1 h-1 bg-emerald-500 rounded-full"></div>
        @endif
    </a>
@endif
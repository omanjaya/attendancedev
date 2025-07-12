@props([
    'items' => [],
    'currentRoute' => null
])

@php
    $defaultItems = [
        [
            'route' => 'dashboard',
            'icon' => 'fas fa-home',
            'label' => 'Home',
            'badge' => null
        ],
        [
            'route' => 'attendance.index',
            'icon' => 'fas fa-clock',
            'label' => 'Attendance',
            'badge' => null
        ],
        [
            'route' => 'attendance.check-in',
            'icon' => 'fas fa-plus-circle',
            'label' => 'Check-in',
            'badge' => null,
            'primary' => true
        ],
        [
            'route' => 'leave.index',
            'icon' => 'fas fa-calendar-alt',
            'label' => 'Leave',
            'badge' => 3
        ],
        [
            'route' => 'profile.edit',
            'icon' => 'fas fa-user',
            'label' => 'Profile',
            'badge' => null
        ]
    ];
    
    $navItems = !empty($items) ? $items : $defaultItems;
    $current = $currentRoute ?? request()->route()->getName();
@endphp

<!-- Mobile Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 md:hidden">
    <div class="grid grid-cols-{{ count($navItems) }} h-16">
        @foreach($navItems as $item)
            @php
                $isActive = str_starts_with($current, $item['route']);
                $isPrimary = $item['primary'] ?? false;
                
                $baseClasses = 'flex flex-col items-center justify-center h-full transition-colors duration-200 relative';
                
                if ($isPrimary) {
                    $classes = $baseClasses . ' bg-emerald-500 text-white mx-2 my-2 rounded-xl shadow-lg transform hover:scale-105';
                } elseif ($isActive) {
                    $classes = $baseClasses . ' text-emerald-600 dark:text-emerald-400';
                } else {
                    $classes = $baseClasses . ' text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300';
                }
            @endphp
            
            <a href="{{ route($item['route']) }}" class="{{ $classes }}">
                <!-- Icon -->
                <div class="relative">
                    <i class="{{ $item['icon'] }} {{ $isPrimary ? 'text-lg' : 'text-base' }}"></i>
                    
                    <!-- Badge -->
                    @if(isset($item['badge']) && $item['badge'])
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center min-w-[16px]">
                            {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                        </span>
                    @endif
                </div>
                
                <!-- Label -->
                <span class="text-xs mt-1 {{ $isPrimary ? 'font-medium' : '' }}">
                    {{ $item['label'] }}
                </span>
                
                <!-- Active Indicator -->
                @if($isActive && !$isPrimary)
                    <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-emerald-500 rounded-full"></div>
                @endif
            </a>
        @endforeach
    </div>
</nav>

<!-- Add bottom padding to body content to account for fixed navigation -->
<style>
    @media (max-width: 767px) {
        body {
            padding-bottom: 4rem;
        }
        
        /* Ensure main content doesn't overlap with bottom nav */
        main {
            padding-bottom: 1rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add haptic feedback for bottom nav on supported devices
    const navLinks = document.querySelectorAll('nav a');
    
    navLinks.forEach(link => {
        link.addEventListener('touchstart', function() {
            // Vibrate for 10ms if supported
            if (navigator.vibrate) {
                navigator.vibrate(10);
            }
        });
    });
    
    // Update active state based on current URL
    function updateActiveNav() {
        const currentPath = window.location.pathname;
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            const isActive = currentPath.startsWith(href.split('?')[0]);
            
            if (isActive && !link.classList.contains('bg-emerald-500')) {
                // Remove active class from others
                navLinks.forEach(otherLink => {
                    otherLink.classList.remove('text-emerald-600', 'dark:text-emerald-400');
                    otherLink.classList.add('text-gray-400', 'dark:text-gray-500');
                });
                
                // Add active class to current
                link.classList.remove('text-gray-400', 'dark:text-gray-500');
                link.classList.add('text-emerald-600', 'dark:text-emerald-400');
            }
        });
    }
    
    // Update on navigation
    window.addEventListener('popstate', updateActiveNav);
});
</script>
@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gradient-to-br from-emerald-50 via-green-50 to-teal-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <!-- Sidebar -->
    <div class="w-64 bg-white/20 dark:bg-gray-800/40 backdrop-blur-xl border-r border-white/30 dark:border-gray-600/50 flex-shrink-0 sidebar overflow-hidden shadow-2xl">
        @include('partials.sidebar-content')
    </div>

    <!-- Main Content - Full Width -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Header Bar - Desktop & Mobile -->
        <header class="bg-white/30 dark:bg-gray-800/50 backdrop-blur-xl border-b border-white/20 dark:border-gray-600/30 py-3 px-4 lg:px-6 top-header sticky top-0 z-40 shadow-lg">
            <div class="flex items-center justify-between gap-4">
                <!-- Left Side - Mobile Menu + Search -->
                <div class="flex items-center gap-4 flex-1">
                    <!-- Mobile Menu Button -->
                    <x-ui.button variant="ghost" size="icon" class="touch-target lg:hidden" onclick="toggleMobileSidebar()">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="sr-only">Open navigation menu</span>
                    </x-ui.button>
                    
                    <!-- Search Bar -->
                    <div class="relative flex-1 max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               placeholder="Search employees, attendance, schedules..." 
                               class="global-search-input block w-full pl-10 pr-3 py-2 border border-white/20 dark:border-gray-600/30 rounded-xl bg-white/30 dark:bg-gray-700/50 backdrop-blur-sm text-gray-800 dark:text-gray-100 placeholder-gray-600 dark:placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 text-sm transition-all duration-300" 
                               id="global-search" />
                    </div>
                </div>

                <!-- Right Side - Profile & Theme Toggle -->
                <div class="flex items-center gap-3">
                    <!-- Theme Toggle -->
                    <x-ui.theme-toggle size="sm" class="touch-target" />
                    
                    <!-- Notifications -->
                    <button class="p-2 bg-white/30 dark:bg-gray-700/50 backdrop-blur-sm border border-white/20 dark:border-gray-600/30 rounded-xl hover:bg-white/40 dark:hover:bg-gray-600/60 transition-all duration-300 touch-target relative group">
                        <svg class="h-5 w-5 text-gray-700 dark:text-gray-300 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <!-- Notification Badge -->
                        <span class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full animate-ping"></span>
                    </button>
                    
                    <!-- Profile Dropdown -->
                    <div class="relative">
                        <button id="profile-dropdown-button"
                                class="flex items-center gap-2 p-2 rounded-xl bg-white/30 dark:bg-gray-700/50 backdrop-blur-sm border border-white/20 dark:border-gray-600/30 hover:bg-white/40 dark:hover:bg-gray-600/60 transition-all duration-300">
                            <x-ui.avatar :name="Auth::user()->name" size="sm" />
                            <div class="hidden sm:block text-left">
                                <div class="text-sm font-medium text-foreground">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-muted-foreground">{{ Auth::user()->email }}</div>
                            </div>
                            <svg class="h-4 w-4 text-muted-foreground transition-transform" id="profile-dropdown-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="profile-dropdown-menu" 
                             class="profile-dropdown absolute right-0 mt-2 w-48 bg-white/90 dark:bg-gray-800/95 backdrop-blur-xl border border-white/20 dark:border-gray-600/40 rounded-xl shadow-2xl z-50 hidden opacity-0 scale-95 transition-all duration-200">
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-white/20 dark:hover:bg-gray-700/20 rounded-lg mx-2 transition-all duration-200">
                                    <svg class="h-4 w-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    My Profile
                                </a>
                                <a href="{{ route('settings') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-white/20 dark:hover:bg-gray-700/20 rounded-lg mx-2 transition-all duration-200">
                                    <svg class="h-4 w-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Settings
                                </a>
                                <div class="border-t border-white/20 dark:border-gray-700/20 my-2"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50/50 dark:hover:bg-red-900/20 rounded-lg mx-2 transition-all duration-200">
                                        <svg class="h-4 w-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content - No Container, Full Width with Better Spacing -->
        <main class="flex-1 overflow-y-auto bg-transparent fullwidth-padding">
            <div class="content-section">
                @yield('page-content')
            </div>
        </main>
    </div>
</div>

<!-- Mobile Sidebar Overlay -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 z-50 lg:hidden hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-background/80 backdrop-blur-sm" onclick="toggleMobileSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="relative w-64 bg-card h-full shadow-xl border-r border-border">
        <div class="absolute top-4 right-4 z-10">
            <x-ui.button variant="ghost" size="icon" onclick="toggleMobileSidebar()">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </x-ui.button>
        </div>
        @include('partials.sidebar-content')
    </div>
</div>

<script>
function toggleMobileSidebar() {
    const overlay = document.getElementById('mobile-sidebar-overlay');
    
    if (overlay.classList.contains('hidden')) {
        // Show sidebar
        overlay.classList.remove('hidden');
        // Prevent body scroll when sidebar is open
        document.body.style.overflow = 'hidden';
    } else {
        // Hide sidebar
        overlay.classList.add('hidden');
        // Restore body scroll
        document.body.style.overflow = '';
    }
}

// Global search functionality
function initializeGlobalSearch() {
    const searchInput = document.getElementById('global-search');
    if (!searchInput) return;

    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) return;
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = e.target.value.trim();
            if (query.length >= 2) {
                performSearch(query);
            }
        }
    });
}

function performSearch(query) {
    // Simple client-side search - redirect to appropriate pages with search params
    const searchResults = [
        { name: 'employees', route: '{{ route("employees.index") }}' },
        { name: 'attendance', route: '{{ route("attendance.index") }}' },
        { name: 'schedules', route: '{{ route("schedules.index") }}' },
        { name: 'leave', route: '{{ route("leave.index") }}' },
        { name: 'payroll', route: '{{ route("payroll.index") }}' }
    ];
    
    const lowerQuery = query.toLowerCase();
    
    // Simple keyword matching
    if (lowerQuery.includes('employee') || lowerQuery.includes('staff') || lowerQuery.includes('teacher')) {
        window.location.href = searchResults[0].route + '?search=' + encodeURIComponent(query);
    } else if (lowerQuery.includes('attendance') || lowerQuery.includes('present') || lowerQuery.includes('absent')) {
        window.location.href = searchResults[1].route + '?search=' + encodeURIComponent(query);
    } else if (lowerQuery.includes('schedule') || lowerQuery.includes('class') || lowerQuery.includes('period')) {
        window.location.href = searchResults[2].route + '?search=' + encodeURIComponent(query);
    } else if (lowerQuery.includes('leave') || lowerQuery.includes('vacation') || lowerQuery.includes('holiday')) {
        window.location.href = searchResults[3].route + '?search=' + encodeURIComponent(query);
    } else if (lowerQuery.includes('payroll') || lowerQuery.includes('salary') || lowerQuery.includes('payment')) {
        window.location.href = searchResults[4].route + '?search=' + encodeURIComponent(query);
    } else {
        // Default to employees search
        window.location.href = searchResults[0].route + '?search=' + encodeURIComponent(query);
    }
}

// Close mobile sidebar on window resize to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) { // lg breakpoint
        const overlay = document.getElementById('mobile-sidebar-overlay');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
});

// Close sidebar on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const overlay = document.getElementById('mobile-sidebar-overlay');
        if (!overlay.classList.contains('hidden')) {
            toggleMobileSidebar();
        }
    }
});

// Profile dropdown functionality
function initializeProfileDropdown() {
    const button = document.getElementById('profile-dropdown-button');
    const menu = document.getElementById('profile-dropdown-menu');
    const arrow = document.getElementById('profile-dropdown-arrow');
    
    if (!button || !menu) return;
    
    let isOpen = false;
    
    function toggleDropdown() {
        isOpen = !isOpen;
        
        if (isOpen) {
            menu.classList.remove('hidden');
            setTimeout(() => {
                menu.classList.remove('opacity-0', 'scale-95');
                menu.classList.add('opacity-100', 'scale-100');
            }, 10);
            arrow.style.transform = 'rotate(180deg)';
        } else {
            menu.classList.remove('opacity-100', 'scale-100');
            menu.classList.add('opacity-0', 'scale-95');
            arrow.style.transform = 'rotate(0deg)';
            setTimeout(() => {
                menu.classList.add('hidden');
            }, 200);
        }
    }
    
    function closeDropdown() {
        if (isOpen) {
            toggleDropdown();
        }
    }
    
    // Toggle dropdown on button click
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleDropdown();
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!button.contains(e.target) && !menu.contains(e.target)) {
            closeDropdown();
        }
    });
    
    // Close dropdown on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDropdown();
        }
    });
}

// Initialize everything on load
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth transitions to all elements
    document.documentElement.style.setProperty('transition', 'color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out');
    
    // Initialize global search
    initializeGlobalSearch();
    
    // Initialize profile dropdown
    initializeProfileDropdown();
});
</script>

<style>
/* Hide desktop sidebar on mobile */
@media (max-width: 1023px) {
    .w-64.bg-card.border-r {
        display: none;
    }
}

/* Mobile sidebar slide animation */
#mobile-sidebar-overlay {
    transition: opacity 0.3s ease-in-out;
}

#mobile-sidebar-overlay.hidden {
    opacity: 0;
    pointer-events: none;
}

#mobile-sidebar-overlay:not(.hidden) {
    opacity: 1;
    pointer-events: auto;
}

/* Sidebar slide in animation */
#mobile-sidebar-overlay .w-64 {
    transform: translateX(-100%);
    transition: transform 0.3s ease-in-out;
}

#mobile-sidebar-overlay:not(.hidden) .w-64 {
    transform: translateX(0);
}

/* Smooth theme transitions for sidebar */
.sidebar {
    transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

/* Ensure full width content */
main > * {
    width: 100%;
}
</style>
@endsection
@props([
    'variant' => 'desktop', // desktop, mobile, mobile-bottom
    'items' => [],
    'currentRoute' => null
])

@php
use App\Services\NavigationService;
use App\Services\IconService;

$navigationService = app(NavigationService::class);
$currentRoute = $currentRoute ?? request()->route()->getName();

// Define navigation items based on roles/permissions
$desktopNavigation = [
    ['name' => 'Dashboard', 'icon' => IconService::getIcon('home', 'outline', '6'), 'route' => 'dashboard', 'type' => 'link'],
    ['name' => 'Manajemen', 'type' => 'section', 'children' => [
        ['name' => 'Karyawan', 'icon' => IconService::getIcon('users', 'outline', '6'), 'route' => 'employees.index', 'type' => 'link'],
        ['name' => 'Pengguna', 'icon' => IconService::getIcon('user-circle', 'outline', '6'), 'route' => 'users.index', 'type' => 'link'],
        ['name' => 'Lokasi', 'icon' => IconService::getIcon('map-pin', 'outline', '6'), 'route' => 'locations.index', 'type' => 'link'],
    ]],
    ['name' => 'Absensi', 'type' => 'section', 'children' => [
        ['name' => 'Check-in/out', 'icon' => IconService::getIcon('finger-print', 'outline', '6'), 'route' => 'attendance.check-in', 'type' => 'link'],
        ['name' => 'Riwayat', 'icon' => IconService::getIcon('clock', 'outline', '6'), 'route' => 'attendance.history', 'type' => 'link'],
        ['name' => 'Manajemen Absensi', 'icon' => IconService::getIcon('calendar-days', 'outline', '6'), 'route' => 'attendance.index', 'type' => 'link'],
    ]],
    ['name' => 'Cuti', 'type' => 'section', 'children' => [
        ['name' => 'Ajukan Cuti', 'icon' => IconService::getIcon('calendar-plus', 'outline', '6'), 'route' => 'leave.create', 'type' => 'link'],
        ['name' => 'Riwayat Cuti', 'icon' => IconService::getIcon('calendar', 'outline', '6'), 'route' => 'leave.index', 'type' => 'link'],
        ['name' => 'Persetujuan Cuti', 'icon' => IconService::getIcon('check-circle', 'outline', '6'), 'route' => 'leave.approvals.index', 'type' => 'link'],
    ]],
    ['name' => 'Penggajian', 'icon' => IconService::getIcon('currency-dollar', 'outline', '6'), 'route' => 'payroll.index', 'type' => 'link'],
    ['name' => 'Laporan', 'icon' => IconService::getIcon('chart-bar', 'outline', '6'), 'route' => 'reports.index', 'type' => 'link'],
    ['name' => 'Pengaturan', 'type' => 'section', 'children' => [
        ['name' => 'Sistem', 'icon' => IconService::getIcon('cog', 'outline', '6'), 'route' => 'system.settings', 'type' => 'link'],
        ['name' => 'Keamanan', 'icon' => IconService::getIcon('shield-check', 'outline', '6'), 'route' => 'security.dashboard', 'type' => 'link'],
        ['name' => 'Izin', 'icon' => IconService::getIcon('lock-closed', 'outline', '6'), 'route' => 'system.permissions', 'type' => 'link'],
    ]],
];

$mobileNavigation = [
    ['name' => 'Dashboard', 'icon' => IconService::getIcon('home', 'outline', '6'), 'route' => 'dashboard', 'type' => 'link'],
    ['name' => 'Absensi', 'icon' => IconService::getIcon('finger-print', 'outline', '6'), 'route' => 'attendance.check-in', 'type' => 'link'],
    ['name' => 'Cuti', 'icon' => IconService::getIcon('calendar', 'outline', '6'), 'route' => 'leave.index', 'type' => 'link'],
    ['name' => 'Penggajian', 'icon' => IconService::getIcon('currency-dollar', 'outline', '6'), 'route' => 'payroll.index', 'type' => 'link'],
    ['name' => 'Lainnya', 'icon' => IconService::getIcon('ellipsis-horizontal', 'outline', '6'), 'route' => 'profile.edit', 'type' => 'link'],
];


@endphp

@if($variant === 'desktop')
    <!-- Desktop Sidebar - Enhanced with Dominant Green Glassmorphism -->
    <nav class="h-full w-64 bg-gradient-to-b from-emerald-50 via-green-50 to-teal-50 dark:from-emerald-900 dark:via-green-900 dark:to-teal-900 border-r border-emerald-200/50 dark:border-emerald-700/50 backdrop-blur-lg flex flex-col"
         aria-label="Main navigation">
        
        <!-- Sidebar Content -->
        <div class="h-full flex flex-col overflow-y-auto">
            
            <!-- Brand Header -->
            <div class="flex items-center p-4 mb-6 bg-white/30 dark:bg-emerald-800/30 backdrop-blur-sm rounded-xl border border-emerald-200/40 dark:border-emerald-700/40 mx-3 mt-3">
                <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-lg font-bold text-emerald-800 dark:text-emerald-100">Attendance</p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400">Management System</p>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="flex-1 py-4 px-3">
                @foreach($desktopNavigation as $item)
                    @if($item['type'] === 'section')
                        <!-- Section with children -->
                        <div class="mb-6">
                            <h3 class="px-3 mb-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                {{ $item['name'] }}
                            </h3>
                            <div class="space-y-1">
                                @foreach($item['children'] as $child)
                                    @include('components.navigation.nav-item', [
                                        'item' => $child,
                                        'isActive' => $navigationService->isActiveRoute($child),
                                        'variant' => 'desktop'
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Single item -->
                        <div class="mb-1">
                            @include('components.navigation.nav-item', [
                                'item' => $item,
                                'isActive' => $navigationService->isActiveRoute($item),
                                'variant' => 'desktop'
                            ])
                        </div>
                    @endif
                @endforeach
            </div>
            
            <!-- Footer Section -->
            <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 shadow-sm">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 bg-emerald-600 rounded-md flex items-center justify-center shadow-sm">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sistem Aktif</span>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Status: Normal</p>
                    </div>
                </div>
            </div>
        </div>
    </nav>

@elseif($variant === 'mobile')
    <!-- Mobile Overlay Sidebar - Enhanced with Dominant Green Glassmorphism -->
    <div class="fixed inset-0 z-50 lg:hidden" 
         x-show="mobileNavOpen" 
         x-transition:enter="transition-all ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-all ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/50" 
             @click="mobileNavOpen = false"
             aria-hidden="true"></div>
        
        <!-- Sidebar -->
        <nav class="relative flex flex-col w-full max-w-sm h-full bg-white dark:bg-gray-800 shadow-lg border-r border-gray-200 dark:border-gray-700"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             aria-label="Mobile navigation">
            
            <!-- Content -->
            <div class="h-full flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center shadow-md">
                            <span class="text-white font-bold text-sm">A</span>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                AttendanceHub
                            </h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Mobile</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Theme Toggle -->
                        <x-ui.theme-toggle size="sm" />
                        
                        <!-- Close Button -->
                        <button @click="mobileNavOpen = false"
                                class="p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors duration-200"
                                aria-label="Close navigation">
                            {!! IconService::getIcon('x-mark', 'outline', '6') !!}
                        </button>
                    </div>
                </div>
                
                <!-- Navigation Menu -->
                <div class="flex-1 py-4 overflow-y-auto">
                    @foreach($desktopNavigation as $item)
                        @if($item['type'] === 'section')
                            <!-- Section with children -->
                            <div class="mb-6">
                                <h3 class="px-6 mb-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    {{ $item['name'] }}
                                </h3>
                                <div class="space-y-1">
                                    @foreach($item['children'] as $child)
                                        @include('components.navigation.nav-item', [
                                            'item' => $child,
                                            'isActive' => $navigationService->isActiveRoute($child),
                                            'variant' => 'mobile'
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- Single item -->
                            <div class="mb-1">
                                @include('components.navigation.nav-item', [
                                    'item' => $item,
                                    'isActive' => $navigationService->isActiveRoute($item),
                                    'variant' => 'mobile'
                                ])
                            </div>
                        @endif
                    @endforeach
                </div>
                
                <!-- Footer -->
                <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-center space-x-2">
                            <div class="w-6 h-6 bg-emerald-600 rounded-md flex items-center justify-center shadow-md">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Terhubung</span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>

@elseif($variant === 'mobile-bottom')
    <!-- Mobile Bottom Navigation - Enhanced with Dominant Green Glassmorphism -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 lg:hidden"
         aria-label="Mobile bottom navigation">
        
        <!-- Background -->
        <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            
            <!-- Navigation Items -->
            <div class="grid grid-cols-5 h-16 px-2 py-2">
                @foreach($mobileNavigation as $item)
                    @include('components.navigation.nav-item', [
                        'item' => $item,
                        'isActive' => $navigationService->isActiveRoute($item),
                        'variant' => 'mobile-bottom'
                    ])
                @endforeach
            </div>
            
            <!-- Safe Area -->
            <div class="h-safe-area-inset-bottom"></div>
        </div>
    </nav>
@endif
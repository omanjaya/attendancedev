@extends('layouts.app-unified')

@section('content')
    <div class="min-h-screen flex" x-data="{ mobileNavOpen: false }">
        <!-- Desktop Sidebar - Fixed Position -->
        <aside class="w-64 bg-white dark:bg-gray-800 shadow-sm border-r border-gray-200 dark:border-gray-700 hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:left-0 lg:z-50">
            <div class="flex flex-col h-full">

                <!-- Logo & Brand with Role Context -->
                <div class="flex items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">Attendance</p>
                        <div class="flex items-center space-x-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">System</p>
                            <span class="text-xs px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full font-medium">
                                {{ ucfirst(auth()->user()->roles->first()->name ?? 'User') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Role-Based Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-3 overflow-y-auto">
                    @foreach($desktopNavigation as $section)
                        @if($section['type'] === 'section')
                            <!-- Section with Children -->
                            <div class="space-y-2">
                                <!-- Section Header -->
                                <div class="px-3 py-2">
                                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $section['name'] }}
                                    </h3>
                                </div>
                                
                                <!-- Section Items -->
                                <div class="space-y-1">
                                    @foreach($section['children'] as $item)
                                        <a href="{{ route($item['route']) }}" 
                                           class="group flex items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 ease-in-out {{ request()->routeIs($item['route']) ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border-r-2 border-emerald-500' : '' }}">
                                            
                                            <!-- Icon with dynamic background -->
                                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shadow-sm transition-colors duration-200 {{ request()->routeIs($item['route']) ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 group-hover:bg-emerald-500 group-hover:text-white' }}">
                                                {!! \App\Services\IconService::getIcon($item['icon'], 'outline', '5') !!}
                                            </div>
                                            
                                            <!-- Menu Text -->
                                            <span class="ml-3 font-medium flex-1">{{ $item['name'] }}</span>
                                            
                                            <!-- Badge if exists -->
                                            @if(isset($item['badge']) && $item['badge'])
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ request()->routeIs($item['route']) ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                                    {{ $item['badge']['count'] ?? $item['badge'] }}
                                                </span>
                                            @endif
                                            
                                            <!-- Highlight indicator for important items -->
                                            @if(isset($item['highlight']) && $item['highlight'])
                                                <div class="w-2 h-2 bg-emerald-400 rounded-full"></div>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- Single Menu Item -->
                            <div class="space-y-1">
                                <a href="{{ route($section['route']) }}" 
                                   class="group flex items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 ease-in-out {{ request()->routeIs($section['route']) ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border-r-2 border-emerald-500' : '' }}">
                                    
                                    <!-- Icon -->
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center shadow-sm transition-colors duration-200 {{ request()->routeIs($section['route']) ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 group-hover:bg-emerald-500 group-hover:text-white' }}">
                                        {!! \App\Services\IconService::getIcon($section['icon'], 'outline', '5') !!}
                                    </div>
                                    
                                    <!-- Menu Text -->
                                    <span class="ml-3 font-medium flex-1">{{ $section['name'] }}</span>
                                    
                                    <!-- Badge -->
                                    @if(isset($section['badge']) && $section['badge'])
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ request()->routeIs($section['route']) ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                            {{ $section['badge']['count'] ?? $section['badge'] }}
                                        </span>
                                    @endif
                                    
                                    <!-- Highlight indicator -->
                                    @if(isset($section['highlight']) && $section['highlight'])
                                        <div class="w-2 h-2 bg-emerald-400 rounded-full"></div>
                                    @endif
                                </a>
                            </div>
                        @endif
                    @endforeach
                    
                    <!-- Role Information at Bottom -->
                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="px-3 py-2 text-center">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Logged in as</div>
                            <div class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                {{ ucfirst(auth()->user()->roles->first()->name ?? 'User') }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ auth()->user()->name }}
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- User Profile Section -->
                <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ substr(auth()->user()->name, 0, 2) }}</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->roles->first()->name ?? 'User' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div x-show="mobileNavOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 lg:hidden"
             @click="mobileNavOpen = false">
            
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
            
            <!-- Mobile sidebar -->
            <aside class="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                <div class="flex flex-col h-full">
                    
                    <!-- Mobile Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center shadow-sm">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Attendance</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">System</p>
                            </div>
                        </div>
                        <button @click="mobileNavOpen = false" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Mobile Navigation Menu (Consistent with Desktop) -->
                    <nav class="flex-1 px-4 py-6 space-y-3 overflow-y-auto">
                        @foreach($desktopNavigation as $section)
                            @if($section['type'] === 'section')
                                <!-- Mobile Section with Children -->
                                <div class="space-y-2">
                                    <!-- Section Header -->
                                    <div class="px-3 py-2">
                                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ $section['name'] }}
                                        </h3>
                                    </div>
                                    
                                    <!-- Section Items -->
                                    <div class="space-y-1">
                                        @foreach($section['children'] as $item)
                                            <a href="{{ route($item['route']) }}" 
                                               @click="mobileNavOpen = false"
                                               class="group flex items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 ease-in-out {{ request()->routeIs($item['route']) ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : '' }}">
                                                
                                                <!-- Icon -->
                                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shadow-sm transition-colors duration-200 {{ request()->routeIs($item['route']) ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 group-hover:bg-emerald-500 group-hover:text-white' }}">
                                                    {!! \App\Services\IconService::getIcon($item['icon'], 'outline', '5') !!}
                                                </div>
                                                
                                                <!-- Text -->
                                                <span class="ml-3 font-medium flex-1">{{ $item['name'] }}</span>
                                                
                                                <!-- Badge -->
                                                @if(isset($item['badge']) && $item['badge'])
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                        {{ $item['badge']['count'] ?? $item['badge'] }}
                                                    </span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <!-- Mobile Single Item -->
                                <div class="space-y-1">
                                    <a href="{{ route($section['route']) }}" 
                                       @click="mobileNavOpen = false"
                                       class="group flex items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 ease-in-out {{ request()->routeIs($section['route']) ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : '' }}">
                                        
                                        <!-- Icon -->
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shadow-sm transition-colors duration-200 {{ request()->routeIs($section['route']) ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 group-hover:bg-emerald-500 group-hover:text-white' }}">
                                            {!! \App\Services\IconService::getIcon($section['icon'], 'outline', '5') !!}
                                        </div>
                                        
                                        <!-- Text -->
                                        <span class="ml-3 font-medium flex-1">{{ $section['name'] }}</span>
                                        
                                        <!-- Badge -->
                                        @if(isset($section['badge']) && $section['badge'])
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                {{ $section['badge']['count'] ?? $section['badge'] }}
                                            </span>
                                        @endif
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </nav>

                    <!-- Mobile User Profile Section -->
                    <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ substr(auth()->user()->name, 0, 2) }}</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->roles->first()->name ?? 'User' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 lg:ml-64">
            <!-- Enhanced Desktop Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">

                        <!-- Left Section: Mobile Menu + Search -->
                        <div class="flex items-center space-x-3">
                            <!-- Mobile Menu Button -->
                            <button type="button" 
                                    @click="mobileNavOpen = true"
                                    class="inline-flex items-center p-2 text-gray-700 dark:text-gray-300 rounded-md lg:hidden hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>

                            <!-- Global Search -->
                            <div class="hidden md:block relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input type="text" 
                                       class="block w-80 pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-sm transition-colors duration-200" 
                                       placeholder="Cari karyawan, laporan, atau pengaturan..."
                                       x-data="{ searchOpen: false }"
                                       @focus="searchOpen = true"
                                       @blur="setTimeout(() => searchOpen = false, 200)">
                            </div>
                        </div>

                        <!-- Center Section: Page Title & Breadcrumbs -->
                        <div class="flex-1 flex items-center justify-center lg:justify-start lg:ml-6">
                            <div class="text-center lg:text-left">
                                <h1 class="text-xl font-bold text-gray-900 dark:text-white">@yield('title', 'Dashboard')</h1>
                                <div class="hidden lg:block">
                                    <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                        <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Dashboard</a>
                                        @hasSection('breadcrumbs')
                                            @yield('breadcrumbs')
                                        @endif
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <!-- Right Section: Actions & User Menu -->
                        <div class="flex items-center space-x-2">

                            <!-- Quick Search (Mobile) -->
                            <button class="md:hidden p-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>

                            <!-- Attendance Clock In/Out -->
                            @if(!auth()->user()->hasRole('superadmin'))
                            <div class="hidden sm:block">
                                <button class="inline-flex items-center px-3 py-2 border border-emerald-300 dark:border-emerald-600 rounded-md text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Check In
                                </button>
                            </div>
                            @endif

                            <!-- Notifications with Dropdown -->
                            <div class="relative" x-data="{ notificationOpen: false }">
                                <button @click="notificationOpen = !notificationOpen"
                                        class="relative p-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5v5zm-7.5-1A1.5 1.5 0 016 16.5v-1.5m0 0a4.5 4.5 0 104.5-4.5V15"/>
                                    </svg>
                                    <span class="absolute -top-1 -right-1 block h-3 w-3 rounded-full bg-red-500 border-2 border-white dark:border-gray-800"></span>
                                </button>
                                
                                <!-- Notification Dropdown -->
                                <div x-show="notificationOpen" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     @click.away="notificationOpen = false"
                                     class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                                    <div class="p-4">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Notifikasi Terbaru</h3>
                                        <div class="space-y-3">
                                            <div class="flex items-start space-x-3 p-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                                <div>
                                                    <p class="text-sm text-gray-900 dark:text-white">Laporan kehadiran bulan ini sudah tersedia</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">2 menit yang lalu</p>
                                                </div>
                                            </div>
                                            <div class="flex items-start space-x-3 p-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                                                <div>
                                                    <p class="text-sm text-gray-900 dark:text-white">5 karyawan baru telah check-in</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">15 menit yang lalu</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <a href="#" class="text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">Lihat semua notifikasi</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Settings -->
                            <button class="p-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors duration-200"
                                    title="Pengaturan">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>

                            <!-- Theme Toggle -->
                            <x-ui.theme-toggle size="sm" />

                            <!-- User Menu with Dropdown -->
                            <div class="relative" x-data="{ userMenuOpen: false }">
                                <button @click="userMenuOpen = !userMenuOpen"
                                        class="flex items-center p-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors duration-200">
                                    <div class="w-8 h-8 bg-emerald-600 rounded-full flex items-center justify-center mr-2">
                                        <span class="text-sm font-medium text-white">{{ substr(auth()->user()->name, 0, 2) }}</span>
                                    </div>
                                    <div class="hidden sm:block text-left mr-2">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->roles->first()->name ?? 'User' }}</div>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                
                                <!-- User Dropdown -->
                                <div x-show="userMenuOpen" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     @click.away="userMenuOpen = false"
                                     class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                                    <div class="py-1">
                                        <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Profil Saya
                                        </a>
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Kehadiran Saya
                                        </a>
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            Cuti & Izin
                                        </a>
                                        <div class="border-t border-gray-200 dark:border-gray-700"></div>
                                        @if(auth()->user()->hasRole('superadmin'))
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            </svg>
                                            Switch Role
                                        </a>
                                        @endif
                                        <div class="border-t border-gray-200 dark:border-gray-700"></div>
                                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                            @csrf
                                            <button type="submit" class="flex items-center w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                                </svg>
                                                Keluar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6">
                <div class="px-4 sm:px-6 lg:px-8">
                    <!-- Role Switch Status (if active) -->
                    @if(Session::has('original_role'))
                        <div class="mb-6 p-3 bg-amber-100 dark:bg-amber-900 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800 flex items-center justify-between text-amber-800 dark:text-amber-200">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <span class="font-medium">Mode Pengalih Peran Aktif: {{ ucfirst(Auth::user()->roles->first()->name ?? 'Unknown') }}</span>
                            </div>
                            <form method="POST" action="{{ route('role.restore') }}" class="inline">
                                @csrf
                                <x-ui.button type="submit" variant="warning" size="sm">
                                    Kembali ke {{ ucfirst(Session::get('original_role')) }}
                                </x-ui.button>
                            </form>
                        </div>
                    @endif

                    @yield('page-content')
                </div>
            </main>
            
            <!-- Notification Component -->
            <x-notification />
        </div>

        <!-- Mobile Bottom Navigation -->
        <x-navigation.unified-nav variant="mobile-bottom" />
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @stack('scripts')
    
    <!-- Role Switch JavaScript -->
    @if(Auth::user()->hasRole('superadmin'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleList = document.getElementById('roleList');

            function loadRoles() {
                fetch(`{{ route('role.available') }}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    roleList.innerHTML = '';
                    
                    data.roles.forEach(role => {
                        const roleItem = document.createElement('div');
                        roleItem.className = 'flex items-center justify-between p-3 hover:bg-white/10 rounded-lg transition-colors';
                        
                        const colorClasses = {
                            'superadmin': 'from-purple-500 to-purple-600',
                            'admin': 'from-blue-500 to-blue-600',
                            'teacher': 'from-emerald-500 to-emerald-600',
                            'staff': 'from-gray-500 to-gray-600'
                        };
                        
                        const iconBg = colorClasses[role.name] || 'from-gray-500 to-gray-600';
                        
                        roleItem.innerHTML = `
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br ${iconBg} rounded-lg flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${role.icon}"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-800 dark:text-white">${role.display_name}</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">${role.description}</p>
                                </div>
                            </div>
                            <x-ui.button onclick="switchRole('${role.name}')" variant="primary" size="sm">
                                Alihkan
                            </x-ui.button>
                        `;
                        
                        roleList.appendChild(roleItem);
                    });
                })
                .catch(error => {
                    console.error('Error loading roles:', error);
                    roleList.innerHTML = '<div class="text-center py-4 text-red-500 text-sm">Error loading roles</div>';
                });
            }

            // Load roles when the dropdown is shown
            document.addEventListener('click', function(e) {
                if (e.target.closest('[title="Switch Role"]')) {
                    loadRoles();
                }
            });
        });

        function handleLogout(formId) {
            const form = document.getElementById(formId);
            if (!form) {
                console.error('Logout form not found:', formId);
                return;
            }
            
            const submitBtn = form.querySelector('button');
            if (submitBtn.disabled) {
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Mengeluarkan...';
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            
            if (window.authCheckInterval) {
                clearInterval(window.authCheckInterval);
            }
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (response.ok || response.redirected) {
                    localStorage.clear();
                    sessionStorage.clear();
                    
                    if ('caches' in window) {
                        caches.keys().then(names => {
                            names.forEach(name => {
                                caches.delete(name);
                            });
                        });
                    }
                    
                    window.location.href = '{{ route("login") }}';
                } else {
                    throw new Error('Logout failed');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = '{{ route("login") }}';
            });
        }
        
        function switchRole(roleName) {
            if (!confirm(`Apakah Anda yakin ingin switch ke role ${roleName}?`)) {
                return;
            }
            
            const roleList = document.getElementById('roleList');
            if (roleList) {
                roleList.innerHTML = `
                    <div class="text-center py-4">
                        <div class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">Switching role...</p>
                    </div>
                `;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('role-switch/switch') }}/${roleName}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                csrfToken.value = csrfMeta.getAttribute('content');
            } else {
                alert('CSRF token not found. Please refresh the page.');
                return;
            }
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            let lastAuthCheck = Date.now();
            
            window.authCheckInterval = setInterval(function() {
                fetch('{{ route("profile.edit") }}', {
                    method: 'HEAD',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .catch(error => {
                    console.log('Auth check failed, redirecting to login');
                    window.location.href = '{{ route("login") }}';
                });
            }, 300000);
            
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && Date.now() - lastAuthCheck > 300000) {
                    fetch('{{ route("profile.edit") }}', {
                        method: 'HEAD',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    })
                    .catch(error => {
                        console.log('Auth expired, redirecting to login');
                        window.location.href = '{{ route("login") }}';
                    });
                    lastAuthCheck = Date.now();
                }
            });
            
            document.addEventListener('click', function(e) {
                if (e.target.closest('button[onclick*="handleLogout"]')) {
                    e.stopPropagation();
                }
                
                if (e.target.closest('button[onclick*="switchRole"]')) {
                    e.stopPropagation();
                }
            });
            
            document.addEventListener('submit', function(e) {
                if (e.target.closest('form[id*="logout"]')) {
                    e.preventDefault();
                    const formId = e.target.id;
                    handleLogout(formId);
                }
            });
        });
    </script>

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => console.log('SW registered'))
                .catch(error => console.log('SW registration failed'));
        }
    </script>
    @endif
@endsection

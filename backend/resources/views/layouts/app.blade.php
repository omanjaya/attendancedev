<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#10b981">
    
    <title>@yield('title', config('app.name', 'AttendanceHub'))</title>
    
    <!-- SEO and Social -->
    <meta name="description" content="@yield('description', 'Modern attendance management system with face recognition')">
    <meta name="author" content="{{ config('app.name') }}">
    
    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    
    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    
    <!-- Alpine.js with Persist Plugin -->
    <script>
        // Initialize Alpine persist before Alpine loads
        document.addEventListener('alpine:init', () => {
            if (!window.Alpine.plugin) return;
            // Simple persist implementation if plugin not available
            if (!Alpine.magic('persist')) {
                Alpine.magic('persist', () => {
                    return (key) => {
                        return {
                            get() {
                                return localStorage.getItem(`_x_${key}`) || 'system';
                            },
                            set(value) {
                                localStorage.setItem(`_x_${key}`, value);
                            }
                        };
                    };
                });
            }
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Additional styles -->
    @stack('styles')
    
    <!-- Additional head content -->
    @stack('head')
    
    <!-- Theme Script (Prevent FOUC) -->
    <script>
        // Prevent flash of unstyled content for theme
        (function() {
            const theme = localStorage.getItem('theme') || 
                         (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', theme === 'dark');
        })();
    </script>
</head>
<body class="h-full font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100" 
      x-data="{ 
          theme: localStorage.getItem('_x_theme') || 'system',
          loading: false,
          notifications: []
      }"
      x-init="
          // Initialize theme system
          $nextTick(() => {
              // Watch theme changes and save to localStorage
              $watch('theme', value => {
                  localStorage.setItem('_x_theme', value);
                  if (value === 'system') {
                      value = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                  }
                  document.documentElement.classList.toggle('dark', value === 'dark');
              });
              
              // Initial theme setup
              let initialTheme = theme;
              if (initialTheme === 'system') {
                  initialTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
              }
              document.documentElement.classList.toggle('dark', initialTheme === 'dark');
              
              // Listen for system theme changes
              window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                  if (theme === 'system') {
                      document.documentElement.classList.toggle('dark', e.matches);
                  }
              });
          });
      ">
    
    <!-- Skip to content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-primary text-primary-foreground px-4 py-2 rounded-md shadow-sm z-50">
        Skip to main content
    </a>
    
    {{-- Global Loading Overlay --}}
    <div x-show="loading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-200 dark:bg-gray-800 flex items-center justify-center z-50"
         style="display: none;">
        <div class="flex flex-col items-center space-y-4">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-emerald-500 border-t-transparent"></div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Loading...</p>
        </div>
    </div>
    
    {{-- App Content --}}
    <div id="app" class="min-h-screen">
        <main id="main-content" class="min-h-screen">
            @yield('content')
        </main>
    </div>
    
    {{-- Notification Container --}}
    <div id="notification-container" 
         class="fixed top-4 right-4 z-50 space-y-2 max-w-sm w-full pointer-events-none">
        {{-- Notifications will be inserted here --}}
    </div>
    
    {{-- Toast Container --}}
    <div id="toast-container"
         class="fixed bottom-4 right-4 z-50 space-y-2 max-w-sm w-full pointer-events-none">
        {{-- Toasts will be inserted here --}}
    </div>
    
    {{-- jQuery (required for some components) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    {{-- JavaScript Stack --}}
    @stack('scripts')
    
    {{-- Global JavaScript --}}
    <script>
        // Global error handler
        window.addEventListener('error', (e) => {
            console.error('Global error:', e.error);
        });
        
        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', (e) => {
            console.error('Unhandled promise rejection:', e.reason);
        });
        
        // Global keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Cmd/Ctrl + K for search
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                // Open search modal
                const searchModal = document.getElementById('search-modal');
                if (searchModal) {
                    searchModal.style.display = 'block';
                }
            }
            
            // ESC to close modals/overlays
            if (e.key === 'Escape') {
                // Close any open dropdowns
                document.querySelectorAll('[x-data]').forEach(el => {
                    if (el.__x && el.__x.$data.open) {
                        el.__x.$data.open = false;
                    }
                });
            }
        });
        
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('SW registered'))
                    .catch(error => console.log('SW registration failed'));
            });
        }
    </script>
</body>
</html>
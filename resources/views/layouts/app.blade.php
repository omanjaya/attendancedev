<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $htmlClass ?? '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', config('app.description', 'Modern Attendance Management System'))">
    <meta http-equiv="x-dns-prefetch-control" content="off">
    
    <title>@yield('title', config('app.name', 'AttendanceHub')) - {{ config('app.name', 'AttendanceHub') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Core Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Page Specific Styles -->
    @stack('styles')
    
    <!-- Theme Variables -->
    <style>
        :root {
            /* Modern Color System */
            --color-primary: 99 102 241;
            --color-primary-dark: 79 70 229;
            --color-secondary: 147 51 234;
            --color-success: 34 197 94;
            --color-warning: 251 146 60;
            --color-danger: 239 68 68;
            --color-info: 59 130 246;
            
            /* Neutral Colors */
            --color-gray-50: 249 250 251;
            --color-gray-100: 243 244 246;
            --color-gray-200: 229 231 235;
            --color-gray-300: 209 213 219;
            --color-gray-400: 156 163 175;
            --color-gray-500: 107 114 128;
            --color-gray-600: 75 85 99;
            --color-gray-700: 55 65 81;
            --color-gray-800: 31 41 55;
            --color-gray-900: 17 24 39;
            
            /* Spacing */
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-full: 9999px;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Z-index Scale */
            --z-dropdown: 1000;
            --z-sticky: 1020;
            --z-fixed: 1030;
            --z-modal-backdrop: 1040;
            --z-modal: 1050;
            --z-popover: 1060;
            --z-tooltip: 1070;
        }
        
        /* Dark mode variables */
        .dark {
            --color-gray-50: 17 24 39;
            --color-gray-100: 31 41 55;
            --color-gray-200: 55 65 81;
            --color-gray-300: 75 85 99;
            --color-gray-400: 107 114 128;
            --color-gray-500: 156 163 175;
            --color-gray-600: 209 213 219;
            --color-gray-700: 229 231 235;
            --color-gray-800: 243 244 246;
            --color-gray-900: 249 250 251;
        }
    </style>
</head>
<body class="@yield('body_class', 'bg-background text-foreground antialiased font-sans')">
    <!-- Skip to content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-white px-4 py-2 rounded-md shadow-lg">
        Skip to main content
    </a>
    
    <!-- App Container -->
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Header/Navigation -->
        @hasSection('navigation')
            @yield('navigation')
        @endif
        
        <!-- Main Content -->
        <main id="main-content" class="flex-1">
            @yield('content')
        </main>
        
        <!-- Footer -->
        @hasSection('footer')
            @yield('footer')
        @endif
    </div>
    
    <!-- Toast Notification Container -->
    <x-ui.toast-container position="top-right" />
    
    <!-- Core Scripts loaded via Vite above -->
    
    <!-- jQuery (required for some legacy components) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- Page Specific Scripts -->
    @stack('scripts')
    
    <!-- Inline Scripts -->
    <script>
        // Global app configuration
        window.App = {
            csrfToken: '{{ csrf_token() }}',
            locale: '{{ app()->getLocale() }}',
            user: @json(auth()->user() ? auth()->user()->only(['id', 'name', 'email']) : null),
            routes: {
                home: '{{ route('dashboard') }}',
            }
        };
    </script>

    <!-- Flash Messages -->
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof showNotification === 'function') {
                    showNotification('{{ session('success') }}', 'success');
                } else if (typeof window.notify !== 'undefined') {
                    window.notify.success('{{ session('success') }}');
                } else {
                    // Fallback alert
                    setTimeout(() => alert('Success: {{ session('success') }}'), 100);
                }
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof showNotification === 'function') {
                    showNotification('{{ session('error') }}', 'error');
                } else if (typeof window.notify !== 'undefined') {
                    window.notify.error('{{ session('error') }}');
                } else {
                    // Fallback alert
                    setTimeout(() => alert('Error: {{ session('error') }}'), 100);
                }
            });
        </script>
    @endif

    @if(session('warning'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof showNotification === 'function') {
                    showNotification('{{ session('warning') }}', 'warning');
                } else if (typeof window.notify !== 'undefined') {
                    window.notify.warning('{{ session('warning') }}');
                } else {
                    // Fallback alert
                    setTimeout(() => alert('Warning: {{ session('warning') }}'), 100);
                }
            });
        </script>
    @endif

    @if(session('info'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof showNotification === 'function') {
                    showNotification('{{ session('info') }}', 'info');
                } else if (typeof window.notify !== 'undefined') {
                    window.notify.info('{{ session('info') }}');
                } else {
                    // Fallback alert
                    setTimeout(() => alert('Info: {{ session('info') }}'), 100);
                }
            });
        </script>
    @endif

</body>
</html>
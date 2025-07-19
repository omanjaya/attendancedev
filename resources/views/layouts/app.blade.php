<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', config('app.description', 'Modern Attendance Management System'))">
    <meta http-equiv="x-dns-prefetch-control" content="off">
    
    <title>@yield('title', config('app.name', 'AttendanceHub')) - {{ config('app.name', 'AttendanceHub') }}</title>
    
    <!-- Prevent FOUC (Flash of Unstyled Content) -->
    <script>
        // Theme initialization - must run before body renders
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.add(theme);
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    
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
    
    <!-- Error Tracking Configuration -->
    @include('partials.error-tracking-config')
</head>
<body class="h-full bg-background text-foreground antialiased font-sans transition-colors duration-200">
    <!-- Skip to content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-white px-4 py-2 rounded-md shadow-lg">
        Skip to main content
    </a>
    
    <!-- App Container -->
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Main Content -->
        <main id="main-content" class="flex-1">
            @yield('content')
        </main>
    </div>
    
    <!-- Toast Notification Container -->
    <x-ui.toast-container position="top-right" />
    
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
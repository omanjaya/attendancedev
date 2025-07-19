<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AttendanceSystem') }}</title>

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
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <!-- Header with Theme Toggle -->
            <div class="absolute top-6 right-6">
                <x-ui.theme-toggle />
            </div>

            <!-- Logo and Brand -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-600 rounded-lg shadow-md mb-4">
                    <span class="text-2xl font-bold text-white">AS</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">AttendanceSystem</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">School Management Platform</p>
            </div>

            <!-- Main Content Card -->
            <div class="w-full sm:max-w-md">
                <x-ui.card class="p-8 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    {{ $slot }}
                </x-ui.card>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>&copy; {{ date('Y') }} AttendanceSystem. All rights reserved.</p>
            </div>
        </div>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AttendanceSystem') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-background">
            <!-- Header with Theme Toggle -->
            <div class="absolute top-6 right-6">
                <x-ui.theme-toggle />
            </div>

            <!-- Logo and Brand -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-primary to-success rounded-2xl shadow-lg mb-4">
                    <span class="text-2xl font-bold text-primary-foreground">AS</span>
                </div>
                <h1 class="text-2xl font-bold text-foreground">AttendanceSystem</h1>
                <p class="text-muted-foreground mt-1">School Management Platform</p>
            </div>

            <!-- Main Content Card -->
            <div class="w-full sm:max-w-md">
                <x-ui.card class="p-8">
                    {{ $slot }}
                </x-ui.card>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-muted-foreground">
                <p>&copy; {{ date('Y') }} AttendanceSystem. All rights reserved.</p>
            </div>
        </div>
    </body>
</html>

@props([
    'title' => 'Page',
    'subtitle' => null,
    'showBackground' => true,
    'showWelcome' => false,
    'welcomeTitle' => null,
    'welcomeSubtitle' => null,
])





<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    @if($showWelcome)
        <!-- Welcome Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 px-6 py-4 sm:px-8 sm:py-6">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-1 sm:mb-2 text-gray-900 dark:text-gray-100">{{ $welcomeTitle ?? $title }}</h1>
            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">{{ $welcomeSubtitle ?? $subtitle }}</p>
        </div>
    @endif

    <!-- Page Content -->
    <div class="relative">
        {{ $slot }}
    </div>
</div>
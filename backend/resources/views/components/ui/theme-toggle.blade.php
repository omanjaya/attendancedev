@props([
    'size' => 'md',
    'variant' => 'default'
])

@php
$sizeClasses = [
    'sm' => 'w-8 h-8',
    'md' => 'w-10 h-10',
    'lg' => 'w-12 h-12'
];

$buttonClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }}>
    <button
        type="button"
        class="{{ $buttonClass }} inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors duration-200"
        onclick="toggleTheme()"
        aria-label="Toggle theme"
        title="Toggle light/dark theme"
    >
        <!-- Light mode icon (visible in dark mode) -->
        <svg 
            class="w-5 h-5 hidden dark:block" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
        </svg>
        
        <!-- Dark mode icon (visible in light mode) -->
        <svg 
            class="w-5 h-5 block dark:hidden" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
        </svg>
    </button>
</div>

<script>
// Use the centralized theme toggle system
function toggleTheme() {
    if (window.themeToggle) {
        window.themeToggle.toggle();
    } else {
        // Fallback if theme-toggle.js hasn't loaded yet
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        html.classList.remove('light', 'dark');
        html.classList.add(newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('theme-changed', {
            detail: { theme: newTheme, activeTheme: newTheme }
        }));
    }
}
</script>
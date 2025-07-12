@props([
    'title' => 'Page',
    'subtitle' => null,
    'showBackground' => true,
    'showWelcome' => false,
    'welcomeTitle' => null,
    'welcomeSubtitle' => null,
])

@push('styles')
    <style>
        /* Glassmorphism Animations */
        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }
            33% {
                transform: translate(30px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }
        
        .animate-blob {
            animation: blob 7s infinite;
        }
        
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        
        /* Enhanced Glassmorphism */
        .backdrop-blur-xl {
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
        }
        
        /* Better glass effect on hover */
        .hover\:bg-white\/40:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }
        
        .dark .hover\:bg-gray-800\/40:hover {
            background-color: rgba(31, 41, 55, 0.4);
        }
        
        /* Enhanced shadow effects */
        .hover\:shadow-emerald-500\/25:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25);
        }
        
        .hover\:shadow-emerald-500\/20:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.2);
        }
        
        .hover\:shadow-emerald-500\/30:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.3);
        }
        
        /* Glass border animation */
        .group:hover .border-white\/20 {
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .dark .group:hover .border-gray-700\/30 {
            border-color: rgba(55, 65, 81, 0.4);
        }
        
        /* Responsive spacing improvements */
        @media (max-width: 640px) {
            .space-y-6 > :not([hidden]) ~ :not([hidden]) {
                --tw-space-y-reverse: 0;
                margin-top: calc(1rem * calc(1 - var(--tw-space-y-reverse)));
                margin-bottom: calc(1rem * var(--tw-space-y-reverse));
            }
            
            .gap-3 {
                gap: 0.75rem;
            }
        }
    </style>
@endpush

@if($showBackground)
    <!-- Glassmorphism Background -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/40 via-green-50/30 to-teal-50/40 dark:from-gray-900/80 dark:via-gray-800/70 dark:to-gray-900/80"></div>
        <div class="absolute top-20 left-20 w-96 h-96 bg-emerald-400/20 dark:bg-emerald-400/30 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-40 right-20 w-96 h-96 bg-green-400/20 dark:bg-green-400/30 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-20 left-40 w-96 h-96 bg-teal-400/20 dark:bg-teal-400/30 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    </div>
@endif

<div class="p-4 sm:p-6 space-y-6 sm:space-y-8">
    @if($showWelcome)
        <!-- Glassmorphism Welcome Section -->
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/80 to-green-600/80 dark:from-emerald-600/95 dark:to-green-700/95 rounded-2xl sm:rounded-3xl backdrop-blur-xl border border-white/20 dark:border-gray-600/40"></div>
            <div class="relative px-6 py-4 sm:px-8 sm:py-6 text-white">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-1 sm:mb-2 drop-shadow-lg">{{ $welcomeTitle ?? $title }}</h1>
                <p class="text-sm sm:text-base text-emerald-100 drop-shadow">{{ $welcomeSubtitle ?? $subtitle }}</p>
            </div>
        </div>
    @endif

    <!-- Page Content -->
    <div class="relative">
        {{ $slot }}
    </div>
</div>
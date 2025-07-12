@props([
    'title' => '',
    'subtitle' => null,
    'showBack' => false,
    'backUrl' => null,
    'actions' => null,
    'searchable' => false,
    'searchPlaceholder' => 'Search...',
    'variant' => 'default', // default, transparent, colored
    'sticky' => true
])

@php
    // Variant classes
    $variantClasses = [
        'default' => 'bg-background/95 border-b border-border',
        'transparent' => 'bg-transparent',
        'colored' => 'bg-primary text-primary-foreground'
    ];
    
    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
    
    $containerClass = $sticky ? 'sticky top-0 z-40' : '';
    $containerClass .= ' ' . $variantClass;
    
    if ($variant !== 'transparent') {
        $containerClass .= ' backdrop-blur-sm';
    }
@endphp

<header class="{{ $containerClass }} md:hidden" 
        x-data="mobileHeader()"
        x-init="init()">
    
    <!-- Main Header -->
    <div class="flex items-center justify-between px-4 py-3 min-h-[56px]">
        <!-- Left Section -->
        <div class="flex items-center space-x-3 min-w-0 flex-1">
            @if($showBack)
            <!-- Back Button -->
            <button @click="goBack()"
                    class="flex items-center justify-center w-10 h-10 rounded-full 
                           hover:bg-muted/50 active:bg-muted transition-colors touch-manipulation"
                    type="button"
                    aria-label="Go back">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            @endif
            
            <!-- Title Section -->
            <div class="min-w-0 flex-1">
                <h1 class="text-lg font-semibold text-foreground truncate">
                    {{ $title }}
                </h1>
                @if($subtitle)
                <p class="text-xs text-muted-foreground truncate">
                    {{ $subtitle }}
                </p>
                @endif
            </div>
        </div>
        
        <!-- Right Section -->
        <div class="flex items-center space-x-2">
            @if($searchable)
            <!-- Search Toggle -->
            <button @click="toggleSearch()"
                    class="flex items-center justify-center w-10 h-10 rounded-full 
                           hover:bg-muted/50 active:bg-muted transition-colors touch-manipulation"
                    type="button"
                    aria-label="Search">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
            @endif
            
            <!-- Custom Actions -->
            @if($actions)
            {{ $actions }}
            @endif
            
            <!-- Menu Button -->
            <button @click="toggleMenu()"
                    class="flex items-center justify-center w-10 h-10 rounded-full 
                           hover:bg-muted/50 active:bg-muted transition-colors touch-manipulation"
                    type="button"
                    aria-label="Menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Search Bar (Hidden by default) -->
    @if($searchable)
    <div x-show="showSearch" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="px-4 pb-3">
        
        <div class="relative">
            <input type="text"
                   x-model="searchQuery"
                   x-ref="searchInput"
                   @keydown.escape="closeSearch()"
                   @input="handleSearch()"
                   placeholder="{{ $searchPlaceholder }}"
                   class="w-full pl-10 pr-10 py-2 text-sm 
                          bg-muted border border-border rounded-lg
                          focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent
                          placeholder:text-muted-foreground">
            
            <!-- Search Icon -->
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <!-- Clear Button -->
            <button x-show="searchQuery.length > 0"
                    @click="clearSearch()"
                    class="absolute inset-y-0 right-0 flex items-center pr-3"
                    type="button">
                <svg class="w-4 h-4 text-muted-foreground hover:text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    @endif
    
    <!-- Safe Area Top Padding for iOS -->
    <div class="h-safe-area-inset-top bg-transparent"></div>
</header>

<!-- Overlay for menu/search -->
<div x-show="showMenu" 
     @click="closeMenu()"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/20 z-30 md:hidden">
</div>

<style>
.h-safe-area-inset-top {
    height: env(safe-area-inset-top);
}

/* Improve header backdrop */
.mobile-header-backdrop {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Status bar handling for iOS */
@supports (padding-top: env(safe-area-inset-top)) {
    .mobile-header {
        padding-top: env(safe-area-inset-top);
    }
}
</style>

<script>
function mobileHeader() {
    return {
        showSearch: false,
        showMenu: false,
        searchQuery: '',
        
        init() {
            // Handle safe area on iOS
            this.handleSafeArea();
            
            // Listen for escape key globally
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeSearch();
                    this.closeMenu();
                }
            });
        },
        
        toggleSearch() {
            this.showSearch = !this.showSearch;
            if (this.showSearch) {
                // Focus search input after transition
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
            }
        },
        
        closeSearch() {
            this.showSearch = false;
            this.searchQuery = '';
        },
        
        clearSearch() {
            this.searchQuery = '';
            this.$refs.searchInput?.focus();
        },
        
        handleSearch() {
            // Debounced search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.$dispatch('mobile-search', { 
                    query: this.searchQuery 
                });
            }, 300);
        },
        
        toggleMenu() {
            this.showMenu = !this.showMenu;
            
            // Prevent body scroll when menu is open
            if (this.showMenu) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },
        
        closeMenu() {
            this.showMenu = false;
            document.body.style.overflow = '';
        },
        
        goBack() {
            // Try to go back in history, fallback to home
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/dashboard';
            }
        },
        
        handleSafeArea() {
            const safeAreaTop = getComputedStyle(document.documentElement)
                .getPropertyValue('env(safe-area-inset-top)');
            
            if (safeAreaTop && safeAreaTop !== '0px') {
                this.$el.style.paddingTop = safeAreaTop;
            }
        }
    };
}
</script>
@props([
    'enableSkipLinks' => true,
    'enableFocusIndicators' => true,
    'enableKeyboardNavigation' => true,
    'enableScreenReader' => true,
    'enableHighContrast' => true,
    'enableReducedMotion' => true,
    'enableAnnouncements' => true,
    'language' => 'en'
])

<!-- Skip Links -->
@if($enableSkipLinks)
<div class="skip-links">
    <a href="#main-content" 
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 
              bg-primary text-primary-foreground px-4 py-2 rounded-md z-[9999]
              focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
        Skip to main content
    </a>
    <a href="#main-navigation" 
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-32
              bg-primary text-primary-foreground px-4 py-2 rounded-md z-[9999]
              focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
        Skip to navigation
    </a>
</div>
@endif

<!-- Accessibility Controls -->
<div class="accessibility-controls fixed top-4 right-16 z-[9998]"
     x-data="accessibilityEnhancer({
        enableFocusIndicators: {{ $enableFocusIndicators ? 'true' : 'false' }},
        enableKeyboardNavigation: {{ $enableKeyboardNavigation ? 'true' : 'false' }},
        enableScreenReader: {{ $enableScreenReader ? 'true' : 'false' }},
        enableHighContrast: {{ $enableHighContrast ? 'true' : 'false' }},
        enableReducedMotion: {{ $enableReducedMotion ? 'true' : 'false' }},
        enableAnnouncements: {{ $enableAnnouncements ? 'true' : 'false' }},
        language: '{{ $language }}'
     })"
     x-init="init()">
     
    <!-- Accessibility Menu Toggle -->
    <button class="accessibility-toggle p-2 rounded-md bg-background border border-border shadow-md
                   hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
            @click="toggleMenu()"
            :aria-expanded="menuOpen"
            aria-label="Accessibility options">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
        </svg>
    </button>
    
    <!-- Accessibility Menu -->
    <div x-show="menuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute top-12 right-0 w-80 bg-background border border-border rounded-lg shadow-lg p-4 space-y-4"
         @click.away="menuOpen = false">
        
        <h3 class="text-lg font-semibold mb-4">Accessibility Options</h3>
        
        <!-- High Contrast Toggle -->
        @if($enableHighContrast)
        <div class="flex items-center justify-between">
            <label for="high-contrast" class="text-sm font-medium">High Contrast</label>
            <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                           focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    :class="highContrast ? 'bg-primary' : 'bg-muted'"
                    @click="toggleHighContrast()"
                    role="switch"
                    :aria-checked="highContrast"
                    id="high-contrast">
                <span class="inline-block h-4 w-4 transform rounded-full bg-background transition-transform"
                      :class="highContrast ? 'translate-x-6' : 'translate-x-1'">
                </span>
            </button>
        </div>
        @endif
        
        <!-- Reduced Motion Toggle -->
        @if($enableReducedMotion)
        <div class="flex items-center justify-between">
            <label for="reduced-motion" class="text-sm font-medium">Reduce Motion</label>
            <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                           focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    :class="reducedMotion ? 'bg-primary' : 'bg-muted'"
                    @click="toggleReducedMotion()"
                    role="switch"
                    :aria-checked="reducedMotion"
                    id="reduced-motion">
                <span class="inline-block h-4 w-4 transform rounded-full bg-background transition-transform"
                      :class="reducedMotion ? 'translate-x-6' : 'translate-x-1'">
                </span>
            </button>
        </div>
        @endif
        
        <!-- Font Size Control -->
        <div class="space-y-2">
            <label class="text-sm font-medium">Font Size</label>
            <div class="flex items-center space-x-2">
                <button class="px-3 py-1 text-sm border border-border rounded hover:bg-muted
                               focus:outline-none focus:ring-2 focus:ring-ring"
                        @click="adjustFontSize(-1)"
                        aria-label="Decrease font size">
                    A-
                </button>
                <span class="text-sm text-muted-foreground" x-text="fontSizeLabel"></span>
                <button class="px-3 py-1 text-sm border border-border rounded hover:bg-muted
                               focus:outline-none focus:ring-2 focus:ring-ring"
                        @click="adjustFontSize(1)"
                        aria-label="Increase font size">
                    A+
                </button>
            </div>
        </div>
        
        <!-- Focus Indicators Toggle -->
        @if($enableFocusIndicators)
        <div class="flex items-center justify-between">
            <label for="focus-indicators" class="text-sm font-medium">Enhanced Focus</label>
            <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                           focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    :class="enhancedFocus ? 'bg-primary' : 'bg-muted'"
                    @click="toggleEnhancedFocus()"
                    role="switch"
                    :aria-checked="enhancedFocus"
                    id="focus-indicators">
                <span class="inline-block h-4 w-4 transform rounded-full bg-background transition-transform"
                      :class="enhancedFocus ? 'translate-x-6' : 'translate-x-1'">
                </span>
            </button>
        </div>
        @endif
        
        <!-- Screen Reader Announcements -->
        @if($enableAnnouncements)
        <div class="flex items-center justify-between">
            <label for="announcements" class="text-sm font-medium">Announcements</label>
            <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                           focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    :class="announcements ? 'bg-primary' : 'bg-muted'"
                    @click="toggleAnnouncements()"
                    role="switch"
                    :aria-checked="announcements"
                    id="announcements">
                <span class="inline-block h-4 w-4 transform rounded-full bg-background transition-transform"
                      :class="announcements ? 'translate-x-6' : 'translate-x-1'">
                </span>
            </button>
        </div>
        @endif
        
        <!-- Reset Button -->
        <div class="pt-2 border-t border-border">
            <button class="w-full px-4 py-2 text-sm bg-secondary text-secondary-foreground rounded-md
                           hover:bg-secondary/80 focus:outline-none focus:ring-2 focus:ring-ring"
                    @click="resetToDefaults()">
                Reset to Defaults
            </button>
        </div>
    </div>
</div>

<!-- Live Region for Announcements -->
@if($enableAnnouncements)
<div aria-live="polite" 
     aria-atomic="true" 
     class="sr-only" 
     id="accessibility-announcements"
     x-ref="announcements">
</div>

<div aria-live="assertive" 
     aria-atomic="true" 
     class="sr-only" 
     id="accessibility-alerts"
     x-ref="alerts">
</div>
@endif

<script>
function accessibilityEnhancer(config) {
    return {
        enableFocusIndicators: config.enableFocusIndicators,
        enableKeyboardNavigation: config.enableKeyboardNavigation,
        enableScreenReader: config.enableScreenReader,
        enableHighContrast: config.enableHighContrast,
        enableReducedMotion: config.enableReducedMotion,
        enableAnnouncements: config.enableAnnouncements,
        language: config.language,
        
        menuOpen: false,
        highContrast: false,
        reducedMotion: false,
        enhancedFocus: false,
        announcements: true,
        fontSize: 0, // -2 to +2
        
        get fontSizeLabel() {
            const labels = ['Very Small', 'Small', 'Normal', 'Large', 'Very Large'];
            return labels[this.fontSize + 2] || 'Normal';
        },
        
        init() {
            this.loadPreferences();
            this.setupKeyboardNavigation();
            this.setupFocusManagement();
            this.detectSystemPreferences();
            this.announcePageLoad();
        },
        
        loadPreferences() {
            try {
                const saved = localStorage.getItem('accessibility-preferences');
                if (saved) {
                    const prefs = JSON.parse(saved);
                    this.highContrast = prefs.highContrast || false;
                    this.reducedMotion = prefs.reducedMotion || false;
                    this.enhancedFocus = prefs.enhancedFocus || false;
                    this.announcements = prefs.announcements !== false;
                    this.fontSize = prefs.fontSize || 0;
                    
                    this.applyPreferences();
                }
            } catch (error) {
                console.warn('Failed to load accessibility preferences:', error);
            }
        },
        
        savePreferences() {
            try {
                const prefs = {
                    highContrast: this.highContrast,
                    reducedMotion: this.reducedMotion,
                    enhancedFocus: this.enhancedFocus,
                    announcements: this.announcements,
                    fontSize: this.fontSize
                };
                localStorage.setItem('accessibility-preferences', JSON.stringify(prefs));
            } catch (error) {
                console.warn('Failed to save accessibility preferences:', error);
            }
        },
        
        applyPreferences() {
            this.applyHighContrast();
            this.applyReducedMotion();
            this.applyEnhancedFocus();
            this.applyFontSize();
        },
        
        detectSystemPreferences() {
            // Detect system preference for reduced motion
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.reducedMotion = true;
                this.applyReducedMotion();
            }
            
            // Detect system preference for high contrast
            if (window.matchMedia('(prefers-contrast: high)').matches) {
                this.highContrast = true;
                this.applyHighContrast();
            }
        },
        
        setupKeyboardNavigation() {
            if (!this.enableKeyboardNavigation) return;
            
            document.addEventListener('keydown', (event) => {
                // Alt + A: Toggle accessibility menu
                if (event.altKey && event.key === 'a') {
                    event.preventDefault();
                    this.toggleMenu();
                }
                
                // Alt + H: Toggle high contrast
                if (event.altKey && event.key === 'h') {
                    event.preventDefault();
                    this.toggleHighContrast();
                }
                
                // Alt + M: Toggle reduced motion
                if (event.altKey && event.key === 'm') {
                    event.preventDefault();
                    this.toggleReducedMotion();
                }
                
                // Alt + F: Toggle enhanced focus
                if (event.altKey && event.key === 'f') {
                    event.preventDefault();
                    this.toggleEnhancedFocus();
                }
                
                // Alt + Plus: Increase font size
                if (event.altKey && (event.key === '+' || event.key === '=')) {
                    event.preventDefault();
                    this.adjustFontSize(1);
                }
                
                // Alt + Minus: Decrease font size
                if (event.altKey && event.key === '-') {
                    event.preventDefault();
                    this.adjustFontSize(-1);
                }
                
                // Escape: Close accessibility menu
                if (event.key === 'Escape' && this.menuOpen) {
                    this.menuOpen = false;
                }
            });
        },
        
        setupFocusManagement() {
            if (!this.enableFocusIndicators) return;
            
            // Track focus changes for screen reader announcements
            document.addEventListener('focusin', (event) => {
                const element = event.target;
                
                // Announce focused element to screen readers
                if (this.announcements && element.getAttribute('aria-label')) {
                    this.announce(element.getAttribute('aria-label'));
                }
            });
            
            // Handle focus trapping in modals
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Tab') {
                    const modal = document.querySelector('[role="dialog"][open], .modal:not(.hidden)');
                    if (modal) {
                        this.trapFocus(event, modal);
                    }
                }
            });
        },
        
        trapFocus(event, container) {
            const focusableElements = container.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            
            const firstFocusable = focusableElements[0];
            const lastFocusable = focusableElements[focusableElements.length - 1];
            
            if (event.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    event.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    event.preventDefault();
                    firstFocusable.focus();
                }
            }
        },
        
        toggleMenu() {
            this.menuOpen = !this.menuOpen;
            
            if (this.menuOpen) {
                this.announce('Accessibility menu opened');
            } else {
                this.announce('Accessibility menu closed');
            }
        },
        
        toggleHighContrast() {
            this.highContrast = !this.highContrast;
            this.applyHighContrast();
            this.savePreferences();
            
            this.announce(this.highContrast ? 'High contrast enabled' : 'High contrast disabled');
        },
        
        applyHighContrast() {
            if (this.highContrast) {
                document.documentElement.classList.add('high-contrast');
            } else {
                document.documentElement.classList.remove('high-contrast');
            }
        },
        
        toggleReducedMotion() {
            this.reducedMotion = !this.reducedMotion;
            this.applyReducedMotion();
            this.savePreferences();
            
            this.announce(this.reducedMotion ? 'Reduced motion enabled' : 'Reduced motion disabled');
        },
        
        applyReducedMotion() {
            if (this.reducedMotion) {
                document.documentElement.classList.add('reduce-motion');
            } else {
                document.documentElement.classList.remove('reduce-motion');
            }
        },
        
        toggleEnhancedFocus() {
            this.enhancedFocus = !this.enhancedFocus;
            this.applyEnhancedFocus();
            this.savePreferences();
            
            this.announce(this.enhancedFocus ? 'Enhanced focus enabled' : 'Enhanced focus disabled');
        },
        
        applyEnhancedFocus() {
            if (this.enhancedFocus) {
                document.documentElement.classList.add('enhanced-focus');
            } else {
                document.documentElement.classList.remove('enhanced-focus');
            }
        },
        
        toggleAnnouncements() {
            this.announcements = !this.announcements;
            this.savePreferences();
            
            if (this.announcements) {
                this.announce('Screen reader announcements enabled');
            }
        },
        
        adjustFontSize(delta) {
            const newSize = Math.max(-2, Math.min(2, this.fontSize + delta));
            if (newSize !== this.fontSize) {
                this.fontSize = newSize;
                this.applyFontSize();
                this.savePreferences();
                
                this.announce(`Font size changed to ${this.fontSizeLabel}`);
            }
        },
        
        applyFontSize() {
            const sizes = ['text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl'];
            const currentClass = sizes[this.fontSize + 2] || 'text-base';
            
            // Remove existing font size classes
            document.documentElement.classList.remove(...sizes);
            document.documentElement.classList.add(currentClass);
            
            // Also apply as CSS custom property for more control
            const scale = 1 + (this.fontSize * 0.125); // 12.5% per step
            document.documentElement.style.setProperty('--font-scale', scale);
        },
        
        resetToDefaults() {
            this.highContrast = false;
            this.reducedMotion = false;
            this.enhancedFocus = false;
            this.announcements = true;
            this.fontSize = 0;
            
            this.applyPreferences();
            this.savePreferences();
            
            this.announce('Accessibility settings reset to defaults');
        },
        
        announce(message, priority = 'polite') {
            if (!this.announcements) return;
            
            const region = priority === 'assertive' ? 
                this.$refs.alerts : this.$refs.announcements;
            
            if (region) {
                region.textContent = message;
                
                // Clear after 5 seconds to avoid buildup
                setTimeout(() => {
                    region.textContent = '';
                }, 5000);
            }
        },
        
        announcePageLoad() {
            if (this.announcements) {
                setTimeout(() => {
                    const title = document.title;
                    const heading = document.querySelector('h1');
                    
                    if (heading) {
                        this.announce(`Page loaded: ${title}. Main heading: ${heading.textContent}`);
                    } else {
                        this.announce(`Page loaded: ${title}`);
                    }
                }, 1000);
            }
        }
    };
}

// Global accessibility API
window.accessibility = {
    announce: (message, priority = 'polite') => {
        const event = new CustomEvent('accessibility-announce', {
            detail: { message, priority }
        });
        window.dispatchEvent(event);
    },
    
    focus: (selector) => {
        const element = document.querySelector(selector);
        if (element) {
            element.focus();
            return true;
        }
        return false;
    },
    
    skipTo: (selector) => {
        const element = document.querySelector(selector);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            element.focus();
            return true;
        }
        return false;
    }
};

// Listen for announcement events
window.addEventListener('accessibility-announce', (event) => {
    const { message, priority } = event.detail;
    
    const region = document.getElementById(
        priority === 'assertive' ? 'accessibility-alerts' : 'accessibility-announcements'
    );
    
    if (region) {
        region.textContent = message;
        setTimeout(() => {
            region.textContent = '';
        }, 5000);
    }
});
</script>

<style>
/* High contrast mode styles */
.high-contrast {
    --background: #000000;
    --foreground: #ffffff;
    --card: #111111;
    --card-foreground: #ffffff;
    --popover: #111111;
    --popover-foreground: #ffffff;
    --primary: #ffffff;
    --primary-foreground: #000000;
    --secondary: #333333;
    --secondary-foreground: #ffffff;
    --muted: #222222;
    --muted-foreground: #cccccc;
    --accent: #444444;
    --accent-foreground: #ffffff;
    --destructive: #ff0000;
    --destructive-foreground: #ffffff;
    --border: #444444;
    --input: #333333;
    --ring: #ffffff;
}

.high-contrast img {
    filter: contrast(1.5) brightness(1.2);
}

.high-contrast .shadow-lg {
    box-shadow: 0 0 0 2px #ffffff;
}

/* Reduced motion styles */
.reduce-motion *,
.reduce-motion *::before,
.reduce-motion *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
}

/* Enhanced focus styles */
.enhanced-focus *:focus {
    outline: 3px solid #0066cc !important;
    outline-offset: 2px !important;
    box-shadow: 0 0 0 5px rgba(0, 102, 204, 0.3) !important;
}

.enhanced-focus button:focus,
.enhanced-focus a:focus,
.enhanced-focus input:focus,
.enhanced-focus select:focus,
.enhanced-focus textarea:focus {
    transform: scale(1.05);
    z-index: 1000;
    position: relative;
}

/* Font scaling */
html {
    font-size: calc(1rem * var(--font-scale, 1));
}

/* Skip links */
.skip-links a:focus {
    position: fixed !important;
    top: 8px !important;
    left: 8px !important;
    z-index: 9999 !important;
}

/* Screen reader only content */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

.focus\:not-sr-only:focus {
    position: static;
    width: auto;
    height: auto;
    padding: inherit;
    margin: inherit;
    overflow: visible;
    clip: auto;
    white-space: normal;
}

/* Keyboard navigation indicators */
.keyboard-navigation *:focus {
    outline: 2px solid var(--ring);
    outline-offset: 2px;
}

/* Accessibility menu animations */
.accessibility-controls .transition {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Print accessibility */
@media print {
    .accessibility-controls,
    .skip-links {
        display: none !important;
    }
}

/* Improve link visibility */
.high-contrast a {
    text-decoration: underline;
    font-weight: bold;
}

.high-contrast a:hover {
    background-color: #ffffff;
    color: #000000;
}
</style>
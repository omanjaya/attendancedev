/**
 * Theme Toggle Implementation
 * Following Shadcn/UI dark mode patterns
 * Supports light and dark modes only
 */

class ThemeToggle {
    constructor() {
        this.storageKey = 'attendance-theme';
        this.themes = ['light', 'dark'];
        this.init();
    }

    init() {
        // Get stored theme or default to light
        const storedTheme = localStorage.getItem(this.storageKey);
        const theme = storedTheme || 'light';
        
        this.setTheme(theme, false);
        this.bindEvents();
    }

    setTheme(theme, store = true) {
        if (store) {
            localStorage.setItem(this.storageKey, theme);
        }

        const root = document.documentElement;
        
        // Remove existing theme classes
        root.classList.remove('light', 'dark');
        
        // Add the selected theme
        root.classList.add(theme);

        // Update meta theme-color for mobile browsers
        this.updateThemeColor(theme);
        
        // Dispatch theme change event
        window.dispatchEvent(new CustomEvent('theme-changed', { 
            detail: { theme, activeTheme: theme } 
        }));
    }

    getActiveTheme() {
        return localStorage.getItem(this.storageKey) || 'light';
    }

    getCurrentTheme() {
        return localStorage.getItem(this.storageKey) || 'light';
    }

    toggle() {
        const currentTheme = this.getCurrentTheme();
        const currentIndex = this.themes.indexOf(currentTheme);
        const nextIndex = (currentIndex + 1) % this.themes.length;
        const nextTheme = this.themes[nextIndex];
        
        this.setTheme(nextTheme);
        return nextTheme;
    }

    updateThemeColor(theme) {
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            // Use CSS variables for theme colors
            const color = theme === 'dark' ? 'hsl(240 10% 3.9%)' : 'hsl(0 0% 100%)';
            metaThemeColor.setAttribute('content', color);
        }
    }


    bindEvents() {
        // Listen for toggle button clicks
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-theme-toggle]') || e.target.closest('[data-theme-toggle]')) {
                e.preventDefault();
                this.toggle();
            }
        });

        // Listen for keyboard shortcuts (Ctrl+Shift+L)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                e.preventDefault();
                this.toggle();
            }
        });
    }

    // Helper method to get theme icon
    getThemeIcon(theme = null) {
        const currentTheme = theme || this.getCurrentTheme();
        
        const icons = {
            light: `<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>`,
            dark: `<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>`
        };
        
        return icons[currentTheme] || icons.light;
    }

    // Helper method to get theme label
    getThemeLabel(theme = null) {
        const currentTheme = theme || this.getCurrentTheme();
        const labels = {
            light: 'Light',
            dark: 'Dark'
        };
        return labels[currentTheme] || 'Light';
    }
}

// Initialize theme toggle
window.themeToggle = new ThemeToggle();

// Export for Alpine.js integration
window.getThemeToggleData = () => ({
    theme: window.themeToggle.getCurrentTheme(),
    activeTheme: window.themeToggle.getActiveTheme(),
    
    toggle() {
        const newTheme = window.themeToggle.toggle();
        this.theme = newTheme;
        this.activeTheme = window.themeToggle.getActiveTheme();
    },
    
    setTheme(theme) {
        window.themeToggle.setTheme(theme);
        this.theme = theme;
        this.activeTheme = window.themeToggle.getActiveTheme();
    },
    
    getIcon() {
        return window.themeToggle.getThemeIcon(this.theme);
    },
    
    getLabel() {
        return window.themeToggle.getThemeLabel(this.theme);
    }
});

// Listen for theme changes and update Alpine.js data
window.addEventListener('theme-changed', (e) => {
    // Trigger Alpine.js reactivity if available
    if (window.Alpine) {
        window.Alpine.nextTick(() => {
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el._x_dataStack && el._x_dataStack[0].theme !== undefined) {
                    el._x_dataStack[0].theme = e.detail.theme;
                    el._x_dataStack[0].activeTheme = e.detail.activeTheme;
                }
            });
        });
    }
});

// Theme toggle initialized and ready
/**
 * Dark Mode Management for Gadget Garage
 * Handles theme switching, persistence, and initialization
 */

class DarkModeManager {
    constructor() {
        this.storageKey = 'gadget-garage-theme';
        this.currentTheme = this.getStoredTheme();
        this.toggleButton = null;
        this.init();
    }

    /**
     * Initialize dark mode system
     */
    init() {
        // Apply stored theme immediately to prevent flash
        this.applyTheme(this.currentTheme);

        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupToggle());
        } else {
            this.setupToggle();
        }

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.getStoredTheme() === 'auto') {
                    this.applyTheme('auto');
                }
            });
        }
    }

    /**
     * Get stored theme preference
     */
    getStoredTheme() {
        try {
            return localStorage.getItem(this.storageKey) || 'light';
        } catch (error) {
            console.warn('Could not access localStorage for theme preference');
            return 'light';
        }
    }

    /**
     * Store theme preference
     */
    setStoredTheme(theme) {
        try {
            localStorage.setItem(this.storageKey, theme);
        } catch (error) {
            console.warn('Could not save theme preference to localStorage');
        }
    }

    /**
     * Get system theme preference
     */
    getSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    /**
     * Apply theme to document
     */
    applyTheme(theme) {
        let actualTheme = theme;

        if (theme === 'auto') {
            actualTheme = this.getSystemTheme();
        }

        // Remove existing theme classes
        document.documentElement.removeAttribute('data-theme');

        // Apply new theme
        if (actualTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        this.currentTheme = theme;
        this.updateToggleUI();

        // Dispatch theme change event
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme: theme, actualTheme: actualTheme }
        }));
    }

    /**
     * Setup toggle button functionality
     */
    setupToggle() {
        this.toggleButton = document.getElementById('themeToggle');

        if (this.toggleButton) {
            this.toggleButton.addEventListener('click', () => this.toggle());
            this.updateToggleUI();
        }

        // Setup any additional toggle buttons
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.addEventListener('click', () => this.toggle());
        });
    }

    /**
     * Toggle between light and dark themes
     */
    toggle() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.setStoredTheme(newTheme);
        this.applyTheme(newTheme);

        // Add visual feedback
        this.addToggleAnimation();
    }

    /**
     * Set specific theme
     */
    setTheme(theme) {
        if (['light', 'dark', 'auto'].includes(theme)) {
            this.setStoredTheme(theme);
            this.applyTheme(theme);
        }
    }

    /**
     * Update toggle UI to match current theme
     */
    updateToggleUI() {
        if (!this.toggleButton) return;

        const isDark = this.currentTheme === 'dark' ||
                      (this.currentTheme === 'auto' && this.getSystemTheme() === 'dark');

        if (isDark) {
            this.toggleButton.classList.add('active');
        } else {
            this.toggleButton.classList.remove('active');
        }

        // Update aria label for accessibility
        const label = isDark ? 'Switch to light mode' : 'Switch to dark mode';
        this.toggleButton.setAttribute('aria-label', label);
    }

    /**
     * Add animation feedback when toggling
     */
    addToggleAnimation() {
        if (!this.toggleButton) return;

        // Add pulse animation
        this.toggleButton.style.transform = 'scale(1.1)';

        setTimeout(() => {
            this.toggleButton.style.transform = 'scale(1)';
        }, 150);

        // Add brief highlight
        const slider = this.toggleButton.querySelector('.toggle-slider');
        if (slider) {
            slider.style.boxShadow = '0 0 20px rgba(102, 126, 234, 0.5)';

            setTimeout(() => {
                slider.style.boxShadow = '';
            }, 300);
        }
    }

    /**
     * Get current theme information
     */
    getCurrentTheme() {
        const actualTheme = this.currentTheme === 'auto' ? this.getSystemTheme() : this.currentTheme;

        return {
            stored: this.currentTheme,
            actual: actualTheme,
            isDark: actualTheme === 'dark'
        };
    }

    /**
     * Initialize theme for specific page elements that need special handling
     */
    initializePageElements() {
        // Handle images that need different versions for light/dark
        this.updateImageSources();

        // Handle charts or graphics that need theme updates
        this.updateCharts();

        // Handle any custom components
        this.updateCustomComponents();
    }

    /**
     * Update image sources for theme
     */
    updateImageSources() {
        const themeImages = document.querySelectorAll('[data-light-src][data-dark-src]');
        const isDark = this.getCurrentTheme().isDark;

        themeImages.forEach(img => {
            const lightSrc = img.getAttribute('data-light-src');
            const darkSrc = img.getAttribute('data-dark-src');

            if (isDark && darkSrc) {
                img.src = darkSrc;
            } else if (!isDark && lightSrc) {
                img.src = lightSrc;
            }
        });
    }

    /**
     * Update charts for theme (if any chart libraries are used)
     */
    updateCharts() {
        // Placeholder for chart updates
        window.dispatchEvent(new CustomEvent('chartThemeUpdate', {
            detail: this.getCurrentTheme()
        }));
    }

    /**
     * Update custom components for theme
     */
    updateCustomComponents() {
        // Handle custom dropdowns
        const customDropdowns = document.querySelectorAll('.custom-dropdown');
        customDropdowns.forEach(dropdown => {
            dropdown.classList.toggle('dark-mode', this.getCurrentTheme().isDark);
        });

        // Handle loading overlays
        const loadingOverlays = document.querySelectorAll('.loading-overlay');
        loadingOverlays.forEach(overlay => {
            overlay.classList.toggle('dark-mode', this.getCurrentTheme().isDark);
        });
    }

    /**
     * Add theme transition class temporarily
     */
    enableTransitions() {
        document.body.classList.add('theme-transition');

        setTimeout(() => {
            document.body.classList.remove('theme-transition');
        }, 300);
    }

    /**
     * Utility method to check if dark mode is active
     */
    isDarkMode() {
        return this.getCurrentTheme().isDark;
    }

    /**
     * Utility method for components to register theme change callbacks
     */
    onThemeChange(callback) {
        window.addEventListener('themeChanged', callback);
    }

    /**
     * Remove theme change listener
     */
    offThemeChange(callback) {
        window.removeEventListener('themeChanged', callback);
    }
}

// Global theme manager instance
window.darkModeManager = new DarkModeManager();

// Global functions for easy access
window.toggleTheme = () => window.darkModeManager.toggle();
window.setTheme = (theme) => window.darkModeManager.setTheme(theme);
window.getCurrentTheme = () => window.darkModeManager.getCurrentTheme();
window.isDarkMode = () => window.darkModeManager.isDarkMode();

// Auto-initialize page elements when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.darkModeManager.initializePageElements();
});

// Listen for theme changes and update page elements
window.addEventListener('themeChanged', () => {
    window.darkModeManager.initializePageElements();
});

// Export for module systems if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DarkModeManager;
}
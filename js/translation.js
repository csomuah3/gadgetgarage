/**
 * Comprehensive Translation System for Gadget Garage
 * Automatically translates all text content across all pages
 */

// Translation data - loaded from PHP
let translationData = {};
let currentLanguage = 'en';

// Initialize translation system
document.addEventListener('DOMContentLoaded', function() {
    loadTranslations();
    setupLanguageSwitcher();
    translatePage();
});

/**
 * Load translation data from server
 */
async function loadTranslations() {
    try {
        const response = await fetch('includes/get_translations.php');
        if (response.ok) {
            const data = await response.json();
            translationData = data.translations;
            currentLanguage = data.current_language;
        }
    } catch (error) {
        console.error('Failed to load translations:', error);
    }
}

/**
 * Set up language switcher functionality
 */
function setupLanguageSwitcher() {
    // Language buttons in header
    const languageButtons = document.querySelectorAll('[data-lang]');
    languageButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = this.getAttribute('data-lang');
            if (lang && lang !== currentLanguage) {
                changeLanguage(lang);
            }
        });
    });

    // Language dropdown if exists
    const langSelect = document.getElementById('language-select');
    if (langSelect) {
        langSelect.addEventListener('change', function() {
            changeLanguage(this.value);
        });
    }
}

/**
 * Change language and translate entire page
 */
async function changeLanguage(newLanguage) {
    if (!translationData[newLanguage]) {
        console.error('Language not supported:', newLanguage);
        return;
    }

    try {
        // Update server session
        const response = await fetch('actions/change_language_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'language=' + encodeURIComponent(newLanguage)
        });

        if (response.ok) {
            currentLanguage = newLanguage;
            translatePage();
            updateLanguageDisplay();
        }
    } catch (error) {
        console.error('Failed to change language:', error);
    }
}

/**
 * Translate the entire page
 */
function translatePage() {
    if (!translationData[currentLanguage]) {
        console.warn('Translation data not available for:', currentLanguage);
        return;
    }

    // Translate elements with data-translate attribute
    translateElementsWithAttribute();

    // Translate specific form elements
    translateFormElements();

    // Translate page title
    translatePageTitle();

    // Translate meta tags
    translateMetaTags();

    // Translate breadcrumbs
    translateBreadcrumbs();

    // Translate buttons and links
    translateButtonsAndLinks();

    // Translate table headers
    translateTableHeaders();

    // Translate common text patterns
    translateCommonText();

    // Translate admin panel if present
    translateAdminPanel();

    // Translate error/success messages
    translateMessages();

    console.log('Page translated to:', currentLanguage);
}

/**
 * Translate elements with data-translate attribute
 */
function translateElementsWithAttribute() {
    const elements = document.querySelectorAll('[data-translate]');
    elements.forEach(element => {
        const key = element.getAttribute('data-translate');
        const translation = getTranslation(key);
        if (translation) {
            if (element.tagName === 'INPUT' && (element.type === 'submit' || element.type === 'button')) {
                element.value = translation;
            } else {
                element.innerHTML = translation;
            }
        }
    });
}

/**
 * Translate form elements
 */
function translateFormElements() {
    // Placeholder text
    const inputs = document.querySelectorAll('input[placeholder], textarea[placeholder]');
    inputs.forEach(input => {
        const placeholder = input.getAttribute('placeholder');
        if (placeholder) {
            const key = getPlaceholderKey(placeholder);
            const translation = getTranslation(key);
            if (translation) {
                input.setAttribute('placeholder', translation);
            }
        }
    });

    // Labels
    const labels = document.querySelectorAll('label');
    labels.forEach(label => {
        const text = label.textContent.trim();
        if (text) {
            const key = getTextKey(text);
            const translation = getTranslation(key);
            if (translation) {
                label.textContent = translation;
            }
        }
    });

    // Submit buttons
    const submitButtons = document.querySelectorAll('input[type="submit"], button[type="submit"]');
    submitButtons.forEach(button => {
        const text = button.value || button.textContent;
        if (text) {
            const key = getTextKey(text.trim());
            const translation = getTranslation(key);
            if (translation) {
                if (button.value) {
                    button.value = translation;
                } else {
                    button.textContent = translation;
                }
            }
        }
    });
}

/**
 * Translate page title
 */
function translatePageTitle() {
    const title = document.title;
    const key = getTitleKey(title);
    const translation = getTranslation(key);
    if (translation) {
        document.title = translation;
    }
}

/**
 * Translate meta tags
 */
function translateMetaTags() {
    const metaDescription = document.querySelector('meta[name="description"]');
    if (metaDescription) {
        const translation = getTranslation('meta_description');
        if (translation) {
            metaDescription.setAttribute('content', translation);
        }
    }

    const metaKeywords = document.querySelector('meta[name="keywords"]');
    if (metaKeywords) {
        const translation = getTranslation('meta_keywords');
        if (translation) {
            metaKeywords.setAttribute('content', translation);
        }
    }
}

/**
 * Translate breadcrumbs
 */
function translateBreadcrumbs() {
    const breadcrumbs = document.querySelectorAll('.breadcrumb a, .breadcrumb span');
    breadcrumbs.forEach(breadcrumb => {
        const text = breadcrumb.textContent.trim();
        if (text) {
            const key = getTextKey(text);
            const translation = getTranslation(key);
            if (translation) {
                breadcrumb.textContent = translation;
            }
        }
    });
}

/**
 * Translate buttons and links
 */
function translateButtonsAndLinks() {
    const buttons = document.querySelectorAll('button:not([data-translate]), .btn:not([data-translate])');
    buttons.forEach(button => {
        const text = button.textContent.trim();
        if (text && !button.querySelector('i')) { // Skip buttons with icons only
            const key = getTextKey(text);
            const translation = getTranslation(key);
            if (translation) {
                button.textContent = translation;
            }
        }
    });

    const links = document.querySelectorAll('a:not([data-translate])');
    links.forEach(link => {
        const text = link.textContent.trim();
        if (text && !link.querySelector('img') && !link.querySelector('i')) {
            const key = getTextKey(text);
            const translation = getTranslation(key);
            if (translation) {
                link.textContent = translation;
            }
        }
    });
}

/**
 * Translate table headers
 */
function translateTableHeaders() {
    const tableHeaders = document.querySelectorAll('th');
    tableHeaders.forEach(th => {
        const text = th.textContent.trim();
        if (text) {
            const key = getTextKey(text);
            const translation = getTranslation(key);
            if (translation) {
                th.textContent = translation;
            }
        }
    });
}

/**
 * Translate common text patterns
 */
function translateCommonText() {
    // Headings
    const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
    headings.forEach(heading => {
        if (!heading.hasAttribute('data-translate') && !heading.querySelector('[data-translate]')) {
            const text = heading.textContent.trim();
            if (text) {
                const key = getTextKey(text);
                const translation = getTranslation(key);
                if (translation) {
                    heading.textContent = translation;
                }
            }
        }
    });

    // Paragraphs with specific keywords
    const paragraphs = document.querySelectorAll('p');
    paragraphs.forEach(p => {
        if (!p.hasAttribute('data-translate') && !p.querySelector('[data-translate]')) {
            const text = p.textContent.trim();
            if (text && isTranslatableParagraph(text)) {
                const key = getTextKey(text);
                const translation = getTranslation(key);
                if (translation) {
                    p.textContent = translation;
                }
            }
        }
    });
}

/**
 * Translate admin panel specific elements
 */
function translateAdminPanel() {
    if (window.location.pathname.includes('/admin/')) {
        // Sidebar navigation
        const navLinks = document.querySelectorAll('.sidebar a, .nav-link');
        navLinks.forEach(link => {
            const text = link.textContent.trim();
            if (text) {
                const key = getTextKey(text);
                const translation = getTranslation(key);
                if (translation) {
                    link.textContent = translation;
                }
            }
        });

        // Admin form labels and buttons
        const adminElements = document.querySelectorAll('.admin-panel label, .admin-panel button, .admin-panel .btn');
        adminElements.forEach(element => {
            const text = element.textContent.trim();
            if (text) {
                const key = getTextKey(text);
                const translation = getTranslation(key);
                if (translation) {
                    element.textContent = translation;
                }
            }
        });
    }
}

/**
 * Translate error and success messages
 */
function translateMessages() {
    const messageElements = document.querySelectorAll('.alert, .message, .error, .success, .warning, .info');
    messageElements.forEach(element => {
        const text = element.textContent.trim();
        if (text) {
            const key = getTextKey(text);
            const translation = getTranslation(key);
            if (translation) {
                element.textContent = translation;
            }
        }
    });
}

/**
 * Update language display
 */
function updateLanguageDisplay() {
    const languageButtons = document.querySelectorAll('[data-lang]');
    languageButtons.forEach(button => {
        const lang = button.getAttribute('data-lang');
        if (lang === currentLanguage) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });

    const langSelect = document.getElementById('language-select');
    if (langSelect) {
        langSelect.value = currentLanguage;
    }
}

/**
 * Get translation for a key
 */
function getTranslation(key) {
    if (!key || !translationData[currentLanguage]) {
        return null;
    }
    return translationData[currentLanguage][key] || translationData['en'][key] || null;
}

/**
 * Convert placeholder text to translation key
 */
function getPlaceholderKey(placeholder) {
    const keyMap = {
        'Search phones, laptops, cameras...': 'search_placeholder',
        'Email': 'email',
        'Password': 'password',
        'First Name': 'first_name',
        'Last Name': 'last_name',
        'Phone': 'phone',
        'Address': 'address',
        'City': 'city',
        'Country': 'country'
    };

    return keyMap[placeholder] || placeholder.toLowerCase().replace(/[^a-z0-9]+/g, '_');
}

/**
 * Convert text to translation key
 */
function getTextKey(text) {
    // Common text mappings
    const keyMap = {
        'Home': 'home',
        'Shop': 'shop',
        'Login': 'login',
        'Register': 'register',
        'Logout': 'logout',
        'Cart': 'view_cart',
        'Checkout': 'checkout',
        'Add to Cart': 'add_to_cart',
        'Buy Now': 'buy_now',
        'Contact': 'contact',
        'About': 'about_us',
        'Dashboard': 'dashboard',
        'Products': 'products',
        'Categories': 'categories',
        'Brands': 'brands',
        'Orders': 'orders',
        'Settings': 'settings',
        'Save': 'save',
        'Cancel': 'cancel',
        'Edit': 'edit',
        'Delete': 'delete',
        'Add': 'add',
        'Update': 'update',
        'Search': 'search',
        'Filter': 'filter',
        'Sort': 'sort',
        'Apply': 'apply',
        'Reset': 'reset',
        'Submit': 'submit',
        'Back': 'back',
        'Next': 'next',
        'Previous': 'previous',
        'Close': 'close',
        'Welcome': 'welcome',
        'Price': 'price',
        'Quantity': 'quantity',
        'Total': 'total',
        'Subtotal': 'subtotal',
        'Loading...': 'loading',
        'Error': 'error',
        'Success': 'success',
        'Warning': 'warning',
        'Information': 'info'
    };

    return keyMap[text] || text.toLowerCase().replace(/[^a-z0-9]+/g, '_');
}

/**
 * Get title key for page title
 */
function getTitleKey(title) {
    if (title.includes('Gadget Garage')) {
        return 'gadget_garage_title';
    }
    return 'gadget_garage';
}

/**
 * Check if paragraph should be translated
 */
function isTranslatableParagraph(text) {
    const keywords = [
        'welcome', 'quality', 'guarantee', 'shipping', 'support', 'payment',
        'latest', 'best', 'new', 'featured', 'trending', 'exclusive',
        'customer', 'testimonial', 'review', 'why choose', 'about',
        'service', 'repair', 'device', 'refurbished', 'premium'
    ];

    const lowerText = text.toLowerCase();
    return keywords.some(keyword => lowerText.includes(keyword));
}

/**
 * Translate all text in a specific container
 */
function translateContainer(containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    // Save current context and translate within this container
    const currentContext = document;
    translatePage();
}

/**
 * Add translation to dynamically created content
 */
function translateElement(element) {
    if (element.hasAttribute('data-translate')) {
        const key = element.getAttribute('data-translate');
        const translation = getTranslation(key);
        if (translation) {
            element.textContent = translation;
        }
    }
}

/**
 * Initialize observer for dynamic content
 */
function initDynamicTranslation() {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Check if the added element or its children need translation
                    const elementsToTranslate = node.querySelectorAll ?
                        node.querySelectorAll('[data-translate]') : [];

                    elementsToTranslate.forEach(translateElement);

                    if (node.hasAttribute && node.hasAttribute('data-translate')) {
                        translateElement(node);
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Initialize dynamic translation observer
document.addEventListener('DOMContentLoaded', initDynamicTranslation);

// Export functions for external use
window.translatePage = translatePage;
window.changeLanguage = changeLanguage;
window.getTranslation = getTranslation;
window.translateContainer = translateContainer;
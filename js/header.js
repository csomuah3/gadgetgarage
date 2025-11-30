/**
 * Header JavaScript Functions
 * Handles header interactions and functionality
 */

// Global variables
let userDropdownOpen = false;

/**
 * Toggle user dropdown menu
 */
function toggleUserDropdown() {
    const dropdownMenu = document.getElementById('userDropdownMenu');
    if (!dropdownMenu) return;

    userDropdownOpen = !userDropdownOpen;

    if (userDropdownOpen) {
        dropdownMenu.style.display = 'block';
        dropdownMenu.style.opacity = '0';
        dropdownMenu.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            dropdownMenu.style.transition = 'all 0.3s ease';
            dropdownMenu.style.opacity = '1';
            dropdownMenu.style.transform = 'translateY(0)';
        }, 10);

        // Close dropdown when clicking outside
        document.addEventListener('click', closeDropdownOnOutsideClick);
    } else {
        dropdownMenu.style.transition = 'all 0.3s ease';
        dropdownMenu.style.opacity = '0';
        dropdownMenu.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            dropdownMenu.style.display = 'none';
        }, 300);

        document.removeEventListener('click', closeDropdownOnOutsideClick);
    }
}

/**
 * Close dropdown when clicking outside
 */
function closeDropdownOnOutsideClick(event) {
    const userDropdown = document.querySelector('.user-dropdown');
    const dropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdown && !userDropdown.contains(event.target)) {
        userDropdownOpen = false;
        dropdownMenu.style.transition = 'all 0.3s ease';
        dropdownMenu.style.opacity = '0';
        dropdownMenu.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            dropdownMenu.style.display = 'none';
        }, 300);

        document.removeEventListener('click', closeDropdownOnOutsideClick);
    }
}

/**
 * Initialize custom language dropdown
 */
function initializeLanguageDropdown() {
    const dropdown = document.getElementById('languageDropdown');
    if (!dropdown) return;

    const selected = document.getElementById('language-selected');
    const options = document.getElementById('language-options');
    const optionsList = options.querySelectorAll('.language-dropdown-option');

    // Toggle dropdown on click
    selected.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const isActive = selected.classList.contains('active');

        // Close all other dropdowns
        document.querySelectorAll('.language-dropdown-selected.active').forEach(sel => {
            if (sel !== selected) {
                sel.classList.remove('active');
            }
        });
        document.querySelectorAll('.language-dropdown-options').forEach(opts => {
            if (opts !== options) {
                opts.style.display = 'none';
            }
        });

        if (isActive) {
            selected.classList.remove('active');
            options.style.display = 'none';
        } else {
            selected.classList.add('active');
            options.style.display = 'block';
        }
    });

    // Handle option selection
    optionsList.forEach(option => {
        option.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const langCode = option.getAttribute('data-value');
            
            // Get language details
            const flag = option.querySelector('.language-flag').textContent;
            const langName = option.querySelector('.language-name').textContent;
            const codeMatch = langName.match(/\((\w+)\)/);
            const code = codeMatch ? codeMatch[1] : langCode.toUpperCase();
            
            // Extract just the language name (without code)
            const languageNameOnly = langName.split('(')[0].trim();

            // Check if this is already the selected language
            const currentSelected = options.querySelector('.language-dropdown-option.selected');
            if (currentSelected && currentSelected.getAttribute('data-value') === langCode) {
                // Already selected, just close dropdown
                selected.classList.remove('active');
                options.style.display = 'none';
                return;
            }

            // Close dropdown first
            selected.classList.remove('active');
            options.style.display = 'none';

            // Show SweetAlert confirmation
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Change Language?',
                    html: `
                        <div style="text-align: center; padding: 20px 0;">
                            <div style="font-size: 3rem; margin-bottom: 15px;">${flag}</div>
                            <p style="font-size: 1.2rem; font-weight: 600; color: #1f2937; margin-bottom: 10px;">
                                ${languageNameOnly}
                            </p>
                            <p style="color: #6b7280; font-size: 0.95rem;">
                                The page will refresh to apply the new language.
                            </p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Change Language',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#2563EB',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                    customClass: {
                        popup: 'language-change-popup',
                        confirmButton: 'language-confirm-btn',
                        cancelButton: 'language-cancel-btn'
                    }
                });

                if (result.isConfirmed) {
                    // User confirmed, proceed with language change
                    // Update selected display
                    selected.querySelector('.language-flag').textContent = flag;
                    selected.querySelector('.language-code').textContent = code;

                    // Update selected state
                    optionsList.forEach(opt => opt.classList.remove('selected'));
                    option.classList.add('selected');

                    // Add checkmark to selected option
                    optionsList.forEach(opt => {
                        const check = opt.querySelector('.language-check');
                        if (check) check.remove();
                    });
                    const checkIcon = document.createElement('i');
                    checkIcon.className = 'fas fa-check language-check';
                    option.appendChild(checkIcon);

                    // Change language
                    changeLanguage(langCode);
                }
            } else {
                // Fallback if SweetAlert is not available
                if (confirm(`Change language to ${languageNameOnly}? The page will refresh.`)) {
                    // Update selected display
                    selected.querySelector('.language-flag').textContent = flag;
                    selected.querySelector('.language-code').textContent = code;

                    // Update selected state
                    optionsList.forEach(opt => opt.classList.remove('selected'));
                    option.classList.add('selected');

                    // Add checkmark to selected option
                    optionsList.forEach(opt => {
                        const check = opt.querySelector('.language-check');
                        if (check) check.remove();
                    });
                    const checkIcon = document.createElement('i');
                    checkIcon.className = 'fas fa-check language-check';
                    option.appendChild(checkIcon);

                    // Change language
                    changeLanguage(langCode);
                }
            }
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target)) {
            selected.classList.remove('active');
            options.style.display = 'none';
        }
    });
}

/**
 * Change website language
 */
function changeLanguage(language) {
    console.log('Changing language to:', language);

    // Show loading state
    const languageSelect = document.querySelector('select[onchange*="changeLanguage"]');
    const languageDropdown = document.getElementById('language-selected');
    
    if (languageSelect) {
        languageSelect.disabled = true;
    }
    if (languageDropdown) {
        languageDropdown.style.opacity = '0.6';
        languageDropdown.style.pointerEvents = 'none';
    }

    // Show loading notification with SweetAlert
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Changing Language...',
            text: 'Please wait while we update the language.',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    } else if (typeof showNotification === 'function') {
        showNotification('Changing language...', 'info');
    }

    // Send AJAX request to change language
    const formData = new FormData();
    formData.append('language', language);

    fetch('actions/change_language_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success notification
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Language Changed!',
                    text: 'The website language has been updated. Refreshing page...',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    timerProgressBar: true
                }).then(() => {
                    // Refresh the page to apply new language
                    window.location.reload();
                });
            } else {
                if (typeof showNotification === 'function') {
                    showNotification('Language changed successfully! Refreshing page...', 'success');
                }
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            // Show error notification
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Failed to change language',
                    icon: 'error',
                    confirmButtonColor: '#D19C97'
                });
            } else {
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Failed to change language', 'error');
                }
            }

            // Re-enable the select
            if (languageSelect) {
                languageSelect.disabled = false;
            }
            if (languageDropdown) {
                languageDropdown.style.opacity = '1';
                languageDropdown.style.pointerEvents = 'auto';
            }
        }
    })
    .catch(error => {
        console.error('Error changing language:', error);

        // Show error notification
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Connection Error',
                text: 'Failed to connect to server. Please try again.',
                icon: 'error',
                confirmButtonColor: '#D19C97'
            });
        } else {
            if (typeof showNotification === 'function') {
                showNotification('Connection error. Please try again.', 'error');
            }
        }

        // Re-enable the select
        if (languageSelect) {
            languageSelect.disabled = false;
        }
        if (languageDropdown) {
            languageDropdown.style.opacity = '1';
            languageDropdown.style.pointerEvents = 'auto';
        }
    });
}

/**
 * Show brand dropdown
 */
function showDropdown() {
    const dropdown = document.getElementById('shopDropdown');
    if (dropdown) {
        dropdown.classList.add('show');
    }
}

/**
 * Hide brand dropdown
 */
function hideDropdown() {
    const dropdown = document.getElementById('shopDropdown');
    if (dropdown) {
        dropdown.classList.remove('show');
    }
}

/**
 * Show shop category dropdown
 */
function showShopDropdown() {
    const dropdown = document.getElementById('shopCategoryDropdown');
    if (dropdown) {
        dropdown.classList.add('show');
    }
}

/**
 * Hide shop category dropdown
 */
function hideShopDropdown() {
    const dropdown = document.getElementById('shopCategoryDropdown');
    if (dropdown) {
        dropdown.classList.remove('show');
    }
}

/**
 * Show more dropdown
 */
function showMoreDropdown() {
    const dropdown = document.getElementById('moreDropdown');
    if (dropdown) {
        dropdown.style.display = 'block';
        dropdown.style.opacity = '0';
        setTimeout(() => {
            dropdown.style.transition = 'all 0.3s ease';
            dropdown.style.opacity = '1';
        }, 10);
    }
}

/**
 * Hide more dropdown
 */
function hideMoreDropdown() {
    const dropdown = document.getElementById('moreDropdown');
    if (dropdown) {
        dropdown.style.transition = 'all 0.3s ease';
        dropdown.style.opacity = '0';
        setTimeout(() => {
            dropdown.style.display = 'none';
        }, 300);
    }
}

/**
 * Toggle theme (dark/light mode)
 */
function toggleTheme() {
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');

    // Toggle dark mode class
    body.classList.toggle('dark-mode');

    // Update toggle appearance
    if (themeToggle) {
        themeToggle.classList.toggle('active');
    }

    // Save preference to localStorage
    if (body.classList.contains('dark-mode')) {
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }

    // Show notification
    if (typeof showNotification === 'function') {
        const mode = body.classList.contains('dark-mode') ? 'Dark' : 'Light';
        showNotification(`${mode} mode activated`, 'info');
    }
}

/**
 * Open profile picture modal
 */
function openProfilePictureModal() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Profile Picture',
            text: 'Profile picture functionality will be available soon!',
            icon: 'info',
            confirmButtonColor: '#D19C97'
        });
    }
}

/**
 * Show notifications panel
 */
function showNotifications() {
    // Redirect to notifications page
    window.location.href = 'notifications.php';
}

/**
 * Initialize header functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Load saved theme
    const savedTheme = localStorage.getItem('theme');
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');

    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        if (themeToggle) {
            themeToggle.classList.add('active');
        }
    }

    // Set current language in dropdown
    const currentLang = document.documentElement.lang || 'en';
    const languageSelect = document.querySelector('select[onchange*="changeLanguage"]');
    if (languageSelect) {
        languageSelect.value = currentLang;
    }

    // Initialize custom language dropdown
    initializeLanguageDropdown();

    console.log('Header JavaScript initialized');
});

/**
 * Show notification helper function
 * Fallback if main notification system is not available
 */
function showNotification(message, type = 'info') {
    // Check if main notification function exists
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }

    // Fallback to simple notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${getBootstrapAlertClass(type)} position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        border-radius: 8px;
        animation: slideInRight 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="fas ${getNotificationIcon(type)} me-2"></i>
        ${message}
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Get Bootstrap alert class
 */
function getBootstrapAlertClass(type) {
    switch (type) {
        case 'success': return 'success';
        case 'error': return 'danger';
        case 'warning': return 'warning';
        default: return 'info';
    }
}

/**
 * Get notification icon
 */
function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        default: return 'fa-info-circle';
    }
}

/**
 * Initialize SweetAlert2-based logout confirmation
 * Applies to all links that point to login/logout.php (any relative path)
 */
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Inject custom styles for the logout dialog buttons (once per page)
        (function injectLogoutStyles() {
            if (document.getElementById('gg-logout-styles')) return;
            const style = document.createElement('style');
            style.id = 'gg-logout-styles';
            style.textContent = `
                .swal2-popup.gg-logout-popup .swal2-title {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #1f2937;
                }

                .swal2-popup.gg-logout-popup .swal2-html-container {
                    font-size: 0.95rem;
                    color: #4b5563;
                    margin-top: 0.5rem;
                    margin-bottom: 1rem;
                }

                .swal2-actions .gg-logout-keep-shopping {
                    background: linear-gradient(135deg, #008060, #006b4e);
                    color: #ffffff;
                    font-weight: 700;
                    font-size: 1rem;
                    padding: 0.7rem 1.9rem;
                    border-radius: 999px;
                    border: none;
                    margin: 0 0.5rem;
                }

                .swal2-actions .gg-logout-keep-shopping:hover {
                    filter: brightness(1.05);
                    transform: translateY(-1px);
                }

                .swal2-actions .gg-logout-leave {
                    background: #ffffff;
                    color: #6b7280;
                    font-weight: 500;
                    font-size: 0.9rem;
                    padding: 0.45rem 1.3rem;
                    border-radius: 999px;
                    border: 1px solid #d1d5db;
                    margin: 0 0.5rem;
                }

                .swal2-actions .gg-logout-leave:hover {
                    background: #f3f4f6;
                }
            `;
            document.head.appendChild(style);
        })();

        // Attach confirmation to all logout links
        const logoutLinks = document.querySelectorAll('a[href*="login/logout.php"]');
        if (!logoutLinks.length) return;

        logoutLinks.forEach(link => {
            // Avoid attaching multiple listeners
            if (link.dataset.ggLogoutBound === 'true') return;
            link.dataset.ggLogoutBound = 'true';

            link.addEventListener('click', function (e) {
                e.preventDefault();

                const targetHref = link.getAttribute('href') || '';
                // Resolve to absolute URL so redirects are always correct
                const logoutUrl = new URL(targetHref, window.location.href).toString();

                // If SweetAlert2 is not available, fallback to direct navigation
                if (typeof Swal === 'undefined') {
                    window.location.href = logoutUrl;
                    return;
                }

                Swal.fire({
                    title: 'Are you sure you want to log out?',
                    html: 'You can stay and <strong>shop a little more</strong>, or leave and log out.',
                    icon: 'question',
                    showCancelButton: true,
                    // Cancel = stay (big button), Confirm = leave (smaller)
                    cancelButtonText: 'Shop a little more',
                    confirmButtonText: 'Leave',
                    reverseButtons: true,
                    focusCancel: true,
                    buttonsStyling: false,
                    customClass: {
                        popup: 'gg-logout-popup',
                        cancelButton: 'gg-logout-keep-shopping',
                        confirmButton: 'gg-logout-leave'
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        // "Leave" -> go to logout script, which will redirect to login
                        window.location.href = logoutUrl;
                    }
                    // "Shop a little more" (cancel) -> do nothing, stay on page
                });
            });
        });
    } catch (err) {
        console.error('Error initializing logout confirmation:', err);
    }
});

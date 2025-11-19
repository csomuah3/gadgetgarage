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
 * Change website language
 */
function changeLanguage(language) {
    console.log('Changing language to:', language);

    // Show loading state
    const languageSelect = document.querySelector('select[onchange*="changeLanguage"]');
    if (languageSelect) {
        languageSelect.disabled = true;
    }

    // Show loading notification
    if (typeof showNotification === 'function') {
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
                    showConfirmButton: false
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
    });
}

/**
 * Show brand dropdown
 */
function showDropdown() {
    const dropdown = document.getElementById('shopDropdown');
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
 * Hide brand dropdown
 */
function hideDropdown() {
    const dropdown = document.getElementById('shopDropdown');
    if (dropdown) {
        dropdown.style.transition = 'all 0.3s ease';
        dropdown.style.opacity = '0';
        setTimeout(() => {
            dropdown.style.display = 'none';
        }, 300);
    }
}

/**
 * Show shop category dropdown
 */
function showShopDropdown() {
    const dropdown = document.getElementById('shopCategoryDropdown');
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
 * Hide shop category dropdown
 */
function hideShopDropdown() {
    const dropdown = document.getElementById('shopCategoryDropdown');
    if (dropdown) {
        dropdown.style.transition = 'all 0.3s ease';
        dropdown.style.opacity = '0';
        setTimeout(() => {
            dropdown.style.display = 'none';
        }, 300);
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
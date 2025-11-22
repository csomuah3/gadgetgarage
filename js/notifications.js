// Comprehensive Notification System
let notificationCache = {
    notifications: [],
    unreadCount: 0,
    lastFetch: 0,
    cacheTimeout: 30000 // 30 seconds
};

// Show notifications popup
async function showNotificationsPopup() {
    try {
        // Fetch latest notifications
        await refreshNotifications();

        const notifications = notificationCache.notifications;
        const unreadCount = notificationCache.unreadCount;

        // Create notification items HTML
        let notificationItems = '';

        if (notifications.length === 0) {
            notificationItems = `
                <div class="notification-empty">
                    <div class="notification-empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <div class="notification-empty-text">
                        <h6>No notifications yet</h6>
                        <p>We'll notify you when something important happens</p>
                    </div>
                </div>
            `;
        } else {
            notifications.forEach(notification => {
                const isUnread = !notification.is_read;
                const timeAgo = notification.time_ago;
                const icon = getNotificationIcon(notification.type, notification.icon);
                const priorityClass = getPriorityClass(notification.priority);

                notificationItems += `
                    <div class="notification-item ${isUnread ? 'unread' : ''} ${priorityClass}"
                         data-notification-id="${notification.id}"
                         data-type="${notification.type}"
                         data-action-url="${notification.action_url || ''}"
                         onclick="handleNotificationClick(${notification.id}, '${notification.action_url || ''}')">
                        <div class="notification-icon">
                            <i class="fas fa-${icon}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">${notification.title}</div>
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">
                                <i class="fas fa-clock"></i>
                                ${timeAgo}
                            </div>
                        </div>
                        ${isUnread ? '<div class="notification-unread-dot"></div>' : ''}
                    </div>
                `;
            });
        }

        // Create and show SweetAlert
        const result = await Swal.fire({
            title: `
                <div class="notification-header">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    ${unreadCount > 0 ? `<span class="notification-badge">${unreadCount}</span>` : ''}
                </div>
            `,
            html: `
                <div class="notifications-container">
                    <div class="notifications-actions">
                        <button class="notification-action-btn refresh-btn" onclick="refreshNotificationsPopup()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                        ${unreadCount > 0 ? `
                            <button class="notification-action-btn mark-all-btn" onclick="markAllNotificationsRead()">
                                <i class="fas fa-check-double"></i>
                                Mark All Read
                            </button>
                        ` : ''}
                    </div>
                    <div class="notifications-list">
                        ${notificationItems}
                    </div>
                </div>
            `,
            width: '600px',
            padding: '0',
            showCloseButton: true,
            showConfirmButton: false,
            allowOutsideClick: true,
            customClass: {
                container: 'notifications-popup-container',
                popup: 'notifications-popup',
                title: 'notifications-popup-title',
                htmlContainer: 'notifications-popup-content'
            },
            didOpen: () => {
                // Add custom styles
                addNotificationStyles();
                // Start auto-refresh
                startNotificationAutoRefresh();
            },
            willClose: () => {
                // Stop auto-refresh
                stopNotificationAutoRefresh();
            }
        });

    } catch (error) {
        console.error('Error showing notifications:', error);
        Swal.fire({
            title: 'Error',
            text: 'Failed to load notifications. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
}

// Fetch notifications from server
async function refreshNotifications(force = false) {
    const now = Date.now();

    // Check cache
    if (!force && (now - notificationCache.lastFetch) < notificationCache.cacheTimeout) {
        return notificationCache;
    }

    try {
        const response = await fetch('../actions/get_notifications_action.php?limit=15');
        const data = await response.json();

        if (data.success) {
            notificationCache.notifications = data.notifications || [];
            notificationCache.unreadCount = data.unread_count || 0;
            notificationCache.lastFetch = now;

            // Update badge in header
            updateNotificationBadge(notificationCache.unreadCount);
        } else {
            throw new Error(data.message || 'Failed to fetch notifications');
        }

        return notificationCache;
    } catch (error) {
        console.error('Error fetching notifications:', error);
        throw error;
    }
}

// Handle notification click
async function handleNotificationClick(notificationId, actionUrl) {
    try {
        // Mark notification as read
        await markNotificationAsRead(notificationId);

        // Navigate to action URL if provided
        if (actionUrl && actionUrl.trim()) {
            window.location.href = actionUrl;
        }
    } catch (error) {
        console.error('Error handling notification click:', error);
    }
}

// Mark single notification as read
async function markNotificationAsRead(notificationId) {
    try {
        const response = await fetch('../actions/mark_notification_read_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update local cache
            const notification = notificationCache.notifications.find(n => n.id === notificationId);
            if (notification) {
                notification.is_read = true;
                notificationCache.unreadCount = Math.max(0, notificationCache.unreadCount - 1);
            }

            // Update UI
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('unread');
                const unreadDot = notificationElement.querySelector('.notification-unread-dot');
                if (unreadDot) {
                    unreadDot.remove();
                }
            }

            // Update badge
            updateNotificationBadge(notificationCache.unreadCount);
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

// Mark all notifications as read
async function markAllNotificationsRead() {
    try {
        const response = await fetch('../actions/mark_notification_read_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                mark_all: true
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update local cache
            notificationCache.notifications.forEach(n => n.is_read = true);
            notificationCache.unreadCount = 0;

            // Update UI
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                const unreadDot = item.querySelector('.notification-unread-dot');
                if (unreadDot) {
                    unreadDot.remove();
                }
            });

            // Update badge
            updateNotificationBadge(0);

            // Hide mark all button
            const markAllBtn = document.querySelector('.mark-all-btn');
            if (markAllBtn) {
                markAllBtn.style.display = 'none';
            }

            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'notification-success';
            successMsg.innerHTML = '<i class="fas fa-check"></i> All notifications marked as read';

            const actionsDiv = document.querySelector('.notifications-actions');
            if (actionsDiv) {
                actionsDiv.appendChild(successMsg);
                setTimeout(() => successMsg.remove(), 3000);
            }
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
}

// Refresh notifications in popup
async function refreshNotificationsPopup() {
    try {
        const refreshBtn = document.querySelector('.refresh-btn i');
        if (refreshBtn) {
            refreshBtn.classList.add('fa-spin');
        }

        await refreshNotifications(true);

        // Close current popup and reopen with fresh data
        Swal.close();
        setTimeout(() => showNotificationsPopup(), 100);
    } catch (error) {
        console.error('Error refreshing notifications:', error);
    }
}

// Update notification badge in header
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count.toString();
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Get notification icon
function getNotificationIcon(type, customIcon) {
    if (customIcon) return customIcon;

    const iconMap = {
        'support': 'envelope',
        'order': 'shopping-cart',
        'appointment': 'calendar',
        'refurbishment': 'tools',
        'payment': 'credit-card',
        'promotion': 'tag',
        'general': 'bell'
    };

    return iconMap[type] || 'bell';
}

// Get priority class
function getPriorityClass(priority) {
    const priorityMap = {
        'urgent': 'priority-urgent',
        'high': 'priority-high',
        'normal': 'priority-normal',
        'low': 'priority-low'
    };

    return priorityMap[priority] || 'priority-normal';
}

// Auto-refresh functionality
let autoRefreshInterval;

function startNotificationAutoRefresh() {
    // Refresh every 30 seconds while popup is open
    autoRefreshInterval = setInterval(async () => {
        try {
            await refreshNotifications(true);
            // Update the display without closing popup
            updateNotificationDisplay();
        } catch (error) {
            console.error('Auto-refresh error:', error);
        }
    }, 30000);
}

function stopNotificationAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function updateNotificationDisplay() {
    // Update the notifications list in the current popup
    const listContainer = document.querySelector('.notifications-list');
    if (listContainer && Swal.isVisible()) {
        // This would update the display, but for simplicity we'll let users manually refresh
        updateNotificationBadge(notificationCache.unreadCount);
    }
}

// Add custom CSS styles
function addNotificationStyles() {
    if (document.getElementById('notification-styles')) return;

    const styles = document.createElement('style');
    styles.id = 'notification-styles';
    styles.textContent = `
        /* Notification Popup Styles */
        .notifications-popup-container {
            z-index: 10000;
        }

        .notifications-popup {
            max-height: 80vh;
            overflow: hidden;
            border-radius: 15px;
        }

        .notifications-popup-title {
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            color: white;
            padding: 20px;
            margin: 0;
            border-radius: 15px 15px 0 0;
        }

        .notification-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .notification-badge {
            background: #ef4444;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .notifications-popup-content {
            padding: 0;
            margin: 0;
        }

        .notifications-container {
            max-height: 500px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .notifications-actions {
            padding: 15px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .notification-action-btn {
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .notification-action-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .refresh-btn:hover {
            color: #059669;
            border-color: #059669;
        }

        .mark-all-btn:hover {
            color: #dc2626;
            border-color: #dc2626;
        }

        .notifications-list {
            overflow-y: auto;
            max-height: 400px;
            padding: 0;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            background: white;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item.unread {
            background: #fef7f7;
            border-left: 4px solid #ef4444;
        }

        .notification-item.unread:hover {
            background: #fef2f2;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #64748b;
            flex-shrink: 0;
        }

        .notification-item[data-type="support"] .notification-icon {
            background: #dbeafe;
            color: #3b82f6;
        }

        .notification-item[data-type="order"] .notification-icon {
            background: #dcfce7;
            color: #16a34a;
        }

        .notification-item[data-type="appointment"] .notification-icon {
            background: #fef3c7;
            color: #d97706;
        }

        .notification-item[data-type="refurbishment"] .notification-icon {
            background: #e0e7ff;
            color: #6366f1;
        }

        .notification-item[data-type="payment"] .notification-icon {
            background: #fde2e7;
            color: #ec4899;
        }

        .notification-item[data-type="promotion"] .notification-icon {
            background: #f3e8ff;
            color: #a855f7;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .notification-message {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .notification-unread-dot {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        .priority-urgent {
            border-left-color: #dc2626 !important;
        }

        .priority-high {
            border-left-color: #ea580c !important;
        }

        .priority-normal {
            border-left-color: #0891b2 !important;
        }

        .priority-low {
            border-left-color: #65a30d !important;
        }

        .notification-empty {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .notification-empty-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #d1d5db;
        }

        .notification-empty-text h6 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .notification-empty-text p {
            font-size: 0.9rem;
            margin: 0;
        }

        .notification-success {
            background: #dcfce7;
            color: #16a34a;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        @media (max-width: 600px) {
            .notifications-popup {
                width: 95% !important;
                max-width: none !important;
                margin: 10px;
            }

            .notification-item {
                padding: 12px 15px;
            }

            .notifications-actions {
                padding: 10px 15px;
                flex-direction: column;
            }

            .notification-action-btn {
                justify-content: center;
            }
        }
    `;

    document.head.appendChild(styles);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load initial notification count
    refreshNotifications().catch(console.error);

    // Set up periodic refresh (every 2 minutes)
    setInterval(() => {
        refreshNotifications().catch(console.error);
    }, 120000);
});

// Export functions for global access
window.showNotificationsPopup = showNotificationsPopup;
window.refreshNotificationsPopup = refreshNotificationsPopup;
window.markAllNotificationsRead = markAllNotificationsRead;
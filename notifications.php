<?php
session_start();
require_once __DIR__ . '/settings/core.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login/login.php');
    exit();
}

// Initialize variables
$is_logged_in = true;
$is_admin = check_admin();
$cart_count = 0;

// Get cart count for logged in users
if (!$is_admin) {
    require_once __DIR__ . '/controllers/cart_controller.php';
    $cart_count = get_cart_count_ctr($_SESSION['user_id']);
}

// Get brands and categories for navigation
require_once __DIR__ . '/controllers/brand_controller.php';
require_once __DIR__ . '/controllers/category_controller.php';

$brands = get_all_brands_ctr() ?: [];
$categories = get_all_categories_ctr() ?: [];

// Get customer notifications
require_once __DIR__ . '/controllers/support_controller.php';
$notifications = get_customer_notifications_ctr($_SESSION['user_id']);
$unread_count = get_unread_notification_count_ctr($_SESSION['user_id']);

$page_title = "Notifications - GadgetGarage";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="includes/header-styles.css">

    <style>
        .notifications-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .notifications-content {
            padding: 60px 0;
            background: #f8f9fa;
            min-height: 70vh;
        }

        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .notification-item.unread {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f1ff 100%);
            border-left-color: #ff6b6b;
        }

        .notification-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }

        .notification-type {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .notification-date {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .notification-message {
            color: #374151;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .mark-read-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .mark-read-btn:hover {
            background: #059669;
        }

        .view-message-btn {
            background: #667eea;
            color: white;
            text-decoration: none;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: background-color 0.3s ease;
        }

        .view-message-btn:hover {
            background: #5a67d8;
            color: white;
        }

        .no-notifications {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .no-notifications i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .no-notifications h3 {
            color: #374151;
            margin-bottom: 10px;
        }

        .no-notifications p {
            color: #6b7280;
            margin-bottom: 30px;
        }

        .stats-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 12px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-weight: 500;
        }

        .mark-all-read-btn {
            background: #059669;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .mark-all-read-btn:hover {
            background: #047857;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Notifications Hero Section -->
    <section class="notifications-hero">
        <div class="container">
            <h1><i class="fas fa-bell me-3"></i>Your Notifications</h1>
            <p class="lead">Stay updated with your support messages and system notifications</p>
        </div>
    </section>

    <!-- Notifications Content -->
    <section class="notifications-content">
        <div class="container">
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= count($notifications) ?></div>
                        <div class="stat-label">Total Notifications</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $unread_count ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= count($notifications) - $unread_count ?></div>
                        <div class="stat-label">Read Messages</div>
                    </div>
                </div>
            </div>

            <!-- Alert for success/error messages -->
            <div id="alertContainer"></div>

            <?php if (!empty($notifications)): ?>
                <!-- Mark All Read Button -->
                <?php if ($unread_count > 0): ?>
                    <button class="mark-all-read-btn" onclick="markAllAsRead()">
                        <i class="fas fa-check-double me-2"></i>Mark All as Read
                    </button>
                <?php endif; ?>

                <!-- Notifications List -->
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" id="notification-<?= $notification['notification_id'] ?>">
                            <div class="notification-header">
                                <span class="notification-type">
                                    <?php
                                    switch($notification['type']) {
                                        case 'support_response':
                                            echo '<i class="fas fa-reply me-1"></i> Support Response';
                                            break;
                                        case 'order_update':
                                            echo '<i class="fas fa-box me-1"></i> Order Update';
                                            break;
                                        default:
                                            echo '<i class="fas fa-info me-1"></i> Notification';
                                    }
                                    ?>
                                </span>
                                <span class="notification-date">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                                </span>
                            </div>

                            <div class="notification-message">
                                <?= htmlspecialchars($notification['message']) ?>
                            </div>

                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                    <button class="mark-read-btn" onclick="markAsRead(<?= $notification['notification_id'] ?>)">
                                        <i class="fas fa-check me-1"></i>Mark as Read
                                    </button>
                                <?php endif; ?>

                                <?php if ($notification['type'] === 'support_response' && $notification['related_id']): ?>
                                    <a href="admin/support_messages.php?view=<?= $notification['related_id'] ?>" class="view-message-btn">
                                        <i class="fas fa-eye me-1"></i>View Message
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Notifications State -->
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No Notifications Yet</h3>
                    <p>You'll see notifications here when there are updates to your support messages or orders.</p>
                    <a href="contact.php" class="btn btn-primary">
                        <i class="fas fa-envelope me-2"></i>Contact Support
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Header JavaScript -->
    <script src="js/header.js"></script>

    <script>
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show`;
            alert.innerHTML = `
                <i class="fas ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            alertContainer.appendChild(alert);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        function markAsRead(notificationId) {
            fetch('actions/mark_notification_read_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const notificationElement = document.getElementById(`notification-${notificationId}`);
                    if (notificationElement) {
                        notificationElement.classList.remove('unread');
                        const markReadBtn = notificationElement.querySelector('.mark-read-btn');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                    }

                    // Update notification badge in header
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        let count = parseInt(badge.textContent) - 1;
                        if (count <= 0) {
                            badge.remove();
                        } else {
                            badge.textContent = count;
                        }
                    }

                    // Update stats
                    updateStats();

                    showAlert('Notification marked as read');
                } else {
                    showAlert(data.message || 'Failed to mark notification as read', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred', 'error');
            });
        }

        function markAllAsRead() {
            const unreadNotifications = document.querySelectorAll('.notification-item.unread');
            const promises = [];

            unreadNotifications.forEach(notification => {
                const notificationId = notification.id.replace('notification-', '');
                promises.push(
                    fetch('actions/mark_notification_read_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            notification_id: parseInt(notificationId)
                        })
                    }).then(response => response.json())
                );
            });

            Promise.all(promises).then(results => {
                let successCount = 0;
                results.forEach((data, index) => {
                    if (data.status === 'success') {
                        successCount++;
                        const notificationElement = unreadNotifications[index];
                        notificationElement.classList.remove('unread');
                        const markReadBtn = notificationElement.querySelector('.mark-read-btn');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                    }
                });

                if (successCount > 0) {
                    // Remove notification badge
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.remove();
                    }

                    // Remove mark all read button
                    const markAllBtn = document.querySelector('.mark-all-read-btn');
                    if (markAllBtn) {
                        markAllBtn.remove();
                    }

                    // Update stats
                    updateStats();

                    showAlert(`${successCount} notifications marked as read`);
                } else {
                    showAlert('Failed to mark notifications as read', 'error');
                }
            }).catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred', 'error');
            });
        }

        function updateStats() {
            const totalNotifications = document.querySelectorAll('.notification-item').length;
            const unreadNotifications = document.querySelectorAll('.notification-item.unread').length;
            const readNotifications = totalNotifications - unreadNotifications;

            const statNumbers = document.querySelectorAll('.stat-number');
            if (statNumbers.length >= 3) {
                statNumbers[1].textContent = unreadNotifications;
                statNumbers[2].textContent = readNotifications;
            }
        }
    </script>
</body>
</html>
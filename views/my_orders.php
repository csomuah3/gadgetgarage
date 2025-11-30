<?php
try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/order_controller.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');
    require_once(__DIR__ . '/../helpers/image_helper.php');

    $is_logged_in = check_login();
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!$is_logged_in) {
        header("Location: ../login/login.php");
        exit;
    }

    // Debug: Log session information
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Customer ID being used: " . $customer_id);

    // Get user orders
    $orders = [];
    try {
        $orders = get_user_orders_ctr($customer_id);
        if (!$orders) {
            $orders = [];
        }
        error_log("Orders found for customer $customer_id: " . count($orders));
    } catch (Exception $e) {
        error_log("Error fetching orders for customer $customer_id: " . $e->getMessage());
        $orders = [];
    }

    // Get cart and wishlist counts for header
    $cart_items = get_user_cart_ctr($customer_id, $ip_address);
    $cart_total_raw = get_cart_total_ctr($customer_id, $ip_address);
    $cart_total = $cart_total_raw ?: 0;
    $cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

    $categories = [];
    $brands = [];

    try {
        require_once(__DIR__ . '/../controllers/category_controller.php');
        $categories = get_all_categories_ctr();
    } catch (Exception $e) {
        error_log("Failed to load categories: " . $e->getMessage());
    }

    try {
        require_once(__DIR__ . '/../controllers/brand_controller.php');
        $brands = get_all_brands_ctr();
    } catch (Exception $e) {
        error_log("Failed to load brands: " . $e->getMessage());
    }

    // Get user's name for welcome message
    $user_name = $_SESSION['name'] ?? 'User';
    $first_name = explode(' ', $user_name)[0];
} catch (Exception $e) {
    die("Critical error: " . $e->getMessage());
}

// Function to determine order status based on date
function getOrderStatus($order_date)
{
    $order_timestamp = strtotime($order_date);
    $current_timestamp = time();
    $days_difference = floor(($current_timestamp - $order_timestamp) / (60 * 60 * 24));

    if ($days_difference == 0) {
        return "PROCESSING";
    } elseif ($days_difference >= 4) {
        return "DELIVERED";
    } elseif ($days_difference >= 2) {
        return "OUT FOR DELIVERY";
    } elseif ($days_difference >= 1) {
        return "SHIPPED";
    } else {
        return "PROCESSING";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/page-background.css" rel="stylesheet">
    <link href="../includes/account_sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ============================================
        // ORDER MANAGEMENT FUNCTIONS - DEFINED IN HEAD FOR IMMEDIATE AVAILABILITY
        // ============================================

        // View Order Details Function
        window.viewOrderDetails = function(orderId, orderReference) {
            console.log('Loading order details for order ID:', orderId);

            // Show loading state
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Loading...',
                    html: 'Please wait while we fetch your order details.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            // Fetch order details
            fetch(`../actions/get_order_details.php?order_id=${orderId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.close();
                        }
                        if (typeof showOrderDetailsModal === 'function') {
                            showOrderDetailsModal(data.order, orderReference);
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to load order details',
                                confirmButtonColor: '#3b82f6'
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load order details: ' + error.message,
                            confirmButtonColor: '#3b82f6'
                        });
                    }
                });
        };

        // Track Order Function
        window.trackOrder = function(orderReference, orderDate) {
            const orderDateTime = new Date(orderDate);
            const now = new Date();
            const daysSinceOrder = Math.floor((now - orderDateTime) / (1000 * 60 * 60 * 24));

            // Calculate status times
            const orderConfirmedTime = new Date(orderDateTime);
            const packagePreparedTime = new Date(orderDateTime);
            packagePreparedTime.setDate(packagePreparedTime.getDate() + 1);
            const inTransitTime = new Date(orderDateTime);
            inTransitTime.setDate(inTransitTime.getDate() + 2);
            const outForDeliveryTime = new Date(orderDateTime);
            outForDeliveryTime.setDate(outForDeliveryTime.getDate() + 4);

            const formatDateTime = (date) => {
                const day = date.getDate();
                const month = date.toLocaleString('default', { month: 'short' }).toUpperCase();
                const time = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                return `${day} ${month}<br>${time}`;
            };

            let currentStep = 0;
            if (daysSinceOrder >= 4) currentStep = 4;
            else if (daysSinceOrder >= 2) currentStep = 3;
            else if (daysSinceOrder >= 1) currentStep = 2;
            else currentStep = 1;

            const trackingHTML = `
                <div style="padding: 30px; max-width: 900px; margin: 0 auto;">
                    <div style="position: relative; padding: 40px 0;">
                        <!-- Timeline Line -->
                        <div style="position: absolute; top: 70px; left: 50px; right: 50px; height: 4px; background: #e9ecef;"></div>
                        <div style="position: absolute; top: 70px; left: 50px; width: ${((currentStep - 1) / 3) * 100}%; height: 4px; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.5s ease;"></div>

                        <!-- Timeline Steps -->
                        <div style="display: flex; justify-content: space-between; position: relative; z-index: 1;">
                            <!-- Order Confirmed -->
                            <div style="flex: 1; text-align: center;">
                                <div style="width: 50px; height: 50px; margin: 0 auto 15px; border-radius: 50%; background: ${currentStep >= 1 ? '#28a745' : '#e9ecef'}; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    ${currentStep >= 1 ? '✓' : '1'}
                                </div>
                                <div style="font-weight: 700; font-size: 14px; color: ${currentStep >= 1 ? '#212529' : '#6c757d'}; margin-bottom: 8px;">Order confirmed</div>
                                <div style="font-size: 12px; color: #6c757d; line-height: 1.4;">${formatDateTime(orderConfirmedTime)}</div>
                                <div style="font-size: 11px; color: #adb5bd; margin-top: 5px;">Order placed and confirmed</div>
                            </div>

                            <!-- Package Prepared -->
                            <div style="flex: 1; text-align: center;">
                                <div style="width: 50px; height: 50px; margin: 0 auto 15px; border-radius: 50%; background: ${currentStep >= 2 ? '#28a745' : '#e9ecef'}; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    ${currentStep >= 2 ? '✓' : '2'}
                                </div>
                                <div style="font-weight: 700; font-size: 14px; color: ${currentStep >= 2 ? '#212529' : '#6c757d'}; margin-bottom: 8px;">Package prepared</div>
                                <div style="font-size: 12px; color: #6c757d; line-height: 1.4;">${formatDateTime(packagePreparedTime)}</div>
                                <div style="font-size: 11px; color: #adb5bd; margin-top: 5px;">Packed and handed to carrier</div>
                            </div>

                            <!-- In Transit -->
                            <div style="flex: 1; text-align: center;">
                                <div style="width: 50px; height: 50px; margin: 0 auto 15px; border-radius: 50%; background: ${currentStep >= 3 ? '#28a745' : '#e9ecef'}; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    ${currentStep >= 3 ? '✓' : '3'}
                                </div>
                                <div style="font-weight: 700; font-size: 14px; color: ${currentStep >= 3 ? '#212529' : '#6c757d'}; margin-bottom: 8px;">In transit</div>
                                <div style="font-size: 12px; color: #6c757d; line-height: 1.4;">${formatDateTime(inTransitTime)}</div>
                                <div style="font-size: 11px; color: #adb5bd; margin-top: 5px;">Package in transit</div>
                            </div>

                            <!-- Out for Delivery -->
                            <div style="flex: 1; text-align: center;">
                                <div style="width: 50px; height: 50px; margin: 0 auto 15px; border-radius: 50%; background: ${currentStep >= 4 ? '#28a745' : '#e9ecef'}; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    ${currentStep >= 4 ? '✓' : '4'}
                                </div>
                                <div style="font-weight: 700; font-size: 14px; color: ${currentStep >= 4 ? '#212529' : '#6c757d'}; margin-bottom: 8px;">Out for delivery</div>
                                <div style="font-size: 12px; color: #6c757d; line-height: 1.4;">${formatDateTime(outForDeliveryTime)}</div>
                                <div style="font-size: 11px; color: #adb5bd; margin-top: 5px;">Will be delivered today</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: `Track Order #${orderReference}`,
                    html: trackingHTML,
                    width: '1000px',
                    showCloseButton: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Close',
                    customClass: {
                        popup: 'tracking-modal'
                    }
                });
            }
        };

        // Request Refund Function
        window.requestRefund = function(orderId, orderReference) {
            if (typeof Swal === 'undefined') {
                alert('Please wait for the page to fully load.');
                return;
            }

            const refundFormHTML = `
                <form id="refundForm" style="text-align: left; max-width: 600px; margin: 0 auto;">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h6 style="margin: 0 0 5px 0; color: #6c757d; font-size: 12px; text-transform: uppercase;">Order Reference</h6>
                        <strong style="font-size: 16px; color: #212529;">#${orderReference}</strong>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">First Name *</label>
                        <input type="text" id="firstName" name="firstName" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Last Name *</label>
                        <input type="text" id="lastName" name="lastName" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Email *</label>
                        <input type="email" id="email" name="email" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Phone *</label>
                        <input type="tel" id="phone" name="phone" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Refund Amount (Optional)</label>
                        <input type="number" id="refundAmount" name="refundAmount" step="0.01" min="0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;"
                               placeholder="Leave blank for full refund">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Reason for Refund *</label>
                        <textarea id="reasonForRefund" name="reasonForRefund" required rows="4"
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; resize: vertical;"
                                  placeholder="Please provide details about why you are requesting a refund..."></textarea>
                    </div>
                </form>
            `;

            Swal.fire({
                title: 'Request Refund',
                html: refundFormHTML,
                width: '700px',
                showCancelButton: true,
                confirmButtonText: 'Submit Request',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6c757d',
                showCloseButton: true,
                allowOutsideClick: false,
                preConfirm: () => {
                    const form = document.getElementById('refundForm');
                    const formData = new FormData(form);

                    // Validate required fields
                    const firstName = formData.get('firstName').trim();
                    const lastName = formData.get('lastName').trim();
                    const email = formData.get('email').trim();
                    const phone = formData.get('phone').trim();
                    const reason = formData.get('reasonForRefund').trim();

                    if (!firstName || !lastName || !email || !phone || !reason) {
                        Swal.showValidationMessage('Please fill in all required fields');
                        return false;
                    }

                    // Validate email format
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        Swal.showValidationMessage('Please enter a valid email address');
                        return false;
                    }

                    return {
                        orderId: orderId,
                        orderReference: orderReference,
                        firstName: firstName,
                        lastName: lastName,
                        email: email,
                        phone: phone,
                        refundAmount: formData.get('refundAmount') || null,
                        reason: reason
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Submitting Request...',
                        html: 'Please wait while we process your refund request.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit refund request
                    fetch('../actions/submit_refund_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(result.value)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Request Submitted!',
                                html: `Your refund request for order <strong>#${orderReference}</strong> has been submitted successfully.<br><br>
                                       <strong>Reference ID:</strong> ${data.refund_id}<br><br>
                                       You will receive an email confirmation shortly. Our team will review your request within 2-3 business days.`,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#10b981'
                            });
                        } else {
                            Swal.fire({
                                title: 'Request Failed',
                                text: data.message || 'There was an error submitting your refund request. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Network Error',
                            text: 'Unable to submit your request. Please check your internet connection and try again.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#ef4444'
                        });
                    });
                }
            });
        };

        // Cancel Order Function
        window.cancelOrder = function(orderId, orderReference) {
            if (typeof Swal === 'undefined') {
                alert('Please wait for the page to fully load.');
                return;
            }

            // Confirm cancellation with SweetAlert
            Swal.fire({
                title: 'Cancel Order?',
                html: `Are you sure you want to cancel order <strong>#${orderReference}</strong>?<br><br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state - find button by order ID
                    const cancelBtn = document.querySelector(`button[onclick*="cancelOrder(${orderId}"]`);
                    let originalText = 'Cancel';
                    if (cancelBtn) {
                        originalText = cancelBtn.innerHTML;
                        cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
                        cancelBtn.disabled = true;
                    }

                    // Send cancellation request
                    fetch('../actions/cancel_order_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            order_reference: orderReference
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Show success message
                            Swal.fire({
                                title: 'Cancelled!',
                                html: `Order <strong>#${orderReference}</strong> has been cancelled successfully.`,
                                icon: 'success',
                                confirmButtonColor: '#3085d6',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload the page to update order display
                                window.location.reload();
                            });
                        } else {
                            // Show error message
                            Swal.fire({
                                title: 'Error!',
                                text: `Failed to cancel order: ${data.message || 'Unknown error'}`,
                                icon: 'error',
                                confirmButtonColor: '#3085d6',
                                confirmButtonText: 'OK'
                            });

                            // Reset button
                            if (cancelBtn) {
                                cancelBtn.innerHTML = originalText;
                                cancelBtn.disabled = false;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Show error message
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to cancel order. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        });
                        
                        // Reset button
                        if (cancelBtn) {
                            cancelBtn.innerHTML = originalText;
                            cancelBtn.disabled = false;
                        }
                    });
                }
            });
        };

        // Open Rating Modal Function
        window.openRatingModal = async function(orderId) {
            if (typeof Swal === 'undefined') {
                alert('Please wait for the page to fully load.');
                return;
            }

            try {
                // Fetch order details
                const response = await fetch(`../actions/get_order_details.php?order_id=${orderId}`);
                const data = await response.json();

                if (!data.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to load order details'
                    });
                    return;
                }

                const orderItems = data.order.items || [];
                
                if (orderItems.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Products',
                        text: 'No products found in this order.'
                    });
                    return;
                }

                // Fetch existing ratings for this order
                const ratingsResponse = await fetch(`../actions/get_product_ratings.php?order_id=${orderId}`);
                const ratingsData = await ratingsResponse.json();
                const existingRatings = ratingsData.success ? ratingsData.ratings : {};

                // Build rating modal for each product
                let currentProductIndex = 0;
                let productRatings = {};

                function showRatingModalForProduct(index) {
                    if (index >= orderItems.length) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thank you!',
                            text: 'All ratings have been submitted successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        return;
                    }

                    const item = orderItems[index];
                    const productId = item.product_id;
                    const existingRating = existingRatings[productId] || null;
                    
                    // Product image is already a full URL from the API
                    const imageUrl = item.product_image || '';

                    // Get product condition (default to 'excellent' if not set)
                    const productCondition = item.product_condition || item.condition || 'excellent';
                    
                    const ratingHTML = `
                        <div style="max-width: 700px; margin: 0 auto; text-align: left;">
                            <h3 style="text-align: center; margin-bottom: 10px; font-weight: 700; color: #212529;">WE'D LOVE YOUR FEEDBACK</h3>
                            <p style="text-align: center; margin-bottom: 30px; color: #6c757d; font-size: 14px;">Click on the stars to review your purchase and share your thoughts!</p>
                            
                            <div style="display: flex; gap: 30px; margin-bottom: 30px; align-items: center;">
                                <div style="flex-shrink: 0;">
                                    <img src="${imageUrl || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjNGNEY2Ii8+Cjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNkI3MjgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pgo8L3N2Zz4K'}" 
                                         alt="${item.product_title}" 
                                         style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6;"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjNGNEY2Ii8+Cjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNkI3MjgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pgo8L3N2Zz4K'">
                                </div>
                                
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600; color: #212529;">${item.product_title}</h4>
                                    <p style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d;">Size: ${item.size || 'OS'}</p>
                                    
                                    <!-- Star Rating -->
                                    <div style="margin-bottom: 15px;">
                                        <div id="starRating${index}" style="display: flex; gap: 5px; font-size: 28px; cursor: pointer;">
                                            ${[1, 2, 3, 4, 5].map(star => `
                                                <span class="star" data-rating="${star}" 
                                                      style="color: ${existingRating && star <= existingRating.rating ? '#FFD700' : '#ddd'}; 
                                                             transition: color 0.2s;">
                                                    ★
                                                </span>
                                            `).join('')}
                                        </div>
                                        <input type="hidden" id="selectedRating${index}" value="${existingRating ? existingRating.rating : 0}">
                                    </div>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                                        <span style="font-size: 18px; font-weight: 700; color: #212529;">GH₵${parseFloat(item.product_price || 0).toFixed(2)}</span>
                                        <span style="font-size: 14px; color: #6c757d;">Qty: ${item.qty || 1}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Text Area -->
                            <div style="margin-bottom: 20px;">
                                <textarea id="reviewText${index}" 
                                          placeholder="Write a short review" 
                                          rows="4"
                                          style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; resize: vertical; font-family: inherit;">${existingRating ? (existingRating.comment || '') : ''}</textarea>
                            </div>
                            
                            ${index < orderItems.length - 1 ? 
                                `<p style="text-align: center; color: #6c757d; font-size: 13px; margin-bottom: 0;">Product ${index + 1} of ${orderItems.length}</p>` 
                                : ''}
                        </div>
                    `;

                    Swal.fire({
                        title: '',
                        html: ratingHTML,
                        width: '800px',
                        showCancelButton: true,
                        confirmButtonText: index < orderItems.length - 1 ? 'Next Product' : 'Submit Review',
                        cancelButtonText: 'Skip',
                        confirmButtonColor: '#000000',
                        cancelButtonColor: '#6c757d',
                        showCloseButton: true,
                        allowOutsideClick: false,
                        didOpen: () => {
                            // Star rating functionality
                            const starContainer = document.getElementById(`starRating${index}`);
                            const ratingInput = document.getElementById(`selectedRating${index}`);
                            
                            if (starContainer) {
                                starContainer.addEventListener('click', (e) => {
                                    if (e.target.classList.contains('star')) {
                                        const rating = parseInt(e.target.dataset.rating);
                                        ratingInput.value = rating;
                                        
                                        // Update star display
                                        const stars = starContainer.querySelectorAll('.star');
                                        stars.forEach((star, idx) => {
                                            star.style.color = idx < rating ? '#FFD700' : '#ddd';
                                        });
                                    }
                                });

                                // Hover effect
                                starContainer.addEventListener('mouseover', (e) => {
                                    if (e.target.classList.contains('star')) {
                                        const hoverRating = parseInt(e.target.dataset.rating);
                                        const stars = starContainer.querySelectorAll('.star');
                                        stars.forEach((star, idx) => {
                                            if (idx < hoverRating) {
                                                star.style.color = '#FFD700';
                                                star.style.opacity = '0.7';
                                            }
                                        });
                                    }
                                });

                                starContainer.addEventListener('mouseleave', () => {
                                    const currentRating = parseInt(ratingInput.value) || 0;
                                    const stars = starContainer.querySelectorAll('.star');
                                    stars.forEach((star, idx) => {
                                        star.style.opacity = '1';
                                        star.style.color = idx < currentRating ? '#FFD700' : '#ddd';
                                    });
                                });
                            }
                        },
                        preConfirm: () => {
                            const rating = parseInt(document.getElementById(`selectedRating${index}`).value) || 0;
                            const comment = document.getElementById(`reviewText${index}`).value.trim();

                            if (rating === 0) {
                                Swal.showValidationMessage('Please select a rating');
                                return false;
                            }

                            return {
                                product_id: productId,
                                rating: rating,
                                comment: comment,
                                product_condition: productCondition,
                                product_price: parseFloat(item.product_price || 0)
                            };
                        }
                    }).then(async (result) => {
                        if (result.isConfirmed && result.value) {
                            const ratingData = result.value;
                            
                            // Save rating
                            try {
                                const submitResponse = await fetch('../actions/submit_product_rating_action.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        order_id: orderId,
                                        product_id: ratingData.product_id,
                                        rating: ratingData.rating,
                                        comment: ratingData.comment,
                                        product_condition: ratingData.product_condition,
                                        product_price: ratingData.product_price
                                    })
                                });

                                const submitData = await submitResponse.json();

                                if (submitData.success) {
                                    productRatings[ratingData.product_id] = ratingData;
                                    
                                    // Show next product or finish
                                    showRatingModalForProduct(index + 1);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: submitData.message || 'Failed to save rating'
                                    });
                                }
                            } catch (error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to submit rating: ' + error.message
                                });
                            }
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            // User skipped - show next product
                            showRatingModalForProduct(index + 1);
                        }
                    });
                }

                // Start with first product
                showRatingModalForProduct(0);

            } catch (error) {
                console.error('Error opening rating modal:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load order details: ' + error.message
                });
            }
        };

        // Also define them without window prefix for onclick handlers (like wishlist does)
        var viewOrderDetails = window.viewOrderDetails;
        var trackOrder = window.trackOrder;
        var requestRefund = window.requestRefund;
        var cancelOrder = window.cancelOrder;
        var openRatingModal = window.openRatingModal;
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background-color: #ffffff;
            color: #1a1a1a;
            overflow-x: hidden;
        }












        /* Header styles are now in header.css */

        /* Account Layout */
        .account-layout {
            display: flex;
            min-height: calc(100vh - 140px);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            position: relative;
            margin-top: 0;
        }


        /* Main Content */
        .account-content {
            flex: 1;
            padding: 25px 30px;
            max-width: calc(100% - 240px);
        }

        .orders-header {
            margin-bottom: 45px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f1f5f9;
        }

        .orders-title {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #1f2937 0%, #4F46E5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .orders-subtitle {
            color: #64748b;
            font-size: 1.15rem;
            font-weight: 400;
            letter-spacing: -0.2px;
        }

        /* Orders Section Styles */
        .orders-section {
            margin-bottom: 40px;
        }

        .section-title {
            color: #1f2937;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .orders-grid {
            display: grid;
            gap: 16px;
            max-width: 100%;
            margin: 0;
        }

        .empty-section {
            text-align: center;
            padding: 60px 30px;
            color: #64748b;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            border: 2px dashed #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .empty-section:hover {
            border-color: #cbd5e1;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .empty-section i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 20px;
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        .empty-section:hover i {
            opacity: 1;
            transform: scale(1.05);
        }

        .empty-section p {
            font-size: 1.15rem;
            margin: 0;
            font-weight: 500;
            letter-spacing: -0.2px;
        }

        /* Order Cards - Modern Enhanced */
        .order-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #e5e7eb;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .order-card:hover {
            border-color: #4F46E5;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.12);
            transform: translateY(-2px);
        }

        .order-card:hover::before {
            background: linear-gradient(180deg, #4F46E5 0%, #7C3AED 100%);
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 20px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .order-status.processing {
            color: #F59E0B;
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .order-status.shipped {
            color: #4F46E5;
            background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);
            border: 1px solid rgba(79, 70, 229, 0.2);
        }

        .order-status.out-for-delivery {
            color: #10B981;
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .order-status.delivered {
            color: #059669;
            background: linear-gradient(135deg, #A7F3D0 0%, #6EE7B7 100%);
            border: 1px solid rgba(5, 150, 105, 0.2);
        }

        .order-images {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            padding: 12px;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #f1f5f9;
        }

        .order-image {
            width: 70px;
            height: 70px;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            flex-shrink: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .order-image:hover {
            transform: scale(1.08);
            border-color: #4F46E5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .order-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-more {
            width: 70px;
            height: 70px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-weight: 600;
            font-size: 0.75rem;
            text-align: center;
            line-height: 1.2;
        }

        .order-details {
            color: #374151;
            font-size: 0.9rem;
            margin-bottom: 18px;
            line-height: 1.6;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .order-number {
            color: #1f2937;
            font-weight: 600;
        }

        .order-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .action-btn {
            flex: 1;
            min-width: 90px;
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .details-btn {
            background: #E0E7FF;
            color: #1e3a8a;
        }

        .details-btn:hover {
            background: #C7D2FE;
            color: #1e3a8a;
        }

        .track-btn {
            background: #2563EB;
            color: white;
        }

        .track-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .refund-btn {
            background: #3b82f6;
            color: white;
        }

        .refund-btn:hover {
            background: #2563EB;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .rate-btn {
            background: #60a5fa;
            color: white;
        }

        .rate-btn:hover {
            background: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(96, 165, 250, 0.3);
        }

        .cancel-order-btn {
            background: #1e3a8a;
            color: white;
        }

        .cancel-order-btn:hover {
            background: #1e40af;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }

        .cancel-order-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .no-orders {
            text-align: center;
            padding: 80px 40px;
            color: #64748b;
        }

        .no-orders i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .no-orders h3 {
            color: #475569;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .no-orders p {
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .start-shopping-btn {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .start-shopping-btn:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: white;
            transform: translateY(-2px);
        }

        /* Footer Styles - EXACT COPY FROM CART */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 0;
        }

        .footer-brand {
            margin-bottom: 30px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .footer-logo img {
            height: 50px !important;
            width: auto !important;
            object-fit: contain !important;
        }

        .footer-description {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social-link:hover {
            background: #2563EB;
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 24px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 14px;
        }

        .footer-links li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #2563EB;
            transform: translateX(4px);
        }

        .footer-divider {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 40px 0 20px;
        }

        .footer-bottom {
            padding-top: 20px;
        }

        .copyright {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
        }

        /* Newsletter Signup Section */
        .newsletter-signup-section {
            background: transparent;
            padding: 0;
            text-align: left;
            max-width: 100%;
            height: fit-content;
        }

        .newsletter-title {
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .newsletter-form {
            display: flex;
            width: 100%;
            margin: 0 0 15px 0;
            gap: 0;
            border-radius: 50px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .newsletter-input {
            flex: 1;
            padding: 14px 20px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: #1a1a1a;
            background: #e5e7eb;
        }

        .newsletter-input::placeholder {
            color: #6b7280;
        }

        .newsletter-submit-btn {
            width: 45px;
            height: 45px;
            min-width: 45px;
            border: none;
            background: #9ca3af;
            color: #ffffff;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            padding: 0;
        }

        .newsletter-submit-btn:hover {
            background: #6b7280;
            transform: scale(1.05);
        }

        .newsletter-disclaimer {
            color: #6b7280;
            font-size: 0.85rem;
            line-height: 1.6;
            margin: 8px 0 0 0;
            text-align: left;
        }

        .newsletter-disclaimer a {
            color: #2563EB;
            text-decoration: underline;
            transition: color 0.3s ease;
        }

        .newsletter-disclaimer a:hover {
            color: #1d4ed8;
        }

        /* Order Details Modal */
        .order-modal {
            z-index: 1060;
        }

        .order-modal .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .order-modal .modal-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .order-modal .modal-body {
            padding: 30px;
        }

        .order-info-section {
            margin-bottom: 25px;
        }

        .order-info-section h6 {
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .order-items-table {
            background: #f8fafc;
            border-radius: 10px;
            overflow: hidden;
        }

        .order-items-table table {
            margin: 0;
        }

        .order-items-table th {
            background: #e2e8f0;
            color: #475569;
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .order-items-table td {
            padding: 15px;
            border: none;
            color: #64748b;
        }

        .order-total-section {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .order-total-amount {
            font-size: 2rem;
            font-weight: 900;
            color: #1e293b;
        }

        @media (max-width: 1200px) {
            .account-content {
                padding: 30px 40px;
            }
        }

        @media (max-width: 768px) {
            .account-layout {
                flex-direction: column;
            }


            .account-content {
                max-width: 100%;
                padding: 20px 15px;
            }

            .orders-title {
                font-size: 28px;
            }

            .orders-subtitle {
                font-size: 1rem;
            }

            .order-card {
                padding: 20px;
                border-radius: 12px;
            }

            .order-actions {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
            }

            .empty-section {
                padding: 40px 20px;
            }

            .empty-section i {
                font-size: 3rem;
            }
        }
    </style>
</head>

<body class="page-background">
    <?php include '../includes/header.php'; ?>

    <!-- Account Layout -->
    <div class="account-layout">
        <!-- Account Sidebar -->
        <?php include '../includes/account_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="account-content">
            <div class="orders-header">
                <h1 class="orders-title">My Orders</h1>
                <p class="orders-subtitle">Track and manage your purchases</p>
            </div>

            <?php if (!empty($orders)): ?>
                <?php
                // Separate orders by status
                $processing_orders = [];
                $delivered_orders = [];

                foreach ($orders as $order) {
                    $order_status = getOrderStatus($order['order_date']);
                    if ($order_status === 'DELIVERED') {
                        $delivered_orders[] = $order;
                    } else {
                        $processing_orders[] = $order;
                    }
                }
                ?>

                <!-- Processing Orders Section -->
                <div class="orders-section">
                    <h2 class="section-title">
                        PROCESSING
                    </h2>

                    <?php if (!empty($processing_orders)): ?>
                        <div class="orders-grid">
                            <?php foreach ($processing_orders as $order): ?>
                                <?php
                                $order_status = getOrderStatus($order['order_date']);
                                $order_items = get_order_details_ctr($order['order_id']);
                                $total_items = count($order_items);
                                ?>
                                <div class="order-card processing">
                                    <div class="order-status <?= strtolower(str_replace(' ', '-', $order_status)) ?>">
                                        <?= $order_status ?>
                                    </div>

                                    <div class="order-images">
                                        <?php
                                        $display_items = array_slice($order_items, 0, 4);
                                        foreach ($display_items as $item):
                                        ?>
                                            <div class="order-image">
                                                <?php
                                                // Direct server URL approach for images
                                                $product_image = $item['product_image'] ?? '';
                                                if (!empty($product_image) && $product_image !== 'null') {
                                                    // Clean filename and use server URL
                                                    $clean_image = str_replace(['uploads/', 'images/', '../', './'], '', $product_image);
                                                    $image_url = 'http://169.239.251.102:442/~chelsea.somuah/uploads/' . ltrim($clean_image, '/');
                                                } else {
                                                    // Use placeholder with product title
                                                    $product_title = htmlspecialchars($item['product_title'] ?? 'Tech Product');
                                                    $image_url = "data:image/svg+xml;base64," . base64_encode('
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80">
                                                            <rect width="100%" height="100%" fill="#f3f4f6"/>
                                                            <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="8" fill="#6b7280" text-anchor="middle" dominant-baseline="middle">Tech Product</text>
                                                        </svg>
                                                    ');
                                                }
                                                ?>
                                                <img src="<?= $image_url ?>"
                                                    alt="<?= htmlspecialchars($item['product_title'] ?? 'Product') ?>"
                                                    onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjRjNGNEY2Ii8+Cjx0ZXh0IHg9IjQwIiB5PSI0MCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjgiIGZpbGw9IiM2QjcyODAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiPk5vIEltYWdlPC90ZXh0Pgo8L3N2Zz4K'">
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if ($total_items > 4): ?>
                                            <div class="order-more">
                                                + <?= $total_items - 4 ?> more
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-details">
                                        Order <span class="order-number">#<?= htmlspecialchars($order['invoice_no']) ?></span> •
                                        <strong>GH₵<?= number_format($order['total_amount'], 2) ?></strong> •
                                        <?= date('d. M/Y', strtotime($order['order_date'])) ?>
                                    </div>

                                    <div class="order-actions">
                                        <button class="action-btn details-btn" onclick="viewOrderDetails(<?= $order['order_id'] ?>, '<?= htmlspecialchars($order['invoice_no']) ?>')">
                                            Details
                                        </button>
                                        <button class="action-btn track-btn" onclick="trackOrder('<?= htmlspecialchars($order['invoice_no']) ?>', '<?= htmlspecialchars($order['order_date']) ?>')">
                                            Track
                                        </button>
                                        <button class="action-btn refund-btn" onclick="requestRefund(<?= $order['order_id'] ?>, '<?= htmlspecialchars($order['invoice_no']) ?>')">
                                            Refund
                                        </button>
                                        <button class="action-btn cancel-order-btn" onclick="cancelOrder(<?= $order['order_id'] ?>, '<?= htmlspecialchars($order['invoice_no']) ?>')">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-section">
                            <i class="fas fa-clock"></i>
                            <p>No processing orders</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Delivered Orders Section -->
                <div class="orders-section">
                    <h2 class="section-title">
                        DELIVERED
                    </h2>

                    <?php if (!empty($delivered_orders)): ?>
                        <div class="orders-grid">
                            <?php foreach ($delivered_orders as $order): ?>
                                <?php
                                $order_status = getOrderStatus($order['order_date']);
                                $order_items = get_order_details_ctr($order['order_id']);
                                $total_items = count($order_items);
                                ?>
                                <div class="order-card delivered">
                                    <div class="order-status delivered">
                                        DELIVERED
                                    </div>

                                    <div class="order-images">
                                        <?php
                                        $display_items = array_slice($order_items, 0, 4);
                                        foreach ($display_items as $item):
                                        ?>
                                            <div class="order-image">
                                                <?php
                                                // Direct server URL approach for images
                                                $product_image = $item['product_image'] ?? '';
                                                if (!empty($product_image) && $product_image !== 'null') {
                                                    // Clean filename and use server URL
                                                    $clean_image = str_replace(['uploads/', 'images/', '../', './'], '', $product_image);
                                                    $image_url = 'http://169.239.251.102:442/~chelsea.somuah/uploads/' . ltrim($clean_image, '/');
                                                } else {
                                                    // Use placeholder with product title
                                                    $product_title = htmlspecialchars($item['product_title'] ?? 'Tech Product');
                                                    $image_url = "data:image/svg+xml;base64," . base64_encode('
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80">
                                                            <rect width="100%" height="100%" fill="#f3f4f6"/>
                                                            <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="8" fill="#6b7280" text-anchor="middle" dominant-baseline="middle">Tech Product</text>
                                                        </svg>
                                                    ');
                                                }
                                                ?>
                                                <img src="<?= $image_url ?>"
                                                    alt="<?= htmlspecialchars($item['product_title'] ?? 'Product') ?>"
                                                    onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjRjNGNEY2Ii8+Cjx0ZXh0IHg9IjQwIiB5PSI0MCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjgiIGZpbGw9IiM2QjcyODAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiPk5vIEltYWdlPC90ZXh0Pgo8L3N2Zz4K'">
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if ($total_items > 4): ?>
                                            <div class="order-more">
                                                + <?= $total_items - 4 ?> more
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-details">
                                        Order <span class="order-number">#<?= htmlspecialchars($order['invoice_no']) ?></span> •
                                        <strong>GH₵<?= number_format($order['total_amount'], 2) ?></strong> •
                                        <?= date('d. M/Y', strtotime($order['order_date'])) ?>
                                    </div>

                                    <div class="order-actions">
                                        <button class="action-btn details-btn" onclick="viewOrderDetails(<?= $order['order_id'] ?>, '<?= htmlspecialchars($order['invoice_no']) ?>')">
                                            Details
                                        </button>
                                        <button class="action-btn rate-btn" onclick="openRatingModal(<?= $order['order_id'] ?>)">
                                            Rate this product
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-section">
                            <i class="fas fa-box"></i>
                            <p>No delivered orders</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="../index.php" class="start-shopping-btn">Start Shopping</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade order-modal" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>
                        Order Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading order details...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row align-items-start">
                    <!-- First Column: Logo and Social -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-brand">
                            <div class="footer-logo" style="margin-bottom: 20px;">
                                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                                    alt="Gadget Garage">
                            </div>
                            <p class="footer-description">Your trusted partner for premium tech devices, expert repairs, and innovative solutions.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- Navigation Links -->
                    <div class="col-lg-5 col-md-12">
                        <div class="row">
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Get Help</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Help Center</a></li>
                                    <li><a href="terms_conditions.php">Shipping Info</a></li>
                                    <li><a href="terms_conditions.php">Returns</a></li>
                                    <li><a href="contact.php">Contact Us</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Company</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Careers</a></li>
                                    <li><a href="contact.php">About</a></li>
                                    <li><a href="contact.php">Stores</a></li>
                                    <li><a href="contact.php">Want to Collab?</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Quick Links</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Size Guide</a></li>
                                    <li><a href="contact.php">Sitemap</a></li>
                                    <li><a href="contact.php">Gift Cards</a></li>
                                    <li><a href="contact.php">Check Gift Card Balance</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Right Side: Email Signup Form -->
                    <div class="col-lg-4 col-md-12 mb-4">
                        <div class="newsletter-signup-section">
                            <h3 class="newsletter-title">SIGN UP FOR DISCOUNTS + UPDATES</h3>
                            <form class="newsletter-form" id="newsletterForm">
                                <input type="text" class="newsletter-input" placeholder="Phone Number or Email" required>
                                <button type="submit" class="newsletter-submit-btn">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                            <p class="newsletter-disclaimer">
                                By signing up for email, you agree to Gadget Garage's <a href="terms_conditions.php">Terms of Service</a> and <a href="legal.php">Privacy Policy</a>.
                            </p>
                            <p class="newsletter-disclaimer">
                                By submitting your phone number, you agree to receive recurring automated promotional and personalized marketing text messages (e.g. cart reminders) from Gadget Garage at the cell number used when signing up. Consent is not a condition of any purchase. Reply HELP for help and STOP to cancel. Msg frequency varies. Msg & data rates may apply. <a href="terms_conditions.php">View Terms</a> & <a href="legal.php">Privacy</a>.
                            </p>
                        </div>
                    </div>
                </div>
                <hr class="footer-divider">
                <div class="footer-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-12 text-center">
                            <p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/header.js"></script>

    <script>
        // Helper function for showing order details modal (used by viewOrderDetails from head)
        function showOrderDetailsModal(order, orderReference) {
            const orderDate = new Date(order.order_date);
            const estimatedDelivery = new Date(orderDate);
            estimatedDelivery.setDate(estimatedDelivery.getDate() + 4);

            const formatDate = (date) => {
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return `${days[date.getDay()]}, ${date.getDate()}. ${months[date.getMonth()].toUpperCase()}`;
            };

            // Calculate delivery status
            const daysSinceOrder = Math.floor((new Date() - orderDate) / (1000 * 60 * 60 * 24));
            let currentStep = 0;
            if (daysSinceOrder >= 4) currentStep = 4;
            else if (daysSinceOrder >= 2) currentStep = 3;
            else if (daysSinceOrder >= 1) currentStep = 2;
            else currentStep = 1;

            const progressPercentage = (currentStep / 4) * 100;

            const modalHTML = `
                <div style="text-align: left; max-width: 900px; margin: 0 auto;">
                    <!-- Delivery Progress -->
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px;">
                        <h6 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: #6c757d; text-transform: uppercase;">Estimated Delivery:</h6>
                        <h3 style="margin: 0 0 20px 0; font-size: 24px; font-weight: bold; color: #212529;">${formatDate(estimatedDelivery)}</h3>
                        
                        <div style="background: #e9ecef; height: 8px; border-radius: 20px; margin-bottom: 15px; overflow: hidden;">
                            <div style="background: linear-gradient(90deg, #28a745, #20c997); height: 100%; width: ${progressPercentage}%; transition: width 0.3s ease;"></div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; gap: 10px;">
                            <div style="flex: 1; text-align: center;">
                                <div style="font-size: 20px; color: ${currentStep >= 1 ? '#28a745' : '#dee2e6'}; margin-bottom: 5px;">✓</div>
                                <div style="font-size: 11px; color: ${currentStep >= 1 ? '#212529' : '#6c757d'}; font-weight: 600;">Ordered</div>
                            </div>
                            <div style="flex: 1; text-align: center;">
                                <div style="font-size: 20px; color: ${currentStep >= 2 ? '#28a745' : '#dee2e6'}; margin-bottom: 5px;">✓</div>
                                <div style="font-size: 11px; color: ${currentStep >= 2 ? '#212529' : '#6c757d'}; font-weight: 600;">Shipped</div>
                            </div>
                            <div style="flex: 1; text-align: center;">
                                <div style="font-size: 20px; color: ${currentStep >= 3 ? '#28a745' : '#dee2e6'}; margin-bottom: 5px;">${currentStep >= 3 ? '✓' : '○'}</div>
                                <div style="font-size: 11px; color: ${currentStep >= 3 ? '#212529' : '#6c757d'}; font-weight: 600;">Arriving Soon</div>
                            </div>
                            <div style="flex: 1; text-align: center;">
                                <div style="font-size: 20px; color: ${currentStep >= 4 ? '#28a745' : '#dee2e6'}; margin-bottom: 5px;">${currentStep >= 4 ? '✓' : '○'}</div>
                                <div style="font-size: 11px; color: ${currentStep >= 4 ? '#212529' : '#6c757d'}; font-weight: 600;">Delivered</div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Info -->
                    <div style="background: white; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px;">
                            <div>
                                <div style="font-size: 12px; color: #6c757d; margin-bottom: 5px;">Order #</div>
                                <div style="font-weight: 600;">${orderReference}</div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #6c757d; margin-bottom: 5px;">Date Placed</div>
                                <div style="font-weight: 600;">${new Date(order.order_date).toLocaleString()}</div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #6c757d; margin-bottom: 5px;">Total</div>
                                <div style="font-weight: 600; color: #28a745;">GH₵${parseFloat(order.total_amount).toFixed(2)}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Three Columns -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                        <!-- Delivery Address -->
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <h6 style="font-size: 13px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; color: #495057;">Delivery Address</h6>
                            <p style="margin: 0; font-size: 14px; line-height: 1.6;">${order.customer_name || 'N/A'}<br>${order.customer_city || ''}<br>${order.customer_country || 'Ghana'}</p>
                        </div>

                        <!-- Shipping Methods -->
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <h6 style="font-size: 13px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; color: #495057;">Shipping Methods</h6>
                            <p style="margin: 0; font-size: 14px; line-height: 1.6;">In Transit<br>Standard Delivery</p>
                        </div>

                        <!-- Billing Address -->
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <h6 style="font-size: 13px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; color: #495057;">Billing Address</h6>
                            <p style="margin: 0; font-size: 14px; line-height: 1.6;">${order.customer_name || 'N/A'}<br>${order.customer_city || ''}<br>${order.customer_country || 'Ghana'}</p>
                        </div>
                    </div>

                    <!-- Product List -->
                    <div style="background: white; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px;">
                        <h6 style="font-size: 15px; font-weight: 700; margin-bottom: 15px; text-transform: uppercase;">Product in Order</h6>
                        <p style="font-size: 13px; color: #6c757d; margin-bottom: 15px;">Once your package is delivered, drop us a review!</p>
                        
                        ${order.items ? order.items.map(item => `
                            <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                                <img src="${item.product_image || '../images/placeholder.jpg'}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;" alt="${item.product_title}">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; margin-bottom: 5px;">${item.product_title}</div>
                                    <div style="font-size: 13px; color: #6c757d;">Size: ${item.size || 'N/A'}</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 700; font-size: 16px; color: #212529;">GH₵${parseFloat(item.product_price).toFixed(2)}</div>
                                    <div style="font-size: 13px; color: #6c757d;">Qty: ${item.qty}</div>
                                </div>
                            </div>
                        `).join('') : '<p>No items found</p>'}
                    </div>
                </div>
            `;

            Swal.fire({
                title: `Order #${orderReference}`,
                html: modalHTML,
                width: '1000px',
                showCloseButton: true,
                showConfirmButton: true,
                confirmButtonText: 'Close',
                customClass: {
                    popup: 'order-details-modal',
                    confirmButton: 'btn btn-primary'
                }
            });
        }

        // Request Refund Function (duplicate removed - already in head)
            const refundFormHTML = `
                <form id="refundForm" style="text-align: left; max-width: 600px; margin: 0 auto;">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h6 style="margin: 0 0 5px 0; color: #6c757d; font-size: 12px; text-transform: uppercase;">Order Reference</h6>
                        <strong style="font-size: 16px; color: #212529;">#${orderReference}</strong>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">First Name *</label>
                        <input type="text" id="firstName" name="firstName" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Last Name *</label>
                        <input type="text" id="lastName" name="lastName" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Email *</label>
                        <input type="email" id="email" name="email" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Phone *</label>
                        <input type="tel" id="phone" name="phone" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Refund Amount (Optional)</label>
                        <input type="number" id="refundAmount" name="refundAmount" step="0.01" min="0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;"
                               placeholder="Leave blank for full refund">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #212529;">Reason for Refund *</label>
                        <textarea id="reasonForRefund" name="reasonForRefund" required rows="4"
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; resize: vertical;"
                                  placeholder="Please provide details about why you are requesting a refund..."></textarea>
                    </div>
                </form>
            `;

            Swal.fire({
                title: 'Request Refund',
                html: refundFormHTML,
                width: '700px',
                showCancelButton: true,
                confirmButtonText: 'Submit Request',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6c757d',
                showCloseButton: true,
                allowOutsideClick: false,
                preConfirm: () => {
                    const form = document.getElementById('refundForm');
                    const formData = new FormData(form);

                    // Validate required fields
                    const firstName = formData.get('firstName').trim();
                    const lastName = formData.get('lastName').trim();
                    const email = formData.get('email').trim();
                    const phone = formData.get('phone').trim();
                    const reason = formData.get('reasonForRefund').trim();

                    if (!firstName || !lastName || !email || !phone || !reason) {
                        Swal.showValidationMessage('Please fill in all required fields');
                        return false;
                    }

                    // Validate email format
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        Swal.showValidationMessage('Please enter a valid email address');
                        return false;
                    }

                    return {
                        orderId: orderId,
                        orderReference: orderReference,
                        firstName: firstName,
                        lastName: lastName,
                        email: email,
                        phone: phone,
                        refundAmount: formData.get('refundAmount') || null,
                        reason: reason
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Submitting Request...',
                        html: 'Please wait while we process your refund request.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit refund request
                    fetch('../actions/submit_refund_action.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(result.value)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Request Submitted!',
                                    html: `Your refund request for order <strong>#${orderReference}</strong> has been submitted successfully.<br><br>
                                       <strong>Reference ID:</strong> ${data.refund_id}<br><br>
                                       You will receive an email confirmation shortly. Our team will review your request within 2-3 business days.`,
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#10b981'
                                });
                            } else {
                                Swal.fire({
                                    title: 'Request Failed',
                                    text: data.message || 'There was an error submitting your refund request. Please try again.',
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Network Error',
                                text: 'Unable to submit your request. Please check your internet connection and try again.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#ef4444'
                            });
                        });
                }
            });
        }

        // Cancel Order Function (duplicate removed - already in head)

        // Display Order Details in Modal (Helper function - not used currently)
        function displayOrderDetails(order) {
            const content = `
                <div class="order-info-section">
                    <h6><i class="fas fa-info-circle me-2"></i>Order Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order ID:</strong> #${order.order_reference}</p>
                            <p><strong>Order Date:</strong> ${new Date(order.order_date).toLocaleDateString()}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tracking Number:</strong> ${order.tracking_number || 'N/A'}</p>
                            <p><strong>Status:</strong>
                                <span class="badge bg-${order.order_status === 'completed' ? 'success' : 'warning'}">
                                    ${order.order_status || 'Processing'}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="order-info-section">
                    <h6><i class="fas fa-user me-2"></i>Customer Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> ${order.customer_name}</p>
                            <p><strong>Email:</strong> ${order.customer_email}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Phone:</strong> ${order.customer_contact}</p>
                            <p><strong>Location:</strong> ${order.customer_city}, ${order.customer_country}</p>
                        </div>
                    </div>
                </div>

                <div class="order-info-section">
                    <h6><i class="fas fa-shopping-cart me-2"></i>Order Items</h6>
                    <div class="order-items-table">
                        <table class="table table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map(item => `
                                    <tr>
                                        <td>${item.product_title}</td>
                                        <td>${item.qty}</td>
                                        <td>GH₵${parseFloat(item.product_price).toFixed(2)}</td>
                                        <td>GH₵${(parseFloat(item.product_price) * parseInt(item.qty)).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="order-total-section">
                    <h6 class="mb-3">Total Amount</h6>
                    <div class="order-total-amount">GH₵${parseFloat(order.total_amount || order.payment_amount || 0).toFixed(2)}</div>
                    <small class="text-muted">Currency: Ghana Cedis</small>
                </div>
            `;

            document.getElementById('orderDetailsContent').innerHTML = content;
        }

        // Promo Timer - EXACT COPY FROM CART
        function updatePromoTimer() {
            const timer = document.getElementById('promoTimer');
            if (timer) {
                const now = new Date();
                const endOfDay = new Date(now);
                endOfDay.setHours(23, 59, 59, 999);

                const diff = endOfDay - now;

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                timer.textContent = `${hours.toString().padStart(2, '0')}h:${minutes.toString().padStart(2, '0')}m:${seconds.toString().padStart(2, '0')}s`;
            }
        }

        // Update timer every second
        setInterval(updatePromoTimer, 1000);
        updatePromoTimer();

        // Header search functionality - EXACT COPY FROM CART
        function performHeaderSearch() {
            const searchInput = document.getElementById('headerSearchInput');
            const searchTerm = searchInput.value.trim();

            if (searchTerm) {
                window.location.href = `shop.php?search=${encodeURIComponent(searchTerm)}`;
            }
        }

        // Search on Enter key
        const headerSearchInput = document.getElementById('headerSearchInput');
        if (headerSearchInput) {
            headerSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performHeaderSearch();
                }
            });
        }

        // Timer functionality
        function updateTimer() {
            const timerElement = document.getElementById('promoTimer');
            if (timerElement) {
                const now = new Date().getTime();
                const nextDay = new Date();
                nextDay.setDate(nextDay.getDate() + 1);
                nextDay.setHours(0, 0, 0, 0);

                const distance = nextDay.getTime() - now;

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60 * 60)) / 1000);

                timerElement.textContent = `${days}d:${hours}h:${minutes}m:${seconds}s`;
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Dropdown navigation functions with timeout delays - MUST BE GLOBAL for inline handlers
        let dropdownTimeout;
        let shopDropdownTimeout;
        let moreDropdownTimeout;
        let userDropdownTimeout;

        function showDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) {
                clearTimeout(dropdownTimeout);
                dropdown.classList.add('show');
            }
        };

        function hideDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) {
                clearTimeout(dropdownTimeout);
                dropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        };

        function showShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) {
                clearTimeout(shopDropdownTimeout);
                dropdown.classList.add('show');
            }
        };

        function hideShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) {
                clearTimeout(shopDropdownTimeout);
                shopDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        };

        function showMoreDropdown() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) {
                clearTimeout(moreDropdownTimeout);
                dropdown.classList.add('show');
            }
        };

        function hideMoreDropdown() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) {
                clearTimeout(moreDropdownTimeout);
                moreDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        };

        function showUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                clearTimeout(userDropdownTimeout);
                dropdown.classList.add('show');
            }
        };

        function hideUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                clearTimeout(userDropdownTimeout);
                userDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        };

        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        };

        // Enhanced dropdown behavior
        document.addEventListener('DOMContentLoaded', function() {
            const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
            const brandsDropdown = document.getElementById('shopDropdown');

            if (shopCategoriesBtn && brandsDropdown) {
                shopCategoriesBtn.addEventListener('mouseenter', showDropdown);
                shopCategoriesBtn.addEventListener('mouseleave', hideDropdown);
                brandsDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(dropdownTimeout);
                });
                brandsDropdown.addEventListener('mouseleave', hideDropdown);
            }

            const userAvatar = document.querySelector('.user-avatar');
            const userDropdown = document.getElementById('userDropdownMenu');

            if (userAvatar && userDropdown) {
                userAvatar.addEventListener('mouseenter', showUserDropdown);
                userAvatar.addEventListener('mouseleave', hideUserDropdown);
                userDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(userDropdownTimeout);
                });
                userDropdown.addEventListener('mouseleave', hideUserDropdown);
            }
        });

        // Language and theme functions
        function changeLanguage(lang) {
            console.log('Language changed to:', lang);
        }

        function toggleTheme() {
            const toggle = document.getElementById('themeToggle');
            const body = document.body;
            if (toggle) {
                toggle.classList.toggle('active');
                body.classList.toggle('dark-mode');
                localStorage.setItem('darkMode', body.classList.contains('dark-mode'));
            }
        }

        function openProfilePictureModal() {
            console.log('Open profile picture modal');
        }

        // Load dark mode preference on page load (openRatingModal is already defined in head)

        // Also define them without window prefix for onclick handlers (like wishlist does)
        var viewOrderDetails = window.viewOrderDetails;
        var trackOrder = window.trackOrder;
        var requestRefund = window.requestRefund;
        var cancelOrder = window.cancelOrder;
        var openRatingModal = window.openRatingModal;

        // Initialize page on load
        document.addEventListener('DOMContentLoaded', function() {
            // Load dark mode preference
            const darkMode = localStorage.getItem('darkMode') === 'true';
            const toggle = document.getElementById('themeToggle');
            if (darkMode && toggle) {
                toggle.classList.add('active');
                document.body.classList.add('dark-mode');
            }

            // Verify all functions are available
            console.log('Order page functions initialized:');
            console.log('viewOrderDetails:', typeof window.viewOrderDetails);
            console.log('trackOrder:', typeof window.trackOrder);
            console.log('requestRefund:', typeof window.requestRefund);
            console.log('cancelOrder:', typeof window.cancelOrder);
            console.log('openRatingModal:', typeof window.openRatingModal);
        });

        // Ensure functions are available immediately (before DOM ready)
        console.log('Order management functions loaded');
    </script>
</body>

</html>
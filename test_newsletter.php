<?php
session_start();
require_once('settings/core.php');

echo "<h1>Newsletter Popup Test</h1>";

$is_logged_in = check_login();
echo "<p>Logged in: " . ($is_logged_in ? 'Yes' : 'No') . "</p>";

if ($is_logged_in) {
    $customer_id = $_SESSION['user_id'];
    echo "<p>Customer ID: $customer_id</p>";

    // Test just_logged_in flag
    if (isset($_SESSION['just_logged_in'])) {
        echo "<p>just_logged_in flag: SET</p>";
    } else {
        echo "<p>just_logged_in flag: NOT SET</p>";
    }

    // Test helper functions
    try {
        require_once(__DIR__ . '/helpers/newsletter_helper.php');

        echo "<h3>Helper Function Tests:</h3>";

        $is_new_session = is_new_login_session();
        echo "<p>is_new_login_session(): " . ($is_new_session ? 'TRUE' : 'FALSE') . "</p>";

        $should_show = should_show_newsletter_popup($customer_id);
        echo "<p>should_show_newsletter_popup($customer_id): " . ($should_show ? 'TRUE' : 'FALSE') . "</p>";

        $final_result = $is_new_session && $should_show;
        echo "<p><strong>Final Result (should show popup): " . ($final_result ? 'TRUE' : 'FALSE') . "</strong></p>";

    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Please log in first to test newsletter functionality.</p>";
    echo "<p><a href='login/login.php'>Login</a></p>";
}

// Test newsletter popup manually
echo "<hr>";
echo "<h3>Manual Popup Test</h3>";
echo "<button onclick='showNewsletterPopup()'>Test Newsletter Popup</button>";

// Include the newsletter popup files
echo "<script src='js/newsletter-popup.js'></script>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>";

echo "<script>
    console.log('Test page loaded');
    console.log('showNewsletterPopup function available:', typeof showNewsletterPopup);
</script>";
?>
<?php
/**
 * Newsletter Helper Functions
 */

require_once(__DIR__ . '/../settings/db_class.php');

/**
 * Check if user should see newsletter popup
 * @param int $customer_id
 * @return bool
 */
function should_show_newsletter_popup($customer_id) {
    if (!$customer_id) {
        return false;
    }

    try {
        $db = new db_connection();
        if (!$db->db_connect()) {
            return false;
        }

        $conn = $db->db_conn();

        // Check if user has already seen the popup
        $stmt = $conn->prepare("SELECT newsletter_popup_shown FROM customer WHERE customer_id = ?");
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return !$row['newsletter_popup_shown']; // Show if not shown yet
        }

        return false;
    } catch (Exception $e) {
        error_log("Newsletter helper error: " . $e->getMessage());
        return false;
    } finally {
        // Clean up - mysqli connections are automatically closed when script ends
        if (isset($conn)) {
            $conn->close();
        }
    }
}

/**
 * Check if this is a new login session (to trigger popup)
 * @return bool
 */
function is_new_login_session() {
    // Check if this is the first page load after login
    if (isset($_SESSION['just_logged_in'])) {
        unset($_SESSION['just_logged_in']); // Remove flag after checking
        return true;
    }
    return false;
}
?>
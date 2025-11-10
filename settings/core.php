<?php

/**
 * Core Functions — role-safe + redirect helpers
 * Adjust ADMIN_ROLE_ID below if your schema differs.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** ====== CONFIG: change this if your DB uses a different id for admin ====== */
const ADMIN_ROLE_ID = 2; // <-- if your DB uses 1 for admin, change to 1

/** Utility: strict redirect and stop */
function redirect(string $path)
{
    header('Location: ' . $path, true, 302);
    exit;
}

/** Check if user is logged in (based on your actual session keys) */
function check_login(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['email'])
        && !empty($_SESSION['user_id'])
        && !empty($_SESSION['email']);
}

/** Get logged-in user id */
function get_user_id()
{
    return check_login() ? $_SESSION['user_id'] : null;
}

/** Get logged-in user email */
function get_user_email()
{
    return check_login() ? $_SESSION['email'] : null;
}

/** Get logged-in user name */
function get_user_name()
{
    return check_login() ? ($_SESSION['name'] ?? null) : null;
}

/** Get logged-in user role (returns int when possible) */
function get_user_role()
{
    if (!check_login()) return null;
    if (!isset($_SESSION['role'])) return null;

    $role = $_SESSION['role'];

    if (is_string($role)) {
        $role = trim($role);

        // Numeric string → int
        if ($role !== '' && ctype_digit($role)) {
            return (int)$role;
        }

        // Common string labels
        $lower = strtolower($role);
        if ($lower === 'admin' || $lower === 'administrator') {
            return ADMIN_ROLE_ID;
        }
    }

    return $role;
}

/** Is admin (supports int id and string labels) */
function check_admin(): bool
{
    if (!check_login()) return false;

    $role = get_user_role();

    // Treat ADMIN_ROLE_ID as admin
    if ($role === ADMIN_ROLE_ID) return true;

    // In case someone stored role as literal string 'admin'
    if (is_string($role)) {
        $lower = strtolower($role);
        return ($lower === 'admin' || $lower === 'administrator');
    }

    return false;
}

/** Gatekeepers you can use at the top of pages */
function require_login(string $fallback = '/login/login.php')
{
    if (!check_login()) redirect($fallback);
}

function require_admin(string $fallback = '/index.php')
{
    if (!check_admin()) redirect($fallback);
}

// Initialize image directories on first load
$image_helper_path = __DIR__ . '/../helpers/image_helper.php';
if (file_exists($image_helper_path)) {
    require_once($image_helper_path);
    ensure_image_directories();
}

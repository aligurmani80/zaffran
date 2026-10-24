<?php
/**
 * Zaffran Admin Panel — Logout
 */
define('ZAFFRAN_APP', true);
require_once __DIR__ . '/auth.php';

initAdminSession();

if (isAdminLoggedIn()) {
    logAdminAudit('admin_logout', 'admin', (string)$_SESSION['admin_id'], 'Admin logged out.');
}

// Clear admin session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header('Location: login.php');
exit;

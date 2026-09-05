<?php
// THE HANGAR - GUND-ORDER SYSTEM ADMIN AUTHENTICATION GUARD
// Unified with primary login platform

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn(): bool {
    if (!empty($_SESSION['hangar_admin_logged']) && $_SESSION['hangar_admin_logged'] === true) {
        return true;
    }
    if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    return false;
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        $msg = 'Administrator authorization required. Please authenticate with an admin account.';
        header('Location: ../login/index.php?status=error&message=' . urlencode($msg));
        exit;
    }
}

function logoutAdmin(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ../login/index.php?status=success&message=' . urlencode('Session terminated. You have been logged out.'));
    exit;
}

<?php
// THE HANGAR - GUND-ORDER SYSTEM ADMIN AUTHENTICATION GUARD

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn() {
    return isset($_SESSION['hangar_admin_logged']) && $_SESSION['hangar_admin_logged'] === true;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function verifyAdminCredentials($username, $password) {
    // Default Admin Credentials for GUND-ORDER SYSTEM
    $validUser = 'admin';
    $validPass = 'hangar2026';

    return ($username === $validUser && $password === $validPass);
}

function logoutAdmin() {
    $_SESSION = [];
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
}

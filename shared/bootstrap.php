<?php
// THE HANGAR - GUND-ORDER SYSTEM
// SHARED BOOTSTRAP: UNIFIED SESSION & ASSET PATH RESOLUTION

function hangarBootstrap(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['hangar_admin_logged']);
    $userRole   = $_SESSION['user_role'] ?? (!empty($_SESSION['hangar_admin_logged']) ? 'admin' : null);
    $userName   = $_SESSION['username'] ?? ($_SESSION['hangar_admin_user'] ?? 'Pilot');

    $resolvePath = function(string $folder): string {
        if (is_dir($folder)) {
            return $folder;
        }
        if (is_dir('../' . $folder)) {
            return '../' . $folder;
        }
        if (is_dir('../../' . $folder)) {
            return '../../' . $folder;
        }
        return $folder;
    };

    $loginDir = $resolvePath('login');
    $adminDir = $resolvePath('admin');
    $profileDir = $resolvePath('homepage/profile');

    return [
        'isLoggedIn'      => $isLoggedIn,
        'userRole'        => $userRole,
        'userName'        => $userName,
        'buttonsPath'     => $resolvePath('buttons'),
        'promotionalPath' => $resolvePath('promotional'),
        'footerPath'      => $resolvePath('footer'),
        'logosPath'       => $resolvePath('logos'),
        'loginPath'       => $loginDir . '/',
        'logoutPath'      => $loginDir . '/logout.php',
        'adminPath'       => $adminDir . '/index.php',
        'profilePath'     => $profileDir . '/profile.php',
    ];
}

<?php
// THE HANGAR - GUND-ORDER SYSTEM
// AUTHENTICATION & REGISTRATION CONTROLLER
// Based on webdev1-midterm-discussion-master function.php architecture

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'login' || isset($_POST['login-submit'])) {
    processLogin();
} elseif ($action === 'register' || isset($_POST['register-submit'])) {
    processRegister();
} else {
    header('Location: index.php');
    exit;
}

function processLogin(): void
{
    $result = validateLoginInput($_POST);
    $errors = $result['errors'];

    if (!empty($errors)) {
        $message = implode(' ', $errors);
        header('Location: index.php?status=error&tab=login&message=' . urlencode($message));
        exit;
    }

    $identifier = $result['data']['identifier'];
    $password   = $result['data']['password'];

    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            header('Location: index.php?status=error&tab=login&message=' . urlencode('Service temporarily unavailable. Please try again shortly.'));
            exit;
        }

        // Search for user by either username (callsign) or email address
        $sql = "SELECT id, username, email, password, role FROM users WHERE username = :ident OR email = :ident LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ident', $identifier);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify user and password hash
        if (!$user || !password_verify($password, $user['password'])) {
            // Backward compatibility check for plain-text password if applicable
            if ($user && $user['password'] === $password) {
                // Rehash password to standard BCRYPT hash
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
                $updateStmt->execute([':p' => $newHash, ':id' => $user['id']]);
            } else {
                header('Location: index.php?status=error&tab=login&message=' . urlencode('Invalid Pilot Callsign/Email or Security Passcode. Access Denied.'));
                exit;
            }
        }

        // Prevent session fixation: issue a fresh session ID before populating
        // the session with authenticated user data.
        session_regenerate_id(true);

        // Establish unified session
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        // Role-based destination routing
        if ($user['role'] === 'admin') {
            $_SESSION['hangar_admin_logged'] = true;
            $_SESSION['hangar_admin_user']   = $user['username'];
            header('Location: ../admin/index.php');
            exit;
        }

        // Standard user / pilot account routing
        header('Location: ../homepage/index.php?status=success&message=' . urlencode('Welcome to THE HANGAR, Pilot ' . $user['username'] . '! Systems nominal.'));
        exit;

    } catch (Throwable $e) {
        error_log('Login error: ' . $e->getMessage());
        header('Location: index.php?status=error&tab=login&message=' . urlencode('Service temporarily unavailable. Please try again shortly.'));
        exit;
    }
}

function processRegister(): void
{
    $result = validateRegisterInput($_POST);
    $errors = $result['errors'];

    if (!empty($errors)) {
        $message = implode(' ', $errors);
        header('Location: index.php?status=error&tab=register&message=' . urlencode($message));
        exit;
    }

    $callsign = $result['data']['callsign'];
    $fullName = $result['data']['full_name'];
    $phone    = $result['data']['phone'];
    $email    = $result['data']['email'];
    $password = $result['data']['password'];

    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            header('Location: index.php?status=error&tab=register&message=' . urlencode('Service temporarily unavailable. Please try again shortly.'));
            exit;
        }

        // Check if callsign or email is already taken
        $checkStmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = :u OR email = :e LIMIT 1");
        $checkStmt->execute([':u' => $callsign, ':e' => $email]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if (strcasecmp($existing['username'], $callsign) === 0) {
                $errMsg = "Pilot callsign '{$callsign}' is already registered in G.O.S records.";
            } else {
                $errMsg = "Communication frequency (email) '{$email}' is already in use.";
            }
            header('Location: index.php?status=error&tab=register&message=' . urlencode($errMsg));
            exit;
        }

        // Hash passcode securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user account with default 'user' role
        $sql = "INSERT INTO users (username, email, password, role, full_name, phone) VALUES (:username, :email, :password, 'user', :full_name, :phone)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username'  => $callsign,
            ':email'     => $email,
            ':password'  => $hashedPassword,
            ':full_name' => $fullName,
            ':phone'     => $phone,
        ]);

        // Auto-login: establish the same unified session processLogin() uses,
        // so new pilots go straight into the hangar without a second login step.
        // Prevent session fixation on auto-login after registration
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int)$pdo->lastInsertId();
        $_SESSION['username']   = $callsign;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = 'user';

        header('Location: ../homepage/index.php?status=success&message=' . urlencode("Pilot account [{$callsign}] registered successfully! Welcome to THE HANGAR."));
        exit;

    } catch (Throwable $e) {
        error_log('Registration error: ' . $e->getMessage());
        header('Location: index.php?status=error&tab=register&message=' . urlencode('Service temporarily unavailable. Please try again shortly.'));
        exit;
    }
}

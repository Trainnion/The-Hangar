<?php
require_once __DIR__ . '/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (verifyAdminCredentials($username, $password)) {
        $_SESSION['hangar_admin_logged'] = true;
        $_SESSION['hangar_admin_user'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid Authorization Credentials. Access Denied.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G.O.S Command Access | THE HANGAR ADMIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-color: #0c0e12;
            background-image: 
                radial-gradient(circle at 50% 20%, rgba(63, 196, 225, 0.12) 0%, transparent 60%),
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 30px 30px, 30px 30px;
            font-family: 'Poppins', sans-serif;
            color: #ffffff;
        }

        .loginBox {
            width: 100%;
            max-width: 440px;
            background: rgba(22, 26, 33, 0.88);
            border: 1px solid rgba(63, 196, 225, 0.35);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8), 0 0 30px rgba(63, 196, 225, 0.12);
            border-radius: 10px;
            padding: 2.8rem 2.2rem;
            position: relative;
            backdrop-filter: blur(14px);
        }

        .loginCorner {
            position: absolute;
            width: 14px;
            height: 14px;
            border: 2px solid #3FC4E1;
            pointer-events: none;
        }
        .lcTL { top: -1px; left: -1px; border-right: none; border-bottom: none; }
        .lcTR { top: -1px; right: -1px; border-left: none; border-bottom: none; }
        .lcBL { bottom: -1px; left: -1px; border-right: none; border-top: none; }
        .lcBR { bottom: -1px; right: -1px; border-left: none; border-top: none; }

        .loginHeader {
            text-align: center;
            margin-bottom: 2rem;
        }

        .systemTag {
            display: inline-block;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: #3FC4E1;
            background: rgba(63, 196, 225, 0.1);
            border: 1px solid rgba(63, 196, 225, 0.3);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            margin-bottom: 1rem;
        }

        .loginTitle {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 2px;
            margin: 0 0 0.3rem 0;
        }

        .loginSub {
            font-size: 0.82rem;
            color: #999999;
            margin: 0;
        }

        .alertBox {
            background: rgba(255, 68, 68, 0.15);
            border: 1px solid rgba(255, 68, 68, 0.4);
            color: #ff8888;
            font-size: 0.82rem;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .formGroup {
            margin-bottom: 1.3rem;
        }

        .formGroup label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #b0b0b0;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
        }

        .formGroup input {
            width: 100%;
            padding: 0.85rem 1rem;
            background: rgba(14, 17, 21, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 6px;
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            box-sizing: border-box;
            transition: all 0.25s ease;
        }

        .formGroup input:focus {
            outline: none;
            border-color: #3FC4E1;
            box-shadow: 0 0 12px rgba(63, 196, 225, 0.3);
        }

        .loginBtn {
            width: 100%;
            padding: 0.95rem;
            background: #3FC4E1;
            color: #231F20;
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(63, 196, 225, 0.35);
            margin-top: 0.5rem;
        }

        .loginBtn:hover {
            background: #59d5f0;
            box-shadow: 0 6px 20px rgba(63, 196, 225, 0.5);
            transform: translateY(-1px);
        }

        .loginFooter {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: #666666;
        }
        .loginFooter a {
            color: #3FC4E1;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="loginBox">
        <span class="loginCorner lcTL"></span>
        <span class="loginCorner lcTR"></span>
        <span class="loginCorner lcBL"></span>
        <span class="loginCorner lcBR"></span>

        <div class="loginHeader">
            <span class="systemTag">GUND-ORDER SYSTEM // G.O.S</span>
            <h1 class="loginTitle">ADMIN ACCESS</h1>
            <p class="loginSub">Enter authorized credentials to manage Hangar operations.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alertBox"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="formGroup">
                <label for="username">Command Callsign</label>
                <input type="text" id="username" name="username" placeholder="e.g. admin" required autofocus>
            </div>

            <div class="formGroup">
                <label for="password">Security Passcode</label>
                <input type="password" id="password" name="password" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="loginBtn">AUTHORIZE LOGIN</button>
        </form>

        <div class="loginFooter">
            <p>Default: <code>admin</code> / <code>hangar2026</code></p>
            <p><a href="../homepage/">← Return to Public Storefront</a></p>
        </div>
    </div>
</body>
</html>

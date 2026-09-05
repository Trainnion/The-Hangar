<?php
// THE HANGAR - GUND-ORDER SYSTEM (G.O.S)
// UNIFIED AUTHENTICATION PLATFORM (PILOT & ADMIN ACCESS)
// Based on webdev1-midterm-discussion-master architecture

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database/config.php';

// Ensure database connection and tables exist
getConnection();

$status    = $_GET['status'] ?? null;
$message   = $_GET['message'] ?? null;
$activeTab = $_GET['tab'] ?? 'login';

$promotionalPath = file_exists(__DIR__ . '/promotional') ? 'promotional' : '../promotional';
$buttonsPath     = file_exists(__DIR__ . '/buttons') ? 'buttons' : '../buttons';
$logosPath       = file_exists(__DIR__ . '/logos') ? 'logos' : '../logos';

$isLoggedIn  = !empty($_SESSION['user_id']) || !empty($_SESSION['hangar_admin_logged']);
$currentUser = $_SESSION['username'] ?? ($_SESSION['hangar_admin_user'] ?? null);
$currentRole = $_SESSION['user_role'] ?? (!empty($_SESSION['hangar_admin_logged']) ? 'admin' : 'user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Access Platform | THE HANGAR - GUND-ORDER SYSTEM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Background Ambient Glow & Cockpit Grid -->
    <div class="loginBg">
        <div class="bgOverlay"></div>
        <div class="hudGrid"></div>
    </div>

    <!-- Top Left Quick Return -->
    <a href="../homepage/" class="returnHomeBtn" aria-label="Back to Homepage">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>RETURN TO HANGAR</span>
    </a>

    <!-- Main Authentication Container -->
    <main class="authContainer">
        <div class="authCard">
            <!-- HUD Corner Accents -->
            <span class="hudCorner cornerTL"></span>
            <span class="hudCorner cornerTR"></span>
            <span class="hudCorner cornerBL"></span>
            <span class="hudCorner cornerBR"></span>

            <!-- Brand Header -->
            <header class="authHeader">
                <a href="../homepage/" class="authBrandLogo">
                    <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR" class="brandLogoImg">
                </a>
                <div class="systemBadge">
                    <span class="pulseDot"></span>
                    <span class="systemCode">GUND-ORDER SYSTEM // G.O.S</span>
                </div>
                <h1 class="authTitle">UNIFIED ACCESS PLATFORM</h1>
                <p class="authSubtitle">Single portal for pilot reserves, order fulfillment, and command deck administration.</p>
            </header>

            <!-- Active Logged-in Session Banner -->
            <?php if ($isLoggedIn): ?>
                <div class="sessionBanner">
                    <div class="sessionBannerHeader">
                        <span class="pulseDot"></span>
                        <span>ACTIVE SESSION DETECTED</span>
                    </div>
                    <div class="sessionUserInfo">
                        Callsign: <strong><?php echo htmlspecialchars($currentUser); ?></strong>
                        <span class="sessionRoleBadge <?php echo htmlspecialchars($currentRole); ?>">
                            ROLE: <?php echo strtoupper(htmlspecialchars($currentRole)); ?>
                        </span>
                    </div>
                    <div class="sessionActions">
                        <?php if ($currentRole === 'admin'): ?>
                            <a href="../admin/index.php" class="sessionBtn primary">COMMAND DASHBOARD &rarr;</a>
                        <?php endif; ?>
                        <a href="../homepage/" class="sessionBtn outline">STOREFRONT</a>
                        <a href="logout.php" class="sessionBtn danger">LOGOUT</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Feedback Notifications (Error / Success) -->
            <?php if (!empty($message)): ?>
                <div class="alertBox <?php echo ($status === 'success') ? 'alertSuccess' : 'alertError'; ?>">
                    <div class="alertIcon">
                        <?php if ($status === 'success'): ?>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="alertText"><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <!-- Mode Switcher Tabs (Login / Register) -->
            <div class="authTabs">
                <button type="button" class="tabBtn <?php echo ($activeTab !== 'register') ? 'activeTab' : ''; ?>" id="loginTabBtn">SIGN IN</button>
                <button type="button" class="tabBtn <?php echo ($activeTab === 'register') ? 'activeTab' : ''; ?>" id="registerTabBtn">REGISTER PILOT</button>
            </div>

            <!-- UNIFIED LOGIN FORM -->
            <form class="authForm <?php echo ($activeTab === 'register') ? 'hiddenForm' : ''; ?>" id="loginForm" action="function.php" method="POST" autocomplete="on">
                <input type="hidden" name="action" value="login">

                <!-- Email or Pilot Callsign -->
                <div class="inputGroup">
                    <label for="loginIdentifier">PILOT CALLSIGN OR EMAIL</label>
                    <div class="inputWrapper">
                        <svg class="inputIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <input 
                            type="text" 
                            id="loginIdentifier" 
                            name="identifier" 
                            placeholder="e.g. admin or Amuro_Ray" 
                            required 
                            autofocus
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="inputGroup">
                    <div class="labelRow">
                        <label for="loginPassword">SECURITY PASSCODE</label>
                        <a href="#" class="forgotLink" onclick="alert('Contact your flight supervisor or administrator to reset your passcode.'); return false;">Forgot passcode?</a>
                    </div>
                    <div class="inputWrapper">
                        <svg class="inputIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input 
                            type="password" 
                            id="loginPassword" 
                            name="password" 
                            placeholder="••••••••••••" 
                            required
                        >
                        <button type="button" class="passwordToggleBtn" aria-label="Toggle password visibility">
                            <svg class="eyeIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me / Keep Active -->
                <div class="checkboxRow">
                    <label class="customCheckbox">
                        <input type="checkbox" name="remember" id="rememberMe" checked>
                        <span class="checkmark"></span>
                        <span class="checkboxLabel">Keep G.O.S session synchronized</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="login-submit" class="submitAuthBtn">
                    <span class="btnText">AUTHENTICATE & ENTER</span>
                    <span class="btnGlow"></span>
                </button>
            </form>

            <!-- REGISTER FORM -->
            <form class="authForm <?php echo ($activeTab === 'register') ? '' : 'hiddenForm'; ?>" id="registerForm" action="function.php" method="POST" autocomplete="off">
                <input type="hidden" name="action" value="register">

                <div class="inputGroup">
                    <label for="regCallsign">PILOT CALLSIGN</label>
                    <div class="inputWrapper">
                        <svg class="inputIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <input type="text" id="regCallsign" name="callsign" placeholder="e.g. Setsuna_F_Seiei" required>
                    </div>
                </div>

                <div class="inputGroup">
                    <label for="regEmail">COMMUNICATION FREQUENCY (EMAIL)</label>
                    <div class="inputWrapper">
                        <svg class="inputIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input type="email" id="regEmail" name="email" placeholder="pilot@thehangar.ph" required>
                    </div>
                </div>

                <div class="inputGroup">
                    <label for="regPassword">CREATE SECURITY PASSCODE</label>
                    <div class="inputWrapper">
                        <svg class="inputIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input type="password" id="regPassword" name="password" placeholder="Minimum 6 characters" required>
                        <button type="button" class="passwordToggleBtn" aria-label="Toggle password visibility">
                            <svg class="eyeIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" name="register-submit" class="submitAuthBtn">
                    <span class="btnText">INITIALIZE PILOT ACCOUNT</span>
                    <span class="btnGlow"></span>
                </button>
            </form>



            <!-- Footer Compliance Info -->
            <footer class="authFooter">
                <p>&copy; 2026 THE HANGAR, LLC. ALL COPYRIGHTS RESERVE.</p>
                <div class="authFooterLinks">
                    <a href="#">Security Protocol</a>
                    <span>•</span>
                    <a href="#">Terms of Service</a>
                </div>
            </footer>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>

<?php
$promotionalPath = file_exists(__DIR__ . '/promotional') ? 'promotional' : '../promotional';
$buttonsPath = file_exists(__DIR__ . '/buttons') ? 'buttons' : '../buttons';
$logosPath = file_exists(__DIR__ . '/logos') ? 'logos' : '../logos';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilot Login | THE HANGAR - GUND-ORDER SYSTEM</title>
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
                <h1 class="authTitle">PILOT AUTHENTICATION</h1>
                <p class="authSubtitle">Identify yourself to access your mobile suit reserves & order tracking.</p>
            </header>

            <!-- Mode Switcher Tabs (Login / Register) -->
            <div class="authTabs">
                <button type="button" class="tabBtn activeTab" id="loginTabBtn">SIGN IN</button>
                <button type="button" class="tabBtn" id="registerTabBtn">REGISTER PILOT</button>
            </div>

            <!-- LOGIN FORM -->
            <form class="authForm" id="loginForm" action="#" method="POST" autocomplete="on">
                
                <!-- Email or Pilot ID -->
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
                            placeholder="e.g. Amuro_Ray or pilot@thehangar.ph" 
                            required
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="inputGroup">
                    <div class="labelRow">
                        <label for="loginPassword">SECURITY PASSCODE</label>
                        <a href="#" class="forgotLink">Forgot passcode?</a>
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
                        <input type="checkbox" name="remember" id="rememberMe">
                        <span class="checkmark"></span>
                        <span class="checkboxLabel">Keep G.O.S session synchronized</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submitAuthBtn">
                    <span class="btnText">ACCESS HANGAR</span>
                    <span class="btnGlow"></span>
                </button>
            </form>

            <!-- REGISTER FORM (Hidden by default) -->
            <form class="authForm hiddenForm" id="registerForm" action="#" method="POST" autocomplete="off">
                <div class="inputGroup">
                    <label for="regCallsign">PILOT CALLSIGN</label>
                    <div class="inputWrapper">
                        <svg class="inputIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <input type="text" id="regCallsign" name="callsign" placeholder="Enter your builder nickname" required>
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
                        <input type="password" id="regPassword" name="password" placeholder="At least 8 characters" required>
                    </div>
                </div>

                <button type="submit" class="submitAuthBtn">
                    <span class="btnText">INITIALIZE PILOT ACCOUNT</span>
                    <span class="btnGlow"></span>
                </button>
            </form>

            <!-- Divider -->
            <div class="authDivider">
                <span>OR ACCESS VIA PARTNER NETWORKS</span>
            </div>

            <!-- Social / Bandai Namco ID Login -->
            <div class="partnerLoginGrid">
                <button type="button" class="partnerBtn bandaiNamcoBtn">
                    <span class="partnerLogoText">BANDAI NAMCO ID</span>
                </button>
                <button type="button" class="partnerBtn googleBtn">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                    </svg>
                    <span>GOOGLE</span>
                </button>
            </div>

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

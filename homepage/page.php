<?php
// THE HANGAR - GUND-ORDER SYSTEM
// STATIC CONTENT PAGES (page.php) — About / Privacy / Contact
// Reached from the storefront footer links (admin-editable in ADMIN -> FOOTER CONTENT).
// About & Privacy body content is admin-editable in ADMIN -> STATIC PAGES (settings keys
// page_about / page_privacy, rendered by shared/static_pages.php with built-in defaults).

require_once __DIR__ . '/../shared/bootstrap.php';
extract(hangarBootstrap());
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/../shared/static_pages.php';

$allowedPages = [
    'about'    => ['title' => 'ABOUT THE HANGAR'],
    'privacy'  => ['title' => 'POLICY OF PRIVACY'],
    'contact'  => ['title' => 'CONTACT US'],
];

$p = $_GET['p'] ?? '';
if (!isset($allowedPages[$p])) {
    header('Location: index.php');
    exit;
}
$pageTitle = $allowedPages[$p]['title'];

// Contact details come from the same admin-editable settings the footer uses
$contactNumber = '';
$contactEmail  = '';
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $contactNumber = (string)getSetting($pdo, 'footer_contact_number', '');
        $contactEmail  = (string)getSetting($pdo, 'footer_contact_email', '');
    }
} catch (Exception $e) {
    // non-fatal — page still renders without contact details
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../shared/hud-design.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: #f7f9fb;
            color: var(--brand-dark, #231F20);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .staticPageDeck { flex: 1; width: 100%; }
        .staticPageWrap { max-width: 860px; margin: 2.5rem auto 3rem; padding: 0 1.25rem; }
        .staticPageCard {
            background: #ffffff;
            border: 1px solid #e3e8ee;
            border-top: 4px solid var(--brand-cyan, #3FC4E1);
            border-radius: 10px;
            padding: 2.25rem 2.5rem;
            box-shadow: 0 2px 10px rgba(35, 31, 32, 0.06);
            color: #231F20;
        }
        .staticPageCard h3 {
            font-family: var(--font-heading, 'Poppins', sans-serif);
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            margin: 1.6rem 0 0.5rem;
            color: #231F20;
        }
        .staticPageCard h3:first-child { margin-top: 0; }
        .staticPageCard p, .staticPageCard li {
            font-size: 0.95rem;
            line-height: 1.75;
            color: #4a4a4a;
        }
        .staticPageCard ul { padding-left: 1.25rem; margin: 0.5rem 0 1rem; }
        .staticContactRow { display: flex; align-items: center; gap: 0.75rem; margin: 0.9rem 0; }
        .staticContactRow svg { flex: 0 0 auto; color: var(--brand-cyan, #3FC4E1); }
        .staticContactLink { font-size: 1rem; font-weight: 700; color: #231F20; text-decoration: none; }
        .staticContactLink:hover { color: var(--brand-cyan, #3FC4E1); }
        .staticContactEmpty { font-size: 0.9rem; color: #8a8f98; font-style: italic; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../shared/menu.php'; ?>

    <!-- SECTION 0: TOP NAVBAR (same structure as profile.php) -->
    <header class="headerContainer headerStatic">
        <div class="headerLeft">
            <a href="index.php" class="navItem navBtnHamburger" aria-label="Menu">
                <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
            </a>
            <a href="index.php" class="navItem navLink">HOME</a>
            <a href="search/search.php" class="navItem navLink">PRODUCTS</a>
        </div>

        <div class="headerCenter">
            <a href="index.php" class="navBrand" aria-label="THE HANGAR Home">
                <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
            </a>
        </div>

        <div class="headerRight">
            <a href="cart/cart.php" class="navItem navLink navCart" style="color: var(--brand-cyan);">CART</a>
            <a href="orders/orders.php" class="navItem navLink" style="color: var(--brand-cyan);">MY ORDERS</a>
            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                    <a href="<?php echo $adminPath; ?>" class="navItem navLink" style="color: #ffaa00; font-weight: 700;">[COMMAND DECK]</a>
                <?php else: ?>
                    <a href="profile/profile.php" class="navItem navLink" style="color: #3FC4E1;">PILOT: <?php echo htmlspecialchars($userName); ?></a>
                <?php endif; ?>
                <a href="<?php echo $logoutPath; ?>" class="navItem navLink" title="Sign out of G.O.S">LOG OUT</a>
            <?php else: ?>
                <a href="<?php echo $loginPath; ?>" class="navItem navLink">LOG IN</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- SECTION HEADER STRIP -->
    <div class="headerContainerMK">
        <div class="headerLeftMK">
            <a href="index.php" class="spBackLink">&larr; STOREFRONT</a>
        </div>
        <div class="headerCenterMK">
            <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
        </div>
        <div class="headerRightMK"></div>
    </div>

    <main class="staticPageDeck">
        <div class="staticPageWrap">
            <div class="staticPageCard">
<?php if ($p === 'about' || $p === 'privacy'): ?>
                <?php echo getStaticPageContent($pdo, $p); ?>
<?php else: ?>
                <h3>CONTACT US</h3>
                <p>Questions about an order, a kit's availability, or anything else — we're here to help. Reach us through any of the channels below.</p>
                <div class="staticContactRow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <?php if ($contactNumber !== ''): ?>
                        <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^\d+]/', '', $contactNumber)); ?>" class="staticContactLink"><?php echo htmlspecialchars($contactNumber); ?></a>
                    <?php else: ?>
                        <span class="staticContactEmpty">Contact number not yet set — check back soon.</span>
                    <?php endif; ?>
                </div>
                <div class="staticContactRow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <?php if ($contactEmail !== ''): ?>
                        <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" class="staticContactLink"><?php echo htmlspecialchars($contactEmail); ?></a>
                    <?php else: ?>
                        <span class="staticContactEmpty">Email not yet set — check back soon.</span>
                    <?php endif; ?>
                </div>
                <h3>STORE HOURS</h3>
                <p>Online orders are accepted 24/7. For inquiries and after-sales support, we typically respond within one business day.</p>
                <h3>ORDER CONCERNS</h3>
                <p>If you have an issue with a delivery or a received item, please have your order code ready (it's shown under MY ORDERS) — it helps us pull up your record instantly.</p>
<?php endif; ?>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
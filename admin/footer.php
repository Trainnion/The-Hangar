<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

$pdo = getDBConnection();
$message = '';
$error = '';

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!csrfValid()) {
        header('Location: footer.php?msg=csrf');
        exit;
    }
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $fields = ['contact_number', 'contact_email', 'url_contact', 'url_privacy', 'url_about', 'social_fb', 'social_ig', 'social_x'];
        foreach ($fields as $k) {
            setSetting($pdo, 'footer_' . $k, trim($_POST[$k] ?? ''));
        }
        header('Location: footer.php?msg=saved');
        exit;
    }

    if ($action === 'upload_logo') {
        $logoPath = handleAssetUpload('paylogo_', 'payment_logo_file', '', '', 'assets/uploads/payments');
        if ($logoPath) {
            setSetting($pdo, 'footer_payment_logo', $logoPath);
            header('Location: footer.php?msg=uploaded');
        } else {
            header('Location: footer.php?msg=uploadfail');
        }
        exit;
    }

    if ($action === 'reset_logo') {
        setSetting($pdo, 'footer_payment_logo', '');
        header('Location: footer.php?msg=reset');
        exit;
    }
}

// ---- Load current footer settings ----
$footerFields = [
    'contact_number' => 'CONTACT NUMBER',
    'contact_email'  => 'CONTACT EMAIL',
    'url_contact'    => 'CONTACT PAGE LINK',
    'url_privacy'    => 'PRIVACY PAGE LINK',
    'url_about'      => 'ABOUT PAGE LINK',
    'social_fb'      => 'FACEBOOK LINK',
    'social_ig'      => 'INSTAGRAM LINK',
    'social_x'       => 'X (TWITTER) LINK',
];
$current = [];
foreach (array_keys($footerFields) as $k) {
    $current[$k] = (string)getSetting($pdo, 'footer_' . $k, '');
}

$paymentLogoPath = getSetting($pdo, 'footer_payment_logo', '');
$usingCustomLogo = ($paymentLogoPath !== '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FOOTER CONTENT | THE HANGAR ADMIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="adminSidebar">
        <div>
<a href="index.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>DASHBOARD</span>
                </a>
                <a href="products.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span>PRODUCTS</span>
                </a>
                <a href="orders.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2h12v16a2 2 0 0 1-2-2M6.5 6l4 4M8 8l-2 2"></path>
                        <polyline points="3 4 9 4 9 14 3 14"></polyline>
                        <line x1="5" y1="6" x2="13" y2="6"></line>
                    </svg>
                    <span>ORDERS</span>
                </a>
                <a href="sliders.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span>SLIDERS (SEC 1, 2, 7)</span>
                </a>
                <a href="categories.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                        <line x1="9" y1="6" x2="15" y2="6"></line>
                        <line x1="9" y1="12" x2="15" y2="12"></line>
                        <line x1="9" y1="18" x2="15" y2="18"></line>
                    </svg>
                    <span>CATEGORIES</span>
                </a>
            <div class="sidebarHeader">
                <a href="index.php" class="sidebarBrand">
                    <img src="../promotional/Asset 8.png" alt="THE HANGAR" class="sidebarLogoImg">
                    <div class="sidebarBadge">
<a href="promos.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                    <span>PROMO CODES</span>
                </a>
                <a href="gcash.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <span>GCASH &amp; PAYMENTS</span>
                </a>
                <a href="footer.php" class="navLink active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <line x1="8" y1="15" x2="16" y2="15"></line>
                    </svg>
                    <span>FOOTER CONTENT</span>
                </a>
            </nav>
        </div>

        <div class="sidebarFooter">
            <a href="../homepage/" target="_blank" class="btnStorefront">
                <span>VIEW STOREFRONT</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
            </a>
            <a href="logout.php" class="btnLogout">
                <span>LOGOUT PILOT</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </aside>
<!-- MAIN CONTENT -->
    <main class="adminMain">
        <header class="topBar">
            <div class="topBarTitle">
                <span>//</span> FOOTER CONTENT
            </div>
        </header>

        <div class="contentArea">
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong>
                    <?php
                        $msg = $_GET['msg'] ?? '';
                        if ($msg === 'saved') echo 'Footer content saved. The storefront footer is updated immediately.';
                        elseif ($msg === 'uploaded') echo 'Payment logo updated. The storefront footer now shows the new image.';
                        elseif ($msg === 'uploadfail') echo 'Upload failed. Select a PNG, JPEG or WebP image and try again.';
                        elseif ($msg === 'reset') echo 'Payment logo restored to the default GCash image.';
                        elseif ($msg === 'csrf') echo 'Security token mismatch. Please try again.';
                        else echo 'Update saved.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="adminCard" style="margin-bottom: 1.5rem;">
                <h2 class="cardTitle">FOOTER TEXT &amp; LINKS</h2>
                <p style="color: #8a8f98; font-size: 0.85rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    These values appear in the storefront footer. Leave the contact number/email empty to hide those lines.
                    Link fields left empty fall back to <code>#</code>.
                </p>

                <form method="POST" action="footer.php" autocomplete="off">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="form_action" value="save">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem 1.5rem; margin-bottom: 1.5rem;">
                        <?php foreach ($footerFields as $key => $label): ?>
                            <div>
                                <label style="display:block; color:#8a8f98; font-size:0.75rem; letter-spacing:0.08em; margin-bottom:0.35rem;">
                                    <?php echo htmlspecialchars($label); ?>
                                </label>
                                <input type="text" name="<?php echo htmlspecialchars($key); ?>"
                                       value="<?php echo htmlspecialchars($current[$key]); ?>"
                                       style="width:100%; padding:0.6rem 0.75rem; background:#0d0d0d; border:1px solid #3a3f47; color:#e6e6e6; border-radius:6px; font-size:0.9rem;">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btnPrimary">SAVE FOOTER CONTENT</button>
                </form>
            </div>

            <div class="adminCard">
                <h2 class="cardTitle">PAYMENT LOGO (FOOTER)</h2>
                <p style="color: #8a8f98; font-size: 0.85rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    The picture shown in the footer's PAYMENT column. Currently:
                    <strong style="color: var(--brand-cyan);"><?php echo $usingCustomLogo ? 'custom uploaded image' : 'default GCash logo'; ?></strong>
                </p>

                <div style="display: flex; gap: 2rem; flex-wrap: wrap; align-items: flex-start;">
                    <div style="flex: 0 0 200px; text-align: center;">
                        <div style="background: #0d0d0d; border: 1px dashed #3a3f47; padding: 1rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; min-height: 90px;">
                            <?php if ($usingCustomLogo): ?>
                                <img src="<?php echo htmlspecialchars(adminAssetUrl($paymentLogoPath)); ?>" alt="Current payment logo" style="max-width: 100%; max-height: 70px;">
                            <?php else: ?>
                                <img src="../footer/banks/gcash.svg" alt="Default GCash logo" style="max-width: 100%; max-height: 70px;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="flex: 1; min-width: 260px;">
                        <form method="POST" action="footer.php" enctype="multipart/form-data" style="margin-bottom: 1rem;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="form_action" value="upload_logo">
                            <input type="file" name="payment_logo_file" accept=".jpg,.jpeg,.png,.webp"
                                   style="color:#8a8f98; font-size:0.85rem; margin-bottom:0.75rem; display:block;">
                            <button type="submit" class="btnPrimary">UPLOAD NEW LOGO</button>
                        </form>

                        <?php if ($usingCustomLogo): ?>
                        <form method="POST" action="footer.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="form_action" value="reset_logo">
                            <button type="submit" class="btnSecondary">RESTORE DEFAULT GCASH LOGO</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
                        <span>G.O.S ADMIN v2.6</span>
                    </div>
                </a>
            </div>
            <nav class="sidebarNav">
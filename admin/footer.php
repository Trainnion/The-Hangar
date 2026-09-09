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
        $fields = ['contact_number', 'contact_email'];
        foreach ($fields as $k) {
            setSetting($pdo, 'footer_' . $k, trim($_POST[$k] ?? ''));
        }
        // Contact / Privacy / About page links were removed from this editor — they
        // now always point at the built-in storefront pages. Clear any legacy URL
        // values so stale links never linger in the DB.
        foreach (['contact', 'privacy', 'about'] as $legacy) {
            setSetting($pdo, 'footer_url_' . $legacy, '');
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
    <?php require __DIR__ . '/sidebar.php'; ?>
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
                    The CUSTOMER SERVICE and ABOUT THE HANGAR links are fixed and point to the built-in storefront pages &mdash;
                    edit their page text under
                    <a href="pages.php" style="color: var(--brand-cyan);">STATIC PAGES</a>.
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
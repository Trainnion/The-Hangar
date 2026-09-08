<?php
// THE HANGAR - G.O.S ADMIN
// GCASH & PAYMENTS: upload / manage the GCash "Receive/QR" image shown at checkout.
// The chosen image path is persisted in the `settings` table (key: gcash_qr_url) and is
// served by the storefront cart page. Mode (qr/stub/live) lives in database/config.php.
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

$pdo = getDBConnection();
$error = '';

if ($pdo) {
    // 1. UPLOAD / REPLACE GCASH QR IMAGE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'upload') {
        if (!csrfValid()) {
            header('Location: gcash.php?msg=csrf');
            exit;
        }
        $path = handleAssetUpload('gcash_', 'gcash_qr_file', 'gcash_qr_cropped', 'gcash_qr_existing', 'assets/uploads/gcash_qr');
        if ($path) {
            setSetting($pdo, 'gcash_qr_url', $path);
            header('Location: gcash.php?msg=uploaded');
            exit;
        }
        $error = 'Upload failed. Select a PNG, JPEG or WebP image and try again.';
    }

    // 2. REMOVE GCASH QR IMAGE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'clear') {
        if (!csrfValid()) {
            header('Location: gcash.php?msg=csrf');
            exit;
        }
        setSetting($pdo, 'gcash_qr_url', '');
        header('Location: gcash.php?msg=cleared');
        exit;
    }
}

// Current configured QR path + mode
$gcashQrPath = '';
if ($pdo) {
    $gcashQrPath = getSetting($pdo, 'gcash_qr_url', '');
}
$gcashQrExists = ($gcashQrPath !== '' && is_file(__DIR__ . '/../' . $gcashQrPath));
$gcashCfg = hangarPaymentConfig();
$gcashMode = trim((string)($gcashCfg['mode'] ?? 'qr'));

// Existing QR images in the managed folder, for "reuse existing"
$existingQrImages = [];
if (is_dir(__DIR__ . '/../assets/uploads/gcash_qr')) {
    foreach (scandir(__DIR__ . '/../assets/uploads/gcash_qr') as $f) {
        if ($f !== '.' && $f !== '..' && !is_dir(__DIR__ . '/../assets/uploads/gcash_qr/' . $f)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $existingQrImages[] = 'assets/uploads/gcash_qr/' . $f;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash &amp; Payments | THE HANGAR ADMIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="adminSidebar">
        <div>
            <div class="sidebarHeader">
                <a href="index.php" class="sidebarBrand">
                    <img src="../promotional/Asset 8.png" alt="THE HANGAR" class="sidebarLogoImg">
                    <div class="sidebarBadge">
                        <span>G.O.S ADMIN v2.6</span>
                    </div>
                </a>
            </div>

            <nav class="sidebarNav">
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
                <a href="categories.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                        <line x1="9" y1="6" x2="15" y2="6"></line>
                        <line x1="9" y1="12" x2="15" y2="12"></line>
                        <line x1="9" y1="18" x2="15" y2="18"></line>
                    </svg>
                    <span>CATEGORIES</span>
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
                <a href="promos.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                    <span>PROMO CODES</span>
                </a>
                <a href="gcash.php" class="navLink active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <span>GCASH &amp; PAYMENTS</span>
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
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="adminMain">
        <header class="topBar">
            <div class="topBarTitle">
                <span>//</span> GCASH & PAYMENTS
            </div>
        </header>

        <div class="contentArea">
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong>
                    <?php
                        if (($_GET['msg'] ?? '') === 'uploaded') echo 'GCash QR image uploaded and activated for checkout!';
                        elseif (($_GET['msg'] ?? '') === 'cleared') echo 'GCash QR image removed. Customers will see &quot;QR not set&quot; until a new one is uploaded.';
                        elseif (($_GET['msg'] ?? '') === 'csrf') echo 'Security token mismatch. Please try again.';
                        else echo 'Update saved.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="adminCard" style="margin-bottom: 1.5rem;">
                <h2 class="cardTitle">PAYMENT MODE</h2>
                <p style="color: #8a8f98; font-size: 0.9rem; line-height: 1.6;">
                    Current mode: <strong style="color: var(--brand-cyan);"><?php echo htmlspecialchars($gcashMode); ?></strong>
                    <?php if ($gcashMode === 'qr'): ?>
                        &mdash; customers scan your QR at checkout, pay the exact total, then submit the GCash reference number. Verify each payment under <a href="orders.php" style="color: var(--brand-cyan);">ORDERS</a> before dispatching.
                    <?php elseif ($gcashMode === 'stub'): ?>
                        &mdash; development auto-pay: orders are marked paid instantly without real payment. Switch back to <strong>qr</strong> in <code>database/config.php</code> before going live.
                    <?php else: ?>
                        &mdash; reserved for a future automated PSP integration (see <code>database/config.php</code>). The QR below is still shown at checkout.
                    <?php endif; ?>
                </p>
            </div>
            <div class="adminCard">
                <h2 class="cardTitle">GCASH QR IMAGE</h2>
                <p style="color: #8a8f98; font-size: 0.85rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    This QR is shown to customers at checkout. Get yours from the GCash app: <strong>Profile &rarr; My QR Code</strong>, save it as an image, then upload it here.
                </p>

                <div style="display: flex; gap: 2rem; flex-wrap: wrap; align-items: flex-start;">
                    <!-- CURRENT QR PREVIEW -->
                    <div style="flex: 0 0 240px; text-align: center;">
                        <div style="background: #0d0d0d; border: 1px dashed #3a3f47; padding: 1rem; border-radius: 8px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                            <?php if ($gcashQrExists): ?>
                                <img src="<?php echo htmlspecialchars(adminAssetUrl($gcashQrPath)); ?>" alt="Current GCash QR" style="width: 100%; height: auto; display: block; border-radius: 4px;">
                            <?php else: ?>
                                <div style="color: #8a8f98; padding: 2.5rem 1rem; font-size: 0.85rem;">
                                    NO QR IMAGE SET<br>
                                    <span style="font-size: 0.75rem;">Checkout will show &quot;QR not set yet&quot;</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #8a8f98; word-break: break-all;">
                            <?php echo $gcashQrExists ? 'ACTIVE: ' . htmlspecialchars(basename($gcashQrPath)) : 'STATUS: NOT CONFIGURED'; ?>
                        </div>
                    </div>
                    <!-- UPLOAD / REPLACE FORM -->
                    <div style="flex: 1 1 320px; min-width: 300px;">
                        <form method="POST" action="gcash.php" enctype="multipart/form-data">
                            <input type="hidden" name="form_action" value="upload">
                            <?php echo csrfField(); ?>

                            <div class="formGroup">
                                <label for="gcash_qr_file">UPLOAD NEW QR IMAGE</label>
                                <input type="file" id="gcash_qr_file" name="gcash_qr_file" accept="image/png,image/jpeg,image/webp">
                                <p style="color: #8a8f98; font-size: 0.75rem; margin-top: 0.4rem;">PNG, JPG or WebP. Square images scan best.</p>
                            </div>

                            <?php if (count($existingQrImages) > 0): ?>
                            <div class="formGroup">
                                <label for="gcash_qr_existing">OR REUSE A PREVIOUSLY UPLOADED QR</label>
                                <select id="gcash_qr_existing" name="gcash_qr_existing">
                                    <option value="">&mdash; None &mdash;</option>
                                    <?php foreach ($existingQrImages as $img): ?>
                                        <option value="<?php echo htmlspecialchars($img); ?>" <?php if ($img === $gcashQrPath) echo 'selected'; ?>><?php echo htmlspecialchars(basename($img)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btnPrimary">
                                <?php echo $gcashQrExists ? 'REPLACE QR IMAGE' : 'ACTIVATE QR IMAGE'; ?>
                            </button>
                        </form>

                        <?php if ($gcashQrExists): ?>
                        <form method="POST" action="gcash.php" style="margin-top: 1.5rem; border-top: 1px solid #262a31; padding-top: 1.25rem;" onsubmit="return confirm('Remove the active GCash QR from checkout?');">
                            <input type="hidden" name="form_action" value="clear">
                            <?php echo csrfField(); ?>
                            <button type="submit" class="btnSecondary" style="border-color: #e5484d; color: #e5484d;">REMOVE QR IMAGE</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>





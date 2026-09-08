<?php
// THE HANGAR - GUND-ORDER SYSTEM
// PILOT PROFILE (profile.php)
// View/edit the logged-in pilot's full name, email, phone, delivery address,
// and avatar. Profile data feeds the checkout auto-fill; the checkout gate
// (see cart.php / api_checkout.php) requires full_name + phone + address.

require_once __DIR__ . '/../../shared/bootstrap.php';
extract(hangarBootstrap());
require_once __DIR__ . '/../../shared/db.php';              // getDBConnection(), isProfileComplete()
require_once __DIR__ . '/../db_helper.php';                 // assetUrl() conventions
require_once __DIR__ . '/../../admin/upload_helper.php';    // handleAssetUpload(), csrfField(), csrfValid()
require_once __DIR__ . '/../../login/validation.php';       // shared validators

// ---- LOGIN REQUIRED -------------------------------------------------------
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $loginPath);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    exit('Database connection failed. Logistics offline.');
}

/**
 * Resolve the user's avatar into a URL usable from /homepage/profile/.
 * Managed paths (assets/uploads/avatars/...) climb to the project root;
 * the placeholder is the HANGAR logo at project-root /promotional/.
 * A cache-buster is appended so a freshly saved avatar appears immediately
 * (browsers would otherwise show the stale cached image until a hard refresh).
 */
function profileAvatarUrl(?string $avatar): string {
    $img = trim((string)$avatar);
    if ($img === '') return '../../promotional/Asset 8.png';
    if (stripos($img, 'http') === 0) return $img;
    if (strpos($img, '/') !== false) return '../../' . $img . '?t=' . time();
    return '../promotional/' . $img;
}

// ---- HANDLE PROFILE UPDATE ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_profile') {
    if (!csrfValid()) {
        header('Location: profile.php?status=error&message=' . urlencode('Security token mismatch. Please try again.'));
        exit;
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    // Normalize phone (strip spaces/dashes) to match the stored format from registration
    $phone = preg_replace('/[\s\-]/', '', $phone);

    $errors = array_filter([
        validateRequired($fullName, 'Full Name'),
        validateRequired($email, 'Email Address'),
        validateEmailFormat($email),
        validateRequired($phone, 'Mobile Number'),
        validatePhoneFormat($phone),
    ]);
    $errors = array_values($errors);

    // Email uniqueness on change (exclude the user's own row)
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1");
        $check->execute([':e' => $email, ':id' => (int)$_SESSION['user_id']]);
        if ($check->fetch()) {
            $errors[] = "Communication frequency (email) '{$email}' is already in use.";
        }
    }

    if (!empty($errors)) {
        header('Location: profile.php?edit=1&status=error&message=' . urlencode(implode(' ', $errors)));
        exit;
    }

    // Avatar upload (cropped base64 > direct file > nothing). null = keep existing.
    $avatarPath = handleAssetUpload('avatar_', 'avatar_file', 'avatar_cropped', 'avatar_existing', 'assets/uploads/avatars');

    // Optional explicit avatar removal
    $removeAvatar = !empty($_POST['remove_avatar']);

    $sql = "UPDATE users SET full_name = :fn, email = :e, phone = :p, address = :a";
    $params = [
        ':fn' => $fullName,
        ':e'  => $email,
        ':p'  => $phone,
        ':a'  => ($address !== '') ? $address : null,
        ':id' => (int)$_SESSION['user_id'],
    ];
    if ($avatarPath !== null) {
        $sql .= ", avatar_url = :av";
        $params[':av'] = $avatarPath;
    } elseif ($removeAvatar) {
        $sql .= ", avatar_url = NULL";
    }
    $sql .= " WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Location: profile.php?status=success&message=' . urlencode('Pilot profile updated successfully.'));
    exit;
}

// ---- FETCH CURRENT USER ---------------------------------------------------
$stmt = $pdo->prepare("SELECT id, username, email, full_name, phone, address, avatar_url, role, created_at FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => (int)$_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$currentUser) {
    header('Location: ' . $logoutPath);
    exit;
}

$inEditMode = isset($_GET['edit']);
$flashStatus  = ($_GET['status'] ?? '') === 'success' ? 'success' : (($_GET['status'] ?? '') === 'error' ? 'error' : '');
$flashMessage = trim($_GET['message'] ?? '');
$memberSince  = $currentUser['created_at'] ? date('F j, Y', strtotime($currentUser['created_at'])) : 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PILOT PROFILE | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../shared/hud-design.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="profile.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: #f7f9fb;
            color: var(--brand-dark, #231F20);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .mainProfileDeck { flex: 1; width: 100%; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../shared/menu.php'; ?>

    <!-- SECTION 0: TOP NAVBAR (same structure as cart.php) -->
    <header class="headerContainer headerStatic">
        <div class="headerLeft">
            <a href="../index.php" class="navItem navBtnHamburger" aria-label="Menu">
                <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
            </a>
            <a href="../index.php" class="navItem navLink">HOME</a>
            <a href="../search/search.php" class="navItem navLink">PRODUCTS</a>
        </div>

        <div class="headerCenter">
            <a href="../index.php" class="navBrand" aria-label="THE HANGAR Home">
                <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
            </a>
        </div>

        <div class="headerRight">
            <a href="../cart/cart.php" class="navItem navLink navCart" style="color: var(--brand-cyan);">CART</a>
            <a href="../orders/orders.php" class="navItem navLink" style="color: var(--brand-cyan);">MY ORDERS</a>
            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                    <a href="<?php echo $adminPath; ?>" class="navItem navLink" style="color: #ffaa00; font-weight: 700;">[COMMAND DECK]</a>
                <?php else: ?>
                    <a href="profile.php" class="navItem navLink" style="color: #3FC4E1;">PILOT: <?php echo htmlspecialchars($userName); ?></a>
                <?php endif; ?>
                <a href="<?php echo $logoutPath; ?>" class="navItem navLink" title="Sign out of G.O.S">LOG OUT</a>
            <?php else: ?>
                <a href="<?php echo $loginPath; ?>" class="navItem navLink">LOG IN</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- SECTION HEADER STRIP - Matching cart.php -->
    <div class="headerContainerMK">
        <div class="headerLeftMK">
            <a href="../index.php" class="spBackLink">
                &larr; STOREFRONT
            </a>
        </div>
        <div class="headerCenterMK">
            <h2>PILOT PROFILE</h2>
        </div>
        <div class="headerRightMK"></div>
    </div>

    <main class="mainProfileDeck">
        <div class="profileDeckWrap">

            <?php if ($flashStatus !== ''): ?>
                <div class="profileFlash <?php echo $flashStatus === 'success' ? 'profileFlashOK' : 'profileFlashErr'; ?>">
                    <?php echo htmlspecialchars($flashMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!isProfileComplete($currentUser)): ?>
                <div class="profileGateNote">
                    Your profile is missing required dispatch details (full name, mobile number, or delivery address).
                    Complete your profile before placing an order.
                </div>
            <?php endif; ?>

            <?php if ($inEditMode): ?>

                <!-- EDIT MODE -->
                <form class="profileCard" action="profile.php" method="POST" enctype="multipart/form-data" autocomplete="off">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="form_action" value="update_profile">

                    <div class="profileAvatarRow">
                        <img src="<?php echo htmlspecialchars(profileAvatarUrl($currentUser['avatar_url'])); ?>"
                             alt="Avatar" class="profileAvatarImg" id="pfAvatarPreview"
                             onerror="this.src='../../promotional/Asset 8.png'">
                        <div class="profileAvatarControls">
                            <label class="profileFileBtn">
                                UPLOAD AVATAR
                                <input type="file" name="avatar_file" id="pfAvatarFile" accept=".jpg,.jpeg,.png,.webp">
                            </label>
                            <?php if (!empty($currentUser['avatar_url'])): ?>
                                <label class="profileRemoveAvatar">
                                    <input type="checkbox" name="remove_avatar" value="1" id="pfRemoveAvatar"> Remove current avatar
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="hidden" name="avatar_cropped" id="pfAvatarCropped">

                    <div class="profileFieldGrid">
                        <div class="profileField">
                            <label for="pfFullName">FULL NAME *</label>
                            <input type="text" id="pfFullName" name="full_name" required maxlength="150"
                                   value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>">
                        </div>
                        <div class="profileField">
                            <label>PILOT CALLSIGN</label>
                            <input type="text" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                        </div>
                        <div class="profileField">
                            <label for="pfEmail">EMAIL ADDRESS *</label>
                            <input type="email" id="pfEmail" name="email" required
                                   value="<?php echo htmlspecialchars($currentUser['email']); ?>">
                        </div>
                        <div class="profileField">
                            <label for="pfPhone">MOBILE NUMBER *</label>
                            <input type="tel" id="pfPhone" name="phone" required placeholder="e.g. 09171234567"
                                   value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                        </div>
                        <div class="profileField profileFieldWide">
                            <label for="pfAddress">DELIVERY ADDRESS <span class="profileOptional">(required to place orders)</span></label>
                            <textarea id="pfAddress" name="address" rows="3"
                                      placeholder="Street, Barangay, City, Province"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="profileActions">
                        <a href="profile.php" class="profileBtn profileBtnGhost">CANCEL</a>
                        <button type="submit" class="profileBtn profileBtnPrimary">SAVE PROFILE</button>
                    </div>
                </form>
            <?php else: ?>

                <!-- VIEW MODE -->
                <div class="profileCard">
                    <div class="profileAvatarRow">
                        <img src="<?php echo htmlspecialchars(profileAvatarUrl($currentUser['avatar_url'])); ?>"
                             alt="Avatar" class="profileAvatarImg profileAvatarView"
                             onerror="this.src='../../promotional/Asset 8.png'">
                        <div class="profileIdentity">
                            <div class="profileDisplayName"><?php echo htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']); ?></div>
                            <div class="profileSubLine">CALLSIGN: <?php echo htmlspecialchars($currentUser['username']); ?></div>
                            <div class="profileSubLine">MEMBER SINCE: <?php echo htmlspecialchars($memberSince); ?></div>
                            <?php if (isProfileComplete($currentUser)): ?>
                                <div class="profileBadgeOK">PROFILE COMPLETE — CLEARED FOR DISPATCH</div>
                            <?php else: ?>
                                <div class="profileBadgeWarn">PROFILE INCOMPLETE — ADDRESS REQUIRED BEFORE ORDERING</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profileFieldGrid profileViewGrid">
                        <div class="profileField">
                            <label>EMAIL ADDRESS</label>
                            <div class="profileValue"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <div class="profileField">
                            <label>MOBILE NUMBER</label>
                            <div class="profileValue"><?php echo htmlspecialchars($currentUser['phone'] ?: '— not set —'); ?></div>
                        </div>
                        <div class="profileField profileFieldWide">
                            <label>DELIVERY ADDRESS</label>
                            <div class="profileValue"><?php echo htmlspecialchars($currentUser['address'] ?: '— not set —'); ?></div>
                        </div>
                    </div>

                    <div class="profileActions">
                        <a href="profile.php?edit=1" class="profileBtn profileBtnPrimary">EDIT PROFILE</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/../footer.php'; ?>

<?php if ($inEditMode): ?>
<!-- AVATAR CROP MODAL (same Cropper.js as admin panel) -->
<div class="pfCropOverlay" id="pfCropOverlay" hidden>
    <div class="pfCropBox">
        <div class="pfCropHead">
            <h3>CROP PROFILE PICTURE</h3>
            <button type="button" class="pfCropClose" id="pfCropClose">&times;</button>
        </div>
        <div class="pfCropStage">
            <img id="pfCropImage" src="" alt="To crop">
        </div>
        <div class="pfCropHint">Drag to position &middot; Scroll to zoom &middot; Square crop (profile picture)</div>
        <div class="pfCropActions">
            <button type="button" class="profileBtn profileBtnGhost" id="pfCropCancel">CANCEL</button>
            <button type="button" class="profileBtn profileBtnPrimary" id="pfCropApply">APPLY CROP</button>
        </div>
    </div>
</div>
<link rel="stylesheet" href="../../shared/vendor/cropper.min.css?v=<?php echo time(); ?>">
<script src="../../shared/vendor/cropper.min.js?v=<?php echo time(); ?>"></script>
<style>
    .pfCropOverlay {
        position: fixed; inset: 0; background: rgba(8, 8, 8, 0.85);
        backdrop-filter: blur(4px); z-index: 100020;
        display: flex; align-items: center; justify-content: center; padding: 1.5rem;
    }
    /* Author CSS beats the UA's [hidden]{display:none}, so this rule is required
       or the modal renders open on page load and blocks the whole edit form. */
    .pfCropOverlay[hidden] { display: none; }
    .pfCropBox {
        background: #11141a; border: 1px solid var(--brand-cyan, #3FC4E1);
        padding: 1.4rem; width: 100%; max-width: 460px; color: #fff;
        font-family: var(--font-heading, 'Poppins', sans-serif);
    }
    .pfCropHead { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .pfCropHead h3 { margin: 0; font-size: 0.95rem; letter-spacing: 0.08em; }
    .pfCropClose { background: none; border: none; color: #fff; font-size: 1.6rem; line-height: 1; cursor: pointer; }
    .pfCropClose:hover { color: var(--brand-cyan, #3FC4E1); }
    .pfCropStage {
        height: 320px; overflow: hidden; background: #0a0d11;
        border: 1px solid rgba(63, 196, 225, 0.3); margin-bottom: 0.8rem;
    }
    .pfCropStage img { display: block; max-width: 100%; }
    .pfCropHint { font-size: 0.72rem; color: #8a97a3; margin-bottom: 1rem; }
    .pfCropActions { display: flex; justify-content: flex-end; gap: 0.7rem; }
</style>
<script>
    // Wait for DOMContentLoaded (defensive) — this block sits near the end of <body>.
    document.addEventListener('DOMContentLoaded', function () {
    (function () {
        var fileInput   = document.getElementById('pfAvatarFile');
        var overlay     = document.getElementById('pfCropOverlay');
        var cropImage   = document.getElementById('pfCropImage');
        var closeBtn    = document.getElementById('pfCropClose');
        var cancelBtn   = document.getElementById('pfCropCancel');
        var applyBtn    = document.getElementById('pfCropApply');
        var croppedField = document.getElementById('pfAvatarCropped');
        var previewImg  = document.getElementById('pfAvatarPreview');
        var removeChk   = document.getElementById('pfRemoveAvatar');
        var cropper = null;

        function closeCrop() {
            overlay.hidden = true;
            if (cropper) { cropper.destroy(); cropper = null; }
            if (fileInput) fileInput.value = ''; // allow re-picking the same file
        }

        if (fileInput) {
            fileInput.addEventListener('change', function (e) {
                var file = e.target.files && e.target.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function (ev) {
                    if (typeof Cropper === 'undefined') {
                        alert('Image cropper library failed to load. Please refresh the page and try again.');
                        return;
                    }
                    cropImage.src = ev.target.result;
                    overlay.hidden = false;
                    if (cropper) cropper.destroy();
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,        // profile pictures are square
                        viewMode: 1,
                        autoCropArea: 0.9,
                        background: false
                    });
                };
                reader.readAsDataURL(file);
            });
        }

        if (closeBtn) closeBtn.addEventListener('click', closeCrop);
        if (cancelBtn) cancelBtn.addEventListener('click', closeCrop);
        if (overlay) overlay.addEventListener('click', function (e) { if (e.target === overlay) closeCrop(); });

        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                if (!cropper) return;
                var canvas = cropper.getCroppedCanvas({
                    maxWidth: 800, maxHeight: 800,
                    imageSmoothingEnabled: true, imageSmoothingQuality: 'high'
                });
                var base64 = canvas.toDataURL('image/png');
                croppedField.value = base64;             // server saves this (cropped wins over raw file)
                if (previewImg) previewImg.src = base64; // REAL-TIME preview, no reload needed
                if (removeChk) removeChk.checked = false; // uploading a new one cancels "remove"
                closeCrop();
            });
        }

        // "Remove avatar" clears any pending crop so the two can't conflict
        if (removeChk) {
            removeChk.addEventListener('change', function () {
                if (removeChk.checked && croppedField) {
                    croppedField.value = '';
                    if (previewImg) previewImg.src = '../../promotional/Asset 8.png';
                }
            });
        }
    })();
    });
</script>
<?php endif; ?>
</body>
</html>


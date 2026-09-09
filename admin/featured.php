<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

// Featured content helpers (defaultFeaturedContent / getFeaturedContent)
// live in shared/db.php so both the admin page and the homepage can use them.

$pdo = getDBConnection();
$featured = getFeaturedContent($pdo);

// Handle SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!csrfValid() || ($_POST['form_action'] ?? '') !== 'save') {
        header('Location: featured.php?msg=csrf');
        exit;
    }
    $old = $featured;
    $new = [];
    foreach (['main', 'ro', 'xz'] as $slot) {
        $new[$slot] = [
            'title'       => trim($_POST[$slot . '_title'] ?? ''),
            'subtitle'    => trim($_POST[$slot . '_subtitle'] ?? ''),
            'status_text' => trim($_POST[$slot . '_status_text'] ?? ''),
        ];
        // Image: cropped base64 (highest priority) > direct file > existing picker
        $new[$slot]['image'] = handleAssetUpload('ft' . $slot . '_', $slot . '_image_file', $slot . '_cropped_image_data', $slot . '_existing_image', 'assets/uploads/featured')
            ?? ($old[$slot]['image'] ?? '');
        // Logo: direct file upload > existing picker (no cropper for logos)
        $new[$slot]['logo'] = handleAssetUpload('ft' . $slot . 'logo_', $slot . '_logo_file', $slot . '_logo_unused', $slot . '_existing_logo', 'assets/uploads/featured')
            ?? ($old[$slot]['logo'] ?? '');
    }
    setSetting($pdo, 'featured_content', json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    // Cleanup replaced assets no longer referenced anywhere in the new content
    $newRefs = [];
    foreach ($new as $s) { $newRefs[] = $s['image']; $newRefs[] = $s['logo']; }
    foreach (['main', 'ro', 'xz'] as $slot) {
        foreach (['image', 'logo'] as $field) {
            $o = $old[$slot][$field] ?? '';
            $n = $new[$slot][$field] ?? '';
            if ($o !== '' && $o !== $n && !in_array($o, $newRefs, true)) {
                deleteOrphanedImage($pdo, $o, $n);
            }
        }
    }
    header('Location: featured.php?msg=saved');
    exit;
}

$featured = getFeaturedContent($pdo); // reload fresh (post-redirect state)

// Raster images for the IMAGE pickers (promotional pool + managed featured uploads)
// Managed uploads are listed WITH their path so values round-trip correctly.
$existingImages = [];
$scanDirs = [
    'promo' => __DIR__ . '/../promotional',
    'managed' => __DIR__ . '/../assets/uploads/featured',
];
foreach ($scanDirs as $dir) {
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || is_dir($dir . '/' . $f)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $val = ($dir === 'managed') ? 'assets/uploads/featured/' . $f : $f;
                if (!in_array($val, $existingImages, true)) {
                    $existingImages[] = $val;
                }
            }
        }
    }
}

// Logos for the LOGO pickers (svg allowed + managed uploads)
$existingLogos = [];
$logoDirs = [
    'pool' => __DIR__ . '/../logos',
    'alt' => __DIR__ . '/../assets/logos',
    'managed' => __DIR__ . '/../assets/uploads/featured',
];
foreach ($logoDirs as $dir) {
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || is_dir($dir . '/' . $f)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['svg', 'jpg', 'jpeg', 'png', 'webp'])) {
                $val = ($dir === 'managed') ? 'assets/uploads/featured/' . $f : $f;
                if (!in_array($val, $existingLogos, true)) {
                    $existingLogos[] = $val;
                }
            }
        }
    }
}

/** Resolve a picker value to a web path from /admin/. Bare legacy names resolve to their pools. */
function featuredAssetUrl(string $val, bool $isLogo = false): string {
    $v = trim($val);
    if ($v === '') return '';
    if (stripos($v, 'http') === 0) return $v;
    if (strpos($v, '/') !== false) return '../' . $v;
    if ($isLogo) return '../logos/' . $v;
    return '../promotional/' . $v;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEATURED SECTION | THE HANGAR ADMIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <!-- Cropper.js CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <?php require __DIR__ . '/sidebar.php'; ?>
    <!-- MAIN CONTENT -->
    <main class="adminMain">
        <header class="topBar">
            <div class="topBarTitle">
                <span>//</span> HOMEPAGE FEATURED SECTION
            </div>
        </header>

        <div class="contentArea">
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong>
                    <?php
                        $msg = $_GET['msg'] ?? '';
                        if ($msg === 'saved') echo 'Featured content saved. The homepage updates immediately.';
                        elseif ($msg === 'csrf') echo 'Security token mismatch. Please try again.';
                        else echo 'Update saved.';
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="featured.php" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="form_action" value="save">

                <div class="adminCard" style="margin-bottom: 1.5rem;">
                    <h2 class="cardTitle">FEATURED SECTION (Homepage Section 7)</h2>
                    <p style="color: #8a8f98; font-size: 0.85rem; line-height: 1.6;">
                        The wide banner slider above this content is managed in <a href="sliders.php" style="color: var(--brand-cyan);">SLIDERS (SEC 1, 2, 7)</a>.
                        Below are the three featured cards: the big left feature and the two stacked right banners.
                    </p>
                </div>

                <?php
                $slotMeta = [
                    'main' => ['THE MAIN FEATURE (Big Left Card)', 'Title, subtitle, status badge and the big banner image.', true],
                    'ro'   => ['RIGHT BANNER 1 (Top: logo+status left, image right)', 'Shows a logo and status text beside the banner image.', false],
                    'xz'   => ['RIGHT BANNER 2 (Bottom: image left, logo+status right)', 'Mirrored layout of the top banner.', false],
                ];
                foreach ($slotMeta as $slot => $meta): ?>
                    <div class="adminCard" style="margin-bottom: 1.5rem;">
                        <h2 class="cardTitle"><?php echo $meta[0]; ?></h2>
                        <p style="color: #8a8f98; font-size: 0.8rem; margin-bottom: 1rem;"><?php echo $meta[1]; ?></p>

                        <div class="formRow">
                            <div class="formGroup">
                                <label for="<?php echo $slot; ?>_title">TITLE</label>
                                <input type="text" id="<?php echo $slot; ?>_title" name="<?php echo $slot; ?>_title" class="formInput" value="<?php echo htmlspecialchars($featured[$slot]['title']); ?>" maxlength="80">
                            </div>
                            <div class="formGroup">
                                <label for="<?php echo $slot; ?>_subtitle">SUBTITLE</label>
                                <input type="text" id="<?php echo $slot; ?>_subtitle" name="<?php echo $slot; ?>_subtitle" class="formInput" value="<?php echo htmlspecialchars($featured[$slot]['subtitle']); ?>" maxlength="120">
                            </div>
                        </div>

                        <div class="formGroup">
                            <label for="<?php echo $slot; ?>_status_text">STATUS BADGE TEXT</label>
                            <input type="text" id="<?php echo $slot; ?>_status_text" name="<?php echo $slot; ?>_status_text" class="formInput" value="<?php echo htmlspecialchars($featured[$slot]['status_text']); ?>" maxlength="40">
                            <small style="color: #8a8f98;">e.g. "NOW AVAILABLE!" or "COMING SOON". Badge color is chosen automatically.</small>
                        </div>
                        <div class="formGroup">
                            <label>FEATURE IMAGE<?php if ($slot === 'main'): ?> &amp; CROPPER<?php endif; ?></label>
                            <?php if ($slot === 'main'): ?>
                            <p style="font-size: 0.78rem; color: var(--text-sub); margin-bottom: 0.6rem;">Pick a photo from your device &#8212; the cropper opens automatically (21:9 banner shape by default).</p>
                            <?php endif; ?>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <input type="file" id="<?php echo $slot; ?>_image_file_input" name="<?php echo $slot; ?>_image_file" accept="image/png, image/jpeg, image/webp" style="display: none;">
                                <button type="button" class="btnSecondary" onclick="document.getElementById('<?php echo $slot; ?>_image_file_input').click();"> CHOOSE<?php if ($slot === 'main'): ?> &amp; CROP<?php endif; ?> IMAGE</button>
                                <span class="chosenFileName" data-slot="<?php echo $slot; ?>" style="font-size: 0.8rem; color: var(--text-muted);">No file selected</span>
                            </div>
                            <input type="hidden" id="<?php echo $slot; ?>_cropped_image_data" name="<?php echo $slot; ?>_cropped_image_data" value="">
                        </div>

                        <div class="formGroup">
                            <label for="<?php echo $slot; ?>_existing_image">OR USE AN EXISTING IMAGE</label>
                            <select id="<?php echo $slot; ?>_existing_image" name="<?php echo $slot; ?>_existing_image" class="formInput">
                                <option value="">-- keep current --</option>
                                <?php foreach ($existingImages as $img): ?>
                                    <option value="<?php echo htmlspecialchars($img); ?>"><?php echo htmlspecialchars($img); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="formGroup">
                            <label>LOGO<?php if ($slot !== 'main'): ?> &amp; UPLOAD<?php endif; ?> (RIGHT BANNERS)</label>
                            <?php if ($slot !== 'main'): ?>
                            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 0.6rem;">
                                <input type="file" id="<?php echo $slot; ?>_logo_file_input" name="<?php echo $slot; ?>_logo_file" accept="image/png, image/jpeg, image/webp, image/svg+xml, .svg" style="display: none;">
                                <button type="button" class="btnSecondary" onclick="document.getElementById('<?php echo $slot; ?>_logo_file_input').click();"> UPLOAD LOGO FILE</button>
                                <span class="chosenLogoFileName" data-slot="<?php echo $slot; ?>" style="font-size: 0.8rem; color: var(--text-muted);">No file selected</span>
                            </div>
                            <?php endif; ?>
                            <select id="<?php echo $slot; ?>_existing_logo" name="<?php echo $slot; ?>_existing_logo" class="formInput" <?php if ($slot === 'main') echo 'disabled'; ?>>
                                <option value="">-- none --</option>
                                <?php foreach ($existingLogos as $lg): ?>
                                    <option value="<?php echo htmlspecialchars($lg); ?>"><?php echo htmlspecialchars($lg); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($slot === 'main'): ?><input type="hidden" name="<?php echo $slot; ?>_logo_file" value=""><?php endif; ?>
                        </div>

                        <div class="formGroup">
                            <span style="font-size: 0.72rem; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">CURRENT PREVIEW:</span>
                            <div style="display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap;">
                                <img id="<?php echo $slot; ?>_preview_img" src="<?php echo htmlspecialchars(featuredAssetUrl($featured[$slot]['image'])); ?>" alt="Preview" style="max-height: 120px; border-radius: 6px; border: 1px solid var(--border-color);">
                                <?php if ($featured[$slot]['logo'] !== ''): ?>
                                <img src="<?php echo htmlspecialchars(featuredAssetUrl($featured[$slot]['logo'], true)); ?>" alt="Logo preview" style="max-height: 48px; border-radius: 4px; border: 1px solid var(--border-color); background: #0b0d10; padding: 4px;">
                                <?php endif; ?>
                                <?php if ($slot === 'main'): ?>
                                <button type="button" class="btnSecondary btnSmall" id="<?php echo $slot; ?>_crop_current_btn" style="border-color: var(--brand-cyan); color: var(--brand-cyan);"> CROP THIS CURRENT IMAGE</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
    <!-- INTERACTIVE CROPPER MODAL (main feature image) -->
    <div class="modalOverlay" id="featuredCropperModal">
        <div class="modalBox" style="max-width: 850px;">
            <div class="modalHeader">
                <h3 class="modalTitle">CROP FEATURE IMAGE</h3>
                <button type="button" class="closeModalBtn" id="closeFeaturedCropperModalBtn">&times;</button>
            </div>
            <div class="cropControls" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="cropRatioBtn activeRatio" data-ratio="2.35">21:9 TILE</button>
                    <button type="button" class="cropRatioBtn" data-ratio="1.3333">4:3 CARD</button>
                    <button type="button" class="cropRatioBtn" data-ratio="1.7777">16:9</button>
                    <button type="button" class="cropRatioBtn" data-ratio="NaN">FREE CROP</button>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Export Quality:</label>
                    <select id="featuredCropFormatSelect" style="padding: 0.35rem 0.6rem; font-size: 0.75rem; background: rgba(14, 17, 21, 0.9); border: 1px solid var(--border-color); color: #fff; border-radius: 4px;">
                        <option value="jpeg" selected>Ultra-Quality JPEG (98%)</option>
                        <option value="png">Lossless PNG (Maximum Clarity)</option>
                        <option value="webp">High-Res WebP (98%)</option>
                    </select>
                </div>
            </div>
            <div class="cropperPreviewContainer" style="max-height: 450px;">
                <img id="featuredImageToCrop" src="" alt="To Crop">
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                <span style="font-size: 0.78rem; color: var(--text-sub);">Adjust frame to fit your image. Scroll mouse wheel to zoom.</span>
                <div style="display: flex; gap: 0.8rem;">
                    <button type="button" class="btnSecondary" id="cancelFeaturedCropBtn">CANCEL</button>
                    <button type="button" class="btnPrimary" id="applyFeaturedCropBtn">APPLY &amp; USE IMAGE</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let featuredCropper = null;
        let activeCropSlot = null;

        const featuredCropperModal = document.getElementById('featuredCropperModal');
        const featuredImageToCrop = document.getElementById('featuredImageToCrop');
        const closeFeaturedCropperModalBtn = document.getElementById('closeFeaturedCropperModalBtn');
        const cancelFeaturedCropBtn = document.getElementById('cancelFeaturedCropBtn');
        const applyFeaturedCropBtn = document.getElementById('applyFeaturedCropBtn');
        const featuredCropFormatSelect = document.getElementById('featuredCropFormatSelect');

        function openFeaturedCropper(src, slot) {
            activeCropSlot = slot;
            featuredImageToCrop.src = src;
            featuredCropperModal.classList.add('active');
            if (featuredCropper) featuredCropper.destroy();
            featuredCropper = new Cropper(featuredImageToCrop, {
                aspectRatio: 2.35,
                viewMode: 1,
                autoCropArea: 0.95,
                background: false,
                checkCrossOrigin: false
            });
        }

        function closeFeaturedCropper() {
            featuredCropperModal.classList.remove('active');
            if (featuredCropper) { featuredCropper.destroy(); featuredCropper = null; }
        }

        if (closeFeaturedCropperModalBtn) closeFeaturedCropperModalBtn.addEventListener('click', closeFeaturedCropper);
        if (cancelFeaturedCropBtn) cancelFeaturedCropBtn.addEventListener('click', closeFeaturedCropper);

        document.querySelectorAll('#featuredCropperModal .cropRatioBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#featuredCropperModal .cropRatioBtn').forEach(b => b.classList.remove('activeRatio'));
                btn.classList.add('activeRatio');
                if (featuredCropper) featuredCropper.setAspectRatio(parseFloat(btn.getAttribute('data-ratio')));
            });
        });
        if (applyFeaturedCropBtn) {
            applyFeaturedCropBtn.addEventListener('click', () => {
                if (!featuredCropper || !activeCropSlot) return;
                const format = featuredCropFormatSelect ? featuredCropFormatSelect.value : 'jpeg';
                const mimeType = (format === 'png') ? 'image/png' : (format === 'jpeg' ? 'image/jpeg' : 'image/webp');
                const quality = (format === 'png') ? 1.0 : 0.98;
                const canvas = featuredCropper.getCroppedCanvas({
                    maxWidth: 2560,
                    maxHeight: 2560,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                    fillColor: (format === 'jpeg') ? '#ffffff' : undefined
                });
                const dataUrl = canvas.toDataURL(mimeType, quality);
                const hidden = document.getElementById(activeCropSlot + '_cropped_image_data');
                if (hidden) hidden.value = dataUrl;
                const preview = document.getElementById(activeCropSlot + '_preview_img');
                if (preview) preview.src = dataUrl;
                const sel = document.getElementById(activeCropSlot + '_existing_image');
                if (sel) sel.value = '';
                closeFeaturedCropper();
            });
        }

        // File inputs -> selected-file name; main slot opens the cropper automatically
        document.querySelectorAll('input[type="file"][id$="_image_file_input"], input[type="file"][id$="_logo_file_input"]').forEach(inp => {
            inp.addEventListener('change', (e) => {
                const f = e.target.files && e.target.files[0];
                const isLogo = inp.id.endsWith('_logo_file_input');
                const slot = inp.id.replace(isLogo ? '_logo_file_input' : '_image_file_input', '');
                const nameSpan = document.querySelector(isLogo ? ('.chosenLogoFileName[data-slot="' + slot + '"]') : ('.chosenFileName[data-slot="' + slot + '"]'));
                if (nameSpan) nameSpan.textContent = f ? f.name : 'No file selected';
                if (!isLogo && slot === 'main' && f) {
                    const reader = new FileReader();
                    reader.onload = (ev) => openFeaturedCropper(ev.target.result, slot);
                    reader.readAsDataURL(f);
                }
            });
        });

        // Crop the currently shown main image
        const mainCropCurrentBtn = document.getElementById('main_crop_current_btn');
        if (mainCropCurrentBtn) {
            mainCropCurrentBtn.addEventListener('click', () => {
                const img = document.getElementById('main_preview_img');
                if (!img || !img.src) { alert('No image is currently available to crop.'); return; }
                openFeaturedCropper(img.src, 'main');
            });
        }

        // Existing-image selects -> update preview + clear pending crop for that slot
        ['main', 'ro', 'xz'].forEach(slot => {
            const sel = document.getElementById(slot + '_existing_image');
            if (sel) {
                sel.addEventListener('change', (e) => {
                    if (!e.target.value) return;
                    const v = e.target.value;
                    const pv = document.getElementById(slot + '_preview_img');
                    if (pv) pv.src = (v.indexOf('/') !== -1) ? '../' + v : '../promotional/' + v;
                    const hidden = document.getElementById(slot + '_cropped_image_data');
                    if (hidden) hidden.value = '';
                });
            }
        });
    </script>
</body>
</html>


                <?php endforeach; ?>

                <div class="adminCard" style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <a href="featured.php" class="btnSecondary">RESET</a>
                    <button type="submit" class="btnPrimary">SAVE FEATURED CONTENT</button>
                </div>
            </form>
        </div>
    </main>



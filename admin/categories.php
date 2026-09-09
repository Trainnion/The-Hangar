<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

$pdo = getDBConnection();
$message = '';
$error = '';

$ALLOWED_GRADES = ['MG', 'RG', 'PG', 'HG', 'METAL BUILD', 'EG', 'SD', 'OTHER'];

// 1. Handle DELETE
if (isset($_GET['delete']) && $pdo) {
    $delId = (int)$_GET['delete'];
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$_GET['token'])) {
        header('Location: categories.php?msg=csrf');
        exit;
    }
    $imgStmt = $pdo->prepare("SELECT `image_url` FROM `category_tiles` WHERE `id` = :id");
    $imgStmt->execute(['id' => $delId]);
    $deletedImg = $imgStmt->fetchColumn() ?: null;
    $stmt = $pdo->prepare("DELETE FROM `category_tiles` WHERE `id` = :id");
    $stmt->execute(['id' => $delId]);
    deleteOrphanedImage($pdo, $deletedImg, null);
    header('Location: categories.php?msg=deleted');
    exit;
}

// 2. Handle ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!csrfValid() || !in_array($_POST['form_action'] ?? '', ['add', 'edit'], true)) {
        header('Location: categories.php?msg=csrf');
        exit;
    }
    $action = $_POST['form_action'] ?? 'add';
    $title = trim($_POST['title'] ?? '');
    $slug = strtolower(trim($_POST['slug'] ?? ''));
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    }
    $grade_key = in_array($_POST['grade_key'] ?? '', $ALLOWED_GRADES, true) ? $_POST['grade_key'] : 'MG';
    $sort_order = max(1, (int)($_POST['sort_order'] ?? 1));
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $uploadedImg = handleAssetUpload('cat_', 'category_file', 'cropped_image_data', 'existing_image', 'assets/uploads/categories');

    if ($action === 'add') {
        if (empty($title)) {
            $error = 'Category Title is required.';
        } else {
            $imgUrl = $uploadedImg ?: 'Asset 8.png';
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `category_tiles` (`slug`, `title`, `grade_key`, `image_url`, `sort_order`, `is_active`)
                    VALUES (:slug, :title, :grade_key, :image_url, :sort_order, :is_active)
                ");
                $stmt->execute([
                    'slug' => $slug,
                    'title' => $title,
                    'grade_key' => $grade_key,
                    'image_url' => $imgUrl,
                    'sort_order' => $sort_order,
                    'is_active' => $is_active
                ]);
                header('Location: categories.php?msg=added');
                exit;
            } catch (Throwable $e) {
                error_log('Category add failed: ' . $e->getMessage());
                $error = 'Could not add category &#8212; the slug may already be in use.';
            }
        }
    } elseif ($action === 'edit') {
        $editId = (int)($_POST['category_id'] ?? 0);
        if ($editId > 0 && !empty($title)) {
            $currentImg = $_POST['current_image_url'] ?? '';
            $finalImg = $uploadedImg ?: $currentImg;
            try {
                $stmt = $pdo->prepare("
                    UPDATE `category_tiles` SET
                        `slug` = :slug,
                        `title` = :title,
                        `grade_key` = :grade_key,
                        `image_url` = :image_url,
                        `sort_order` = :sort_order,
                        `is_active` = :is_active
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    'slug' => $slug,
                    'title' => $title,
                    'grade_key' => $grade_key,
                    'image_url' => $finalImg,
                    'sort_order' => $sort_order,
                    'is_active' => $is_active,
                    'id' => $editId
                ]);
                deleteOrphanedImage($pdo, $currentImg, $finalImg);
                header('Location: categories.php?msg=updated');
                exit;
            } catch (Throwable $e) {
                error_log('Category update failed: ' . $e->getMessage());
                $error = 'Could not update category &#8212; the slug may already be in use.';
            }
        }
    }
}

// Fetch all categories
$categories = [];
if ($pdo) {
    try {
        $categories = $pdo->query("SELECT * FROM `category_tiles` ORDER BY `sort_order` ASC, `id` ASC")->fetchAll();
    } catch (Throwable $e) {
        error_log('Categories listing failed: ' . $e->getMessage());
        $error = 'Could not load categories. Please try again.';
    }
}

// Category to edit if ?edit=ID
$editCategory = null;
if (isset($_GET['edit']) && $pdo) {
    try {
        $editStmt = $pdo->prepare("SELECT * FROM `category_tiles` WHERE `id` = :id");
        $editStmt->execute(['id' => (int)$_GET['edit']]);
        $editCategory = $editStmt->fetch();
    } catch (Throwable $e) {
        error_log('Category fetch failed: ' . $e->getMessage());
    }
}

// Fetch existing promotional images for the image picker
$existingImages = [];
$promotionalDir = __DIR__ . '/../promotional';
if (is_dir($promotionalDir)) {
    $files = scandir($promotionalDir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && !is_dir($promotionalDir . '/' . $f)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $existingImages[] = $f;
            }
        }
    }
}
$uploadCatDir = __DIR__ . '/../assets/uploads/categories';
if (is_dir($uploadCatDir)) {
    foreach (scandir($uploadCatDir) as $f) {
        if ($f !== '.' && $f !== '..' && !is_dir($uploadCatDir . '/' . $f)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $existingImages[] = 'assets/uploads/categories/' . $f;
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
    <title>CATEGORY TILES | THE HANGAR ADMIN</title>
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
                <span>//</span> HOMEPAGE CATEGORY TILES
            </div>
            <div class="topBarRight">
                <button type="button" class="btnPrimary" id="openAddCategoryModalBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    ADD CATEGORY
                </button>
            </div>
        </header>

        <div class="contentArea">
            <?php if (!empty($error)): ?>
                <div class="adminCard" style="background: rgba(255, 70, 70, 0.1); border-color: #ff4646; padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: #ff4646;">Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong>
                    <?php
                        $msg = $_GET['msg'] ?? '';
                        if ($msg === 'added') echo 'Category tile added. The homepage updates immediately.';
                        elseif ($msg === 'updated') echo 'Category tile updated. The homepage updates immediately.';
                        elseif ($msg === 'deleted') echo 'Category tile removed from the homepage.';
                        elseif ($msg === 'csrf') echo 'Security token mismatch. Please try again.';
                        else echo 'Update saved.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="adminCard">
                <h2 class="cardTitle">CATEGORY TILES (Homepage Section 4)</h2>
                <p style="color: #8a8f98; font-size: 0.85rem; line-height: 1.6; margin-bottom: 1rem;">
                    Each tile shows an image + label on the homepage. The <strong>Linked Grade</strong> is the PRODUCTS-section
                    category it filters to (e.g. MG opens the Master Grade catalog). The first tile renders full-width.
                </p>

                <div style="overflow-x: auto;">
                    <table class="adminTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Preview</th>
                                <th>Title</th>
                                <th>Linked Grade</th>
                                <th>Status</th>
                                <th>Sort</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="7" style="text-align:center; color: var(--text-muted);">No category tiles yet. Click "ADD CATEGORY" to create one.</td></tr>
                            <?php else: ?>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td>#<?php echo $c['id']; ?></td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars(adminAssetUrl($c['image_url'])); ?>"
                                                 alt="<?php echo htmlspecialchars($c['title']); ?>"
                                                 style="width: 64px; height: 44px; object-fit: cover; border-radius: 4px; border: 1px solid #333;">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($c['title']); ?></strong></td>
                                        <td><span class="badge badge-grade"><?php echo htmlspecialchars($c['grade_key']); ?></span></td>
                                        <td>
                                            <?php if ($c['is_active']): ?>
                                                <span class="badge badge-active">ACTIVE</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">INACTIVE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $c['sort_order']; ?></td>
                                        <td>
                                            <div class="actionBtns">
                                                <a href="categories.php?edit=<?php echo $c['id']; ?>" class="btnSecondary btnSmall">EDIT</a>
                                                <a href="categories.php?delete=<?php echo $c['id']; ?>&token=<?php echo csrfToken(); ?>" class="btnDanger" onclick="return confirm('Remove this category tile from the homepage?');">DELETE</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
<!-- ADD / EDIT CATEGORY MODAL -->
    <div class="modalOverlay <?php if ($editCategory || isset($_GET['action'])) echo 'active'; ?>" id="categoryModal">
        <div class="modalBox">
            <div class="modalHeader">
                <h3 class="modalTitle"><?php echo $editCategory ? 'EDIT CATEGORY TILE' : 'ADD NEW CATEGORY TILE'; ?></h3>
                <button type="button" class="closeModalBtn" id="closeCategoryModalBtn">&times;</button>
            </div>

            <form method="POST" action="categories.php" enctype="multipart/form-data" id="categoryForm">
                <input type="hidden" name="form_action" value="<?php echo $editCategory ? 'edit' : 'add'; ?>">
                <?php echo csrfField(); ?>
                <?php if ($editCategory): ?>
                    <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                    <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($editCategory['image_url']); ?>">
                <?php endif; ?>

                <div class="formGroup">
                    <label for="catTitle">TITLE *</label>
                    <input type="text" id="catTitle" name="title" class="formInput" required
                           value="<?php echo htmlspecialchars(($editCategory['title'] ?? '')); ?>"
                           placeholder="e.g. METALBUILD">
                </div>

                <div class="formGroup">
                    <label for="catSlug">SLUG (URL identifier)</label>
                    <input type="text" id="catSlug" name="slug" class="formInput"
                           value="<?php echo htmlspecialchars(($editCategory['slug'] ?? '')); ?>"
                           placeholder="auto-generated from title if empty">
                    <small style="color: #8a8f98;">Unique key. Leave empty to auto-generate.</small>
                </div>

                <div class="formGroup">
                    <label for="catGrade">LINK TO PRODUCTS-SECTION CATEGORY (Grade) *</label>
                    <select id="catGrade" name="grade_key" class="formInput" required>
                        <?php foreach ($ALLOWED_GRADES as $g): ?>
                            <option value="<?php echo htmlspecialchars($g); ?>" <?php if (($editCategory['grade_key'] ?? 'MG') === $g) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($g); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #8a8f98;">Tapping this tile on the homepage opens the PRODUCTS page filtered to this grade.</small>
                </div>

                <div class="formGroup">
                    <label for="catSort">SORT ORDER</label>
                    <input type="number" id="catSort" name="sort_order" class="formInput" min="1" value="<?php echo (int)($editCategory['sort_order'] ?? 1); ?>">
                    <small style="color: #8a8f98;">Lower numbers appear first. The first tile renders full-width.</small>
                </div>

                <div class="formGroup">
                    <label class="formCheckbox">
                        <input type="checkbox" name="is_active" <?php if (($editCategory['is_active'] ?? 1)) echo 'checked'; ?>> Active (show on homepage)
                    </label>
                </div>

                <div class="formGroup">
                    <label>CATEGORY IMAGE &amp; CROPPER</label>
                    <p style="font-size: 0.78rem; color: var(--text-sub); margin-bottom: 0.6rem;">
                        Upload a photo from your device and crop it to the tile banner shape (~21:9).
                    </p>

                    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 0.8rem;">
                        <input type="file" id="categoryFileInput" name="category_file" accept="image/png, image/jpeg, image/webp" style="display: none;">
                        <button type="button" class="btnSecondary" onclick="document.getElementById('categoryFileInput').click();">
                             CHOOSE &amp; CROP TILE PHOTO
                        </button>
                        <span id="chosenCategoryFileName" style="font-size: 0.8rem; color: var(--text-muted);">No file selected</span>
                    </div>

                    <input type="hidden" id="croppedImageData" name="cropped_image_data" value="">

                    <div id="catImagePreviewBox" style="margin-top: 1rem; display: <?php echo !empty($editCategory['image_url']) ? 'block' : 'none'; ?>;">
                        <span style="font-size: 0.72rem; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">CURRENT PREVIEW:</span>
                        <div style="display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap;">
                            <img id="catPreviewImg" src="<?php echo !empty($editCategory['image_url']) ? adminAssetUrl($editCategory['image_url']) : ''; ?>" alt="Preview" style="max-height: 120px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <button type="button" class="btnSecondary btnSmall" id="cropCurrentCatBtn" style="border-color: var(--brand-cyan); color: var(--brand-cyan);">
                                 CROP THIS CURRENT IMAGE
                            </button>
                        </div>
                    </div>
                </div>

                <div class="formGroup">
                    <label for="existingImageSelect">OR USE AN EXISTING IMAGE</label>
                    <select id="existingImageSelect" name="existing_image" class="formInput">
                        <option value="">-- none --</option>
                        <?php foreach ($existingImages as $img): ?>
                            <option value="<?php echo htmlspecialchars($img); ?>" <?php if (($editCategory['image_url'] ?? '') === $img) echo 'selected'; ?>><?php echo htmlspecialchars($img); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btnPrimary"><?php echo $editCategory ? 'SAVE CHANGES' : 'ADD CATEGORY'; ?></button>
            </form>
        </div>
    </div>

    <!-- INTERACTIVE CATEGORY TILE CROPPER MODAL -->
    <div class="modalOverlay" id="catCropperModal">
        <div class="modalBox" style="max-width: 850px;">
            <div class="modalHeader">
                <h3 class="modalTitle">CROP CATEGORY TILE</h3>
                <button type="button" class="closeModalBtn" id="closeCatCropperModalBtn">&times;</button>
            </div>
            <div class="cropControls" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="cropRatioBtn activeRatio" data-ratio="2.3333">21:9 TILE BANNER</button>
                    <button type="button" class="cropRatioBtn" data-ratio="1.7777">16:9 WIDE</button>
                    <button type="button" class="cropRatioBtn" data-ratio="1">1:1 SQUARE</button>
                    <button type="button" class="cropRatioBtn" data-ratio="NaN">FREE CROP</button>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Export Quality:</label>
                    <select id="catCropFormatSelect" style="padding: 0.35rem 0.6rem; font-size: 0.75rem; background: rgba(14, 17, 21, 0.9); border: 1px solid var(--border-color); color: #fff; border-radius: 4px;">
                        <option value="jpeg" selected>Ultra-Quality JPEG (98%)</option>
                        <option value="png">Lossless Ultra-HD (PNG - Maximum Clarity)</option>
                        <option value="webp">High-Res WebP (98%)</option>
                    </select>
                </div>
            </div>
            <div class="cropperPreviewContainer" style="max-height: 450px;">
                <img id="catImageToCrop" src="" alt="To Crop">
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                <span style="font-size: 0.78rem; color: var(--text-sub);">Adjust frame to fit your tile. Scroll mouse wheel to zoom.</span>
                <div style="display: flex; gap: 0.8rem;">
                    <button type="button" class="btnSecondary" id="cancelCatCropBtn">CANCEL</button>
                    <button type="button" class="btnPrimary" id="applyCatCropBtn">APPLY &amp; USE IMAGE</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        const openAddCategoryModalBtn = document.getElementById('openAddCategoryModalBtn');
        const closeCategoryModalBtn = document.getElementById('closeCategoryModalBtn');

        if (openAddCategoryModalBtn) {
            openAddCategoryModalBtn.addEventListener('click', () => {
                window.location.href = 'categories.php?action=new';
            });
        }
        if (closeCategoryModalBtn) {
            closeCategoryModalBtn.addEventListener('click', () => {
                window.location.href = 'categories.php';
            });
        }

        // Category Tile Cropper Logic
        const CAT_TILE_RATIO = 2.3333; // ~21:9 banner, matches homepage tile shape
        let catCropper = null;
        let originalCatMime = 'image/jpeg';
        const categoryFileInput = document.getElementById('categoryFileInput');
        const chosenCategoryFileName = document.getElementById('chosenCategoryFileName');
        const catCropperModal = document.getElementById('catCropperModal');
        const catImageToCrop = document.getElementById('catImageToCrop');
        const closeCatCropperModalBtn = document.getElementById('closeCatCropperModalBtn');
        const cancelCatCropBtn = document.getElementById('cancelCatCropBtn');
        const applyCatCropBtn = document.getElementById('applyCatCropBtn');
        const croppedImageData = document.getElementById('croppedImageData');
        const catPreviewImg = document.getElementById('catPreviewImg');
        const catImagePreviewBox = document.getElementById('catImagePreviewBox');
        const catCropFormatSelect = document.getElementById('catCropFormatSelect');

        function openCatCropper(src, defaultMime) {
            catImageToCrop.src = src;
            catCropperModal.classList.add('active');
            if (catCropper) {
                catCropper.destroy();
            }
            if (catCropFormatSelect && defaultMime) {
                catCropFormatSelect.value = defaultMime.includes('png') ? 'png' : 'jpeg';
            }
            catCropper = new Cropper(catImageToCrop, {
                aspectRatio: CAT_TILE_RATIO,
                viewMode: 1,
                autoCropArea: 0.95,
                background: false,
                checkCrossOrigin: false
            });
        }

        function closeCatCropper() {
            catCropperModal.classList.remove('active');
            if (catCropper) {
                catCropper.destroy();
                catCropper = null;
            }
        }

        if (categoryFileInput) {
            categoryFileInput.addEventListener('change', (e) => {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    chosenCategoryFileName.textContent = file.name;
                    originalCatMime = file.type || 'image/jpeg';
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        openCatCropper(event.target.result, originalCatMime);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Ratio Buttons
        const catRatioBtns = document.querySelectorAll('#catCropperModal .cropRatioBtn');
        catRatioBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                catRatioBtns.forEach(b => b.classList.remove('activeRatio'));
                btn.classList.add('activeRatio');
                const ratio = parseFloat(btn.getAttribute('data-ratio'));
                if (catCropper) {
                    catCropper.setAspectRatio(ratio);
                }
            });
        });

        if (closeCatCropperModalBtn) closeCatCropperModalBtn.addEventListener('click', closeCatCropper);
        if (cancelCatCropBtn) cancelCatCropBtn.addEventListener('click', closeCatCropper);

        // Apply High-Quality Tile Crop
        if (applyCatCropBtn) {
            applyCatCropBtn.addEventListener('click', () => {
                if (!catCropper) return;

                const format = catCropFormatSelect ? catCropFormatSelect.value : 'jpeg';
                const mimeType = (format === 'png') ? 'image/png' : (format === 'jpeg' ? 'image/jpeg' : 'image/webp');
                const quality = (format === 'png') ? 1.0 : 0.98;

                const canvas = catCropper.getCroppedCanvas({
                    maxWidth: 2560,
                    maxHeight: 2560,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                    fillColor: (format === 'jpeg') ? '#ffffff' : undefined
                });

                const base64Url = canvas.toDataURL(mimeType, quality);
                croppedImageData.value = base64Url;

                catPreviewImg.src = base64Url;
                catImagePreviewBox.style.display = 'block';
                closeCatCropper();
            });
        }

        const existingImageSelect = document.getElementById('existingImageSelect');
        if (existingImageSelect) {
            existingImageSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    catPreviewImg.src = (e.target.value.indexOf('/') !== -1) ? '../' + e.target.value : '../promotional/' + e.target.value;
                    catImagePreviewBox.style.display = 'block';
                    croppedImageData.value = '';
                }
            });
        }

        // Crop Current / Selected Tile Image
        const cropCurrentCatBtn = document.getElementById('cropCurrentCatBtn');
        if (cropCurrentCatBtn) {
            cropCurrentCatBtn.addEventListener('click', () => {
                if (!catPreviewImg.src || catPreviewImg.src === '' || catPreviewImg.src === window.location.href) {
                    alert('No image is currently available to crop.');
                    return;
                }
                openCatCropper(catPreviewImg.src, 'image/jpeg');
            });
        }
    </script>
</body>
</html>
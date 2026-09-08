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
    $stmt = $pdo->prepare("DELETE FROM `category_tiles` WHERE `id` = :id");
    $stmt->execute(['id' => $delId]);
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
                $error = 'Could not add category — the slug may already be in use.';
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
                header('Location: categories.php?msg=updated');
                exit;
            } catch (Throwable $e) {
                error_log('Category update failed: ' . $e->getMessage());
                $error = 'Could not update category — the slug may already be in use.';
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
                <a href="categories.php" class="navLink active">
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                        <line x1="9" y1="6" x2="15" y2="6"></line>
                        <line x1="9" y1="12" x2="15" y2="12"></line>
                        <line x1="9" y1="18" x2="15" y2="18"></line>
                    </svg>
                    <span>CATEGORIES</span>
                </a>
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
                <a href="footer.php" class="navLink">
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
                    <label for="categoryFile">CATEGORY IMAGE</label>
                    <input type="file" id="categoryFile" name="category_file" accept=".jpg,.jpeg,.png,.webp" class="formInput">
                    <input type="hidden" id="croppedImageData" name="cropped_image_data" value="">
                    <small style="color: #8a8f98;">Upload a new image for this tile, or choose one below.</small>
                </div>

                <div class="formGroup">
                    <label for="existingImageSelect">OR USE AN EXISTING IMAGE</label>
                    <select id="existingImageSelect" name="existing_image" class="formInput">
                        <option value="">-- none --</option>
                        <?php foreach ($existingImages as $img): ?>
                            <option value="<?php echo htmlspecialchars($img); ?>"><?php echo htmlspecialchars($img); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btnPrimary"><?php echo $editCategory ? 'SAVE CHANGES' : 'ADD CATEGORY'; ?></button>
            </form>
        </div>
    </div>

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
    </script>
</body>
</html>
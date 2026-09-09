<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

$pdo = getDBConnection();
$message = '';
$error = '';

// 1. Handle DELETE
if (isset($_GET['delete']) && $pdo) {
    $delId = (int)$_GET['delete'];
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$_GET['token'])) {
        header('Location: products.php?msg=csrf');
        exit;
    }
    $imgStmt = $pdo->prepare("SELECT `image_url` FROM `products` WHERE `id` = :id");
    $imgStmt->execute(['id' => $delId]);
    $deletedImg = $imgStmt->fetchColumn() ?: null;
    $stmt = $pdo->prepare("DELETE FROM `products` WHERE `id` = :id");
    $stmt->execute(['id' => $delId]);
    deleteOrphanedImage($pdo, $deletedImg, null);
    header('Location: products.php?msg=deleted');
    exit;
}

// 2. Handle ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!csrfValid() || !in_array($_POST['form_action'] ?? '', ['add', 'edit'], true)) {
        header('Location: products.php?msg=csrf');
        exit;
    }
    $action = $_POST['form_action'] ?? 'add';
    $name = trim($_POST['name'] ?? '');
    $grade = trim($_POST['grade'] ?? 'MG');
    $scale = trim($_POST['scale'] ?? '1/100');
    $price = (float)($_POST['price'] ?? 0);
    $sold_count = (int)($_POST['sold_count'] ?? 0);
    $brand = trim($_POST['brand'] ?? 'BANDAI');
    $stock_status = trim($_POST['stock_status'] ?? 'IN-STOCK');
    $is_new_release = isset($_POST['is_new_release']) ? 1 : 0;
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
    $is_model_kit   = isset($_POST['is_model_kit'])   ? 1 : 0;
    // 3. Stock quantity input
    $stock = isset($_POST['stock']) && $_POST['stock'] !== '' ? max(0, (int)$_POST['stock']) : ($action === 'add' ? 10 : 0);

    // Resolve the displayed stock_status:
    //  - Manual overrides (PRE-ORDER, SOLD OUT) are preserved.
    //  - Otherwise status is auto-derived from stock (> 0 → IN-STOCK, ≤ 0 → OUT OF STOCK).
    $resolvedStatus = hangarResolveStockStatus($stock, $stock_status);
    // Server-side validation (T18)
    $grade = in_array(trim($_POST['grade'] ?? 'MG'), ['MG','RG','PG','HG','SD','FG','BB','METAL BUILD','HI-RES','RE 1/100','RE'], true) ? trim($_POST['grade']) : 'MG';
    $scale = in_array(trim($_POST['scale'] ?? '1/100'), ['1/60','1/100','1/144','1/220','NONSCALE'], true) ? trim($_POST['scale']) : '1/100';
    $price = max(0.0, (float)($_POST['price'] ?? 0));
    $sold_count = max(0, (int)($_POST['sold_count'] ?? 0));
    $brand = (trim($_POST['brand'] ?? '') === '') ? 'BANDAI' : trim($_POST['brand']);
    $stock_status = in_array(trim($_POST['stock_status'] ?? 'IN-STOCK'), ['IN-STOCK','PRE-ORDER','SOLD OUT'], true) ? trim($_POST['stock_status']) : 'IN-STOCK';

    $uploadedImg = handleAssetUpload('prod_', 'product_file', 'cropped_image_data', 'existing_image', 'assets/uploads/products');

    if ($action === 'add') {
        if (empty($name)) {
            $error = 'Product Name is required.';
        } else {
            $imgUrl = $uploadedImg ?: HANGAR_DEFAULT_IMAGE;
            $stmt = $pdo->prepare("
                INSERT INTO `products` 
                (`name`, `grade`, `scale`, `price`, `sold_count`, `brand`, `stock`, `stock_status`, `image_url`, `is_new_release`, `is_best_seller`, `is_model_kit`) 
                VALUES 
                (:name, :grade, :scale, :price, :sold_count, :brand, :stock, :stock_status, :image_url, :is_new_release, :is_best_seller, :is_model_kit)
            ");
            $stmt->execute([
                'name' => $name,
                'grade' => $grade,
                'scale' => $scale,
                'price' => $price,
                'sold_count' => $sold_count,
                'brand' => $brand,
                'stock' => $stock,
                'stock_status' => $resolvedStatus,
                'image_url' => $imgUrl,
                'is_new_release' => $is_new_release,
                'is_best_seller' => $is_best_seller,
                'is_model_kit' => $is_model_kit
            ]);
            header('Location: products.php?msg=added');
            exit;
        }
    } elseif ($action === 'edit') {
        $editId = (int)($_POST['product_id'] ?? 0);
        if ($editId > 0 && !empty($name)) {
            $currentImg = $_POST['current_image_url'] ?? '';
            $finalImg = $uploadedImg ?: $currentImg;

            $stmt = $pdo->prepare("
                UPDATE `products` SET 
                `name` = :name,
                `grade` = :grade,
                `scale` = :scale,
                `price` = :price,
                `sold_count` = :sold_count,
                `brand` = :brand,
                `stock` = :stock,
                `stock_status` = :stock_status,
                `image_url` = :image_url,
                `is_new_release` = :is_new_release,
                `is_best_seller` = :is_best_seller,
                `is_model_kit` = :is_model_kit
                WHERE `id` = :id
            ");
            $stmt->execute([
                'name' => $name,
                'grade' => $grade,
                'scale' => $scale,
                'price' => $price,
                'sold_count' => $sold_count,
                'brand' => $brand,
                'stock' => $stock,
                'stock_status' => $resolvedStatus,
                'image_url' => $finalImg,
                'is_new_release' => $is_new_release,
                'is_best_seller' => $is_best_seller,
                'is_model_kit' => $is_model_kit,
                'id' => $editId
            ]);
            deleteOrphanedImage($pdo, $currentImg, $finalImg);
            header('Location: products.php?msg=updated');
            exit;
        }
    }
}

// Fetch existing promotional images for the dropdown
$promotionalDir = __DIR__ . '/../promotional';
$existingImages = [];
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
// Include admin-uploaded product images (managed paths under assets/uploads/products/)
$uploadProdDir = __DIR__ . '/../assets/uploads/products';
if (is_dir($uploadProdDir)) {
    foreach (scandir($uploadProdDir) as $f) {
        if ($f !== '.' && $f !== '..' && !is_dir($uploadProdDir . '/' . $f)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $existingImages[] = 'assets/uploads/products/' . $f;
            }
        }
    }
}

// Fetch products with search/filter
$search = trim($_GET['search'] ?? '');
$filterGrade = trim($_GET['grade'] ?? '');

$sql = "SELECT * FROM `products` WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (`name` LIKE :search OR `brand` LIKE :search)";
    $params['search'] = "%$search%";
}
if (!empty($filterGrade)) {
    $sql .= " AND `grade` = :grade";
    $params['grade'] = $filterGrade;
}

$sql .= " ORDER BY `id` DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Product to edit if ?edit=ID
$editProduct = null;
if (isset($_GET['edit']) && $pdo) {
    $editStmt = $pdo->prepare("SELECT * FROM `products` WHERE `id` = :id");
    $editStmt->execute(['id' => (int)$_GET['edit']]);
    $editProduct = $editStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog Manager | THE HANGAR ADMIN</title>
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
                <span>//</span> PRODUCT CATALOG MANAGEMENT
            </div>
            <div class="topBarRight">
                <button type="button" class="btnPrimary" id="openAddModalBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>ADD NEW PRODUCT</span>
                </button>
            </div>
        </header>

        <div class="contentArea">
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong> 
                    <?php 
                        if ($_GET['msg'] === 'added') echo 'Product successfully registered to the Hangar catalog!';
                        elseif ($_GET['msg'] === 'updated') echo 'Product details successfully updated!';
                        elseif ($_GET['msg'] === 'deleted') echo 'Product removed from database.';
                    ?>
                </div>
            <?php endif; ?>

            <!-- Filter / Search Bar -->
            <div class="adminCard">
                <form method="GET" action="products.php" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex-grow: 1; min-width: 250px;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search product by title, grade, or brand..." style="width: 100%; padding: 0.75rem 1rem; background: rgba(14, 17, 21, 0.85); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                    </div>
                    <div>
                        <select name="grade" style="padding: 0.75rem 1rem; background: rgba(14, 17, 21, 0.85); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                            <option value="">All Grades</option>
                            <option value="RG" <?php if ($filterGrade === 'RG') echo 'selected'; ?>>Real Grade (RG)</option>
                            <option value="MG" <?php if ($filterGrade === 'MG') echo 'selected'; ?>>Master Grade (MG)</option>
                            <option value="PG" <?php if ($filterGrade === 'PG') echo 'selected'; ?>>Perfect Grade (PG)</option>
                            <option value="HG" <?php if ($filterGrade === 'HG') echo 'selected'; ?>>High Grade (HG)</option>
                            <option value="METAL BUILD" <?php if ($filterGrade === 'METAL BUILD') echo 'selected'; ?>>Metal Build</option>
                        </select>
                    </div>
                    <button type="submit" class="btnSecondary">FILTER</button>
                    <?php if (!empty($search) || !empty($filterGrade)): ?>
                        <a href="products.php" class="btnSecondary" style="text-decoration: none;">RESET</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Products Data Table -->
            <div class="adminCard">
                <div class="cardHeader">
                    <h2 class="cardTitle">REGISTERED PRODUCTS (<?php echo count($products); ?>)</h2>
                </div>

                <div style="overflow-x: auto;">
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>THUMB</th>
                                <th>KIT NAME</th>
                                <th>GRADE &amp; SCALE</th>
                                <th>PRICE (PHP)</th>
                                <th>SOLD</th>
                                <th>PLACEMENTS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">No products match your query.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars(adminAssetUrl($p['image_url'])); ?>" alt="" class="prodThumbnail" onerror="this.src='../promotional/Asset 8.png'">
                                        </td>
                                        <td>
                                            <strong style="font-size: 0.95rem;"><?php echo htmlspecialchars($p['name']); ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                                <?php echo htmlspecialchars($p['brand']); ?> &bull; <?php echo htmlspecialchars($p['stock_status']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-grade"><?php echo htmlspecialchars($p['grade']); ?></span>
                                            <span style="font-size: 0.78rem; color: var(--text-sub); margin-left: 4px;"><?php echo htmlspecialchars($p['scale']); ?></span>
                                        </td>
                                        <td>
                                            <strong style="color: var(--brand-cyan);">&#8369; <?php echo number_format($p['price'], 2); ?></strong>
                                        </td>
                                        <td><?php echo number_format($p['sold_count']); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                                <?php if ($p['is_new_release']): ?>
                                                    <span class="badge badge-nr">NEW RELEASE</span>
                                                <?php endif; ?>
                                                <?php if ($p['is_best_seller']): ?>
                                                    <span class="badge badge-bs">BEST SELLER</span>
                                                <?php endif; ?>
                                                <?php if ($p['is_model_kit']): ?>
                                                    <span class="badge badge-mk">MODEL KITS</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="actionBtns">
                                                <a href="products.php?edit=<?php echo $p['id']; ?>" class="btnSecondary btnSmall">EDIT</a>
                                                <a href="products.php?delete=<?php echo $p['id']; ?>&token=<?php echo csrfToken(); ?>" class="btnDanger" onclick="return confirm('Are you sure you want to remove this product?');">DELETE</a>
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

    <!-- ADD / EDIT PRODUCT MODAL -->
    <div class="modalOverlay <?php if ($editProduct || isset($_GET['action'])) echo 'active'; ?>" id="productModal">
        <div class="modalBox">
            <div class="modalHeader">
                <h3 class="modalTitle"><?php echo $editProduct ? 'EDIT PRODUCT' : 'ADD NEW PRODUCT'; ?></h3>
                <button type="button" class="closeModalBtn" id="closeProductModalBtn">&times;</button>
            </div>

            <form method="POST" action="products.php" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="form_action" value="<?php echo $editProduct ? 'edit' : 'add'; ?>">
                <?php echo csrfField(); ?>
                <?php if ($editProduct): ?>
                    <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                    <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($editProduct['image_url']); ?>">
                <?php endif; ?>

                <!-- Hidden field for Cropped Base64 Data -->
                <input type="hidden" name="cropped_image_data" id="croppedImageData">

                <div class="formGroup">
                    <label for="pName">PRODUCT NAME *</label>
                    <input type="text" id="pName" name="name" value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" placeholder="e.g. MG ASW-G-XX Gundam Vidar" required>
                </div>

                <div class="formRow">
                    <div class="formGroup">
                        <label for="pGrade">GRADE</label>
                        <select id="pGrade" name="grade">
                            <?php 
                                $grades = ['MG', 'RG', 'PG', 'HG', 'METAL BUILD', 'EG', 'SD', 'OTHER'];
                                $selGrade = $editProduct['grade'] ?? 'MG';
                                foreach ($grades as $g) {
                                    $selected = ($selGrade === $g) ? 'selected' : '';
                                    echo "<option value=\"$g\" $selected>$g</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="formGroup">
                        <label for="pScale">SCALE</label>
                        <select id="pScale" name="scale">
                            <?php 
                                $scales = ['1/100', '1/144', '1/60', '1/48', 'NON-SCALE'];
                                $selScale = $editProduct['scale'] ?? '1/100';
                                foreach ($scales as $sc) {
                                    $selected = ($selScale === $sc) ? 'selected' : '';
                                    echo "<option value=\"$sc\" $selected>$sc</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="formRow">
                    <div class="formGroup">
                        <label for="pPrice">PRICE (PHP &#8369;) *</label>
                        <input type="number" step="0.01" id="pPrice" name="price" value="<?php echo htmlspecialchars($editProduct['price'] ?? '0.00'); ?>" required>
                    </div>
                    <div class="formGroup">
                        <label for="pSold">SOLD COUNT</label>
                        <input type="number" id="pSold" name="sold_count" value="<?php echo htmlspecialchars($editProduct['sold_count'] ?? '0'); ?>">
                    </div>
                </div>

                <!-- Stock Quantity Input -->
                <div class="formRow">
                    <div class="formGroup">
                        <label for="pStock">STOCK QUANTITY</label>
                        <input type="number" id="pStock" name="stock" min="0" step="1" value="<?php echo htmlspecialchars($editProduct['stock'] ?? '10'); ?>" required>
                        <p style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.3rem;">The actual number of units available. Set to 0 to mark as OUT OF STOCK (unless manually overridden below).</p>
                    </div>
                    <div class="formGroup">
                        <label for="pStockStatus">STOCK STATUS</label>
                        <select id="pStockStatus" name="stock_status">
                            <option value="IN-STOCK" <?php if (($editProduct['stock_status'] ?? '') === 'IN-STOCK') echo 'selected'; ?>>IN-STOCK</option>
                            <option value="PRE-ORDER" <?php if (($editProduct['stock_status'] ?? '') === 'PRE-ORDER') echo 'selected'; ?>>PRE-ORDER</option>
                            <option value="SOLD OUT" <?php if (($editProduct['stock_status'] ?? '') === 'SOLD OUT') echo 'selected'; ?>>SOLD OUT</option>
                        </select>
                        <p style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.3rem;">
                            Status is auto-derived from stock <strong>unless</strong> you manually select PRE-ORDER or SOLD OUT.
                        </p>
                    </div>
                </div>

                <!-- Section Placement Checkboxes -->
                <div class="formGroup">
                    <label>STORE SECTION PLACEMENTS</label>
                    <div class="checkboxGroup">
                        <label class="checkItem">
                            <input type="checkbox" name="is_new_release" value="1" <?php if (!empty($editProduct['is_new_release'])) echo 'checked'; ?>>
                            <span><strong>NEW RELEASE (Section 3)</strong> &bull; Showcase on New Release product carousel</span>
                        </label>
                        <label class="checkItem">
                            <input type="checkbox" name="is_best_seller" value="1" <?php if (!empty($editProduct['is_best_seller'])) echo 'checked'; ?>>
                            <span><strong>BEST SELLERS (Section 5)</strong> &bull; Showcase in Best Sellers grid</span>
                        </label>
                        <label class="checkItem">
                            <input type="checkbox" name="is_model_kit" value="1" <?php if (!empty($editProduct['is_model_kit']) || !$editProduct) echo 'checked'; ?>>
                            <span><strong>MODEL KITS (Section 6)</strong> &bull; Include in Model Kits catalog with grade filter</span>
                        </label>
                    </div>
                </div>

                <!-- IMAGE UPLOAD & CROPPER SECTION -->
                <div class="formGroup">
                    <label>PRODUCT IMAGE &amp; CROPPER</label>
                    <p style="font-size: 0.78rem; color: var(--text-sub); margin-bottom: 0.6rem;">
                        Upload a photo from your computer to launch the interactive cropping tool, or select an existing asset.
                    </p>

                    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 0.8rem;">
                        <input type="file" id="productFileInput" name="product_file" accept="image/png, image/jpeg, image/webp" style="display: none;">
                        <button type="button" class="btnSecondary" onclick="document.getElementById('productFileInput').click();">
                             CHOOSE &amp; CROP NEW PHOTO
                        </button>
                        <span id="chosenFileName" style="font-size: 0.8rem; color: var(--text-muted);">No file selected</span>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="flex-grow: 1;">
                            <label style="font-size: 0.72rem; color: var(--text-muted);">OR REUSE EXISTING ASSET:</label>
                            <select name="existing_image" id="existingImageSelect">
                                <option value="">-- Choose from existing promotional assets --</option>
                                <?php foreach ($existingImages as $img): ?>
                                    <option value="<?php echo htmlspecialchars($img); ?>" <?php if (($editProduct['image_url'] ?? '') === $img) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($img); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Thumbnail Preview -->
                    <div id="imagePreviewBox" style="margin-top: 1rem; display: <?php echo !empty($editProduct['image_url']) ? 'block' : 'none'; ?>;">
                        <span style="font-size: 0.72rem; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">CURRENT PREVIEW:</span>
                        <div style="display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap;">
                            <img id="previewImg" src="<?php echo !empty($editProduct['image_url']) ? adminAssetUrl($editProduct['image_url']) : ''; ?>" alt="Preview" style="max-height: 120px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <button type="button" class="btnSecondary btnSmall" id="cropCurrentProductBtn" style="border-color: var(--brand-cyan); color: var(--brand-cyan);">
                                 CROP THIS CURRENT PICTURE
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <a href="products.php" class="btnSecondary">CANCEL</a>
                    <button type="submit" class="btnPrimary"><?php echo $editProduct ? 'SAVE CHANGES' : 'CREATE PRODUCT'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- INTERACTIVE CROPPER MODAL -->
    <div class="modalOverlay" id="cropperModal">
        <div class="modalBox" style="max-width: 700px;">
            <div class="modalHeader">
                <h3 class="modalTitle">CROP PRODUCT IMAGE</h3>
                <button type="button" class="closeModalBtn" id="closeCropperModalBtn">&times;</button>
            </div>

            <div class="cropControls" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="cropRatioBtn activeRatio" data-ratio="1">1:1 SQUARE</button>
                    <button type="button" class="cropRatioBtn" data-ratio="0.8">4:5 CARD RATIO</button>
                    <button type="button" class="cropRatioBtn" data-ratio="NaN">FREE CROP</button>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Export Quality:</label>
                    <select id="cropFormatSelect" style="padding: 0.35rem 0.6rem; font-size: 0.75rem; background: rgba(14, 17, 21, 0.9); border: 1px solid var(--border-color); color: #fff; border-radius: 4px;">
                        <option value="png" selected>Lossless Ultra-HD (PNG - Maximum Clarity)</option>
                        <option value="jpeg">Ultra-Quality JPEG (98%)</option>
                        <option value="webp">High-Res WebP (98%)</option>
                    </select>
                </div>
            </div>

            <div class="cropperPreviewContainer">
                <img id="imageToCrop" src="" alt="To Crop">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                <span style="font-size: 0.78rem; color: var(--text-sub);">Drag handles to adjust framing. Scroll mouse wheel to zoom.</span>
                <div style="display: flex; gap: 0.8rem;">
                    <button type="button" class="btnSecondary" id="cancelCropBtn">CANCEL</button>
                    <button type="button" class="btnPrimary" id="applyCropBtn">APPLY &amp; USE IMAGE</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cropper.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        // Modal Open / Close Logic
        const openAddModalBtn = document.getElementById('openAddModalBtn');
        const productModal = document.getElementById('productModal');
        const closeProductModalBtn = document.getElementById('closeProductModalBtn');

        if (openAddModalBtn) {
            openAddModalBtn.addEventListener('click', () => {
                productModal.classList.add('active');
            });
        }
        if (closeProductModalBtn) {
            closeProductModalBtn.addEventListener('click', () => {
                productModal.classList.remove('active');
            });
        }

        // Cropper Logic
        let cropper = null;
        let originalFileMime = 'image/png';
        const productFileInput = document.getElementById('productFileInput');
        const chosenFileName = document.getElementById('chosenFileName');
        const cropperModal = document.getElementById('cropperModal');
        const imageToCrop = document.getElementById('imageToCrop');
        const closeCropperModalBtn = document.getElementById('closeCropperModalBtn');
        const cancelCropBtn = document.getElementById('cancelCropBtn');
        const applyCropBtn = document.getElementById('applyCropBtn');
        const croppedImageData = document.getElementById('croppedImageData');
        const previewImg = document.getElementById('previewImg');
        const imagePreviewBox = document.getElementById('imagePreviewBox');
        const cropFormatSelect = document.getElementById('cropFormatSelect');

        productFileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                chosenFileName.textContent = file.name;
                originalFileMime = file.type || 'image/png';

                // Automatically default to PNG if input is PNG (preserves transparency)
                if (cropFormatSelect) {
                    cropFormatSelect.value = originalFileMime.includes('png') ? 'png' : 'jpeg';
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    imageToCrop.src = event.target.result;
                    cropperModal.classList.add('active');

                    if (cropper) {
                        cropper.destroy();
                    }

                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: 1, // Default 1:1 square
                        viewMode: 1,
                        autoCropArea: 0.95,
                        background: false
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Ratio Buttons
        const ratioBtns = document.querySelectorAll('.cropRatioBtn');
        ratioBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                ratioBtns.forEach(b => b.classList.remove('activeRatio'));
                btn.classList.add('activeRatio');
                const ratio = parseFloat(btn.getAttribute('data-ratio'));
                if (cropper) {
                    cropper.setAspectRatio(ratio);
                }
            });
        });

        function closeCropper() {
            cropperModal.classList.remove('active');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        if (closeCropperModalBtn) closeCropperModalBtn.addEventListener('click', closeCropper);
        if (cancelCropBtn) cancelCropBtn.addEventListener('click', closeCropper);

        // Apply High-Quality Crop
        if (applyCropBtn) {
            applyCropBtn.addEventListener('click', () => {
                if (!cropper) return;

                const format = cropFormatSelect ? cropFormatSelect.value : 'png';
                const mimeType = (format === 'png') ? 'image/png' : (format === 'jpeg' ? 'image/jpeg' : 'image/webp');
                const quality = (format === 'png') ? 1.0 : 0.98;

                // Full native resolution up to 4096px with high-quality bicubic smoothing
                const canvas = cropper.getCroppedCanvas({
                    maxWidth: 4096,
                    maxHeight: 4096,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                    fillColor: (format === 'jpeg') ? '#ffffff' : undefined
                });

                const base64Url = canvas.toDataURL(mimeType, quality);
                croppedImageData.value = base64Url;

                // Update Preview
                previewImg.src = base64Url;
                imagePreviewBox.style.display = 'block';

                closeCropper();
            });
        }

        // Existing image select change
        const existingImageSelect = document.getElementById('existingImageSelect');
        if (existingImageSelect) {
            existingImageSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    previewImg.src = (e.target.value.indexOf('/') !== -1) ? '../' + e.target.value : '../promotional/' + e.target.value;
                    imagePreviewBox.style.display = 'block';
                    croppedImageData.value = ''; // Reset custom crop
                }
            });
        }

        // Crop Current / Selected Product Image
        const cropCurrentProductBtn = document.getElementById('cropCurrentProductBtn');
        if (cropCurrentProductBtn) {
            cropCurrentProductBtn.addEventListener('click', () => {
                if (!previewImg.src || previewImg.src === '') {
                    alert('No image is currently available to crop.');
                    return;
                }

                imageToCrop.src = previewImg.src;
                cropperModal.classList.add('active');

                if (cropper) {
                    cropper.destroy();
                }

                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1, // 1:1 default
                    viewMode: 1,
                    autoCropArea: 0.95,
                    background: false,
                    checkCrossOrigin: false
                });
            });
        }
    </script>
</body>
</html>

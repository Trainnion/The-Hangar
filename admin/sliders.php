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
        header('Location: sliders.php?msg=csrf');
        exit;
    }
    $imgStmt = $pdo->prepare("SELECT `image_url` FROM `sliders` WHERE `id` = :id");
    $imgStmt->execute(['id' => $delId]);
    $deletedImg = $imgStmt->fetchColumn() ?: null;
    $stmt = $pdo->prepare("DELETE FROM `sliders` WHERE `id` = :id");
    $stmt->execute(['id' => $delId]);
    deleteOrphanedImage($pdo, $deletedImg, null);
    header('Location: sliders.php?msg=deleted');
    exit;
}

// 2. Handle ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!csrfValid() || !in_array($_POST['form_action'] ?? '', ['add', 'edit'], true)) {
        header('Location: sliders.php?msg=csrf');
        exit;
    }
    $action = $_POST['form_action'] ?? 'add';
    $section_key = trim($_POST['section_key'] ?? 'section1');
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $badge = trim($_POST['badge'] ?? '');
    $quote = trim($_POST['quote'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $button_text = trim($_POST['button_text'] ?? 'ORDER NOW!');
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $custom_url = trim($_POST['custom_url'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 1);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    // Server-side validation (T18)
    $section_key =in_array(trim($_POST['section_key'] ?? 'section1'), ['section1','section2','section7'], true) ? trim($_POST['section_key']) : 'section1';
    $button_text = (trim($_POST['button_text'] ?? '') === '') ? 'ORDER NOW!' : trim($_POST['button_text']);
    $sort_order = max(1, (int)$_POST['sort_order'] ?? 1);
    $product_id = (isset($_POST['product_id']) && $_POST['product_id'] !== '') ? max(1,(int)$_POST['product_id']) : null;

    $uploadedImg = handleAssetUpload('slider_', 'slider_file', 'cropped_image_data', 'existing_image', 'assets/uploads/sliders');

    if ($action === 'add') {
        if (empty($title)) {
            $error = 'Slide Title is required.';
        } else {
            $imgUrl = $uploadedImg ?: HANGAR_DEFAULT_IMAGE;
            $stmt = $pdo->prepare("
                INSERT INTO `sliders` 
                (`section_key`, `title`, `subtitle`, `badge`, `quote`, `author`, `image_url`, `button_text`, `product_id`, `custom_url`, `sort_order`, `is_active`) 
                VALUES 
                (:section_key, :title, :subtitle, :badge, :quote, :author, :image_url, :button_text, :product_id, :custom_url, :sort_order, :is_active)
            ");
            $stmt->execute([
                'section_key' => $section_key,
                'title' => $title,
                'subtitle' => $subtitle,
                'badge' => $badge,
                'quote' => $quote,
                'author' => $author,
                'image_url' => $imgUrl,
                'button_text' => $button_text,
                'product_id' => $product_id,
                'custom_url' => $custom_url,
                'sort_order' => $sort_order,
                'is_active' => $is_active
            ]);
            header('Location: sliders.php?msg=added');
            exit;
        }
    } elseif ($action === 'edit') {
        $editId = (int)($_POST['slider_id'] ?? 0);
        if ($editId > 0 && !empty($title)) {
            $currentImg = $_POST['current_image_url'] ?? '';
            $finalImg = $uploadedImg ?: $currentImg;

            $stmt = $pdo->prepare("
                UPDATE `sliders` SET 
                `section_key` = :section_key,
                `title` = :title,
                `subtitle` = :subtitle,
                `badge` = :badge,
                `quote` = :quote,
                `author` = :author,
                `image_url` = :image_url,
                `button_text` = :button_text,
                `product_id` = :product_id,
                `custom_url` = :custom_url,
                `sort_order` = :sort_order,
                `is_active` = :is_active
                WHERE `id` = :id
            ");
            $stmt->execute([
                'section_key' => $section_key,
                'title' => $title,
                'subtitle' => $subtitle,
                'badge' => $badge,
                'quote' => $quote,
                'author' => $author,
                'image_url' => $finalImg,
                'button_text' => $button_text,
                'product_id' => $product_id,
                'custom_url' => $custom_url,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
                'id' => $editId
            ]);
            deleteOrphanedImage($pdo, $currentImg, $finalImg);
            header('Location: sliders.php?msg=updated');
            exit;
        }
    }
}

// Fetch all products for the "Link to Product" dropdown
$allProducts = [];
if ($pdo) {
    $allProducts = $pdo->query("SELECT `id`, `name`, `grade`, `price` FROM `products` ORDER BY `name` ASC")->fetchAll();
}

// Fetch existing promotional images
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
// Include admin-uploaded slider images (managed paths under assets/uploads/sliders/)
$uploadSliderDir = __DIR__ . '/../assets/uploads/sliders';
if (is_dir($uploadSliderDir)) {
    foreach (scandir($uploadSliderDir) as $f) {
        if ($f !== '.' && $f !== '..' && !is_dir($uploadSliderDir . '/' . $f)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $existingImages[] = 'assets/uploads/sliders/' . $f;
            }
        }
    }
}

// Fetch sliders with section filtering
$filterSec = trim($_GET['sec'] ?? '');
$sql = "
    SELECT s.*, p.name AS linked_product_name, p.grade AS linked_product_grade 
    FROM `sliders` s 
    LEFT JOIN `products` p ON s.product_id = p.id 
    WHERE 1=1
";
$params = [];
if (!empty($filterSec)) {
    $sql .= " AND s.section_key = :sec";
    $params['sec'] = $filterSec;
}
$sql .= " ORDER BY s.section_key ASC, s.sort_order ASC, s.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sliders = $stmt->fetchAll();

// Slide to edit if ?edit=ID
$editSlide = null;
if (isset($_GET['edit']) && $pdo) {
    $editStmt = $pdo->prepare("SELECT * FROM `sliders` WHERE `id` = :id");
    $editStmt->execute(['id' => (int)$_GET['edit']]);
    $editSlide = $editStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slider Customizer (Sec 1, 2, 7) | THE HANGAR ADMIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Cropper.js CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
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
                <a href="sliders.php" class="navLink active">
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
                <a href="gcash.php" class="navLink">
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
                <span>//</span> HERO &amp; REPRINT SLIDERS CUSTOMIZER
            </div>
            <div class="topBarRight">
                <button type="button" class="btnPrimary" id="openAddSlideModalBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>ADD NEW SLIDE</span>
                </button>
            </div>
        </header>

        <div class="contentArea">
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong> 
                    <?php 
                        if ($_GET['msg'] === 'added') echo 'Slide created and deployed to the storefront!';
                        elseif ($_GET['msg'] === 'updated') echo 'Slide settings and product link updated!';
                        elseif ($_GET['msg'] === 'deleted') echo 'Slide removed from slider rotation.';
                    ?>
                </div>
            <?php endif; ?>

            <!-- Section Navigation Filter Tabs -->
            <div class="adminCard" style="padding: 1rem 1.5rem;">
                <div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
                    <a href="sliders.php" class="btnSecondary <?php if (empty($filterSec)) echo 'btnPrimary'; ?>" style="text-decoration: none;">ALL SLIDERS</a>
                    <a href="sliders.php?sec=section1" class="btnSecondary <?php if ($filterSec === 'section1') echo 'btnPrimary'; ?>" style="text-decoration: none;">SECTION 1: TOP HERO</a>
                    <a href="sliders.php?sec=section2" class="btnSecondary <?php if ($filterSec === 'section2') echo 'btnPrimary'; ?>" style="text-decoration: none;">SECTION 2: REPRINT RUN (TIMER SLAP)</a>
                    <a href="sliders.php?sec=section7" class="btnSecondary <?php if ($filterSec === 'section7') echo 'btnPrimary'; ?>" style="text-decoration: none;">SECTION 7: CINEMATIC BANNER</a>
                </div>
            </div>

            <!-- Sliders Table -->
            <div class="adminCard">
                <div class="cardHeader">
                    <h2 class="cardTitle">SLIDER CONFIGURATIONS (<?php echo count($sliders); ?>)</h2>
                </div>

                <div style="overflow-x: auto;">
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>SECTION</th>
                                <th>BANNER / ART</th>
                                <th>HEADLINE &amp; BADGE</th>
                                <th>LINKED PRODUCT (ORDER NOW TARGET)</th>
                                <th>STATUS</th>
                                <th>SORT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sliders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">No slides found in this section.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sliders as $s): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-nr">
                                                <?php 
                                                    if ($s['section_key'] === 'section1') echo 'SEC 1: TOP HERO';
                                                    elseif ($s['section_key'] === 'section2') echo 'SEC 2: REPRINT';
                                                    elseif ($s['section_key'] === 'section7') echo 'SEC 7: CINEMATIC';
                                                    else echo htmlspecialchars($s['section_key']);
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars(adminAssetUrl($s['image_url'])); ?>" alt="" class="prodThumbnail" style="width: 80px; height: 45px; object-fit: cover;" onerror="this.src='../promotional/Asset 8.png'">
                                        </td>
                                        <td>
                                            <strong style="font-size: 0.95rem;"><?php echo strip_tags($s['title']); ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                                Badge: <span style="color: var(--brand-cyan);"><?php echo htmlspecialchars($s['badge'] ?: 'None'); ?></span> 
                                                <?php if (!empty($s['subtitle'])): ?>
                                                    &bull; <?php echo htmlspecialchars($s['subtitle']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($s['linked_product_name'])): ?>
                                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                                    <span style="color: var(--brand-cyan); font-weight: 700;">🔗 <?php echo htmlspecialchars($s['linked_product_name']); ?></span>
                                                    <span class="badge badge-grade"><?php echo htmlspecialchars($s['linked_product_grade']); ?></span>
                                                </div>
                                            <?php elseif (!empty($s['custom_url'])): ?>
                                                <span style="color: var(--text-sub);">Custom URL: <code><?php echo htmlspecialchars($s['custom_url']); ?></code></span>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);">No Target Link</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($s['is_active']): ?>
                                                <span class="badge badge-active">ACTIVE</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">INACTIVE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>#<?php echo $s['sort_order']; ?></td>
                                        <td>
                                            <div class="actionBtns">
                                                <a href="sliders.php?edit=<?php echo $s['id']; ?>" class="btnSecondary btnSmall">EDIT</a>
                                                <a href="sliders.php?delete=<?php echo $s['id']; ?>&token=<?php echo csrfToken(); ?>" class="btnDanger" onclick="return confirm('Remove this slide from the rotation?');">DELETE</a>
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

    <!-- ADD / EDIT SLIDE MODAL -->
    <div class="modalOverlay <?php if ($editSlide || isset($_GET['action'])) echo 'active'; ?>" id="slideModal">
        <div class="modalBox">
            <div class="modalHeader">
                <h3 class="modalTitle"><?php echo $editSlide ? 'EDIT SLIDE' : 'ADD NEW SLIDE'; ?></h3>
                <button type="button" class="closeModalBtn" id="closeSlideModalBtn">&times;</button>
            </div>

            <form method="POST" action="sliders.php" enctype="multipart/form-data" id="slideForm">
                <input type="hidden" name="form_action" value="<?php echo $editSlide ? 'edit' : 'add'; ?>">
                <?php echo csrfField(); ?>
                <?php if ($editSlide): ?>
                    <input type="hidden" name="slider_id" value="<?php echo $editSlide['id']; ?>">
                    <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($editSlide['image_url']); ?>">
                <?php endif; ?>

                <input type="hidden" name="cropped_image_data" id="croppedImageData">

                <div class="formRow">
                    <div class="formGroup">
                        <label for="sSection">TARGET SLIDER SECTION *</label>
                        <select id="sSection" name="section_key" required>
                            <option value="section1" <?php if (($editSlide['section_key'] ?? '') === 'section1') echo 'selected'; ?>>Section 1: Top Hero Banner</option>
                            <option value="section2" <?php if (($editSlide['section_key'] ?? '') === 'section2') echo 'selected'; ?>>Section 2: Reprint Run (Timer Slap)</option>
                            <option value="section7" <?php if (($editSlide['section_key'] ?? '') === 'section7') echo 'selected'; ?>>Section 7: Featured Cinematic Banner</option>
                        </select>
                    </div>
                    <div class="formGroup">
                        <label for="sSort">DISPLAY ORDER</label>
                        <input type="number" id="sSort" name="sort_order" value="<?php echo htmlspecialchars($editSlide['sort_order'] ?? '1'); ?>">
                    </div>
                </div>

                <div class="formGroup">
                    <label for="sTitle">SLIDE TITLE / MECHA NAME *</label>
                    <input type="text" id="sTitle" name="title" value="<?php echo htmlspecialchars($editSlide['title'] ?? ''); ?>" placeholder="e.g. RX-98-ν2 Hi-ν GUNDAM" required>
                </div>

                <div class="formRow">
                    <div class="formGroup">
                        <label for="sBadge">GRADE BADGE / TAG</label>
                        <input type="text" id="sBadge" name="badge" value="<?php echo htmlspecialchars($editSlide['badge'] ?? 'METALBUILD'); ?>" placeholder="e.g. METALBUILD or REPRINT RUN!">
                    </div>
                    <div class="formGroup">
                        <label for="sSubtitle">SUBTITLE / WEAPONRY</label>
                        <input type="text" id="sSubtitle" name="subtitle" value="<?php echo htmlspecialchars($editSlide['subtitle'] ?? ''); ?>" placeholder="e.g. HYPER MEGA BAZOOKA LAUNCER">
                    </div>
                </div>

                <!-- Section 2 Quote & Author -->
                <div class="formRow" id="sec2QuoteRow">
                    <div class="formGroup">
                        <label for="sQuote">LORE QUOTE (SECTION 2)</label>
                        <textarea id="sQuote" name="quote" rows="2" placeholder="“Even so, there is still a future we must protect!”"><?php echo htmlspecialchars($editSlide['quote'] ?? ''); ?></textarea>
                    </div>
                    <div class="formGroup">
                        <label for="sAuthor">QUOTE AUTHOR (SECTION 2)</label>
                        <input type="text" id="sAuthor" name="author" value="<?php echo htmlspecialchars($editSlide['author'] ?? ''); ?>" placeholder="-Kira Yamato">
                    </div>
                </div>

                <!-- LINK TO PRODUCT SELECTOR -->
                <div class="formGroup" style="background: rgba(63, 196, 225, 0.06); border: 1px solid rgba(63, 196, 225, 0.25); border-radius: 6px; padding: 1.2rem;">
                    <label style="color: var(--brand-cyan);">🔗 LINK TO PRODUCT (CLICKING SLIDE / ORDER NOW! DIRECTS HERE)</label>
                    <p style="font-size: 0.78rem; color: var(--text-sub); margin-bottom: 0.6rem;">
                        Select an existing Gunpla model kit from your store. When visitors click the slide or the "ORDER NOW!" button, they will be sent straight to this product.
                    </p>
                    <select id="sProduct" name="product_id" style="width: 100%;">
                        <option value="">-- No Direct Product Link (Custom URL or None) --</option>
                        <?php foreach ($allProducts as $ap): ?>
                            <option value="<?php echo $ap['id']; ?>" <?php if (($editSlide['product_id'] ?? '') == $ap['id']) echo 'selected'; ?>>
                                [<?php echo htmlspecialchars($ap['grade']); ?>] <?php echo htmlspecialchars($ap['name']); ?> &bull; ₱ <?php echo number_format($ap['price'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div style="margin-top: 0.8rem;">
                        <label for="sCustomUrl" style="font-size: 0.72rem; color: var(--text-muted);">OR ENTER CUSTOM DESTINATION URL:</label>
                        <input type="text" id="sCustomUrl" name="custom_url" value="<?php echo htmlspecialchars($editSlide['custom_url'] ?? ''); ?>" placeholder="https://... or #product-anchor">
                    </div>
                </div>

                <div class="formRow">
                    <div class="formGroup">
                        <label for="sBtnText">BUTTON TEXT</label>
                        <input type="text" id="sBtnText" name="button_text" value="<?php echo htmlspecialchars($editSlide['button_text'] ?? 'ORDER NOW!'); ?>">
                    </div>
                    <div class="formGroup" style="display: flex; align-items: center; padding-top: 1.5rem;">
                        <label class="checkItem">
                            <input type="checkbox" name="is_active" value="1" <?php if (!isset($editSlide['is_active']) || $editSlide['is_active'] == 1) echo 'checked'; ?>>
                            <span><strong>ENABLE SLIDE IN ROTATION</strong></span>
                        </label>
                    </div>
                </div>

                <!-- SLIDER IMAGE UPLOAD & CROPPER -->
                <div class="formGroup">
                    <label>SLIDE IMAGE / BANNER PHOTO &amp; CROPPER</label>
                    <p style="font-size: 0.78rem; color: var(--text-sub); margin-bottom: 0.6rem;">
                        Upload a wide banner or artwork from your device to crop it with widescreen presets.
                    </p>

                    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 0.8rem;">
                        <input type="file" id="sliderFileInput" name="slider_file" accept="image/png, image/jpeg, image/webp" style="display: none;">
                        <button type="button" class="btnSecondary" onclick="document.getElementById('sliderFileInput').click();">
                            📁 CHOOSE &amp; CROP BANNER PHOTO
                        </button>
                        <span id="chosenSliderFileName" style="font-size: 0.8rem; color: var(--text-muted);">No file selected</span>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="flex-grow: 1;">
                            <label style="font-size: 0.72rem; color: var(--text-muted);">OR REUSE EXISTING ASSET:</label>
                            <select name="existing_image" id="existingSliderImageSelect">
                                <option value="">-- Choose from existing promotional assets --</option>
                                <?php foreach ($existingImages as $img): ?>
                                    <option value="<?php echo htmlspecialchars($img); ?>" <?php if (($editSlide['image_url'] ?? '') === $img) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($img); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="sliderImagePreviewBox" style="margin-top: 1rem; display: <?php echo !empty($editSlide['image_url']) ? 'block' : 'none'; ?>;">
                        <span style="font-size: 0.72rem; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">CURRENT PREVIEW:</span>
                        <div style="display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap;">
                            <img id="sliderPreviewImg" src="<?php echo !empty($editSlide['image_url']) ? adminAssetUrl($editSlide['image_url']) : ''; ?>" alt="Preview" style="max-height: 120px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <button type="button" class="btnSecondary btnSmall" id="cropCurrentSlideBtn" style="border-color: var(--brand-cyan); color: var(--brand-cyan);">
                                ✂️ CROP THIS CURRENT BANNER
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <a href="sliders.php" class="btnSecondary">CANCEL</a>
                    <button type="submit" class="btnPrimary"><?php echo $editSlide ? 'SAVE SLIDE' : 'CREATE SLIDE'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- INTERACTIVE SLIDER CROPPER MODAL -->
    <div class="modalOverlay" id="sliderCropperModal">
        <div class="modalBox" style="max-width: 850px;">
            <div class="modalHeader">
                <h3 class="modalTitle">CROP SLIDER BANNER</h3>
                <button type="button" class="closeModalBtn" id="closeSliderCropperModalBtn">&times;</button>
            </div>

            <div class="cropControls" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="cropRatioBtn activeRatio" data-ratio="1.7777">16:9 WIDESCREEN HERO</button>
                    <button type="button" class="cropRatioBtn" data-ratio="2.3333">21:9 CINEMATIC BANNER</button>
                    <button type="button" class="cropRatioBtn" data-ratio="3.5">3.5:1 WIDE STRIP (SEC 7)</button>
                    <button type="button" class="cropRatioBtn" data-ratio="NaN">FREE CROP</button>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Export Quality:</label>
                    <select id="sliderCropFormatSelect" style="padding: 0.35rem 0.6rem; font-size: 0.75rem; background: rgba(14, 17, 21, 0.9); border: 1px solid var(--border-color); color: #fff; border-radius: 4px;">
                        <option value="png" selected>Lossless Ultra-HD (PNG - Maximum Clarity)</option>
                        <option value="jpeg">Ultra-Quality JPEG (98%)</option>
                        <option value="webp">High-Res WebP (98%)</option>
                    </select>
                </div>
            </div>

            <div class="cropperPreviewContainer" style="max-height: 450px;">
                <img id="sliderImageToCrop" src="" alt="To Crop">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                <span style="font-size: 0.78rem; color: var(--text-sub);">Adjust frame to fit your banner. Scroll mouse wheel to zoom.</span>
                <div style="display: flex; gap: 0.8rem;">
                    <button type="button" class="btnSecondary" id="cancelSliderCropBtn">CANCEL</button>
                    <button type="button" class="btnPrimary" id="applySliderCropBtn">APPLY &amp; USE BANNER</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        const openAddSlideModalBtn = document.getElementById('openAddSlideModalBtn');
        const slideModal = document.getElementById('slideModal');
        const closeSlideModalBtn = document.getElementById('closeSlideModalBtn');

        if (openAddSlideModalBtn) {
            openAddSlideModalBtn.addEventListener('click', () => {
                slideModal.classList.add('active');
            });
        }
        if (closeSlideModalBtn) {
            closeSlideModalBtn.addEventListener('click', () => {
                slideModal.classList.remove('active');
            });
        }

        // Cropper Logic
        let sliderCropper = null;
        let originalSliderMime = 'image/png';
        const sliderFileInput = document.getElementById('sliderFileInput');
        const chosenSliderFileName = document.getElementById('chosenSliderFileName');
        const sliderCropperModal = document.getElementById('sliderCropperModal');
        const sliderImageToCrop = document.getElementById('sliderImageToCrop');
        const closeSliderCropperModalBtn = document.getElementById('closeSliderCropperModalBtn');
        const cancelSliderCropBtn = document.getElementById('cancelSliderCropBtn');
        const applySliderCropBtn = document.getElementById('applySliderCropBtn');
        const croppedImageData = document.getElementById('croppedImageData');
        const sliderPreviewImg = document.getElementById('sliderPreviewImg');
        const sliderImagePreviewBox = document.getElementById('sliderImagePreviewBox');
        const sliderCropFormatSelect = document.getElementById('sliderCropFormatSelect');

        sliderFileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                chosenSliderFileName.textContent = file.name;
                originalSliderMime = file.type || 'image/png';

                if (sliderCropFormatSelect) {
                    sliderCropFormatSelect.value = originalSliderMime.includes('png') ? 'png' : 'jpeg';
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    sliderImageToCrop.src = event.target.result;
                    sliderCropperModal.classList.add('active');

                    if (sliderCropper) {
                        sliderCropper.destroy();
                    }

                    sliderCropper = new Cropper(sliderImageToCrop, {
                        aspectRatio: 16 / 9,
                        viewMode: 1,
                        autoCropArea: 0.95,
                        background: false
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Ratio Buttons
        const ratioBtns = document.querySelectorAll('#sliderCropperModal .cropRatioBtn');
        ratioBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                ratioBtns.forEach(b => b.classList.remove('activeRatio'));
                btn.classList.add('activeRatio');
                const ratio = parseFloat(btn.getAttribute('data-ratio'));
                if (sliderCropper) {
                    sliderCropper.setAspectRatio(ratio);
                }
            });
        });

        function closeSliderCropper() {
            sliderCropperModal.classList.remove('active');
            if (sliderCropper) {
                sliderCropper.destroy();
                sliderCropper = null;
            }
        }

        if (closeSliderCropperModalBtn) closeSliderCropperModalBtn.addEventListener('click', closeSliderCropper);
        if (cancelSliderCropBtn) cancelSliderCropBtn.addEventListener('click', closeSliderCropper);

        // Apply High-Quality Banner Crop
        if (applySliderCropBtn) {
            applySliderCropBtn.addEventListener('click', () => {
                if (!sliderCropper) return;

                const format = sliderCropFormatSelect ? sliderCropFormatSelect.value : 'png';
                const mimeType = (format === 'png') ? 'image/png' : (format === 'jpeg' ? 'image/jpeg' : 'image/webp');
                const quality = (format === 'png') ? 1.0 : 0.98;

                // Ultra HD resolution up to 4096px with bicubic anti-aliasing
                const canvas = sliderCropper.getCroppedCanvas({
                    maxWidth: 4096,
                    maxHeight: 4096,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                    fillColor: (format === 'jpeg') ? '#ffffff' : undefined
                });

                const base64Url = canvas.toDataURL(mimeType, quality);
                croppedImageData.value = base64Url;

                sliderPreviewImg.src = base64Url;
                sliderImagePreviewBox.style.display = 'block';

                closeSliderCropper();
            });
        }

        const existingSliderImageSelect = document.getElementById('existingSliderImageSelect');
        if (existingSliderImageSelect) {
            existingSliderImageSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    sliderPreviewImg.src = (e.target.value.indexOf('/') !== -1) ? '../' + e.target.value : '../promotional/' + e.target.value;
                    sliderImagePreviewBox.style.display = 'block';
                    croppedImageData.value = '';
                }
            });
        }

        // Crop Current / Selected Slider Banner
        const cropCurrentSlideBtn = document.getElementById('cropCurrentSlideBtn');
        if (cropCurrentSlideBtn) {
            cropCurrentSlideBtn.addEventListener('click', () => {
                if (!sliderPreviewImg.src || sliderPreviewImg.src === '') {
                    alert('No banner is currently available to crop.');
                    return;
                }

                sliderImageToCrop.src = sliderPreviewImg.src;
                sliderCropperModal.classList.add('active');

                if (sliderCropper) {
                    sliderCropper.destroy();
                }

                // Default ratio based on current section
                const currentSec = document.getElementById('sSection')?.value || 'section1';
                let defaultRatio = 16 / 9;
                if (currentSec === 'section7') {
                    defaultRatio = 3.5;
                }

                sliderCropper = new Cropper(sliderImageToCrop, {
                    aspectRatio: defaultRatio,
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

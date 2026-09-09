<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

$pdo = getDBConnection();

// Fetch summary metrics
$totalProducts = 0;
$nrCount = 0;
$bsCount = 0;
$mkCount = 0;
$sliderCount = 0;
$orderCount = 0;
$pendingOrders = 0;
$recentProducts = [];
$recentOrders = [];
$activeSliders = [];

if ($pdo) {
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
    $nrCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE `is_new_release` = 1")->fetchColumn();
    $bsCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE `is_best_seller` = 1")->fetchColumn();
    $mkCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE `is_model_kit` = 1")->fetchColumn();
    $sliderCount = (int)$pdo->query("SELECT COUNT(*) FROM `sliders` WHERE `is_active` = 1")->fetchColumn();
    $orderCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    $pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'pending'")->fetchColumn();

    $recentProducts = $pdo->query("SELECT * FROM `products` ORDER BY `id` DESC LIMIT 6")->fetchAll();
    $recentOrders = $pdo->query("SELECT * FROM `orders` ORDER BY `id` DESC LIMIT 6")->fetchAll();
    $activeSliders = $pdo->query("
        SELECT s.*, p.name AS linked_product_name 
        FROM `sliders` s 
        LEFT JOIN `products` p ON s.product_id = p.id 
        WHERE s.is_active = 1 
        ORDER BY s.section_key ASC, s.sort_order ASC
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Command Dashboard | THE HANGAR ADMIN</title>
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
                <span>//</span> COMMAND DASHBOARD
            </div>
            <div class="topBarRight">
                <span class="pilotTag">Logged in: <strong><?php echo htmlspecialchars($_SESSION['hangar_admin_user'] ?? 'admin'); ?></strong></span>
            </div>
        </header>

        <div class="contentArea">
            <!-- Stat Grid -->
            <div class="statGrid">
                <div class="statCard">
                    <span class="statLabel">TOTAL PRODUCTS</span>
                    <span class="statValue"><?php echo $totalProducts; ?></span>
                    <span class="statSub">In Database Catalog</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">NEW RELEASES (SEC 3)</span>
                    <span class="statValue"><?php echo $nrCount; ?></span>
                    <span class="statSub">Active on Carousel</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">BEST SELLERS (SEC 5)</span>
                    <span class="statValue"><?php echo $bsCount; ?></span>
                    <span class="statSub">Standardized Uniform Cards</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">MODEL KITS (SEC 6)</span>
                    <span class="statValue"><?php echo $mkCount; ?></span>
                    <span class="statSub">Filterable Catalog</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">ACTIVE SLIDERS</span>
                    <span class="statValue"><?php echo $sliderCount; ?></span>
                    <span class="statSub">Across Sec 1, 2, and 7</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">PENDING ORDERS</span>
                    <span class="statValue"><?php echo $pendingOrders; ?></span>
                    <span class="statSub"><?php echo $orderCount; ?> Total Manifests</span>
                </div>
            </div>

            <!-- Quick Action Bar -->
            <div class="adminCard">
                <div class="cardHeader">
                    <h2 class="cardTitle">QUICK OPERATIONS</h2>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="products.php?action=new" class="btnPrimary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>ADD NEW PRODUCT (WITH CROPPER)</span>
                    </a>
                    <a href="sliders.php" class="btnSecondary">
                        <span>CUSTOMIZE HERO &amp; REPRINT SLIDERS</span>
                    </a>
                    <a href="orders.php" class="btnSecondary">
                        <span>MANAGE ORDER DISPATCH</span>
                    </a>
                    <a href="featured.php" class="btnSecondary">
                        <span>EDIT FEATURED SECTION (SEC 7)</span>
                    </a>
                    <a href="categories.php" class="btnSecondary">
                        <span>EDIT CATEGORY TILES</span>
                    </a>
                </div>
            </div>

            <!-- Recent Products Table -->
            <div class="adminCard">
                <div class="cardHeader">
                    <h2 class="cardTitle">RECENTLY ADDED PRODUCTS</h2>
                    <a href="products.php" class="btnSecondary btnSmall">VIEW ALL PRODUCTS &#8594;</a>
                </div>

                <div style="overflow-x: auto;">
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>IMG</th>
                                <th>NAME</th>
                                <th>GRADE</th>
                                <th>PRICE</th>
                                <th>SOLD</th>
                                <th>SECTIONS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentProducts)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted);">No products registered in the hangar yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentProducts as $prod): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars(adminAssetUrl($prod['image_url'])); ?>" alt="" class="prodThumbnail" onerror="this.src='../promotional/Asset 8.png'">
                                        </td>
                                        <td>
                                            <strong style="font-size: 0.92rem;"><?php echo htmlspecialchars($prod['name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-grade"><?php echo htmlspecialchars($prod['grade']); ?></span>
                                        </td>
                                        <td>
                                            <strong>&#8369; <?php echo number_format($prod['price'], 2); ?></strong>
                                        </td>
                                        <td><?php echo number_format($prod['sold_count']); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.3rem; flex-wrap: wrap;">
                                                <?php if ($prod['is_new_release']): ?>
                                                    <span class="badge badge-nr">NEW RELEASE</span>
                                                <?php endif; ?>
                                                <?php if ($prod['is_best_seller']): ?>
                                                    <span class="badge badge-bs">BEST SELLER</span>
                                                <?php endif; ?>
                                                <?php if ($prod['is_model_kit']): ?>
                                                    <span class="badge badge-mk">MODEL KITS</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="actionBtns">
                                                <a href="products.php?edit=<?php echo $prod['id']; ?>" class="btnSecondary btnSmall">EDIT</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Sliders Overview -->
            <div class="adminCard">
                <div class="cardHeader">
                    <h2 class="cardTitle">ACTIVE SLIDERS STATUS (SECTION 1, 2, 7)</h2>
                    <a href="sliders.php" class="btnSecondary btnSmall">MANAGE SLIDERS &#8594;</a>
                </div>

                <div style="overflow-x: auto;">
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>SECTION</th>
                                <th>PREVIEW</th>
                                <th>TITLE</th>
                                <th>SUBTITLE / BADGE</th>
                                <th>LINKED PRODUCT</th>
                                <th>SORT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeSliders as $slider): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-nr" style="text-transform: uppercase;">
                                            <?php 
                                                if ($slider['section_key'] === 'section1') echo 'SEC 1: TOP HERO';
                                                elseif ($slider['section_key'] === 'section2') echo 'SEC 2: REPRINT RUN';
                                                elseif ($slider['section_key'] === 'section7') echo 'SEC 7: CINEMATIC BANNER';
                                                else echo htmlspecialchars($slider['section_key']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars(adminAssetUrl($slider['image_url'])); ?>" alt="" class="prodThumbnail" style="width: 70px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td><strong><?php echo strip_tags($slider['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($slider['subtitle'] ?: $slider['badge'] ?: '-'); ?></td>
                                    <td>
                                        <?php if (!empty($slider['linked_product_name'])): ?>
                                            <span style="color: var(--brand-cyan);">&#8594; <?php echo htmlspecialchars($slider['linked_product_name']); ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>#<?php echo $slider['sort_order']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/db.php';

$pdo = getDBConnection();

// Fetch summary metrics
$totalProducts = 0;
$nrCount = 0;
$bsCount = 0;
$mkCount = 0;
$sliderCount = 0;
$recentProducts = [];
$activeSliders = [];

if ($pdo) {
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
    $nrCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE `is_new_release` = 1")->fetchColumn();
    $bsCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE `is_best_seller` = 1")->fetchColumn();
    $mkCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE `is_model_kit` = 1")->fetchColumn();
    $sliderCount = (int)$pdo->query("SELECT COUNT(*) FROM `sliders` WHERE `is_active` = 1")->fetchColumn();

    $recentProducts = $pdo->query("SELECT * FROM `products` ORDER BY `id` DESC LIMIT 6")->fetchAll();
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
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                <a href="index.php" class="navLink active">
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
                <a href="sliders.php" class="navLink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span>SLIDERS (SEC 1, 2, 7)</span>
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
                </div>
            </div>

            <!-- Recent Products Table -->
            <div class="adminCard">
                <div class="cardHeader">
                    <h2 class="cardTitle">RECENTLY ADDED PRODUCTS</h2>
                    <a href="products.php" class="btnSecondary btnSmall">VIEW ALL PRODUCTS →</a>
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
                                            <img src="../promotional/<?php echo htmlspecialchars($prod['image_url']); ?>" alt="" class="prodThumbnail" onerror="this.src='../promotional/Asset 8.png'">
                                        </td>
                                        <td>
                                            <strong style="font-size: 0.92rem;"><?php echo htmlspecialchars($prod['name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-grade"><?php echo htmlspecialchars($prod['grade']); ?></span>
                                        </td>
                                        <td>
                                            <strong>₱ <?php echo number_format($prod['price'], 2); ?></strong>
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
                    <a href="sliders.php" class="btnSecondary btnSmall">MANAGE SLIDERS →</a>
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
                                        <img src="../promotional/<?php echo htmlspecialchars($slider['image_url']); ?>" alt="" class="prodThumbnail" style="width: 70px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td><strong><?php echo strip_tags($slider['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($slider['subtitle'] ?: $slider['badge'] ?: '-'); ?></td>
                                    <td>
                                        <?php if (!empty($slider['linked_product_name'])): ?>
                                            <span style="color: var(--brand-cyan);">→ <?php echo htmlspecialchars($slider['linked_product_name']); ?></span>
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

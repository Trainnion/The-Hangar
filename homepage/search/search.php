<?php
// THE HANGAR - GUND-ORDER SYSTEM
// MODEL KITS CATALOG SEARCH & PRODUCT GRID
// Aligned with Section 6 (Model Kits) & Section 7 (Static Filter Banner) design language.

require_once __DIR__ . '/../../shared/bootstrap.php';
extract(hangarBootstrap());

require_once __DIR__ . '/../../shared/db.php';
require_once __DIR__ . '/../db_helper.php';
$pdo = getDBConnection();

$query       = trim($_GET['q'] ?? '');
$filterGrade = trim($_GET['grade'] ?? '');
$sort        = trim($_GET['sort'] ?? 'sold_desc');

$gradeAliases = getGradeAliases();
$lowerQuery = strtolower($query);
if (isset($gradeAliases[$lowerQuery]) && empty($filterGrade)) {
    $filterGrade = $gradeAliases[$lowerQuery];
}

$products     = [];
$totalResults = 0;

if ($pdo) {
    $sql    = "SELECT * FROM `products` WHERE 1=1";
    $params = [];

    if (!empty($filterGrade)) {
        $sql .= " AND UPPER(`grade`) = :filter_grade";
        $params['filter_grade'] = strtoupper($filterGrade);
    }

    if (!empty($query) && !isset($gradeAliases[$lowerQuery])) {
        $sql .= " AND (`name` LIKE :q OR `grade` LIKE :q OR `scale` LIKE :q OR `brand` LIKE :q OR IFNULL(`description`,'') LIKE :q)";
        $params['q'] = "%$query%";
    }

    switch ($sort) {
        case 'price_asc':  $sql .= " ORDER BY `price` ASC, `id` DESC"; break;
        case 'price_desc': $sql .= " ORDER BY `price` DESC, `id` DESC"; break;
        case 'name_asc':   $sql .= " ORDER BY `name` ASC"; break;
        case 'newest':     $sql .= " ORDER BY `is_new_release` DESC, `id` DESC"; break;
        default:
            if (!empty($query) && !isset($gradeAliases[$lowerQuery])) {
                $sql .= " ORDER BY CASE WHEN `name` LIKE :exact_start THEN 1 WHEN `name` LIKE :exact_word THEN 2 WHEN UPPER(`grade`)=:exact_grade THEN 3 ELSE 4 END ASC, `sold_count` DESC, `id` DESC";
                $params['exact_start'] = "$query%";
                $params['exact_word']  = "%$query%";
                $params['exact_grade'] = strtoupper($query);
            } else {
                $sql .= " ORDER BY `sold_count` DESC, `id` DESC";
            }
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products     = $stmt->fetchAll();
        $totalResults = count($products);
    } catch (Exception $e) {
        error_log("Search execution failed: " . $e->getMessage());
    }
}

// Section 7 Static Banner Content based on active filter
$heroBannerTitle = '';
$heroBannerSub = '';
if (!empty($filterGrade)) {
    $gUpper = strtoupper($filterGrade);
    if ($gUpper === 'MG') {
        $heroBannerTitle = 'MASTER GRADE // 1/100 SCALE MECHANICS';
        $heroBannerSub = 'Engineered internal skeletons, high articulation, and precision Gunpla detailing.';
    } elseif ($gUpper === 'RG') {
        $heroBannerTitle = 'REAL GRADE // 1/144 SCALE COMPACT ENGINEERING';
        $heroBannerSub = 'Uncompromising miniaturization with advanced multi-jointed inner frames.';
    } elseif ($gUpper === 'PG') {
        $heroBannerTitle = 'PERFECT GRADE // 1/60 SCALE SUPREME ARTICULATION';
        $heroBannerSub = 'The pinnacle of Gunpla mechanics, full inner frames, and unmatched presence.';
    } elseif ($gUpper === 'HG') {
        $heroBannerTitle = 'HIGH GRADE // 1/144 SCALE EXTENSIVE SORTIE';
        $heroBannerSub = 'Vast mobile suit lineup spanning all Gundam eras and tactical timelines.';
    } elseif (stripos($gUpper, 'METAL') !== false) {
        $heroBannerTitle = 'METAL BUILD // DIE-CAST COLLECTOR ALLOY';
        $heroBannerSub = 'Finished composite masterpieces featuring die-cast metal armatures.';
    } else {
        $heroBannerTitle = $gUpper . ' // SPECIFICATION SORTIE';
        $heroBannerSub = 'Precision Bandai Spirits model kit units matched to active filters.';
    }
} elseif (!empty($query)) {
    $heroBannerTitle = 'TARGET ACQUISITION // "' . $query . '"';
    $heroBannerSub = 'Filtered inventory results matching requested tactical designations.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($query) ? htmlspecialchars($query) . ' — ' : ''; ?>Model Kits | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../shared/hud-design.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="search.css?v=<?php echo time(); ?>">
</head>
<body class="searchPageBody">

    <!-- NAVBAR (identical to storefront) -->
    <header class="headerContainer headerStatic">
        <div class="headerLeft">
            <a href="../index.php" class="navItem navBtnHamburger" aria-label="Menu">
                <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
            </a>
            <a href="../index.php" class="navItem navLink">HOME</a>
            <a href="search.php" class="navItem navLink" style="color: var(--brand-cyan);">PRODUCTS</a>
        </div>
        <div class="headerCenter">
            <a href="../index.php" class="navBrand" aria-label="THE HANGAR Home">
                <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
            </a>
        </div>
        <div class="headerRight">
            <a href="../cart/cart.php" class="navItem navLink navCart">CART</a>
            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                    <a href="<?php echo $adminPath; ?>" class="navItem navLink" style="color:#ffaa00;font-weight:700;">[COMMAND DECK]</a>
                <?php else: ?>
                    <span class="navItem navLink" style="color:var(--brand-cyan);cursor:default;">PILOT: <?php echo htmlspecialchars($userName); ?></span>
                <?php endif; ?>
                <a href="<?php echo $logoutPath; ?>" class="navItem navLink">LOG OUT</a>
            <?php else: ?>
                <a href="<?php echo $loginPath; ?>" class="navItem navLink">LOG IN</a>
            <?php endif; ?>
            <a href="search.php?focus=1" class="navItem navBtnSearch" aria-label="Search">
                <img src="<?php echo $buttonsPath; ?>/Search.svg" alt="Search">
            </a>
        </div>
    </header>

    <!-- SECTION HEADER STRIP - Matching SECTION 6: MODEL KITS -->
    <div class="headerContainerMK searchHeaderContainerMK">
        <div class="headerLeftMK">
            <a href="../index.php" class="spBackLink">&larr; STOREFRONT</a>
        </div>
        <div class="headerCenterMK">
            <h2>MODEL KITS</h2>
        </div>
        <div class="headerRightMK">
            <div class="sliderCounterMK">
                <span class="activeCount"><?php echo str_pad($totalResults, 2, '0', STR_PAD_LEFT); ?></span>
                <span class="divider">|</span>
                <span class="totalCount"><?php echo str_pad($totalResults, 2, '0', STR_PAD_LEFT); ?> UNITS</span>
            </div>
        </div>
    </div>

    <!-- MAIN VIEWPORT CONTAINER - Matching SECTION 6: MODEL KITS -->
    <main class="productSetContainerMK searchContainerMK">

        <?php if (!empty($heroBannerTitle)): ?>
            <!-- Static Section-7-look Cinematic Filter Banner -->
            <div class="hangarStaticBanner searchHeroBannerMK">
                <div class="staticBannerHero">
                    <div class="staticBannerContent">
                        <span class="staticBannerBadge">SORTIE CATALOGUE // ACTIVE FILTER</span>
                        <h3 class="staticBannerTitle"><?php echo htmlspecialchars($heroBannerTitle); ?></h3>
                        <p class="staticBannerSub"><?php echo htmlspecialchars($heroBannerSub); ?></p>
                    </div>
                    <div class="staticBannerAction">
                        <a href="search.php" class="filterBtnMK" style="color:#ffffff; border-color:rgba(255,255,255,0.4);">&times; RESET</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['notfound'])): ?>
            <div style="width: 100%; margin-bottom: 1.5rem; padding: 1rem 1.5rem; background: #fff8e1; border: 1px solid #ffe082; color: #b78103; font-family: 'Poppins', sans-serif; font-size: 0.88rem; font-weight: 600; display: flex; align-items: center; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>REQUISITION NOTICE: The requested Mobile Suit unit could not be located in Hangar active inventory. Please browse available units below.</span>
            </div>
        <?php endif; ?>

        <!-- Filter Options Bar -->
        <form method="GET" action="search.php" class="filterBarMK" id="spSearchForm">
            
            <!-- Search Text Input with Live Suggestion Dropdown -->
            <div class="filterSearchInputWrapMK">
                <input type="text" name="q" id="spSearchInput" value="<?php echo htmlspecialchars($query); ?>"
                       placeholder="Search model kits, grade, scale, brand..."
                       class="filterInputMK" autocomplete="off">
                <?php if (!empty($query)): ?>
                    <a href="search.php<?php echo !empty($filterGrade) ? '?grade='.urlencode($filterGrade) : ''; ?>" class="filterClearBtnMK" title="Clear">&times;</a>
                <?php endif; ?>
                <!-- In-page Live Dropdown Suggestions -->
                <div class="searchDropdownMK" id="searchDropdownMK" style="display: none;"></div>
            </div>

            <!-- Sort Shortcut Filter Buttons -->
            <a href="search.php?sort=sold_desc<?php echo !empty($query) ? '&q='.urlencode($query) : ''; ?><?php echo !empty($filterGrade) ? '&grade='.urlencode($filterGrade) : ''; ?>" 
               class="filterBtnMK <?php echo ($sort === 'sold_desc') ? 'active' : ''; ?>">popular</a>

            <a href="search.php?sort=newest<?php echo !empty($query) ? '&q='.urlencode($query) : ''; ?><?php echo !empty($filterGrade) ? '&grade='.urlencode($filterGrade) : ''; ?>" 
               class="filterBtnMK <?php echo ($sort === 'newest') ? 'active' : ''; ?>">latest</a>

            <a href="search.php?sort=price_desc<?php echo !empty($query) ? '&q='.urlencode($query) : ''; ?><?php echo !empty($filterGrade) ? '&grade='.urlencode($filterGrade) : ''; ?>" 
               class="filterBtnMK <?php echo ($sort === 'price_desc') ? 'active' : ''; ?>">Top sales</a>

            <!-- Grade Filter Dropdown -->
            <div class="selectWrapperMK">
                <select name="grade" class="filterSelectMK" onchange="this.form.submit()">
                    <option value="">Grade (All)</option>
                    <option value="rg" <?php if (strtolower($filterGrade)==='rg') echo 'selected'; ?>>Real Grade (RG)</option>
                    <option value="mg" <?php if (strtolower($filterGrade)==='mg') echo 'selected'; ?>>Master Grade (MG)</option>
                    <option value="pg" <?php if (strtolower($filterGrade)==='pg') echo 'selected'; ?>>Perfect Grade (PG)</option>
                    <option value="hg" <?php if (strtolower($filterGrade)==='hg') echo 'selected'; ?>>High Grade (HG)</option>
                    <option value="metal build" <?php if (strtolower($filterGrade)==='metal build') echo 'selected'; ?>>Metal Build</option>
                </select>
            </div>

            <!-- Sort By Dropdown -->
            <div class="selectWrapperMK">
                <select name="sort" class="filterSelectMK" onchange="this.form.submit()">
                    <option value="sold_desc"  <?php if ($sort==='sold_desc')  echo 'selected'; ?>>Sort: Most Popular</option>
                    <option value="price_asc"  <?php if ($sort==='price_asc')  echo 'selected'; ?>>Sort: Price Low to High</option>
                    <option value="price_desc" <?php if ($sort==='price_desc') echo 'selected'; ?>>Sort: Price High to Low</option>
                    <option value="name_asc"   <?php if ($sort==='name_asc')   echo 'selected'; ?>>Sort: Name A – Z</option>
                    <option value="newest"     <?php if ($sort==='newest')     echo 'selected'; ?>>Sort: New Releases</option>
                </select>
            </div>

            <button type="submit" class="filterBtnMK active">SEARCH</button>
            <?php if (!empty($query) || !empty($filterGrade) || $sort !== 'sold_desc'): ?>
                <a href="search.php" class="filterBtnMK">RESET</a>
            <?php endif; ?>
        </form>

        <!-- Quick Filter Pills -->
        <div class="quickPillsBarMK">
            <span class="quickPillLabelMK">QUICK BROWSE:</span>
            <a href="search.php?grade=MG"          class="filterBtnMK <?php echo strtoupper($filterGrade)==='MG'          ? 'active' : ''; ?>">Master Grade (MG)</a>
            <a href="search.php?grade=RG"          class="filterBtnMK <?php echo strtoupper($filterGrade)==='RG'          ? 'active' : ''; ?>">Real Grade (RG)</a>
            <a href="search.php?grade=PG"          class="filterBtnMK <?php echo strtoupper($filterGrade)==='PG'          ? 'active' : ''; ?>">Perfect Grade (PG)</a>
            <a href="search.php?grade=HG"          class="filterBtnMK <?php echo strtoupper($filterGrade)==='HG'          ? 'active' : ''; ?>">High Grade (HG)</a>
            <a href="search.php?grade=METAL+BUILD" class="filterBtnMK <?php echo strtoupper($filterGrade)==='METAL BUILD' ? 'active' : ''; ?>">Metal Build</a>
            <a href="search.php?q=Barbatos"        class="filterBtnMK <?php echo stripos($query,'Barbatos') !==false ? 'active' : ''; ?>">Barbatos</a>
            <a href="search.php?q=Sazabi"          class="filterBtnMK <?php echo stripos($query,'Sazabi')   !==false ? 'active' : ''; ?>">Sazabi</a>
            <a href="search.php?q=Freedom"         class="filterBtnMK <?php echo stripos($query,'Freedom')  !==false ? 'active' : ''; ?>">Freedom</a>
        </div>

        <!-- PRODUCT GRID (Section 6 Card Layout) -->
        <?php if (!empty($products)): ?>
            <div class="productGridMK">
                <?php foreach ($products as $p): ?>
                    <?php echo renderProductCard($p, $promotionalPath, '../'); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="emptyStateMK">
                <h3 class="emptyTitleMK">NO MODEL KITS FOUND</h3>
                <p class="emptySubMK">
                    <?php if (!empty($query)): ?>No model kits found for <strong>"<?php echo htmlspecialchars($query); ?>"</strong>.<?php else: ?>No products match the selected filters.<?php endif; ?>
                </p>
                <div class="emptyActionsMK">
                    <a href="search.php" class="filterBtnMK active">VIEW ALL MODEL KITS</a>
                    <a href="../index.php" class="filterBtnMK">BACK TO STOREFRONT</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bottom Counter Controls Summary -->
        <div class="sliderControlsMK searchFooterControlsMK">
            <div class="sliderCounterMK">
                <span class="activeCount"><?php echo str_pad($totalResults, 2, '0', STR_PAD_LEFT); ?></span>
                <span class="divider">|</span>
                <span class="totalCount"><?php echo str_pad($totalResults, 2, '0', STR_PAD_LEFT); ?> UNITS TOTAL</span>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../footer.php'; ?>

    <!-- CLIENT CONTROLLER SCRIPTS -->
    <script>
        window.HANGAR_PATHS = {
            cartPage: '../cart/cart.php',
            searchPage: 'search.php',
            apiSearch: 'api_search.php',
            productDetails: '../product-details.php',
            promotionalBase: '../../promotional',
            apiCheckout: '../cart/api_checkout.php'
        };
    </script>
    <script src="search.js?v=<?php echo time(); ?>"></script>
    <?php $_cartAssetPrefix = '../cart/'; require_once __DIR__ . '/../cart/cart_modal.php'; ?>
</body>
</html>
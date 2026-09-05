<?php
// THE HANGAR - GUND-ORDER SYSTEM
// FULL CATALOG SEARCH & TARGET RADAR DECK

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['hangar_admin_logged']);
$userRole   = $_SESSION['user_role'] ?? (!empty($_SESSION['hangar_admin_logged']) ? 'admin' : null);
$userName   = $_SESSION['username'] ?? ($_SESSION['hangar_admin_user'] ?? 'Pilot');

$buttonsPath = is_dir('buttons') ? 'buttons' : '../buttons';
$promotionalPath = is_dir('promotional') ? 'promotional' : '../promotional';
$footerPath = is_dir('footer') ? 'footer' : '../footer';
$logosPath = is_dir('logos') ? 'logos' : '../logos';

require_once __DIR__ . '/../admin/db.php';
$pdo = getDBConnection();

$query       = trim($_GET['q'] ?? '');
$filterGrade = trim($_GET['grade'] ?? '');
$sort        = trim($_GET['sort'] ?? 'sold_desc');

// Grade aliases normalization
$gradeAliases = [
    'master grade'  => 'MG',
    'mastergrade'   => 'MG',
    'real grade'    => 'RG',
    'realgrade'     => 'RG',
    'perfect grade' => 'PG',
    'perfectgrade'  => 'PG',
    'high grade'    => 'HG',
    'highgrade'     => 'HG',
    'metal build'   => 'METAL BUILD',
    'metalbuild'    => 'METAL BUILD'
];
$lowerQuery = strtolower($query);
if (isset($gradeAliases[$lowerQuery]) && empty($filterGrade)) {
    $filterGrade = $gradeAliases[$lowerQuery];
}

$products = [];
$totalResults = 0;

if ($pdo) {
    $sql = "SELECT * FROM `products` WHERE 1=1";
    $params = [];

    if (!empty($filterGrade)) {
        $sql .= " AND UPPER(`grade`) = :filter_grade";
        $params['filter_grade'] = strtoupper($filterGrade);
    }

    if (!empty($query) && !isset($gradeAliases[$lowerQuery])) {
        $sql .= " AND (
            `name` LIKE :q 
            OR `grade` LIKE :q 
            OR `scale` LIKE :q 
            OR `brand` LIKE :q 
            OR IFNULL(`description`, '') LIKE :q
        )";
        $params['q'] = "%$query%";
    }

    // Sorting
    switch ($sort) {
        case 'price_asc':
            $sql .= " ORDER BY `price` ASC, `id` DESC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY `price` DESC, `id` DESC";
            break;
        case 'name_asc':
            $sql .= " ORDER BY `name` ASC";
            break;
        case 'newest':
            $sql .= " ORDER BY `is_new_release` DESC, `id` DESC";
            break;
        case 'sold_desc':
        default:
            if (!empty($query) && !isset($gradeAliases[$lowerQuery])) {
                $sql .= " ORDER BY 
                    CASE 
                        WHEN `name` LIKE :exact_start THEN 1
                        WHEN `name` LIKE :exact_word THEN 2
                        WHEN UPPER(`grade`) = :exact_grade THEN 3
                        ELSE 4
                    END ASC,
                    `sold_count` DESC, 
                    `id` DESC";
                $params['exact_start'] = "$query%";
                $params['exact_word']  = "%$query%";
                $params['exact_grade'] = strtoupper($query);
            } else {
                $sql .= " ORDER BY `sold_count` DESC, `id` DESC";
            }
            break;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        $totalResults = count($products);
    } catch (Exception $e) {
        error_log("Search execution failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($query) ? htmlspecialchars($query) . ' - ' : ''; ?>Search Mobile Suits | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="search.css?v=<?php echo time(); ?>">
</head>
<body class="searchPageBody">

    <!-- HEADER -->
    <header class="headerContainer searchPageHeader">
        <div class="headerLeft">
            <a href="index.php" class="navItem navBtnHamburger" aria-label="Menu">
                <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
            </a>
            <a href="index.php" class="navItem navLink">LANGUAGE</a>
            <a href="search.php" class="navItem navLink" style="color: var(--brand-cyan);">PRODUCTS</a>
        </div>

        <div class="headerCenter">
            <a href="index.php" class="navBrand" aria-label="THE HANGAR Home">
                <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
            </a>
        </div>

        <div class="headerRight">
            <a href="#" class="navItem navLink navCart">CART</a>
            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                    <a href="../admin/index.php" class="navItem navLink" style="color: #ffaa00; font-weight: 700;">[COMMAND DECK]</a>
                <?php else: ?>
                    <span class="navItem navLink" style="color: #3FC4E1; cursor: default;">PILOT: <?php echo htmlspecialchars($userName); ?></span>
                <?php endif; ?>
                <a href="../login/logout.php" class="navItem navLink" title="Sign out of G.O.S">LOG OUT</a>
            <?php else: ?>
                <a href="../login/" class="navItem navLink">LOG IN</a>
            <?php endif; ?>
            <button class="navItem navBtnSearch" type="button" aria-label="Open Search Radar">
                <img src="<?php echo $buttonsPath; ?>/Search.svg" alt="Search">
            </button>
        </div>
    </header>

    <!-- SECTION HEADER STRIP -->
    <div class="pdSectionHeader searchSectionHeader">
        <div class="pdHeaderLeft">
            <a href="index.php" class="backStorefrontLink">
                &larr; RETURN TO STOREFRONT
            </a>
        </div>
        <div class="pdHeaderCenter">
            <h2>TARGET ACQUISITION RADAR</h2>
        </div>
        <div class="pdHeaderRight">
            <div class="systemBadgeText">
                GUND-ORDER <span>// SEARCH</span>
            </div>
        </div>
    </div>

    <!-- MAIN SEARCH CONTENT AREA -->
    <main class="searchPageMain">
        <!-- Breadcrumbs & Status Banner -->
        <div class="searchTopBanner">
            <div class="searchBreadcrumb">
                <a href="index.php">THE HANGAR</a> &gt; <span>TARGET DATABASE</span> 
                <?php if (!empty($query)): ?>
                    &gt; <span class="activeCrumb">SEARCH: "<?php echo htmlspecialchars($query); ?>"</span>
                <?php endif; ?>
            </div>
            <div class="searchCounterBadge">
                <span class="radarDot"></span>
                <strong><?php echo $totalResults; ?></strong> TARGET<?php echo $totalResults === 1 ? '' : 'S'; ?> ACQUIRED
            </div>
        </div>

        <!-- In-Page Search & Filter Control Bar -->
        <div class="searchControlDeck">
            <form method="GET" action="search.php" class="searchDeckForm">
                <div class="searchDeckInputWrap">
                    <svg class="searchDeckSvg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input 
                        type="text" 
                        name="q" 
                        value="<?php echo htmlspecialchars($query); ?>" 
                        placeholder="Search callsign, mobile suit, grade (MG, RG, PG), weapons..." 
                        class="searchDeckInput"
                    >
                    <?php if (!empty($query)): ?>
                        <a href="search.php<?php echo !empty($filterGrade) ? '?grade=' . urlencode($filterGrade) : ''; ?>" class="searchDeckClear" title="Clear keyword">&times;</a>
                    <?php endif; ?>
                </div>

                <div class="searchDeckSelects">
                    <div class="selectGroup">
                        <label for="gradeSelect">GRADE</label>
                        <select name="grade" id="gradeSelect" class="searchDeckSelect">
                            <option value="">ALL GRADES</option>
                            <option value="MG" <?php if (strtoupper($filterGrade) === 'MG') echo 'selected'; ?>>MASTER GRADE (MG 1/100)</option>
                            <option value="RG" <?php if (strtoupper($filterGrade) === 'RG') echo 'selected'; ?>>REAL GRADE (RG 1/144)</option>
                            <option value="PG" <?php if (strtoupper($filterGrade) === 'PG') echo 'selected'; ?>>PERFECT GRADE (PG 1/60)</option>
                            <option value="HG" <?php if (strtoupper($filterGrade) === 'HG') echo 'selected'; ?>>HIGH GRADE (HG 1/144)</option>
                            <option value="METAL BUILD" <?php if (strtoupper($filterGrade) === 'METAL BUILD') echo 'selected'; ?>>METAL BUILD</option>
                        </select>
                    </div>

                    <div class="selectGroup">
                        <label for="sortSelect">SORT BY</label>
                        <select name="sort" id="sortSelect" class="searchDeckSelect">
                            <option value="sold_desc" <?php if ($sort === 'sold_desc') echo 'selected'; ?>>POPULARITY (MOST SOLD)</option>
                            <option value="price_asc" <?php if ($sort === 'price_asc') echo 'selected'; ?>>PRICE: LOW TO HIGH</option>
                            <option value="price_desc" <?php if ($sort === 'price_desc') echo 'selected'; ?>>PRICE: HIGH TO LOW</option>
                            <option value="name_asc" <?php if ($sort === 'name_asc') echo 'selected'; ?>>CALLSIGN: A TO Z</option>
                            <option value="newest" <?php if ($sort === 'newest') echo 'selected'; ?>>NEW RELEASES</option>
                        </select>
                    </div>

                    <button type="submit" class="searchDeckSubmitBtn">UPDATE SCAN</button>

                    <?php if (!empty($query) || !empty($filterGrade) || $sort !== 'sold_desc'): ?>
                        <a href="search.php" class="searchDeckResetBtn">RESET</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Quick Pill Tags for Fast Browsing -->
            <div class="searchDeckPills">
                <span class="pillsLabel">QUICK TARGETS:</span>
                <a href="search.php?q=Barbatos" class="searchPill <?php echo stripos($query, 'Barbatos') !== false ? 'active' : ''; ?>">Barbatos</a>
                <a href="search.php?q=Sazabi" class="searchPill <?php echo stripos($query, 'Sazabi') !== false ? 'active' : ''; ?>">Sazabi</a>
                <a href="search.php?q=Nu+Gundam" class="searchPill <?php echo stripos($query, 'Nu Gundam') !== false ? 'active' : ''; ?>">Nu Gundam</a>
                <a href="search.php?q=Freedom" class="searchPill <?php echo stripos($query, 'Freedom') !== false ? 'active' : ''; ?>">Freedom 2.0</a>
                <a href="search.php?q=Vidar" class="searchPill <?php echo stripos($query, 'Vidar') !== false ? 'active' : ''; ?>">Vidar</a>
                <a href="search.php?grade=MG" class="searchPill <?php echo strtoupper($filterGrade) === 'MG' ? 'active' : ''; ?>">Master Grade (MG)</a>
                <a href="search.php?grade=RG" class="searchPill <?php echo strtoupper($filterGrade) === 'RG' ? 'active' : ''; ?>">Real Grade (RG)</a>
                <a href="search.php?grade=PG" class="searchPill <?php echo strtoupper($filterGrade) === 'PG' ? 'active' : ''; ?>">Perfect Grade (PG)</a>
            </div>
        </div>

        <!-- Products Results Grid -->
        <section class="searchResultsSection">
            <?php if (!empty($products)): ?>
                <div class="searchResultsGrid">
                    <?php foreach ($products as $p): ?>
                        <a href="product-details.php?id=<?php echo $p['id']; ?>" class="productCard searchProductCard">
                            <div class="productImgContainer">
                                <img src="<?php echo $promotionalPath; ?>/<?php echo htmlspecialchars($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" onerror="this.src='<?php echo $promotionalPath; ?>/Asset 8.png'">
                            </div>
                            <div class="productDetails">
                                <div class="productBadges">
                                    <span class="productBadge gradeBadge-<?php echo htmlspecialchars(strtolower($p['grade'])); ?>"><?php echo htmlspecialchars($p['grade']); ?></span>
                                    <span class="productBadge"><?php echo htmlspecialchars($p['scale']); ?></span>
                                    <span class="productBadge"><?php echo htmlspecialchars($p['stock_status']); ?></span>
                                </div>
                                <h3 class="productTitle"><?php echo htmlspecialchars($p['name']); ?></h3>
                                <div class="productFooter">
                                    <span class="productPrice">₱ <?php echo number_format($p['price'], 2); ?></span>
                                    <span class="productSold">SOLD <?php echo number_format($p['sold_count']); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Results State -->
                <div class="searchEmptyState">
                    <div class="emptyRadarReticle">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="22" y1="12" x2="18" y2="12"></line>
                            <line x1="6" y1="12" x2="2" y2="12"></line>
                            <line x1="12" y1="6" x2="12" y2="2"></line>
                            <line x1="12" y1="22" x2="12" y2="18"></line>
                            <circle cx="12" cy="12" r="4"></circle>
                        </svg>
                    </div>
                    <h3 class="emptyTitle">NO TARGETS ACQUIRED IN G.O.S DATABASE</h3>
                    <p class="emptySub">
                        No mobile suits matched "<strong><?php echo htmlspecialchars($query); ?></strong>"
                        <?php if (!empty($filterGrade)): ?>
                            with grade filter "<strong><?php echo htmlspecialchars($filterGrade); ?></strong>"
                        <?php endif; ?>.
                    </p>
                    <div class="emptySuggestions">
                        <span>SUGGESTED ACTIONS:</span>
                        <ul>
                            <li>Check the spelling of the mobile suit name or model number</li>
                            <li>Try broader search terms like <a href="search.php?q=Gundam">Gundam</a>, <a href="search.php?q=Sazabi">Sazabi</a>, or <a href="search.php?q=Bandai">Bandai</a></li>
                            <li>Browse by grade: <a href="search.php?grade=MG">Master Grade</a>, <a href="search.php?grade=RG">Real Grade</a>, or <a href="search.php?grade=PG">Perfect Grade</a></li>
                        </ul>
                    </div>
                    <div class="emptyActionBtns">
                        <a href="search.php" class="emptyBtnSecondary">VIEW ALL PRODUCTS</a>
                        <a href="index.php" class="emptyBtnPrimary">RETURN TO STOREFRONT</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="footerFT">
        <div class="footerContainerFT">
            <div class="footerTopFT">
                <div class="footerRow1FT">
                    <div class="footerColFT">
                        <h4 class="footerHeadingFT">CUSTOMER SERVICE</h4>
                        <ul class="footerListFT">
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">Shipping & Logistics</a></li>
                            <li><a href="#">Returns & Replacements</a></li>
                            <li><a href="#">Order Tracking</a></li>
                        </ul>
                    </div>
                    <div class="footerColFT">
                        <h4 class="footerHeadingFT">ABOUT THE HANGAR</h4>
                        <ul class="footerListFT">
                            <li><a href="#">Our Story</a></li>
                            <li><a href="#">Official Bandai Partner</a></li>
                            <li><a href="#">Authenticity Guarantee</a></li>
                            <li><a href="#">Hangar Pilot Program</a></li>
                        </ul>
                    </div>
                    <div class="footerColFT">
                        <h4 class="footerHeadingFT">COMMUNITY</h4>
                        <ul class="footerListFT">
                            <li><a href="#">Gunpla Builders Guild</a></li>
                            <li><a href="#">Event Schedule</a></li>
                            <li><a href="#">Custom Paint Gallery</a></li>
                            <li><a href="#">Community Forum</a></li>
                        </ul>
                    </div>
                    <div class="footerColFT">
                        <h4 class="footerHeadingFT">LEGAL & PRIVACY</h4>
                        <ul class="footerListFT">
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Terms of Service</a></li>
                            <li><a href="#">Intellectual Property</a></li>
                            <li><a href="#">Security Protocol</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="footerDisclaimerFT">
                <p>The image is for illustrative purposes only. The actual product may differ slightly from the image.</p>
            </div>

            <div class="footerBottomFT">
                <div class="copyrightFT">
                    &copy;2026, THE HANGAR, LLC<br>
                    ALL COPYRIGHTS RESERVE
                </div>
                <div class="officialLogosFT">
                    <img src="<?php echo $footerPath; ?>/bandaiTamiya/images.jpg" alt="BANDAI NAMCO" class="partnerLogoBandaiNamco">
                    <img src="<?php echo $footerPath; ?>/bandaiTamiya/Logo_Bandai.svg.webp" alt="BANDAI SPIRITS" class="partnerLogoBandaiSpirits">
                    <img src="<?php echo $footerPath; ?>/bandaiTamiya/bandai.webp" alt="BANDAI" class="partnerLogoBandai">
                </div>
            </div>
        </div>
    </footer>

    <!-- SHARED SEARCH HUD MODAL OVERLAY (Self-contained: includes search.css & search.js) -->
    <?php require_once __DIR__ . '/search_modal.php'; ?>
</body>
</html>

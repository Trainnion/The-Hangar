<?php
require_once __DIR__ . '/../shared/bootstrap.php';
extract(hangarBootstrap());

require_once __DIR__ . '/db_helper.php';
$sfData = getStorefrontData();

$sec1Slides = !empty($sfData['section1Slides']) ? $sfData['section1Slides'] : [];
$sec2Slides = !empty($sfData['section2Slides']) ? $sfData['section2Slides'] : [];
$sec7Slides = !empty($sfData['section7Slides']) ? $sfData['section7Slides'] : [];
// Section 7 FEATURED card content (admin-editable via Admin > FEATURED; falls back to defaults)
$featuredContent = getFeaturedContent(getDBConnection());
$nrProducts = !empty($sfData['newReleases']) ? $sfData['newReleases'] : [];
$bsProducts = !empty($sfData['bestSellers']) ? $sfData['bestSellers'] : [];
$mkProducts = !empty($sfData['modelKits']) ? $sfData['modelKits'] : [];
$ctTiles    = !empty($sfData['categoryTiles']) ? $sfData['categoryTiles'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THE HANGAR</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
<?php include __DIR__ . '/../shared/menu.php'; ?>

    <!-- SECTION 0 & 1 -->
    <div class="promotionalContainer">
        <!-- SECTION 0: HEADER -->
        <header class="headerContainer">
            <div class="headerLeft">
                <a href="#" class="navItem navBtnHamburger" aria-label="Menu">
                    <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
                </a>
                <a href="index.php" class="navItem navLink">HOME</a>
                <a href="search/search.php" class="navItem navLink">PRODUCTS</a>
            </div>

            <div class="headerCenter">
                <a href="#" class="navBrand" aria-label="THE HANGAR Home">
                    <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
                </a>
            </div>

            <div class="headerRight">
                <a href="cart/cart.php" class="navItem navLink navCart">CART</a>
                <a href="orders/orders.php" class="navItem navLink">MY ORDERS</a>
                <?php if ($isLoggedIn): ?>
                    <?php if ($userRole === 'admin'): ?>
                        <a href="<?php echo $adminPath; ?>" class="navItem navLink" style="color: #ffaa00; font-weight: 700;">[COMMAND DECK]</a>
                    <?php else: ?>
                        <a href="<?php echo $profilePath; ?>" class="navItem navLink" style="color: #3FC4E1;">PILOT: <?php echo htmlspecialchars($userName); ?></a>
                    <?php endif; ?>
                    <a href="<?php echo $logoutPath; ?>" class="navItem navLink" title="Sign out of G.O.S">LOG OUT</a>
                <?php else: ?>
                    <a href="<?php echo $loginPath; ?>" class="navItem navLink">LOG IN</a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main Prominent Heading -->
        <h1 class="heroTopTitle">NEW RELEASE</h1>

        <!-- SECTION 1: SLIDER -->
        <div class="promotionalWrapper">
            <div class="slider">
                <?php 
                $s1Count = count($sec1Slides);
                foreach ($sec1Slides as $i => $s): 
                    $slideId = 'slide' . ($i + 1);
                    $isActive = ($i === 0) ? 'activeSlide' : '';
                    $targetUrl = !empty($s['product_id']) ? "product-details.php?id=" . $s['product_id'] : ($s['custom_url'] ?: '#');
                ?>
                    <div class="slide <?php echo $isActive; ?>" id="<?php echo $slideId; ?>">
                        <img src="<?php echo htmlspecialchars(assetUrl($s['image_url'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>">
                        <div class="slideContent">
                            <?php if (!empty($s['badge'])): ?>
                                <span class="heroGradeTag"><?php echo htmlspecialchars($s['badge']); ?></span>
                            <?php endif; ?>
                            <h2><?php echo htmlspecialchars($s['title']); ?></h2>
                            <?php if (!empty($s['subtitle'])): ?>
                                <p class="heroWeaponSub"><?php echo htmlspecialchars($s['subtitle']); ?></p>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars($targetUrl); ?>" class="orderBtn"><?php echo htmlspecialchars($s['button_text'] ?: 'ORDER NOW!'); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="heroSliderControls">
                <div class="heroCounterSec1">
                    <span id="heroActiveNumSec1" class="activeNum">01</span>
                    <span class="divider">|</span>
                    <span id="heroTotalNumSec1" class="totalNum"><?php echo str_pad($s1Count ?: 4, 2, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="sliderNav">
                    <?php for ($i = 0; $i < $s1Count; $i++): ?>
                        <button data-target="slide<?php echo ($i + 1); ?>" class="<?php echo ($i === 0) ? 'active' : ''; ?>" aria-label="Go to slide <?php echo ($i + 1); ?>"></button>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2 -->
     
  <!-- SECTION 2: HERO CAROUSEL -->
<!-- SECTION 2: HERO CAROUSEL (FULLSCREEN) -->
<div class="heroCarouselSec2">
    <div class="heroTrackSec2" id="heroTrackSec2">
        <?php 
        $s2Count = count($sec2Slides);
        foreach ($sec2Slides as $i => $s): 
            $targetUrl = !empty($s['product_id']) ? "product-details.php?id=" . $s['product_id'] : ($s['custom_url'] ?: '#');
            $slideNum = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            $totalNum = str_pad($s2Count, 2, '0', STR_PAD_LEFT);
        ?>
            <!-- Slide <?php echo $i + 1; ?> -->
            <div class="heroSlideSec2">
                <div class="heroInfoPanelSec2" style="background-image: url('<?php echo htmlspecialchars(assetUrl($s['image_url'], $promotionalPath)); ?>');">
                    <div class="heroOverlaySec2"></div>
                    <div class="heroContentSec2">
                        <span class="badgeReprintSec2"><?php echo htmlspecialchars($s['badge'] ?: 'REPRINT RUN!'); ?></span>
                        <h2 class="heroTitleSec2"><?php echo htmlspecialchars($s['subtitle'] ?: 'MASTER GRADE'); ?></h2>
                        <h3 class="heroSubtitleSec2"><?php echo $s['title']; ?></h3>
                        <?php if (!empty($s['quote'])): ?>
                            <blockquote class="heroQuoteSec2">
                                “<?php echo htmlspecialchars(trim($s['quote'], '“”"')); ?>”
                            </blockquote>
                        <?php endif; ?>
                        <?php if (!empty($s['author'])): ?>
                            <span class="heroAuthorSec2"><?php echo htmlspecialchars($s['author']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($s['product_id']) || !empty($s['custom_url'])): ?>
                            <a href="<?php echo htmlspecialchars($targetUrl); ?>" class="orderBtn" style="margin-top: 1.5rem; display: inline-block;"><?php echo htmlspecialchars($s['button_text'] ?: 'ORDER NOW!'); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="heroCounterContainerSec2">
                        <div class="heroCounterSec2">
                            <span class="activeNumSec2"><?php echo $slideNum; ?></span>
                            <span class="dividerSec2">|</span>
                            <span class="totalNumSec2"><?php echo $totalNum; ?></span>
                        </div>
                        <div class="heroTimerBarSec2">
                            <div class="heroTimerFillSec2"></div>
                        </div>
                    </div>
                </div>
                <div class="heroImagePanelSec2">
                    <img src="<?php echo htmlspecialchars(assetUrl($s['image_url'], $promotionalPath)); ?>" alt="<?php echo strip_tags($s['title']); ?>">
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Progress Indicator Bar -->
    <div class="heroProgressBarSec2">
        <div class="heroProgressTrackSec2">
            <?php for ($i = 0; $i < $s2Count; $i++): ?>
                <span class="dashSec2 <?php echo ($i === 0) ? 'activeDashSec2' : ''; ?>" data-slide="<?php echo $i; ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- SECTION 2 -->

<!-- SECTION 3 -->
<div class="newReleaseContainerNR">
    <div class="headerContainerNR">
        <div class="headerLeftNR"></div>
        <div class="headerCenterNR">
            <h2>NEW RELEASE</h2> 
        </div>
        <div class="headerRightNR"></div>
    </div>

    <!-- Centered Viewport Container -->
    <div class="productSetContainer">
        <!-- Horizontal Sliding Track -->
        <div class="productTrackNR" id="productTrackNR">
            <?php 
            $nrTotal = count($nrProducts);
            foreach ($nrProducts as $p): 
            ?>
                <a href="product-details.php?id=<?php echo $p['id']; ?>" class="productCard">
                    <div class="productImgContainer">
                        <img src="<?php echo htmlspecialchars(assetUrl($p['image_url'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" onerror="this.src='<?php echo $promotionalPath; ?>/Asset 8.png'">
                    </div>
                    <div class="productDetails">
                        <div class="productBadges">
                            <span class="productBadge"><?php echo htmlspecialchars($p['brand']); ?></span>
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

        <!-- Counter & Navigation Controls -->
        <div class="sliderControlsNR">
            <div class="sliderCounterNR">
                <span id="activeCountNR" class="activeCount">04</span>
                <span class="divider">|</span>
                <span id="totalCountNR" class="totalCount"><?php echo str_pad($nrTotal ?: 4, 2, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="sliderBtnsNR">
                <button class="sliderBtnNR" id="prevBtnNR" type="button" aria-label="Previous Products">&lt;</button>
                <button class="sliderBtnNR" id="nextBtnNR" type="button" aria-label="Next Products">&gt;</button>
            </div>
        </div>
    </div>
</div>


<!-- SECTION 4 -->
<!-- SECTION 4 -->
<div class="categoryContainer">
    <div class="headerCatContainer">
        <div class="headerCatLeft"></div>
        <div class="headerCatCenter">
            <h2>CATEGORIES</h2> 
        </div>
        <div class="headerCatRight"></div>
    </div>

    <!-- Centered Viewport Container -->
    <div class="categorySetContainer">
        <div class="categoryGrid">
            <?php if (!empty($ctTiles)): ?>
                <?php foreach ($ctTiles as $ti => $ct): ?>
                    <a href="search/search.php?grade=<?php echo urlencode($ct['grade_key']); ?>" class="catBox<?php echo ($ti === 0) ? ' catBoxFull' : ''; ?>" title="View <?php echo htmlspecialchars($ct['title']); ?>">
                        <span class="catTitleText"><?php echo htmlspecialchars($ct['title']); ?></span>
                        <img src="<?php echo htmlspecialchars(assetUrl($ct['image_url'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($ct['title']); ?>" onerror="this.src='<?php echo $promotionalPath; ?>/Asset 8.png'">
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Full Width Container: METALBUILD -->
                <a href="search/search.php?grade=METAL BUILD" class="catBox catBoxFull">
                    <span class="catTitleText">METALBUILD</span>
                    <img src="<?php echo $promotionalPath; ?>/rWKgWU4OCaLNzEFg20z6P7AroZR9iKXl66hhP6DL.jpg" alt="Metalbuild">
                </a>

                <!-- Row 1 Left: PERFECT GRADE -->
                <a href="search/search.php?grade=PG" class="catBox">
                    <span class="catTitleText">PERFECT GRADE</span>
                    <img src="<?php echo $promotionalPath; ?>/PG NU GUNDAM.webp" alt="Perfect Grade">
                </a>

                <!-- Row 1 Right: MASTER GRADE -->
                <a href="search/search.php?grade=MG" class="catBox">
                    <span class="catTitleText">MASTER GRADE</span>
                    <img src="<?php echo $promotionalPath; ?>/BAS5055457-6.jpg" alt="Master Grade">
                </a>

                <!-- Row 2 Left: REAL GRADE -->
                <a href="search/search.php?grade=RG" class="catBox">
                    <span class="catTitleText">REAL GRADE</span>
                    <img src="<?php echo $promotionalPath; ?>/cut-out rg.png" alt="Real Grade">
                </a>

                <!-- Row 2 Right: HIGH GRADE -->
                <a href="search/search.php?grade=HG" class="catBox">
                    <span class="catTitleText">HIGH GRADE</span>
                    <img src="<?php echo $promotionalPath; ?>/hg.webp" alt="High Grade">
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SECTION 5: BEST SELLERS -->
<!-- SECTION 5: BEST SELLERS -->
<div class="bestSellersContainerBS">
    <div class="headerContainerBS">
        <div class="headerLeftBS"></div>
        <div class="headerCenterBS">
            <h2>BEST SELLERS</h2> 
        </div>
        <div class="headerRightBS"></div>
    </div>

    <!-- Centered Viewport Container -->
    <div class="productSetContainerBS">
        <div class="bestSellersGridBS">
            <?php foreach ($bsProducts as $p): ?>
                <a href="product-details.php?id=<?php echo $p['id']; ?>" class="productCard">
                    <div class="productImgContainer">
                        <img src="<?php echo htmlspecialchars(assetUrl($p['image_url'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" onerror="this.src='<?php echo $promotionalPath; ?>/Asset 8.png'">
                    </div>
                    <div class="productDetails">
                        <div class="productBadges">
                            <span class="productBadge"><?php echo htmlspecialchars($p['brand']); ?></span>
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
    </div>
</div>

<!-- SECTION 5: BEST SELLERS -->


<!-- SECTION 6: MODEL KITS -->
<div class="modelKitsContainerMK">
    <div class="headerContainerMK">
        <div class="headerLeftMK"></div>
        <div class="headerCenterMK">
            <h2>MODEL KITS</h2> 
        </div>
        <div class="headerRightMK"></div>
    </div>

    <!-- Centered Viewport Container -->
    <div class="productSetContainerMK">
        
        <!-- Filter Options Bar -->
        <div class="filterBarMK">
            <button class="filterBtnMK active">popular</button>
            <button class="filterBtnMK">latest</button>
            <button class="filterBtnMK">Top sales</button>
            <div class="selectWrapperMK">
                <select class="filterSelectMK">
                    <option value="">Grade</option>
                    <option value="rg">Real Grade (RG)</option>
                    <option value="mg">Master Grade (MG)</option>
                    <option value="pg">Perfect Grade (PG)</option>
                    <option value="hg">High Grade (HG)</option>
                </select>
            </div>
            <a href="search/search.php" class="filterBtnMK" style="text-decoration:none; font-weight:700; margin-left:auto;">SHOW ALL &rarr;</a>
        </div>

        <!-- Horizontal Sliding Track -->
        <div class="productTrackMK" id="productTrackMK">
            <?php 
            $mkTotal = count($mkProducts);
            foreach ($mkProducts as $p): 
            ?>
                <a href="product-details.php?id=<?php echo $p['id']; ?>" class="productCard">
                    <div class="productImgContainer">
                        <img src="<?php echo htmlspecialchars(assetUrl($p['image_url'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" onerror="this.src='<?php echo $promotionalPath; ?>/Asset 8.png'">
                    </div>
                    <div class="productDetails">
                        <div class="productBadges">
                            <span class="productBadge"><?php echo htmlspecialchars($p['brand']); ?></span>
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

        <!-- Counter & Navigation Controls -->
        <div class="sliderControlsMK">
            <div class="sliderCounterMK">
                <span id="activeCountMK" class="activeCount">04</span>
                <span class="divider">|</span>
                <span id="totalCountMK" class="totalCount"><?php echo str_pad($mkTotal ?: 4, 2, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="sliderBtnsMK">
                <button class="sliderBtnMK" id="prevBtnMK" type="button" aria-label="Previous Products">&lt;</button>
                <button class="sliderBtnMK" id="nextBtnMK" type="button" aria-label="Next Products">&gt;</button>
            </div>
        </div>

    </div>
</div>
<!-- SECTION 6: MODEL KITS -->

<!-- SECTION 7: FEATURED -->
<div class="featuredContainerFT">
    <!-- Top Hero Banner Slider -->
    <div class="topBannerSliderFT" id="featuredTopSlider">
        <div class="topBannerTrackFT" id="featuredTopTrack">
            <?php foreach ($sec7Slides as $s): 
                $targetUrl = !empty($s['product_id']) ? "product-details.php?id=" . $s['product_id'] : ($s['custom_url'] ?: '');
            ?>
                <div class="topBannerSlideFT">
                    <?php if (!empty($targetUrl)): ?>
                        <a href="<?php echo htmlspecialchars($targetUrl); ?>" style="display: block; width: 100%; height: 100%;">
                            <img src="<?php echo htmlspecialchars(assetUrl($s['image_url'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" onerror="this.src='<?php echo $promotionalPath; ?>/SEED_kv_main001(2012Mecha)_base_withLogo.png'">
                        </a>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars(assetUrl($s['image_url'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" onerror="this.src='<?php echo $promotionalPath; ?>/SEED_kv_main001(2012Mecha)_base_withLogo.png'">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Timer Bar at bottom -->
        <div class="featuredTimerBarFT">
            <div class="featuredTimerFillFT" id="featuredTimerFill"></div>
        </div>
    </div>

    <!-- Header -->
    <div class="headerContainerFT">
        <h2 class="featuredHeadlineFT">F E A T U R E D</h2>
    </div>

    <!-- Main Grid Content (card content managed in Admin > FEATURED) -->
    <div class="featuredGridFT">
        <!-- Left Column: Main Feature -->
        <div class="featuredCardLeftFT">
            <div class="featuredImgContainerFT">
                <img src="<?php echo htmlspecialchars(assetUrl($featuredContent['main']['image'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($featuredContent['main']['title']); ?>">
            </div>
            <div class="featuredDetailsFT">
                <h3 class="featuredTitleFT"><?php echo htmlspecialchars($featuredContent['main']['title']); ?></h3>
                <p class="featuredSubFT"><?php echo htmlspecialchars($featuredContent['main']['subtitle']); ?></p>
                <span class="featuredStatusFT statusAvailableFT"><?php echo htmlspecialchars($featuredContent['main']['status_text']); ?></span>
            </div>
        </div>

        <!-- Right Column: Stacked Banners -->
        <div class="featuredRightColFT">
            <!-- Top Stacked Banner -->
            <div class="featuredBannerRowFT bannerRogueOrbit">
                <div class="bannerImgBoxFT">
                    <img src="<?php echo htmlspecialchars(assetUrl($featuredContent['ro']['image'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($featuredContent['ro']['title']); ?>">
                </div>
                <div class="bannerTextBoxFT textRightFT">
                    <div class="bannerLogoWrapperFT">
                        <?php if ($featuredContent['ro']['logo'] !== ''): ?>
                        <img src="<?php echo htmlspecialchars(assetUrl($featuredContent['ro']['logo'], $logosPath)); ?>" alt="<?php echo htmlspecialchars($featuredContent['ro']['title']); ?>" class="bannerLogoSvgFT">
                        <?php endif; ?>
                    </div>
                    <span class="featuredStatusFT statusSpacedFT"><?php echo htmlspecialchars($featuredContent['ro']['status_text']); ?></span>
                </div>
            </div>

            <!-- Bottom Stacked Banner -->
            <div class="featuredBannerRowFT bannerXarxZero">
                <div class="bannerTextBoxFT textLeftFT">
                    <div class="bannerLogoWrapperFT">
                        <?php if ($featuredContent['xz']['logo'] !== ''): ?>
                        <img src="<?php echo htmlspecialchars(assetUrl($featuredContent['xz']['logo'], $logosPath)); ?>" alt="<?php echo htmlspecialchars($featuredContent['xz']['title']); ?>" class="bannerLogoSvgFT">
                        <?php endif; ?>
                    </div>
                    <span class="featuredStatusFT statusSpacedFT"><?php echo htmlspecialchars($featuredContent['xz']['status_text']); ?></span>
                </div>
                <div class="bannerImgBoxFT">
                    <img src="<?php echo htmlspecialchars(assetUrl($featuredContent['xz']['image'], $promotionalPath)); ?>" alt="<?php echo htmlspecialchars($featuredContent['xz']['title']); ?>">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- FOOTER -->
<?php require_once __DIR__ . '/footer.php'; ?>


<!-- G.O.S SEARCH & CART HUD OVERLAYS (Self-contained modular components) -->
<script>window.HANGAR_PATHS = { cartPage: 'cart/cart.php', searchPage: 'search/search.php', apiSearch: 'search/api_search.php', productDetails: 'product-details.php', promotionalBase: '../promotional', apiCheckout: 'cart/api_checkout.php' };</script>
<?php $_cartAssetPrefix   = 'cart/';   require_once __DIR__ . '/cart/cart_modal.php'; ?>

<!-- Script tags -->
<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>


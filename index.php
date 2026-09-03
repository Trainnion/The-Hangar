<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THE HANGAR</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- SECTION 0 & 1 -->
    <div class="promotionalContainer">
        <!-- SECTION 0: HEADER -->
        <div class="headerContainer">
            <div class="headerLeft">
                <a href="#" class="navBtnHamburger" aria-label="Menu">
                    <img src="buttons/hamberger menu icon.svg" alt="">
                </a>
                <a href="#" class="navBtn">LANGUAGE</a>
                <a href="#" class="navBtn">PRODUCTS</a>
            </div>

            <div class="headerCenter">
                <img src="promotional/Asset 8.png" alt="THE HANGAR Logo">
            </div>

            <div class="headerRight">
                <button class="iconBtn">SEARCH</button>
                <a href="#" class="actionBtn">CART (0)</a>
            </div>
        </div>

        <!-- SECTION 1: SLIDER -->
        <div class="promotionalWrapper">
            <div class="slider">
                <div class="slide" id="slide1">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="Slide 1">
                    <div class="slideContent">
                        <h2>FEATURED RELEASE</h2>
                        <p>Explore the latest additions to the hangar collection.</p>
                        <a href="#" class="orderBtn">ORDER NOW</a>
                    </div>
                </div>

                <div class="slide" id="slide2">
                    <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="Slide 2">
                    <div class="slideContent">
                        <h2>LIMITED EDITION</h2>
                        <p>Exclusive models available for a limited time.</p>
                        <a href="#" class="orderBtn">ORDER NOW</a>
                    </div>
                </div>

                <div class="slide" id="slide3">
                    <img src="promotional/download (1).jpg" alt="Slide 3">
                    <div class="slideContent">
                        <h2>NEW ARRIVALS</h2>
                        <p>Discover fresh stock straight from the factory.</p>
                        <a href="#" class="orderBtn">ORDER NOW</a>
                    </div>
                </div>
            </div>
            
            <div class="sliderNav">
                <button data-target="slide1" aria-label="Go to slide 1"></button>
                <button data-target="slide2" aria-label="Go to slide 2"></button>
                <button data-target="slide3" aria-label="Go to slide 3"></button>
            </div>
        </div>
    </div>

    <!-- SECTION 2 -->
     
  <!-- SECTION 2: HERO CAROUSEL -->
<!-- SECTION 2: HERO CAROUSEL (FULLSCREEN) -->
<div class="heroCarouselSec2">
    <div class="heroTrackSec2" id="heroTrackSec2">
        
        <!-- Slide Item -->
        <div class="heroSlideSec2">
            <!-- Left Info Panel with Blurred Background -->
            <div class="heroInfoPanelSec2" style="background-image: url('promotional/578079302143824449.jpg');">
                <div class="heroOverlaySec2"></div>
                <div class="heroContentSec2">
                    <span class="badgeReprintSec2">REPRINT RUN!</span>
                    <h2 class="heroTitleSec2">MASTER GRADE</h2>
                    <h3 class="heroSubtitleSec2">FA-78 Full Armor Gundam<br>“Ver. Ka” (Thunderbolt Ver.)</h3>
                    <blockquote class="heroQuoteSec2">
                        “This is a heavily armored highly maneuverable Mobile Suit and unfortunately, this is exactly what the Living Dead Division is least equipped to handle”
                    </blockquote>
                    <span class="heroAuthorSec2">-Murroughs</span>
                </div>

                <!-- Slide Counter (Bottom-Right of Left Glass Panel) -->
                <div class="heroCounterSec2">
                    <span class="activeNumSec2" id="heroActiveNum">04</span>
                    <span class="dividerSec2">|</span>
                    <span class="totalNumSec2">07</span>
                </div>
            </div>

            <!-- Right Image Banner -->
            <div class="heroImagePanelSec2">
                <img src="promotional/578079302143824449.jpg" alt="FA-78 Full Armor Gundam Ver. Ka">
            </div>
        </div>

    </div>

    <!-- Bottom Progress Indicator Bar -->
    <div class="heroProgressBarSec2">
        <div class="heroProgressTrackSec2">
            <span class="dashSec2"></span>
            <span class="dashSec2"></span>
            <span class="dashSec2"></span>
            <span class="dashSec2 activeDashSec2"></span>
            <span class="dashSec2"></span>
            <span class="dashSec2"></span>
            <span class="dashSec2"></span>
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
            
            <a href="product-details.php?id=1" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="MODEL FX-01">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MODEL FX-01</h3>
                    <div class="productFooter">
                        <span class="productPrice">$149.00</span>
                        <span class="productSold">SOLD 10K+</span>
                    </div>
                </div>
            </a>

            <a href="product-details.php?id=2" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="MODEL FX-02">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MODEL FX-02</h3>
                    <div class="productFooter">
                        <span class="productPrice">$189.00</span>
                        <span class="productSold">SOLD 9080</span>
                    </div>
                </div>
            </a>

            <a href="product-details.php?id=3" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/download (1).jpg" alt="MODEL FX-03">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MODEL FX-03</h3>
                    <div class="productFooter">
                        <span class="productPrice">$129.00</span>
                        <span class="productSold">SOLD 8041</span>
                    </div>
                </div>
            </a>

            <a href="product-details.php?id=4" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="MODEL FX-04">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MODEL FX-04</h3>
                    <div class="productFooter">
                        <span class="productPrice">$210.00</span>
                        <span class="productSold">SOLD 8021</span>
                    </div>
                </div>
            </a>

            <a href="product-details.php?id=5" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="MODEL FX-05">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MODEL FX-05</h3>
                    <div class="productFooter">
                        <span class="productPrice">$175.00</span>
                        <span class="productSold">SOLD 5000</span>
                    </div>
                </div>
            </a>

            <a href="product-details.php?id=6" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/download (1).jpg" alt="MODEL FX-06">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MODEL FX-06</h3>
                    <div class="productFooter">
                        <span class="productPrice">$135.00</span>
                        <span class="productSold">SOLD 4200</span>
                    </div>
                </div>
            </a>

            <a href="product-details.php?id=7" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="MODEL FX-07">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MODEL FX-07</h3>
                    <div class="productFooter">
                        <span class="productPrice">$225.00</span>
                        <span class="productSold">SOLD 3100</span>
                    </div>
                </div>
            </a>

            <a href="product-details.php?id=8" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="MODEL FX-08">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MODEL FX-08</h3>
                    <div class="productFooter">
                        <span class="productPrice">$199.00</span>
                        <span class="productSold">SOLD 2500</span>
                    </div>
                </div>
            </a>

        </div>

        <!-- Buttons INSIDE productSetContainer -->
        <div class="sliderControlsNR">
            <button class="sliderBtnNR" id="prevBtnNR" type="button" aria-label="Previous Products">&lt;</button>
            <button class="sliderBtnNR" id="nextBtnNR" type="button" aria-label="Next Products">&gt;</button>
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
            
            <!-- Full Width Container: METALBUILD -->
            <div class="catBox catBoxFull">
                <span class="catTitleText">METALBUILD</span>
                <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="Metalbuild">
            </div>

            <!-- Row 1 Left: PERFECT GRADE -->
            <div class="catBox">
                <span class="catTitleText">PERFECT GRADE</span>
                <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="Perfect Grade">
            </div>

            <!-- Row 1 Right: MASTER GRADE -->
            <div class="catBox">
                <span class="catTitleText">MASTER GRADE</span>
                <img src="promotional/download (1).jpg" alt="Master Grade">
            </div>

            <!-- Row 2 Left: REAL GRADE -->
            <div class="catBox">
                <span class="catTitleText">REAL GRADE</span>
                <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="Real Grade">
            </div>

            <!-- Row 2 Right: HIGH GRADE -->
            <div class="catBox">
                <span class="catTitleText">HIGH GRADE</span>
                <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="High Grade">
            </div>

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
            
            <!-- Card 1 -->
            <a href="product-details.php?id=1" class="bsCard">
                <div class="bsImgContainer">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="RG RX-93 Nu Gundam">
                </div>
                <div class="bsDetails">
                    <div class="bsBadges">
                        <span class="bsBadge">BANDAI</span>
                        <span class="bsBadge">IN-STOCK</span>
                    </div>
                    <h3 class="bsTitle">RG RX-93 Nu Gundam</h3>
                    <div class="bsFooter">
                        <span class="bsPrice">P 4,620.00</span>
                        <span class="bsSold">SOLD 10K+</span>
                    </div>
                </div>
            </a>

            <!-- Card 2 -->
            <a href="product-details.php?id=2" class="bsCard">
                <div class="bsImgContainer">
                    <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="MG FREEDOM GUNDAM 2.0">
                </div>
                <div class="bsDetails">
                    <div class="bsBadges">
                        <span class="bsBadge">BANDAI</span>
                        <span class="bsBadge">IN-STOCK</span>
                    </div>
                    <h3 class="bsTitle">MG FREEDOM GUNDAM 2.0</h3>
                    <div class="bsFooter">
                        <span class="bsPrice">P 4,880.00</span>
                        <span class="bsSold">SOLD 9080</span>
                    </div>
                </div>
            </a>

            <!-- Card 3 -->
            <a href="product-details.php?id=3" class="bsCard">
                <div class="bsImgContainer">
                    <img src="promotional/download (1).jpg" alt="MG JUSTICE GUNDAM 2.0">
                </div>
                <div class="bsDetails">
                    <div class="bsBadges">
                        <span class="bsBadge">BANDAI</span>
                        <span class="bsBadge">IN-STOCK</span>
                    </div>
                    <h3 class="bsTitle">MG JUSTICE GUNDAM 2.0</h3>
                    <div class="bsFooter">
                        <span class="bsPrice">P 4,880.00</span>
                        <span class="bsSold">SOLD 8041</span>
                    </div>
                </div>
            </a>

            <!-- Card 4 -->
            <a href="product-details.php?id=4" class="bsCard">
                <div class="bsImgContainer">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="RG HI-NU GUNDAM">
                </div>
                <div class="bsDetails">
                    <div class="bsBadges">
                        <span class="bsBadge">BANDAI</span>
                        <span class="bsBadge">IN-STOCK</span>
                    </div>
                    <h3 class="bsTitle">RG HI-NU GUNDAM</h3>
                    <div class="bsFooter">
                        <span class="bsPrice">P 3,709.00</span>
                        <span class="bsSold">SOLD 8021</span>
                    </div>
                </div>
            </a>

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
        </div>

        <!-- Horizontal Sliding Track -->
        <div class="productTrackMK" id="productTrackMK">
            
            <!-- Card 1 -->
            <a href="product-details.php?id=1" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="RG RX-93 Nu Gundam">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">RG RX-93 Nu Gundam</h3>
                    <div class="productFooter">
                        <span class="productPrice">P 4,620.00</span>
                        <span class="productSold">SOLD 10K+</span>
                    </div>
                </div>
            </a>

            <!-- Card 2 -->
            <a href="product-details.php?id=2" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="RG SINANJU">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">RG SINANJU</h3>
                    <div class="productFooter">
                        <span class="productPrice">P 3,035.25</span>
                        <span class="productSold">SOLD 879</span>
                    </div>
                </div>
            </a>

            <!-- Card 3 -->
            <a href="product-details.php?id=3" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/download (1).jpg" alt="RG Sazabi">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">RG Sazabi "Chars Counterattack"</h3>
                    <div class="productFooter">
                        <span class="productPrice">P 3,642.30</span>
                        <span class="productSold">SOLD 420</span>
                    </div>
                </div>
            </a>

            <!-- Card 4 -->
            <a href="product-details.php?id=4" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="RG HI-NU GUNDAM">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">RG HI-NU GUNDAM</h3>
                    <div class="productFooter">
                        <span class="productPrice">P 3,709.00</span>
                        <span class="productSold">SOLD 8021</span>
                    </div>
                </div>
            </a>

            <!-- Card 5 -->
            <a href="product-details.php?id=5" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="MG FREEDOM GUNDAM 2.0">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MG FREEDOM GUNDAM 2.0</h3>
                    <div class="productFooter">
                        <span class="productPrice">P 4,880.00</span>
                        <span class="productSold">SOLD 9080</span>
                    </div>
                </div>
            </a>

            <!-- Card 6 -->
            <a href="product-details.php?id=6" class="productCard">
                <div class="productImgContainer">
                    <img src="promotional/download (1).jpg" alt="MG JUSTICE GUNDAM 2.0">
                </div>
                <div class="productDetails">
                    <div class="productBadges">
                        <span class="productBadge">BANDAI</span>
                        <span class="productBadge">IN-STOCK</span>
                    </div>
                    <h3 class="productTitle">MG JUSTICE GUNDAM 2.0</h3>
                    <div class="productFooter">
                        <span class="productPrice">P 4,880.00</span>
                        <span class="productSold">SOLD 8041</span>
                    </div>
                </div>
            </a>

        </div>

        <!-- Counter & Navigation Controls -->
        <div class="sliderControlsMK">
            <div class="sliderCounterMK">
                <span id="activeCountMK" class="activeCount">01</span>
                <span class="divider">|</span>
                <span id="totalCountMK" class="totalCount">20</span>
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
<!-- SECTION 7: FEATURED -->
<div class="featuredContainerFT">
    <!-- Top Hero Banner -->
    <div class="topBannerFT">
        <img src="promotional/285286ad-ebd9-494b-a0fe-cf91633042ed.webp" alt="Gundam Featured Banner">
        <div class="accentBarFT"></div>
    </div>

    <!-- Header -->
    <div class="headerContainerFT">
        <h2>FEATURED</h2>
    </div>

    <!-- Main Grid Content -->
    <div class="featuredGridFT">
        <!-- Left Column: Main Anniversary Feature -->
        <div class="featuredCardLeftFT">
            <div class="featuredImgContainerFT">
                <img src="promotional/METAL BUILD ZGMF-X42S Destiny Gundam.jpg" alt="METAL BUILD Destiny Gundam">
            </div>
            <div class="featuredDetailsFT">
                <h3 class="featuredTitleFT">DESTINY GUNDAM<br>SPECIAL EDITION</h3>
                <p class="featuredSubFT">METAL BUILD ZGMF-X42S DESTINY</p>
                <span class="featuredStatusFT statusAvailableFT">NOW AVAILABLE!</span>
            </div>
        </div>

        <!-- Right Column: Stacked Banners -->
        <div class="featuredRightColFT">
            <!-- Top Stacked Banner -->
            <div class="featuredBannerRowFT">
                <div class="bannerImgBoxFT">
                    <img src="promotional/869ba4c1-0558-40ac-b57e-5f4d25f7958d.webp" alt="Hi-Nu Hyper Mega Bazooka">
                </div>
                <div class="bannerTextBoxFT textRightFT">
                    <span class="brandSubFT">METAL BUILD EXPO</span>
                    <h3 class="bannerTitleFT">HYPER MEGA BAZOOKA</h3>
                    <span class="featuredStatusFT">COMING SOON</span>
                </div>
            </div>

            <!-- Bottom Stacked Banner -->
            <div class="featuredBannerRowFT">
                <div class="bannerTextBoxFT textLeftFT">
                    <span class="brandSubFT">METAL ROBOT SPIRITS</span>
                    <h3 class="bannerTitleFT">STRIKE FREEDOM</h3>
                    <span class="featuredStatusFT">COMING SOON</span>
                </div>
                <div class="bannerImgBoxFT">
                    <img src="promotional/download (1).jpg" alt="Mighty Strike Freedom">
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footerFT">
    <div class="footerContainerFT">
        
        <div class="footerLogoRowFT">
            <img src="promotional/Asset 5.png" alt="THE HANGAR" class="footerLogoFT">
        </div>

        <div class="footerSectionsWrapperFT">
            
            <div class="footerRowFT footerRow1FT">
                <div class="footerColFT">
                    <h4 class="footerHeadingFT">CUSTOMER SERVICE</h4>
                    <ul class="footerLinksFT">
                        <li><a href="#">return and refund</a></li>
                        <li><a href="#">payment methods</a></li>
                        <li><a href="#">contact us</a></li>
                    </ul>
                </div>

                <div class="footerColFT">
                    <h4 class="footerHeadingFT">ABOUT THE HANGAR</h4>
                    <ul class="footerLinksFT">
                        <li><a href="#">policy of privacy</a></li>
                        <li><a href="#">about us</a></li>
                    </ul>
                </div>

                <div class="footerColFT">
                    <h4 class="footerHeadingFT">PAYMENT</h4>
                    <div class="brandListFT paymentLogosFT">
                        <img src="https://placehold.co/120x30/2e7d32/ffffff?text=LANDBANK" alt="Landbank">
                        <img src="https://placehold.co/120x30/0d47a1/ffffff?text=BDO" alt="BDO">
                        <img src="https://placehold.co/120x30/b71c1c/ffffff?text=BPI" alt="BPI">
                        <img src="https://placehold.co/120x30/1a237e/ffffff?text=VISA" alt="VISA">
                    </div>
                </div>
            </div>

            <div class="footerRowFT footerRow2FT">
                <div class="footerColFT">
                    <h4 class="footerHeadingFT">LOGISTICS</h4>
                    <div class="brandListFT logisticsLogosFT">
                        <img src="footer/logo.5f09a646.png" alt="J&T Express">
                        <img src="footer/ninjavan-logo-white.webp" alt="Ninja Van">
                    </div>
                </div>

                <div class="footerColFT socialColFT">
                    <h4 class="footerHeadingFT">FOLLOW US</h4>
                    <div class="socialListFT">
                        <a href="#" class="socialItemFT">
                            <svg class="socialIconFT" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10 10 0 0 0 22 12.06C22 6.53 17.5 2.04 12 2.04Z"/></svg>
                            <span>thehangarmodelshop</span>
                        </a>
                        <a href="#" class="socialItemFT">
                            <svg class="socialIconFT" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            <span>@thehangarmodelshop</span>
                        </a>
                        <a href="#" class="socialItemFT">
                            <svg class="socialIconFT" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            <span>thehangarmodelshop</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="footerDisclaimerFT">
            <p>The image is for illustrative purposes only. The actual product may differ slightly from the image.</p>
        </div>

        <div class="footerBottomFT">
            <div class="copyrightFT">
                &copy;2026, THE HANGAR, LLC<br>
                ALL RIGHTS RESERVED
            </div>
            <div class="officialLogosFT">
                <img src="https://placehold.co/100x32/d50000/ffffff?text=BANDAI+NAMCO" alt="Bandai Namco">
                <img src="https://placehold.co/60x32/0288d1/ffffff?text=BANDAI" alt="Bandai Blue">
                <img src="https://placehold.co/60x32/d50000/ffffff?text=BANDAI" alt="Bandai Red">
                <img src="https://placehold.co/60x32/0d47a1/ffffff?text=TAMIYA" alt="Tamiya">
            </div>
        </div>

    </div>
</footer>


<!-- Replace your current script tag with this -->
<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>


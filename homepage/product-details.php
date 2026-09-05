<?php
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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$product = null;

if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM `products` WHERE `id` = :id");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();
}

// Fallback if product id not found
if (!$product) {
    $product = [
        'id' => $id,
        'name' => 'MG ASW-G-XX Gundam Vidar',
        'grade' => 'MG',
        'scale' => '1/100',
        'price' => 4620.00,
        'sold_count' => 250,
        'brand' => 'BANDAI',
        'stock_status' => 'IN-STOCK',
        'image_url' => 'mg vidar.webp',
        'description' => 'Equipped with the specialized Ahab reactor output and hunter edge sabers, this Master Grade model kit boasts unprecedented internal frame articulation and die-cast stability.'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        :root {
            --brand-cyan: #3FC4E1;
            --brand-dark: #231F20;
            --font-system: 'Orbitron', sans-serif;
            --font-heading: 'Poppins', sans-serif;
            --font-body: 'Poppins', sans-serif;
        }

        body {
            background-color: #ffffff;
            color: var(--brand-dark);
            margin: 0;
            padding: 0;
            font-family: var(--font-body);
            overflow-x: hidden;
        }

        /* Top Header Strip matching Sections 3, 5, 6 */
        .pdSectionHeader {
            width: 95%;
            max-width: 110rem;
            margin: 72px auto 0 auto;
            height: 90px;
            padding: 0 2.5rem;
            box-sizing: border-box;
            background-color: #ffffff;
            border-top: 0.5px solid #080808;
            border-bottom: 0.5px solid #080808;
            border-left: 0.5px solid #080808;
            border-right: 0.5px solid #080808;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pdHeaderLeft, .pdHeaderCenter, .pdHeaderRight {
            flex: 1;
            display: flex;
            align-items: center;
            height: 100%;
        }

        .pdHeaderLeft {
            justify-content: flex-start;
        }

        .pdHeaderCenter {
            justify-content: center;
            border-left: 1px solid #080808;
            border-right: 1px solid #080808;
        }

        .pdHeaderCenter h2 {
            font-family: var(--font-heading);
            font-size: clamp(1.8rem, 2.8vw, 3.25rem);
            font-weight: 700;
            letter-spacing: 2px;
            color: #080808;
            text-transform: uppercase;
            margin: 0;
            line-height: 1;
            text-align: center;
        }

        .pdHeaderRight {
            justify-content: flex-end;
        }

        .backStorefrontLink {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: var(--brand-dark);
            text-decoration: none;
            font-family: var(--font-heading);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            transition: color 0.2s ease;
        }

        .backStorefrontLink:hover {
            color: var(--brand-cyan);
        }

        .systemBadgeText {
            font-family: var(--font-system);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--brand-dark);
        }

        .systemBadgeText span {
            color: var(--brand-cyan);
        }

        /* Outer Frame Container Matching Section 3 and 6 */
        .pdMainFrame {
            width: 95%;
            max-width: 110rem;
            margin: 0 auto 5rem auto;
            box-sizing: border-box;
            border-left: 0.5px solid #080808;
            border-right: 0.5px solid #080808;
            border-bottom: 0.5px solid #080808;
            padding: 3rem 2.5rem;
            background-color: #ffffff;
            overflow: hidden;
        }

        .pdGrid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
            gap: 3rem;
            align-items: start;
            width: 100%;
        }

        /* Product Gallery Image Box */
        .pdGalleryBox {
            background-color: #f0f0f0;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
            min-height: 480px;
            max-height: 600px;
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
        }

        .pdGalleryBox img {
            max-width: 100%;
            max-height: 460px;
            object-fit: contain;
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            user-select: none;
        }

        .pdGalleryBox:hover img {
            transform: scale(1.05);
        }

        /* Product Information */
        .pdInfoCol {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-width: 0;
            overflow: hidden;
        }

        .pdBadgesRow {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.8rem;
        }

        .pdBadge {
            background-color: #e5e5e5;
            color: #333333;
            font-family: var(--font-heading);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 0.25rem 0.65rem;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .pdBadgeDark {
            background-color: var(--brand-dark);
            color: #ffffff;
            font-weight: 800;
        }

        .pdBadgeCyan {
            background-color: var(--brand-cyan);
            color: var(--brand-dark);
            font-weight: 800;
        }

        .pdTitle {
            font-family: var(--font-heading);
            font-size: clamp(1.8rem, 2.5vw, 2.4rem);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: 0.5px;
            color: var(--brand-dark);
            text-transform: uppercase;
            margin: 0.4rem 0 0.4rem 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .pdScaleSub {
            font-family: var(--font-body);
            font-size: 0.95rem;
            font-weight: 600;
            color: #666666;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0 0 1.2rem 0;
        }

        .pdPriceRow {
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.2rem;
            border-bottom: 0.5px solid #e5e5e5;
        }

        .pdPriceVal {
            font-family: var(--font-heading);
            font-size: clamp(1.8rem, 2.5vw, 2.4rem);
            font-weight: 800;
            color: var(--brand-dark);
            line-height: 1;
        }

        .pdSoldBadge {
            font-family: var(--font-heading);
            font-size: 0.8rem;
            font-weight: 700;
            color: #333333;
            background-color: #f0f0f0;
            padding: 0.35rem 0.8rem;
            border-radius: 4px;
            letter-spacing: 0.8px;
        }

        .pdDesc {
            font-family: var(--font-body);
            font-size: 0.92rem;
            line-height: 1.7;
            color: #444444;
            margin: 0 0 1.8rem 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        /* Quantity & Action Buttons */
        .pdActionSection {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            margin-bottom: 2rem;
            width: 100%;
        }

        .pdQuantityRow {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .pdQtyLabel {
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--brand-dark);
            text-transform: uppercase;
        }

        .pdQtyPicker {
            display: inline-flex;
            align-items: center;
            border: 1.5px solid var(--brand-dark);
            border-radius: 4px;
            overflow: hidden;
            box-sizing: border-box;
        }

        .pdQtyBtn {
            background: #ffffff;
            border: none;
            width: 38px;
            height: 38px;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand-dark);
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pdQtyBtn:hover {
            background-color: #f0f0f0;
        }

        .pdQtyInput {
            width: 48px;
            height: 38px;
            border: none;
            border-left: 1.5px solid var(--brand-dark);
            border-right: 1.5px solid var(--brand-dark);
            text-align: center;
            font-family: var(--font-heading);
            font-size: 1rem;
            font-weight: 700;
            color: var(--brand-dark);
            box-sizing: border-box;
        }

        .pdButtonsGroup {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
        }

        /* Uniform Pill Buttons Matching Section 1 Order Button */
        .pdBtnOrderNow {
            flex: 1 1 200px;
            min-width: 160px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0.9rem 1.6rem;
            background-color: var(--brand-cyan);
            color: var(--brand-dark);
            text-decoration: none;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 0.9rem;
            letter-spacing: 1.5px;
            border: 2px solid var(--brand-cyan);
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 2rem;
            box-shadow: 0 4px 14px rgba(63, 196, 225, 0.35);
            text-transform: uppercase;
            box-sizing: border-box;
            white-space: nowrap;
        }

        .pdBtnOrderNow:hover {
            background-color: transparent;
            color: var(--brand-dark);
            border-color: var(--brand-dark);
            box-shadow: none;
            transform: translateY(-2px);
        }

        .pdBtnAddToCart {
            flex: 1 1 180px;
            min-width: 160px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0.9rem 1.5rem;
            background-color: var(--brand-dark);
            color: #ffffff;
            text-decoration: none;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 0.9rem;
            letter-spacing: 1.5px;
            border: 2px solid var(--brand-dark);
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 2rem;
            text-transform: uppercase;
            box-sizing: border-box;
            white-space: nowrap;
        }

        .pdBtnAddToCart:hover {
            background-color: transparent;
            color: var(--brand-dark);
            transform: translateY(-2px);
        }

        /* Specifications Table */
        .pdSpecsContainer {
            background-color: #fafafa;
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            padding: 1.2rem 1.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        .pdSpecsHeading {
            font-family: var(--font-heading);
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: var(--brand-dark);
            text-transform: uppercase;
            margin: 0 0 0.8rem 0;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .pdSpecsTable {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            table-layout: fixed;
        }

        .pdSpecsTable tr {
            border-bottom: 1px solid #eeeeee;
        }

        .pdSpecsTable tr:last-child {
            border-bottom: none;
        }

        .pdSpecsTable td {
            padding: 0.55rem 0;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .pdSpecKey {
            font-family: var(--font-heading);
            font-weight: 700;
            color: #777777;
            width: 38%;
            letter-spacing: 0.5px;
        }

        .pdSpecVal {
            font-family: var(--font-body);
            font-weight: 600;
            color: var(--brand-dark);
            width: 62%;
        }

        @media (max-width: 1100px) {
            .pdSectionHeader {
                padding: 0 1.5rem;
            }
            .pdHeaderCenter h2 {
                font-size: 2rem;
            }
            .pdGrid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .pdMainFrame {
                padding: 2rem 1.5rem;
            }
            .pdGalleryBox {
                min-height: 380px;
            }
        }

        @media (max-width: 600px) {
            .pdButtonsGroup {
                flex-direction: column;
            }
            .pdBtnOrderNow, .pdBtnAddToCart {
                width: 100%;
                flex: 1 1 100%;
            }
            .pdSectionHeader {
                height: auto;
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            .pdHeaderCenter {
                border-left: none;
                border-right: none;
                padding: 0.5rem 0;
            }
        }
    </style>
</head>
<body>

    <!-- TOP NAVIGATION (Exact Site Navbar) -->
    <header class="headerContainer">
        <div class="headerLeft">
            <a href="index.php" class="navItem navBtnHamburger" aria-label="Menu">
                <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
            </a>
            <a href="index.php" class="navItem navLink">LANGUAGE</a>
            <a href="search/search.php" class="navItem navLink">PRODUCTS</a>
        </div>

        <div class="headerCenter">
            <a href="index.php" class="navBrand" aria-label="THE HANGAR Home">
                <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
            </a>
        </div>

        <div class="headerRight">
            <a href="cart/cart.php" class="navItem navLink navCart">CART</a>
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
            <button class="navItem navBtnSearch" type="button" aria-label="Search">
                <img src="<?php echo $buttonsPath; ?>/Search.svg" alt="Search">
            </button>
        </div>
    </header>

    <!-- SECTION HEADER STRIP (Matches Section 3, 5, 6 Header) -->
    <div class="pdSectionHeader">
        <div class="pdHeaderLeft">
            <a href="index.php" class="backStorefrontLink">
                &larr; RETURN TO STOREFRONT
            </a>
        </div>
        <div class="pdHeaderCenter">
            <h2>PRODUCT DETAILS</h2>
        </div>
        <div class="pdHeaderRight">
            <div class="systemBadgeText">
                GUND-ORDER <span>// G.O.S</span>
            </div>
        </div>
    </div>

    <!-- MAIN PRODUCT DETAIL FRAME (Uniform With Storefront Containers) -->
    <main class="pdMainFrame">
        <div class="pdGrid">
            
            <!-- Left Column: Product Image Gallery -->
            <div class="pdGalleryBox">
                <img src="<?php echo $promotionalPath; ?>/<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='<?php echo $promotionalPath; ?>/Asset 8.png'">
            </div>

            <!-- Right Column: Product Details & Purchase Actions -->
            <div class="pdInfoCol">
                
                <div class="pdBadgesRow">
                    <span class="pdBadge pdBadgeCyan"><?php echo htmlspecialchars($product['grade']); ?></span>
                    <span class="pdBadge"><?php echo htmlspecialchars($product['brand']); ?></span>
                    <span class="pdBadge pdBadgeDark"><?php echo htmlspecialchars($product['stock_status']); ?></span>
                </div>

                <h1 class="pdTitle"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="pdScaleSub"><?php echo htmlspecialchars($product['scale']); ?> SCALE MODEL KIT</p>

                <div class="pdPriceRow">
                    <span class="pdPriceVal">₱ <?php echo number_format($product['price'], 2); ?></span>
                    <span class="pdSoldBadge">SOLD <?php echo number_format($product['sold_count']); ?></span>
                </div>

                <p class="pdDesc">
                    <?php echo nl2br(htmlspecialchars($product['description'] ?: 'Equipped with precision engineered internal frame articulation, die-cast stability, snap-fit assembly without glue, and authentic color separation by Bandai Spirits.')); ?>
                </p>

                <!-- Quantity and Pill Buttons -->
                <div class="pdActionSection">
                    <div class="pdQuantityRow">
                        <span class="pdQtyLabel">Quantity:</span>
                        <div class="pdQtyPicker">
                            <button type="button" class="pdQtyBtn" onclick="let q=document.getElementById('pdQty'); if(parseInt(q.value)>1) q.value=parseInt(q.value)-1;">-</button>
                            <input type="text" id="pdQty" class="pdQtyInput" value="1" readonly>
                            <button type="button" class="pdQtyBtn" onclick="let q=document.getElementById('pdQty'); q.value=parseInt(q.value)+1;">+</button>
                        </div>
                    </div>

                    <div class="pdButtonsGroup">
                        <button type="button" class="pdBtnOrderNow" id="pdBtnOrderNow">
                            ORDER NOW!
                        </button>
                        <button type="button" class="pdBtnAddToCart" id="pdBtnAddToCart">
                            ADD TO CART
                        </button>
                    </div>
                </div>

                <!-- Technical Specs Box -->
                <div class="pdSpecsContainer">
                    <h3 class="pdSpecsHeading">MODEL KIT SPECIFICATIONS</h3>
                    <table class="pdSpecsTable">
                        <tr>
                            <td class="pdSpecKey">License &amp; Brand</td>
                            <td class="pdSpecVal"><?php echo htmlspecialchars($product['brand']); ?> &bull; SUNRISE &bull; SOTSU</td>
                        </tr>
                        <tr>
                            <td class="pdSpecKey">Kit Grade</td>
                            <td class="pdSpecVal"><?php echo htmlspecialchars($product['grade']); ?> (Master / Real Grade)</td>
                        </tr>
                        <tr>
                            <td class="pdSpecKey">Model Scale</td>
                            <td class="pdSpecVal"><?php echo htmlspecialchars($product['scale']); ?></td>
                        </tr>
                        <tr>
                            <td class="pdSpecKey">Availability</td>
                            <td class="pdSpecVal" style="color: var(--brand-dark); font-weight: 700;">
                                <?php echo htmlspecialchars($product['stock_status']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="pdSpecKey">System Verification</td>
                            <td class="pdSpecVal" style="color: var(--brand-cyan); font-weight: 700; font-family: var(--font-system);">
                                G.O.S // AUTHENTIC BANDAI
                            </td>
                        </tr>
                    </table>
                </div>

            </div>
        </div>
    </main>

    <!-- FOOTER (Reusable Component) -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- G.O.S SEARCH & CART HUD OVERLAYS (Self-contained modular components) -->
    <script>window.HANGAR_PATHS = { cartPage: 'cart/cart.php', searchPage: 'search/search.php', apiSearch: 'search/api_search.php', productDetails: 'product-details.php', promotionalBase: 'promotional' };</script>
    <?php $_searchAssetPrefix = 'search/'; require_once __DIR__ . '/search/search_modal.php'; ?>
    <?php $_cartAssetPrefix   = 'cart/';   require_once __DIR__ . '/cart/cart_modal.php'; ?>
</body>
</html>

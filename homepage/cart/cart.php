<?php
// THE HANGAR - GUND-ORDER SYSTEM
// DEDICATED CART & SUPPLY REQUISITION DECK (cart.php)

require_once __DIR__ . '/../../shared/bootstrap.php';
extract(hangarBootstrap());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUPPLY MANIFEST // CART | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="cart.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: #f7f9fb;
            color: var(--brand-dark, #231F20);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .mainCartWrapper {
            flex: 1;
        }

        .statusTagHeader {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #080808;
        }

        /* Checkout Success Modal */
        .orderSuccessOverlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 9, 14, 0.85);
            backdrop-filter: blur(12px);
            z-index: 100020;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .orderSuccessOverlay.show {
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
        }

        .orderSuccessDialog {
            position: relative;
            background: rgba(14, 18, 25, 0.98);
            border: 1px solid #3FC4E1;
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
            color: #ffffff;
            box-shadow: 0 25px 60px rgba(0,0,0,0.9), 0 0 35px rgba(63,196,225,0.3);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .orderSuccessOverlay.show .orderSuccessDialog {
            transform: scale(1);
        }

        .successRadarPulse {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(63, 196, 225, 0.15);
            border: 2px solid #3FC4E1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: #3FC4E1;
        }

        .successOrderTitle {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .successOrderCode {
            font-family: 'Orbitron', monospace;
            font-size: 0.9rem;
            color: #3FC4E1;
            margin-bottom: 1.25rem;
            letter-spacing: 1px;
        }

        .successOrderDesc {
            font-size: 0.85rem;
            color: #8E9BAE;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

    <!-- SECTION 0: TOP NAVBAR -->
    <header class="headerContainer" style="background-color: rgba(14, 18, 24, 0.92);">
        <div class="headerLeft">
            <a href="../index.php" class="navItem navBtnHamburger" aria-label="Menu">
                <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
            </a>
            <a href="../index.php" class="navItem navLink">LANGUAGE</a>
            <a href="../search/search.php" class="navItem navLink">PRODUCTS</a>
        </div>

        <div class="headerCenter">
            <a href="../index.php" class="navBrand" aria-label="THE HANGAR Home">
                <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
            </a>
        </div>

        <div class="headerRight">
            <a href="cart.php" class="navItem navLink navCart" style="color: var(--brand-cyan);">CART</a>
            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                    <a href="<?php echo $adminPath; ?>" class="navItem navLink" style="color: #ffaa00; font-weight: 700;">[COMMAND DECK]</a>
                <?php else: ?>
                    <span class="navItem navLink" style="color: #3FC4E1; cursor: default;">PILOT: <?php echo htmlspecialchars($userName); ?></span>
                <?php endif; ?>
                <a href="<?php echo $logoutPath; ?>" class="navItem navLink" title="Sign out of G.O.S">LOG OUT</a>
            <?php else: ?>
                <a href="<?php echo $loginPath; ?>" class="navItem navLink">LOG IN</a>
            <?php endif; ?>
            <button class="navItem navBtnSearch" type="button" aria-label="Search">
                <img src="<?php echo $buttonsPath; ?>/Search.svg" alt="Search">
            </button>
        </div>
    </header>

    <!-- SECTION HEADER STRIP -->
    <div class="pdSectionHeader">
        <div class="pdHeaderLeft">
            <a href="../index.php" class="backStorefrontLink">
                &larr; RETURN TO STOREFRONT
            </a>
        </div>
        <div class="pdHeaderCenter">
            <h2>SUPPLY MANIFEST</h2>
        </div>
        <div class="pdHeaderRight">
            <span class="statusTagHeader">[ONLINE] ALLOCATION DECK</span>
        </div>
    </div>

    <!-- MAIN CART SECTION -->
    <div class="mainCartWrapper">
        <div class="cartPageContainer">
            
            <!-- Empty State -->
            <div id="cartPageEmptyMessage" style="display: none; text-align: center; padding: 6rem 1rem;">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#8E9BAE" stroke-width="1.5" style="margin-bottom: 1.5rem;">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    <line x1="3" y1="3" x2="21" y2="21" stroke="#FF5555" stroke-width="2"></line>
                </svg>
                <h3 style="font-family:'Orbitron', sans-serif; font-size:1.4rem; font-weight:800; color:#080808; margin-bottom:0.75rem;">SUPPLY MANIFEST EMPTY</h3>
                <p style="color:#666; max-width:400px; margin:0 auto 2rem; font-size:0.95rem;">No Mobile Suits or armaments have been allocated to your hangar requisitions.</p>
                <a href="../search/search.php" class="orderBtn" style="background:#080808; color:#fff; border-color:#080808;">DEPLOY TO CATALOGUE</a>
            </div>

            <!-- Content Grid -->
            <div class="cartPageGrid" id="cartPageContent">
                
                <!-- Left: Requisitions Table Card -->
                <div class="cartPageItemsCard">
                    <div class="cartPageCardHeader">
                        <h3 class="cartPageCardTitle">REQUISITIONED UNITS</h3>
                        <button type="button" class="cartClearAllBtn" id="cartPageClearBtn">PURGE MANIFEST</button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="cartTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 260px;">UNIT / SPECIFICATION</th>
                                    <th>UNIT PRICE</th>
                                    <th>QUANTITY</th>
                                    <th>TOTAL</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartPageTableBody">
                                <!-- Populated dynamically by cart.js -->
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; padding-top: 1.2rem; border-top: 1px solid #f0f0f0;">
                        <a href="../search/search.php" style="color: #080808; font-weight: 700; font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                            &larr; ADD MORE UNITS
                        </a>
                        <span style="font-size: 0.78rem; color: #888;">
                            Official Bandai Spirits Model Kits &bull; Genuine Import
                        </span>
                    </div>
                </div>

                <!-- Right: Summary & Checkout Card -->
                <div class="cartPageSummaryCard">
                    <div class="cartPageCardHeader">
                        <h3 class="cartPageCardTitle">LOGISTICS &amp; TOTAL</h3>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <div class="cartSummaryRow" style="color: #333; font-size: 0.9rem;">
                            <span class="cartSummaryLabel">MANUFACTURE SUBTOTAL:</span>
                            <span class="cartSummaryVal" id="cartPageSubtotal">—</span>
                        </div>
                        <div class="cartSummaryRow" style="color: #008a3e; font-size: 0.9rem;" id="cartPageDiscountRow">
                            <span class="cartSummaryLabel">PILOT CLEARANCE PROMO:</span>
                            <span class="cartSummaryVal" id="cartPageDiscount">- ₱ 0.00</span>
                        </div>
                        <div class="cartSummaryRow" style="color: #333; font-size: 0.9rem;">
                            <span class="cartSummaryLabel">LOGISTICS DISPATCH:</span>
                            <span class="cartSummaryVal" id="cartPageShipping">—</span>
                        </div>
                        <div class="cartSummaryRow totalRow" style="border-top: 2px solid #e0e0e0; margin-top: 1rem; padding-top: 1rem;">
                            <span class="cartSummaryLabel" style="font-weight: 800; font-size: 1.05rem; color: #080808;">GRAND TOTAL:</span>
                            <span class="cartTotalVal" id="cartPageTotal" style="color: #080808; text-shadow: none;">—</span>
                        </div>
                    </div>

                    <!-- Promo Code Input -->
                    <div style="margin-bottom: 1.5rem;">
                        <label for="cartPagePromoInput" style="display: block; font-size: 0.72rem; font-family: 'Orbitron', sans-serif; font-weight: 700; color: #555; margin-bottom: 0.4rem; letter-spacing: 1px;">
                            PILOT CLEARANCE CODE
                        </label>
                        <div class="cartPromoRow" style="margin: 0;">
                            <input type="text" id="cartPagePromoInput" class="cartPromoInput" placeholder="E.G. PILOT10" style="background: #ffffff; color: #080808; border-color: #ccc;" autocomplete="off" autocapitalize="characters" spellcheck="false">
                            <button type="button" id="cartPagePromoBtn" class="cartPromoBtn" style="background: #080808; border-color: #080808; color: #ffffff;">APPLY</button>
                        </div>
                        <div id="cartPagePromoStatus" style="font-size: 0.72rem; color: #008a3e; margin-top: 0.4rem; display: none; font-weight: 600;"></div>
                    </div>

                    <!-- Dispatch Button -->
                    <button type="button" class="cartCheckoutBtn" id="cartFinalizeDispatchBtn" style="background: #080808; color: #ffffff; border-color: #080808;">
                        <span>DISPATCH ORDER</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>

                    <!-- Partner Logistics & Payments -->
                    <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid #e5e5e5; padding-top: 1.2rem;">
                        <p style="font-size: 0.72rem; font-family: 'Orbitron', sans-serif; color: #888; letter-spacing: 1px; margin-bottom: 0.75rem;">
                            SECURE LOGISTICS &amp; SETTLEMENT
                        </p>
                        <div style="display: flex; justify-content: center; gap: 1rem; align-items: center; opacity: 0.75;">
                            <img src="<?php echo $footerPath; ?>/Logistics/logo.5f09a646.png" alt="J&T" style="height: 18px; object-fit: contain;">
                            <img src="<?php echo $footerPath; ?>/Logistics/ninjavan-logo-white.webp" alt="NinjaVan" style="height: 16px; object-fit: contain; filter: invert(1);">
                            <img src="<?php echo $footerPath; ?>/banks/BDO_50th_362_x_126_px_reverse (1).svg" alt="BDO" style="height: 14px; object-fit: contain; filter: invert(1);">
                            <img src="<?php echo $footerPath; ?>/banks/BPI_RT__96x42_header_Reverse.svg" alt="BPI" style="height: 14px; object-fit: contain; filter: invert(1);">
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- ORDER SUCCESS CONFIRMATION MODAL -->
    <div id="orderSuccessOverlay" class="orderSuccessOverlay" aria-hidden="true">
        <div class="orderSuccessDialog">
            <div class="successRadarPulse">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h2 class="successOrderTitle">SORTIE DISPATCH AUTHORIZED!</h2>
            <div class="successOrderCode" id="successOrderNumber">MANIFEST ORDER #HGR-984271</div>
            <p class="successOrderDesc">
                Your Gundam Mobile Suit units have been logged into the Hangar distribution queue. Logistics tracking will be relayed to your registered pilot terminal.
            </p>
            <button type="button" class="orderBtn" id="successDismissBtn" style="background: #3FC4E1; border-color: #3FC4E1; color: #0A0D14; width: 100%;">
                RETURN TO STOREFRONT
            </button>
        </div>
    </div>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../footer.php'; ?>

    <!-- GUND-ORDER SYSTEM SEARCH & CART HUD OVERLAYS -->
    <script>window.HANGAR_PATHS = { cartPage: 'cart.php', searchPage: '../search/search.php', apiSearch: '../search/api_search.php', productDetails: '../product-details.php', promotionalBase: '../promotional', apiCheckout: 'api_checkout.php' };</script>
    <?php $_searchAssetPrefix = './'; require_once __DIR__ . '/../search/search_modal.php'; ?>
    <?php $_cartAssetPrefix   = './'; require_once __DIR__ . '/cart_modal.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // NOTE: HangarCart.init() already runs via cart_modal.php and calls renderDedicatedPage()
            // We only need page-specific logic here.

            const dispatchBtn = document.getElementById('cartFinalizeDispatchBtn');
            const successOverlay = document.getElementById('orderSuccessOverlay');
            const successDismissBtn = document.getElementById('successDismissBtn');
            const orderNumberEl = document.getElementById('successOrderNumber');

            if (dispatchBtn && successOverlay) {
                dispatchBtn.addEventListener('click', async function() {
                    const items = window.HangarCart ? window.HangarCart.getItems() : [];
                    if (!items || items.length === 0) {
                        if (window.HangarCart) {
                            window.HangarCart.showToast('MANIFEST EMPTY', 'Requisition Mobile Suit units before dispatching.', true);
                        }
                        return;
                    }

                    // Format payload: ONLY product_id and quantity (never client-supplied prices)
                    const payloadItems = items.map(function(item) {
                        return {
                            product_id: parseInt(item.id, 10),
                            quantity: parseInt(item.quantity, 10) || 1
                        };
                    });

                    const promoCode = (window.HangarCart && window.HangarCart.activePromo) ? window.HangarCart.activePromo : '';

                    // UI Loading State
                    dispatchBtn.disabled = true;
                    const originalBtnContent = dispatchBtn.innerHTML;
                    dispatchBtn.innerHTML = '<span>TRANSMITTING ORDER...</span>';

                    try {
                        const apiUrl = (window.HANGAR_PATHS && window.HANGAR_PATHS.apiCheckout) ? window.HANGAR_PATHS.apiCheckout : 'api_checkout.php';
                        const response = await fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'checkout',
                                items: payloadItems,
                                promo_code: promoCode
                            })
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            if (orderNumberEl) {
                                orderNumberEl.textContent = `MANIFEST ORDER #${result.order_code}`;
                            }

                            successOverlay.classList.add('show');
                            successOverlay.setAttribute('aria-hidden', 'false');
                            document.body.style.overflow = 'hidden';

                            // Clear cart upon verified server-side order dispatch
                            if (window.HangarCart) {
                                localStorage.removeItem('hangar_cart_manifest');
                                localStorage.removeItem('hangar_cart_promo');
                                localStorage.removeItem('hangar_cart_promo_meta');
                                window.HangarCart.items = [];
                                window.HangarCart.activePromo = null;
                                window.HangarCart.promoDetails = null;
                                window.HangarCart.saveCart(false);
                                window.HangarCart.updateNavBadges();
                                window.HangarCart.renderDedicatedPage();
                            }
                        } else {
                            const errorMsg = result.message || 'Dispatch authorization failed. Please try again.';
                            if (window.HangarCart) {
                                window.HangarCart.showToast('DISPATCH REJECTED', errorMsg, true);
                            } else {
                                alert(errorMsg);
                            }
                        }
                    } catch (err) {
                        console.error('Checkout error:', err);
                        if (window.HangarCart) {
                            window.HangarCart.showToast('TRANSMISSION ERROR', 'Failed to communicate with Hangar Command.', true);
                        } else {
                            alert('Network error connecting to logistics dispatch.');
                        }
                    } finally {
                        dispatchBtn.disabled = false;
                        dispatchBtn.innerHTML = originalBtnContent;
                    }
                });
            }

            // Dismiss by button
            if (successDismissBtn && successOverlay) {
                successDismissBtn.addEventListener('click', function() {
                    window.location.href = '../index.php';
                });
            }

            // Dismiss by clicking backdrop
            if (successOverlay) {
                successOverlay.addEventListener('click', function(e) {
                    if (e.target === successOverlay) {
                        successOverlay.classList.remove('show');
                        successOverlay.setAttribute('aria-hidden', 'true');
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
// THE HANGAR - GUND-ORDER SYSTEM
// DEDICATED CART & SUPPLY REQUISITION DECK (cart.php)
// Redesigned to align with Section 6 (Model Kits) & Section 7 (Static Banner) design language.

require_once __DIR__ . '/../../shared/bootstrap.php';
extract(hangarBootstrap());
require_once __DIR__ . '/../../shared/db.php'; // exposes hangarPaymentConfig() for the GCash QR path
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CART // SUPPLY MANIFEST | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../shared/hud-design.css?v=<?php echo time(); ?>">
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

        .mainCartDeck {
            flex: 1;
            width: 100%;
        }

        /* Order Success Modal - Minimal & Clean */
        .orderSuccessOverlay {
            position: fixed;
            inset: 0;
            background: rgba(8, 8, 8, 0.88);
            backdrop-filter: blur(8px);
            z-index: 100020;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: all 0.25s ease;
        }

        .orderSuccessOverlay.show {
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
        }

        .orderSuccessDialog {
            position: relative;
            background: #11141a;
            border: 1px solid var(--brand-cyan, #3FC4E1);
            padding: 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            color: #ffffff;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
            transform: scale(0.96);
            transition: transform 0.25s ease;
        }

        .orderSuccessOverlay.show .orderSuccessDialog {
            transform: scale(1);
        }

        .successOrderTitle {
            font-family: var(--font-heading, 'Poppins', sans-serif);
            font-size: 1.4rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .successOrderCode {
            font-family: var(--font-system, 'Poppins', sans-serif);
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--brand-cyan, #3FC4E1);
            margin-bottom: 1.25rem;
            letter-spacing: 1px;
        }

        .successOrderDesc {
            font-size: 0.88rem;
            color: #9ea4b0;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .successDismissBtn {
            display: inline-block;
            width: 100%;
            padding: 0.9rem 1.5rem;
            background: var(--brand-cyan, #3FC4E1);
            color: #080808;
            border: 1px solid var(--brand-cyan, #3FC4E1);
            font-family: var(--font-heading, 'Poppins', sans-serif);
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .successDismissBtn:hover {
            background: #ffffff;
            border-color: #ffffff;
        }

        /* Track order + dismiss pair inside the success modal */
        .successOrderActions {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            width: 100%;
        }

        .successTrackBtn {
            display: inline-block;
            width: 100%;
            padding: 0.9rem 1.5rem;
            background: transparent;
            color: var(--brand-cyan, #3FC4E1);
            border: 1px solid var(--brand-cyan, #3FC4E1);
            font-family: var(--font-heading, 'Poppins', sans-serif);
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
        }

        .successTrackBtn:hover {
            background: var(--brand-cyan, #3FC4E1);
            color: #080808;
        }
    </style>
</head>
<body>

    <!-- SECTION 0: TOP NAVBAR -->
    <header class="headerContainer headerStatic">
        <div class="headerLeft">
            <a href="../index.php" class="navItem navBtnHamburger" aria-label="Menu">
                <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
            </a>
            <a href="../index.php" class="navItem navLink">HOME</a>
            <a href="../search/search.php" class="navItem navLink">PRODUCTS</a>
        </div>

        <div class="headerCenter">
            <a href="../index.php" class="navBrand" aria-label="THE HANGAR Home">
                <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
            </a>
        </div>

        <div class="headerRight">
            <a href="cart.php" class="navItem navLink navCart" style="color: var(--brand-cyan);">CART</a>
            <a href="../orders/orders.php" class="navItem navLink" style="color: var(--brand-cyan);">MY ORDERS</a>
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
            <a href="../search/search.php" class="navItem navBtnSearch" aria-label="Search">
                <img src="<?php echo $buttonsPath; ?>/Search.svg" alt="Search">
            </a>
        </div>
    </header>

    <!-- SECTION HEADER STRIP - Matching SECTION 6: MODEL KITS -->
    <div class="headerContainerMK">
        <div class="headerLeftMK">
            <a href="../index.php" class="spBackLink">
                &larr; STOREFRONT
            </a>
        </div>
        <div class="headerCenterMK">
            <h2>CART</h2>
        </div>
        <div class="headerRightMK">
            <div class="sliderCounterMK">
                <span id="cartHeaderActiveCount" class="activeCount">00</span>
                <span class="divider">|</span>
                <span id="cartHeaderTotalCount" class="totalCount">00 UNITS</span>
            </div>
        </div>
    </div>

    <!-- MAIN VIEWPORT CONTAINER - Matching SECTION 6 Layout Grid -->
    <div class="mainCartDeck">
        <main class="cartPageContainerMK">
            
            <!-- Empty State (Matching Section 6 emptyStateMK) -->
            <div class="emptyStateMK" id="cartPageEmptyMessage" style="display: none;">
                <h3 class="emptyTitleMK">SUPPLY MANIFEST EMPTY</h3>
                <p class="emptySubMK">No Mobile Suits or armaments have been allocated to your hangar requisitions.</p>
                <div class="emptyActionsMK">
                    <a href="../search/search.php" class="filterBtnMK active">ACCESS CATALOGUE</a>
                    <a href="../index.php" class="filterBtnMK">BACK TO STOREFRONT</a>
                </div>
            </div>

            <!-- Active Cart Grid -->
            <div class="cartPageGridMK" id="cartPageContent">
                
                <!-- Left: Requisitioned Items Cards List -->
                <div class="cartItemsSectionMK">
                    <div class="cartItemsHeaderMK">
                        <div class="cartItemsHeaderTitle">REQUISITIONED UNITS</div>
                        <div class="cartItemsHeaderActions">
                            <a href="../search/search.php" class="filterBtnMK">+ ADD MORE UNITS</a>
                            <button type="button" class="filterBtnMK" id="cartPageClearBtn">PURGE ALL</button>
                        </div>
                    </div>

                    <!-- Dynamic Card List Populated by cart.js -->
                    <div class="cartItemsListMK" id="cartPageItemsList">
                        <!-- Rendered by window.HangarCart.renderDedicatedPage() -->
                    </div>
                </div>

                <!-- Right: Summary & Checkout Deck (Static Section 7 Banner Layout) -->
                <div class="cartSummarySectionMK">
                    <div class="hangarStaticBanner cartOrderBannerMK">
                        
                        <div class="cartBannerHeaderMK">
                            <span class="staticBannerBadge">ORDER SETTLEMENT &bull; DISPATCH READY</span>
                            <h3 class="staticBannerTitle">ORDER SUMMARY</h3>
                        </div>

                        <div class="cartSummaryRowsMK">
                            <div class="cartSummaryRowMK">
                                <span class="summaryLabelMK">MANUFACTURE SUBTOTAL:</span>
                                <span class="summaryValMK" id="cartPageSubtotal">—</span>
                            </div>
                            <div class="cartSummaryRowMK discountRowMK" id="cartPageDiscountRow" style="display: none;">
                                <span class="summaryLabelMK">PILOT CLEARANCE PROMO:</span>
                                <span class="summaryValMK" id="cartPageDiscount">- ₱ 0.00</span>
                            </div>
                            <div class="cartSummaryRowMK">
                                <span class="summaryLabelMK">LOGISTICS DISPATCH:</span>
                                <span class="summaryValMK" id="cartPageShipping">—</span>
                            </div>
                            <div class="cartSummaryRowMK grandTotalRowMK">
                                <span class="summaryLabelMK">GRAND TOTAL:</span>
                                <span class="summaryValMK grandTotalMK" id="cartPageTotal">—</span>
                            </div>
                        </div>

                        <!-- Promo Code Input -->
                        <div class="cartPromoAreaMK">
                            <label for="cartPagePromoInput" class="cartPromoLabelMK">PILOT CLEARANCE CODE</label>
                            <div class="cartPromoInputRowMK">
                                <input type="text" id="cartPagePromoInput" class="cartPromoInputMK" placeholder="E.G. PILOT10" autocomplete="off" autocapitalize="characters" spellcheck="false">
                                <button type="button" id="cartPagePromoBtn" class="cartPromoBtnMK">APPLY</button>
                            </div>
                            <div id="cartPagePromoStatus" class="cartPromoStatusMK" style="display: none;"></div>
                        </div>

                        <!-- Contact & Payment Deck (GCash QR scan-to-pay) -->
                        <div class="cartContactDeckMK">
                            <label class="cartPromoLabelMK" for="coCustomerName">PILOT CONTACT DETAILS</label>
                            <input type="text" id="coCustomerName" class="cartPromoInputMK" placeholder="Full Name *" autocomplete="name">
                            <input type="email" id="coCustomerEmail" class="cartPromoInputMK" placeholder="Email Address *" autocomplete="email">
                            <input type="tel" id="coCustomerPhone" class="cartPromoInputMK" placeholder="Mobile Number *" autocomplete="tel">
                            <textarea id="coShippingAddress" class="cartPromoInputMK" placeholder="Delivery Address (street, city, province) *" rows="2" autocomplete="street-address"></textarea>

                            <label class="cartPromoLabelMK" for="coLogistics">DELIVERY LOGISTICS (COURIER)</label>
                            <select id="coLogistics" class="cartPromoInputMK">
                                <option value="J&T Express">J&T Express</option>
                                <option value="NinjaVan">NinjaVan</option>
                            </select>

                            <div class="payDividerMK"></div>

                            <label class="cartPromoLabelMK">PAYMENT METHOD</label>
                            <div class="cartPayMethodPickerMK">
                                <label class="payMethodOptionMK">
                                    <input type="radio" name="coPaymentMethod" value="gcash" checked>
                                    <span class="payOptionLabelMK"><strong>GCash</strong>&nbsp;&middot;&nbsp;Pay now via QR scan</span>
                                </label>
                                <label class="payMethodOptionMK">
                                    <input type="radio" name="coPaymentMethod" value="cod">
                                    <span class="payOptionLabelMK"><strong>Cash on Delivery</strong>&nbsp;&middot;&nbsp;Pay the courier when it arrives</span>
                                </label>
                            </div>

                            <div id="coGcashBlock">
                                <label class="cartPromoLabelMK" for="coGcashRef">PAY VIA GCASH QR (Amount shown at order total)</label>
                                <div class="gcashQrBoxMK">
                                <?php
                                    $gcashCfg = hangarPaymentConfig();
                                    $gcashQrPdo = getDBConnection();
                                    $gcashQrUrl = getSetting($gcashQrPdo, 'gcash_qr_url', '');
                                    if ($gcashQrUrl === '') {
                                        $gcashQrUrl = trim((string)($gcashCfg['qr_image_url'] ?? ''));
                                    }
                                    $gcashQrExists = ($gcashQrUrl !== '' && is_file(__DIR__ . '/../../' . $gcashQrUrl));
                                ?>
                                <?php if ($gcashQrExists): ?>
                                    <img id="coGcashQr" class="gcashQrImageMK" src="../../<?php echo htmlspecialchars($gcashQrUrl); ?>" alt="GCash QR">
                                <?php else: ?>
                                    <div class="gcashQrMissingMK">GCash QR not set yet.<br>Upload it in <strong>Admin &rarr; GCash &amp; Payments</strong>.</div>
                                <?php endif; ?>
                            </div>
                            <div class="cartPayMethodRowMK">
                                <span class="payMethodBadgeMK">GCASH</span>
                                <span class="payMethodNoteMK">1. Open GCash &nbsp;&bull;&nbsp; 2. Scan QR &nbsp;&bull;&nbsp; 3. Pay exact total &nbsp;&bull;&nbsp; 4. Paste reference below</span>
                            </div>
                            <input type="text" id="coGcashRef" class="cartPromoInputMK" placeholder="GCash reference / transaction number *" autocomplete="off">
                            </div>

                            <div id="coCodBlock" style="display: none;">
                                <label class="cartPromoLabelMK">CASH ON DELIVERY</label>
                                <div class="codNoteMK">Pay the exact order total to the courier when your items arrive. No upfront payment required.</div>
                            </div>
                        </div>

                        <!-- Dispatch Button -->
                        <button type="button" class="cartDispatchBtnMK" id="cartFinalizeDispatchBtn">
                            <span>INITIATE ORDER DISPATCH</span>
                            &rarr;
                        </button>

                        <!-- Logistics Trust Strip -->
                        <div class="cartTrustStripMK">
                            <div class="trustTextMK">OFFICIAL BANDAI SPIRITS LOGISTICS</div>
                            <div class="trustLogosMK">
                                <img src="<?php echo $footerPath; ?>/Logistics/logo.5f09a646.png" alt="J&T">
                                <img src="<?php echo $footerPath; ?>/Logistics/ninjavan-logo-white.webp" alt="NinjaVan" class="invertLogo">
                                <img src="<?php echo $footerPath; ?>/banks/BDO_50th_362_x_126_px_reverse (1).svg" alt="BDO" class="invertLogo">
                                <img src="<?php echo $footerPath; ?>/banks/BPI_RT__96x42_header_Reverse.svg" alt="BPI" class="invertLogo">
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- ORDER SUCCESS CONFIRMATION MODAL -->
    <div id="orderSuccessOverlay" class="orderSuccessOverlay" aria-hidden="true">
        <div class="orderSuccessDialog">
            <h2 class="successOrderTitle">SORTIE DISPATCH AUTHORIZED!</h2>
            <div class="successOrderCode" id="successOrderNumber">MANIFEST ORDER #HGR-984271</div>
            <p class="successOrderDesc" id="successOrderDesc">
                Your Gundam Mobile Suit units have been logged into the Hangar distribution queue. Logistics tracking will be relayed to your registered pilot terminal.
            </p>
            <div class="successOrderActions">
                <button type="button" class="successDismissBtn" id="successDismissBtn">
                    RETURN TO STOREFRONT
                </button>
                <a href="../orders/orders.php" class="successTrackBtn" id="successTrackBtn">
                    TRACK MY ORDER
                </a>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../footer.php'; ?>

    <!-- GUND-ORDER SYSTEM PATHS & CART SCRIPT -->
    <script>
        window.HANGAR_PATHS = {
            cartPage: 'cart.php',
            searchPage: '../search/search.php',
            apiSearch: '../search/api_search.php',
            productDetails: '../product-details.php',
            promotionalBase: '../../promotional',
            apiCheckout: 'api_checkout.php'
        };
    </script>
    <?php $_cartAssetPrefix = './'; require_once __DIR__ . '/cart_modal.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dispatchBtn = document.getElementById('cartFinalizeDispatchBtn');
            const successOverlay = document.getElementById('orderSuccessOverlay');
            const successDismissBtn = document.getElementById('successDismissBtn');
            const orderNumberEl = document.getElementById('successOrderNumber');

            // Payment method (GCash / Cash on Delivery) toggle elements — shared between the
            // dispatch handler and the UI switcher below.
            const coGcashBlockEl = document.getElementById('coGcashBlock');
            const coCodBlockEl = document.getElementById('coCodBlock');
            const coPaymentEls = document.querySelectorAll('input[name="coPaymentMethod"]');

            // Toggle GCash vs Cash on Delivery sections when the payment method changes
            function syncPaymentMethodUI() {
                const sel = document.querySelector('input[name="coPaymentMethod"]:checked');
                const isCod = sel ? sel.value === 'cod' : false;
                if (coGcashBlockEl) coGcashBlockEl.style.display = isCod ? 'none' : '';
                if (coCodBlockEl) coCodBlockEl.style.display = isCod ? '' : 'none';
            }
            if (coPaymentEls.length > 0) {
                coPaymentEls.forEach(function(r) { r.addEventListener('change', syncPaymentMethodUI); });
            }
            syncPaymentMethodUI();

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

                    // Collect + validate pilot contact / delivery details (GCash checkout)
                    const coNameEl = document.getElementById('coCustomerName');
                    const coEmailEl = document.getElementById('coCustomerEmail');
                    const coPhoneEl = document.getElementById('coCustomerPhone');
                    const coAddressEl = document.getElementById('coShippingAddress');
                    const coGcashRefEl = document.getElementById('coGcashRef');
                    const coLogisticsEl = document.getElementById('coLogistics');

                    const customerName = coNameEl ? coNameEl.value.trim() : '';
                    const customerEmail = coEmailEl ? coEmailEl.value.trim() : '';
                    const customerPhone = coPhoneEl ? coPhoneEl.value.trim() : '';
                    const shippingAddress = coAddressEl ? coAddressEl.value.trim() : '';
                    const gcashRef = coGcashRefEl ? coGcashRefEl.value.trim() : '';
                    const logistics = coLogisticsEl ? coLogisticsEl.value.trim() : 'J&T Express';
                    const paymentSel = document.querySelector('input[name="coPaymentMethod"]:checked');
                    const paymentMethod = paymentSel ? paymentSel.value : 'gcash';

                    if (!customerName || !customerEmail || !customerPhone || !shippingAddress) {
                        if (window.HangarCart) {
                            window.HangarCart.showToast('DETAILS REQUIRED', 'Complete your contact & delivery details before dispatching.', true);
                        } else {
                            alert('Please complete your contact & delivery details before dispatching.');
                        }
                        return;
                    }
                    if (paymentMethod !== 'cod' && !gcashRef) {
                        if (window.HangarCart) {
                            window.HangarCart.showToast('GCASH REFERENCE REQUIRED', 'Pay via the QR code, then paste your GCash reference number.', true);
                        } else {
                            alert('Please enter your GCash reference number after paying via the QR code.');
                        }
                        return;
                    }

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
                                promo_code: promoCode,
                                payment_method: paymentMethod,
                                customer_name: customerName,
                                customer_email: customerEmail,
                                customer_phone: customerPhone,
                                shipping_address: shippingAddress,
                                logistics: logistics,
                                gcash_ref: paymentMethod === 'cod' ? '' : gcashRef
                            })
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            if (orderNumberEl) {
                                orderNumberEl.textContent = `MANIFEST ORDER #${result.order_code}`;
                            }

                            // Reflect payment outcome in confirmation dialog (GCash / Cash on Delivery)
                            const successDesc = document.getElementById('successOrderDesc');
                            if (successDesc) {
                                if (result.payment_status === 'cod') {
                                    successDesc.textContent = `Order #${result.order_code} confirmed with Cash on Delivery (${'₱' + (result.total || '0.00')}). Pay the exact amount to the courier upon delivery. Your Gundam Mobile Suit units are logged into the Hangar distribution queue. Tracking will be relayed to your registered pilot terminal.`;
                                } else if (result.payment_status === 'paid') {
                                    successDesc.textContent = `GCash payment authorized (Reference: ${result.payment_ref}). Your Gundam Mobile Suit units are PAID and logged into the Hangar distribution queue. Logistics tracking will be relayed to your registered pilot terminal.`;
                                } else if (result.payment_status === 'payment_pending') {
                                    successDesc.textContent = `Order #${result.order_code} received. Your payment of ${'₱' + (result.total || '0.00')} is being verified against GCash reference ${result.gcash_ref || '—'}. We'll confirm once checked.`;
                                } else {
                                    successDesc.textContent = `Your Gundam Mobile Suit units are logged into the Hangar queue. Payment is pending confirmation. Tracking will be relayed to your registered pilot terminal.`;
                                }
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

            // Track the freshly dispatched order in MY ORDERS
            const successTrackBtn = document.getElementById('successTrackBtn');
            if (successTrackBtn && successOverlay) {
                successTrackBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = this.getAttribute('href');
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

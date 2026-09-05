<?php
// THE HANGAR - GUND-ORDER SYSTEM
// SUPPLY CART HUD MODAL & DRAWER OVERLAY COMPONENT (Self-contained & Reusable)
// $_cartAssetPrefix should be set by the including page (e.g. 'cart/' from homepage/, './' from cart/)
if (!isset($_cartAssetPrefix)) $_cartAssetPrefix = './';
?>
<!-- Cart Component Modular Stylesheet -->
<link rel="stylesheet" href="<?php echo $_cartAssetPrefix; ?>cart.css?v=<?php echo time(); ?>">

<!-- GUND-ORDER SYSTEM SUPPLY CART HUD OVERLAY -->
<div id="hangarCartOverlay" class="hangarCartOverlay" aria-hidden="true">
    <div class="hangarCartBackdrop" id="hangarCartBackdrop"></div>
    
    <div class="hangarCartDrawer" role="dialog" aria-modal="true" aria-labelledby="cartHudTitle">
        <!-- Sci-Fi Corner Brackets -->
        <span class="hudCorner cornerTL"></span>
        <span class="hudCorner cornerBL"></span>

        <!-- HUD Header Strip -->
        <div class="cartHudHeader">
            <div class="cartHudSystem">
                <span class="cartRadarPulse"></span>
                <span id="cartHudTitle" class="cartHudCode">G.O.S SUPPLY MANIFEST // CART</span>
            </div>
            <div class="cartHudControls">
                <span class="cartKeyHint"><kbd>ESC</kbd></span>
                <button type="button" class="cartCloseBtn" id="cartCloseBtn" aria-label="Close cart">&times;</button>
            </div>
        </div>

        <!-- Status & Clear Strip -->
        <div class="cartStatusStrip">
            <div class="cartStatusLeft">
                <span class="cartStatusDot"></span>
                <span id="cartStatusCount">STANDBY</span>
            </div>
            <button type="button" class="cartClearAllBtn" id="cartClearAllBtn">PURGE ALL</button>
        </div>

        <!-- Dynamic Cart Items Container -->
        <div class="cartBody" id="hangarCartBody" role="region" aria-live="polite">
            <!-- Populated dynamically via cart.js -->
        </div>

        <!-- Order Summary & Checkout Footer -->
        <div class="cartHudFooter" id="hangarCartFooter">
            <!-- Populated dynamically via cart.js -->
        </div>
    </div>
</div>

<!-- Cart Controller Script -->
<script src="<?php echo $_cartAssetPrefix; ?>cart.js?v=<?php echo time(); ?>"></script>

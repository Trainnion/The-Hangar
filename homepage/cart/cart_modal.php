<?php
// THE HANGAR - GUND-ORDER SYSTEM
// SUPPLY CART CLIENT SCRIPTS & ASSETS (Drawer retired — nav badge & storage sync)
if (!isset($_cartAssetPrefix)) $_cartAssetPrefix = './';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Per-account cart isolation: localStorage is browser-wide, so the cart keys
// must be namespaced by the logged-in user's id (0 = guest bucket).
$_cartUserId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
?>
<!-- Cart Component Modular Stylesheet -->
<link rel="stylesheet" href="<?php echo $_cartAssetPrefix; ?>cart.css?v=<?php echo time(); ?>">

<!-- Cart Controller Script -->
<script>window.HANGAR_USER_ID = <?php echo $_cartUserId; ?>;</script>
<script src="<?php echo $_cartAssetPrefix; ?>cart.js?v=<?php echo time(); ?>"></script>

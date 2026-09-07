<?php
// THE HANGAR - GUND-ORDER SYSTEM
// SUPPLY CART CLIENT SCRIPTS & ASSETS (Drawer retired — nav badge & storage sync)
if (!isset($_cartAssetPrefix)) $_cartAssetPrefix = './';
?>
<!-- Cart Component Modular Stylesheet -->
<link rel="stylesheet" href="<?php echo $_cartAssetPrefix; ?>cart.css?v=<?php echo time(); ?>">

<!-- Cart Controller Script -->
<script src="<?php echo $_cartAssetPrefix; ?>cart.js?v=<?php echo time(); ?>"></script>

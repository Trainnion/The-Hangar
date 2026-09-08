<?php
// THE HANGAR - GUND-ORDER SYSTEM
// SHARED HAMBURGER DRAWER MENU (shared/menu.php)
// Self-contained slide-in navigation drawer. Include once per page right
// after <body>; it wires up every .navBtnHamburger link on the page.
// Uses absolute URLs computed from the current script path, so it works
// from any folder depth without per-page path tweaks.

$menuScriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
// Project root = strip the /homepage/... part of the path
$menuRoot = preg_replace('#/homepage(/[a-z0-9_\-]+)*$#i', '', $menuScriptDir);
if ($menuRoot === $menuScriptDir) {
    $menuRoot = $menuScriptDir; // pages not under /homepage (e.g. login)
}

$menuIsLoggedIn = !empty($isLoggedIn) || !empty($_SESSION['user_id']) || !empty($_SESSION['hangar_admin_logged']);
$menuRole       = $userRole ?? (!empty($_SESSION['hangar_admin_logged']) ? 'admin' : null);
$menuUser       = $userName ?? ($_SESSION['username'] ?? ($_SESSION['hangar_admin_user'] ?? 'Pilot'));

$menuLinks = [
    ['href' => $menuRoot . '/homepage/index.php',           'label' => 'HOME'],
    ['href' => $menuRoot . '/homepage/search/search.php',   'label' => 'PRODUCTS'],
    ['href' => $menuRoot . '/homepage/cart/cart.php',       'label' => 'CART'],
    ['href' => $menuRoot . '/homepage/orders/orders.php',   'label' => 'MY ORDERS'],
];
if ($menuIsLoggedIn) {
    $menuLinks[] = ['href' => $menuRoot . '/homepage/profile/profile.php', 'label' => 'PROFILE'];
    if ($menuRole === 'admin') {
        $menuLinks[] = ['href' => $menuRoot . '/admin/index.php', 'label' => 'ADMIN PANEL'];
    }
}
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
?>
<div class="menuOverlay" id="menuOverlay" hidden></div>
<aside class="menuDrawer" id="menuDrawer" aria-hidden="true" aria-label="Navigation menu">
    <div class="menuDrawerHead">
        <img src="<?php echo $menuRoot; ?>/promotional/Asset 8.png" alt="THE HANGAR" class="menuDrawerLogo"
             onerror="this.style.display='none'">
        <button type="button" class="menuDrawerClose" id="menuDrawerClose" aria-label="Close menu">&times;</button>
    </div>
    <nav class="menuDrawerNav">
        <?php foreach ($menuLinks as $menuLink): ?>
            <a href="<?php echo htmlspecialchars($menuLink['href']); ?>" class="menuDrawerLink"><?php echo htmlspecialchars($menuLink['label']); ?></a>
        <?php endforeach; ?>
        <?php if ($menuIsLoggedIn): ?>
            <a href="<?php echo $menuRoot; ?>/login/logout.php" class="menuDrawerLink menuDrawerLinkMuted">LOG OUT</a>
        <?php else: ?>
            <a href="<?php echo $menuRoot; ?>/login/index.php" class="menuDrawerLink menuDrawerLinkMuted">LOG IN</a>
        <?php endif; ?>
    </nav>
    <?php if ($menuIsLoggedIn): ?>
        <div class="menuDrawerFoot">PILOT: <?php echo htmlspecialchars($menuUser); ?></div>
    <?php endif; ?>
</aside>

<style>
    .menuOverlay {
        position: fixed; inset: 0; background: rgba(8, 8, 8, 0.6);
        backdrop-filter: blur(3px); z-index: 99990; opacity: 0;
        transition: opacity 0.25s ease;
    }
    .menuOverlay.open { opacity: 1; }
    .menuDrawer {
        position: fixed; top: 0; left: 0; bottom: 0; width: 280px; max-width: 85vw;
        background: #11141a; border-right: 1px solid var(--brand-cyan, #3FC4E1);
        box-shadow: 12px 0 40px rgba(0,0,0,0.55);
        z-index: 99991; transform: translateX(-100%);
        transition: transform 0.28s ease; display: flex; flex-direction: column;
        font-family: var(--font-heading, 'Poppins', sans-serif); color: #fff;
    }
    .menuDrawer.open { transform: translateX(0); }
    .menuDrawerHead {
        display: flex; align-items: center; justify-content: space-between;
        padding: 1.1rem 1.2rem; border-bottom: 1px solid rgba(63, 196, 225, 0.25);
    }
    .menuDrawerLogo { height: 30px; width: auto; }
    .menuDrawerClose {
        background: none; border: none; color: #fff; font-size: 1.8rem;
        line-height: 1; cursor: pointer; padding: 0 0.2rem;
    }
    .menuDrawerClose:hover { color: var(--brand-cyan, #3FC4E1); }
    .menuDrawerNav { display: flex; flex-direction: column; padding: 1rem 0; flex: 1; }
    .menuDrawerLink {
        display: block; padding: 0.95rem 1.4rem; color: #e8eef2; text-decoration: none;
        font-size: 0.85rem; font-weight: 700; letter-spacing: 0.08em;
        border-left: 3px solid transparent; transition: all 0.15s ease;
    }
    .menuDrawerLink:hover, .menuDrawerLink:focus {
        color: var(--brand-cyan, #3FC4E1); border-left-color: var(--brand-cyan, #3FC4E1);
        background: rgba(63, 196, 225, 0.08);
    }
    .menuDrawerLinkMuted { color: #8a97a3; }
    .menuDrawerFoot {
        padding: 1rem 1.4rem; border-top: 1px solid rgba(63, 196, 225, 0.25);
        font-size: 0.72rem; letter-spacing: 0.08em; color: var(--brand-cyan, #3FC4E1);
    }
</style>
<script>
    // Wait for DOMContentLoaded: this include renders BEFORE the page header,
    // so the .navBtnHamburger buttons do not exist yet at parse time.
    document.addEventListener('DOMContentLoaded', function () {
    (function () {
        var overlay = document.getElementById('menuOverlay');
        var drawer  = document.getElementById('menuDrawer');
        var closeBtn = document.getElementById('menuDrawerClose');
        if (!overlay || !drawer) return;

        function openMenu() {
            overlay.hidden = false;
            requestAnimationFrame(function () {
                overlay.classList.add('open');
                drawer.classList.add('open');
            });
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
        function closeMenu() {
            overlay.classList.remove('open');
            drawer.classList.remove('open');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            setTimeout(function () { overlay.hidden = true; }, 260);
        }

        // Wire every hamburger icon on the page
        document.querySelectorAll('.navBtnHamburger').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                openMenu();
            });
        });
        overlay.addEventListener('click', closeMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('open')) closeMenu();
        });
        // Close when a link inside the drawer is clicked (lets navigation proceed)
        drawer.querySelectorAll('.menuDrawerLink').forEach(function (a) {
            a.addEventListener('click', closeMenu);
        });
    })();
    });
</script>

<?php
// THE HANGAR - G.O.S ADMIN
// SHARED SIDEBAR NAVIGATION
// Every admin page renders the SAME complete module list through this include,
// so no feature is ever missing from the sidebar. The current page is detected
// from the running script name and highlighted with the .active state.

$sidebarCurrent = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');

$sidebarLinks = [
    ['href' => 'index.php',     'label' => 'DASHBOARD',           'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>'],
    ['href' => 'products.php',  'label' => 'PRODUCTS',            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>'],
    ['href' => 'categories.php', 'label' => 'CATEGORIES',          'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="6" x2="15" y2="6"></line><line x1="9" y1="12" x2="15" y2="12"></line><line x1="9" y1="18" x2="15" y2="18"></line></svg>'],
    ['href' => 'orders.php',    'label' => 'ORDERS',              'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h12v16a2 2 0 0 1-2-2M6.5 6l4 4M8 8l-2 2"></path><polyline points="3 4 9 4 9 14 3 14"></polyline><line x1="5" y1="6" x2="13" y2="6"></line></svg>'],
    ['href' => 'sliders.php',   'label' => 'SLIDERS (SEC 1, 2, 7)', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>'],
    ['href' => 'featured.php',  'label' => 'FEATURED',            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'],
    ['href' => 'promos.php',    'label' => 'PROMO CODES',         'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>'],
    ['href' => 'gcash.php',     'label' => 'GCASH &amp; PAYMENTS', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>'],
    ['href' => 'pages.php',     'label' => 'STATIC PAGES',        'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line></svg>'],
    ['href' => 'footer.php',    'label' => 'FOOTER CONTENT',      'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line><line x1="8" y1="15" x2="16" y2="15"></line></svg>'],
];
?>
<!-- SIDEBAR -->
<aside class="adminSidebar">
    <div>
        <div class="sidebarHeader">
            <a href="index.php" class="sidebarBrand">
                <img src="../promotional/Asset 8.png" alt="THE HANGAR" class="sidebarLogoImg">
                <div class="sidebarBadge">
                    <span>G.O.S ADMIN v2.6</span>
                </div>
            </a>
        </div>

        <nav class="sidebarNav">
            <?php foreach ($sidebarLinks as $sidebarLink): ?>
                <a href="<?php echo htmlspecialchars($sidebarLink['href']); ?>" class="navLink<?php echo $sidebarCurrent === $sidebarLink['href'] ? ' active' : ''; ?>">
                    <?php echo $sidebarLink['icon']; ?>
                    <span><?php echo $sidebarLink['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="sidebarFooter">
        <a href="../homepage/" target="_blank" class="btnStorefront">
            <span>VIEW STOREFRONT</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
        </a>
        <a href="logout.php" class="btnLogout">
            <span>LOGOUT PILOT</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </a>
    </div>
</aside>
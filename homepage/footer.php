<?php
// THE HANGAR - GUND-ORDER SYSTEM
// REUSABLE STOREFRONT FOOTER COMPONENT
// Text content (contact number/email) is admin-editable via the `settings` table
// (ADMIN -> FOOTER CONTENT). The CUSTOMER SERVICE / ABOUT THE HANGAR links are
// fixed to the built-in storefront pages (page.php?p=contact|privacy|about) whose
// text is admin-editable under ADMIN -> STATIC PAGES. Empty settings fall back
// to safe built-in defaults.

if (!isset($footerPath)) {
    $footerPath = is_dir('../../footer') ? '../../footer' : (is_dir('../footer') ? '../footer' : 'footer');
}
if (!isset($promotionalPath)) {
    $promotionalPath = is_dir('../../promotional') ? '../../promotional' : (is_dir('../promotional') ? '../promotional' : 'promotional');
}

// Reuse the caller's DB connection when available; bootstrap our own otherwise
$footerPdo = (isset($pdo) && $pdo) ? $pdo : null;
if (!$footerPdo) {
    require_once __DIR__ . '/../shared/db.php';
    try { $footerPdo = getDBConnection(); } catch (Exception $e) { $footerPdo = null; }
}

$footerSettings = [
    'contact_number' => '',
    'contact_email'  => '',
    'social_fb'      => '#',
    'social_ig'      => '#',
    'social_x'       => '#',
    'payment_logo'   => '',
];
if ($footerPdo) {
    foreach (array_keys($footerSettings) as $fKey) {
        $footerSettings[$fKey] = (string)getSetting($footerPdo, 'footer_' . $fKey, $footerSettings[$fKey]);
    }
}

// Depth-aware prefix for managed asset paths (assets/uploads/...), derived from $footerPath
$footerRootPrefix = rtrim(str_replace('footer', '', $footerPath), '/');
$footerRootPrefix = ($footerRootPrefix === '') ? '' : $footerRootPrefix . '/';

// Resolve a footer link for the current page depth: absolute URLs / anchors /
// tel: / mailto: pass through untouched, relative paths get the root prefix.
$footerLink = function (string $url) use ($footerRootPrefix): string {
    $u = trim($url);
    if ($u === '' || $u === '#') return '#';
    if (preg_match('~^(https?:)?//~i', $u)
        || strncasecmp($u, 'tel:', 4) === 0
        || strncasecmp($u, 'mailto:', 7) === 0) {
        return $u;
    }
    return $footerRootPrefix . $u;
};

$footerPaymentLogo = trim($footerSettings['payment_logo']) !== ''
    ? $footerRootPrefix . trim($footerSettings['payment_logo'])
    : $footerPath . '/banks/gcash.svg';
?>
<!-- FOOTER -->
<footer class="footerFT">
    <div class="footerContainerFT">
        
        <div class="footerLogoRowFT">
            <img src="<?php echo $promotionalPath; ?>/Asset 5.png" alt="THE HANGAR" class="footerLogoFT">
        </div>

        <div class="footerSectionsWrapperFT">
            
            <div class="footerRowFT footerRow1FT">
                <div class="footerColFT">
                    <h4 class="footerHeadingFT">CUSTOMER SERVICE</h4>
                    <ul class="footerLinksFT">
                        <li><a href="<?php echo htmlspecialchars($footerLink('homepage/page.php?p=contact')); ?>">contact us</a></li>
                        <?php if ($footerSettings['contact_number'] !== ''): ?>
                        <li><a href="tel:<?php echo htmlspecialchars(preg_replace('/[^\d+]/', '', $footerSettings['contact_number'])); ?>">contact number: <?php echo htmlspecialchars($footerSettings['contact_number']); ?></a></li>
                        <?php endif; ?>
                        <?php if ($footerSettings['contact_email'] !== ''): ?>
                        <li><a href="mailto:<?php echo htmlspecialchars($footerSettings['contact_email']); ?>">email: <?php echo htmlspecialchars($footerSettings['contact_email']); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="footerColFT">
                    <h4 class="footerHeadingFT">ABOUT THE HANGAR</h4>
                    <ul class="footerLinksFT">
                        <li><a href="<?php echo htmlspecialchars($footerLink('homepage/page.php?p=privacy')); ?>">policy of privacy</a></li>
                        <li><a href="<?php echo htmlspecialchars($footerLink('homepage/page.php?p=about')); ?>">about us</a></li>
                    </ul>
                </div>

                <div class="footerColFT">
                    <h4 class="footerHeadingFT">PAYMENT</h4>
                    <div class="brandListFT paymentLogosFT">
                        <img src="<?php echo $footerPaymentLogo; ?>" alt="GCash" class="payLogoImg">
                    </div>
                </div>
            </div>

            <div class="footerRowFT footerRow2FT">
                <div class="footerColFT">
                    <h4 class="footerHeadingFT">LOGISTICS</h4>
                    <div class="brandListFT logisticsLogosFT">
                        <img src="<?php echo $footerPath; ?>/Logistics/logo.5f09a646.png" alt="J&amp;T Express" class="logisticsLogoImg">
                        <img src="<?php echo $footerPath; ?>/Logistics/ninjavan-logo-white.webp" alt="Ninja Van" class="logisticsLogoImg">
                    </div>
                </div>

                <div class="footerColFT socialColFT">
                    <h4 class="footerHeadingFT">FOLLOW US</h4>
                    <div class="socialListFT">
                        <a href="<?php echo htmlspecialchars($footerLink($footerSettings['social_fb'])); ?>" target="_blank" rel="noopener" class="socialItemFT">
                            <svg class="socialIconFT" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10 10 0 0 0 22 12.06C22 6.53 17.5 2.04 12 2.04Z"/></svg>
                            <span>thehangarmodelshop</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($footerLink($footerSettings['social_ig'])); ?>" target="_blank" rel="noopener" class="socialItemFT">
                            <svg class="socialIconFT" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            <span>@thehangarmodelshop</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($footerLink($footerSettings['social_x'])); ?>" target="_blank" rel="noopener" class="socialItemFT">
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
                ALL COPYRIGHTS RESERVED
            </div>
            <div class="officialLogosFT">
                <img src="<?php echo $footerPath; ?>/bandaiTamiya/images.jpg" alt="BANDAI NAMCO" class="partnerLogoBandaiNamco">
                <img src="<?php echo $footerPath; ?>/bandaiTamiya/Logo_Bandai.svg.webp" alt="BANDAI SPIRITS" class="partnerLogoBandaiSpirits">
                <img src="<?php echo $footerPath; ?>/bandaiTamiya/bandai.webp" alt="BANDAI" class="partnerLogoBandai">
            </div>
        </div>

    </div>
</footer>

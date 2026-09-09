<?php
// THE HANGAR - GUND-ORDER SYSTEM
// STATIC PAGE CONTENT (About / Privacy)
// Admin-editable via ADMIN -> STATIC PAGES. Content is stored as HTML fragments
// in the `settings` table (keys: page_about / page_privacy) and rendered by
// homepage/page.php. Empty settings fall back to the built-in defaults below.
//
// NOTE: Only an authenticated administrator can write this content (the admin
// panel is CSRF-protected), and hangarSanitizePageHtml() below is applied both
// when saving and when rendering, so the stored markup is kept to a safe subset.

/**
 * Keep only the friendly rich-text subset, stripping scripts, event handlers,
 * style/class attributes and javascript: links.
 *
 * @param string $html
 * @return string
 */
function hangarSanitizePageHtml(string $html): string {
    $allowed = '<h3><p><ul><ol><li><strong><b><em><i><u><a><br><span>';
    $clean   = strip_tags($html, $allowed);
    // Remove style / class / any on* (event handler) attributes.
    $clean = preg_replace('/\s(?:style|class|on\w+)\s*=\s*(["\'])[^"\']*\1/i', '', $clean);
    // Neutralize javascript: links.
    $clean = preg_replace('/\shref\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', ' href="#"', $clean);
    // Drop stray script/iframe/style tags (in case strip_tags left fragments).
    $clean = str_replace(['<script', '</script', '<iframe', '</iframe', '<style', '</style'], '', $clean);
    return trim($clean);
}

/**
 * Built-in markup for a static storefront page.
 *
 * @param string $p  'about' | 'privacy'
 * @return string
 */
function defaultStaticPageContent(string $p): string {
    if ($p === 'about') {
        return implode("\n", [
            '<h3>ABOUT THE HANGAR</h3>',
            '<p>THE HANGAR is a Philippine-based online store for Gundam model kits and hobby collectibles. We stock genuine Bandai kits — from entry-grade builds for first-timers to master-grade displays for seasoned builders — and deliver them straight to your door nationwide.</p>',
            '<h3>WHY SHOP WITH US</h3>',
            '<ul>',
            '    <li><strong>100% authentic kits</strong> — sourced directly from official Bandai distributors.</li>',
            '    <li><strong>Fresh stock</strong> — kits ship sealed in their original boxes.</li>',
            '    <li><strong>Nationwide delivery</strong> — dispatched via trusted couriers (J&amp;T Express / NinjaVan).</li>',
            '    <li><strong>Secure payment</strong> — pay instantly with GCash.</li>',
            '</ul>',
            '<h3>OUR MISSION</h3>',
            '<p>To make the gunpla hobby accessible to every Filipino builder — whether you\'re snapping your first runner or adding to a decades-old collection. Every kit we sell is one we\'d proudly build ourselves.</p>',
        ]);
    }
    if ($p === 'privacy') {
        return implode("\n", [
            '<h3>POLICY OF PRIVACY</h3>',
            '<p>THE HANGAR (&quot;we&quot;, &quot;our&quot;, &quot;us&quot;) respects your privacy and is committed to protecting the personal information you share with us. This policy explains what we collect, why, and how we protect it — in accordance with the Philippine Data Privacy Act of 2012 (RA 10173).</p>',
            '<h3>INFORMATION WE COLLECT</h3>',
            '<ul>',
            '    <li><strong>Account details</strong> — your callsign (username), full name, email address, and mobile number.</li>',
            '    <li><strong>Delivery details</strong> — the shipping address you provide when placing an order.</li>',
            '    <li><strong>Order history</strong> — the items you purchase and your payment references.</li>',
            '</ul>',
            '<h3>HOW WE USE YOUR INFORMATION</h3>',
            '<ul>',
            '    <li>To process and deliver your orders.</li>',
            '    <li>To contact you about your order status or concerns.</li>',
            '    <li>To secure your account and prevent fraudulent transactions.</li>',
            '</ul>',
            '<h3>WHAT WE NEVER DO</h3>',
            '<ul>',
            '    <li>We never sell or rent your personal information to third parties.</li>',
            '    <li>We never share your contact details with anyone beyond the courier handling your delivery.</li>',
            '</ul>',
            '<h3>DATA SECURITY &amp; RETENTION</h3>',
            '<p>Passwords are stored using industry-standard one-way encryption. Order and account records are retained only for as long as needed to serve you and comply with legal obligations. You may request access to, correction of, or deletion of your personal data by contacting us via the details on our Contact Us page.</p>',
            '<h3>COOKIES</h3>',
            '<p>We use essential browser cookies and local storage strictly to keep you signed in and remember your cart. No advertising trackers are used.</p>',
        ]);
    }
    return '';
}

/**
 * Read a static page's content for the storefront.
 *
 * Returns the admin-saved HTML when present, otherwise the built-in default.
 * Output is sanitized before being returned.
 *
 * @param ?PDO   $pdo  Shared DB connection (may be null — falls back to default).
 * @param string $p    'about' | 'privacy'
 * @return string
 */
function getStaticPageContent($pdo, string $p): string {
    $saved = $pdo ? trim((string)getSetting($pdo, 'page_' . $p, '')) : '';
    $html  = $saved !== '' ? $saved : defaultStaticPageContent($p);
    return hangarSanitizePageHtml($html);
}
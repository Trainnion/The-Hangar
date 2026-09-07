<?php
// THE HANGAR - PUBLIC STOREFRONT DATABASE HELPER

require_once __DIR__ . '/../shared/db.php';

function getStorefrontData() {
    $pdo = getDBConnection();

    $data = [
        'section1Slides' => [],
        'section2Slides' => [],
        'section7Slides' => [],
        'newReleases' => [],
        'bestSellers' => [],
        'modelKits' => []
    ];

    if ($pdo) {
        try {
            // Section 1
            $stmt = $pdo->query("
                SELECT s.*, p.id AS linked_pid, p.name AS linked_pname 
                FROM `sliders` s 
                LEFT JOIN `products` p ON s.product_id = p.id 
                WHERE s.section_key = 'section1' AND s.is_active = 1 
                ORDER BY s.sort_order ASC, s.id ASC
            ");
            $data['section1Slides'] = $stmt->fetchAll();

            // Section 2
            $stmt = $pdo->query("
                SELECT s.*, p.id AS linked_pid, p.name AS linked_pname 
                FROM `sliders` s 
                LEFT JOIN `products` p ON s.product_id = p.id 
                WHERE s.section_key = 'section2' AND s.is_active = 1 
                ORDER BY s.sort_order ASC, s.id ASC
            ");
            $data['section2Slides'] = $stmt->fetchAll();

            // Section 7
            $stmt = $pdo->query("
                SELECT s.*, p.id AS linked_pid, p.name AS linked_pname 
                FROM `sliders` s 
                LEFT JOIN `products` p ON s.product_id = p.id 
                WHERE s.section_key = 'section7' AND s.is_active = 1 
                ORDER BY s.sort_order ASC, s.id ASC
            ");
            $data['section7Slides'] = $stmt->fetchAll();

            // Products
            $data['newReleases'] = $pdo->query("SELECT * FROM `products` WHERE `is_new_release` = 1 ORDER BY `id` DESC")->fetchAll();
            $data['bestSellers'] = $pdo->query("SELECT * FROM `products` WHERE `is_best_seller` = 1 ORDER BY `id` DESC LIMIT 4")->fetchAll();
            $data['modelKits']   = $pdo->query("SELECT * FROM `products` WHERE `is_model_kit` = 1 ORDER BY `id` DESC")->fetchAll();
            if (empty($data['modelKits'])) {
                $data['modelKits'] = $pdo->query("SELECT * FROM `products` ORDER BY `id` DESC")->fetchAll();
            }
        } catch (Exception $e) {
            error_log("Failed to query storefront data: " . $e->getMessage());
        }
    }

    return $data;
}

/**
 * Resolve an image_url into a web-relative URL.
 * - Legacy rows store a bare filename (rendered under the legacy 'promotional'/seed base).
 * - Admin-uploaded rows store a managed path (e.g. assets/uploads/products/prod_...webp) used as-is。
 * @return string
 */
function assetUrl($imageUrl, $basePath = 'promotional') {
    $img = trim((string)$imageUrl);
    if ($img === '') {
        return $basePath . '/Asset 8.png';
    }
    // Already a managed path (or absolute URL) -> use as-is
    if (strpos($img, '/') !== false || stripos($img, 'http') === 0) {
        return $img;
    }
    return $basePath . '/' . $img;
}

/**
 * Reusable Product Card Component Renderer
 */
function renderProductCard($p, $promotionalPath = 'promotional', $detailsPrefix = '') {
    $id    = (int)($p['id'] ?? 0);
    $name  = htmlspecialchars($p['name'] ?? '');
    $img   = htmlspecialchars(assetUrl($p['image_url'] ?? '', $promotionalPath), ENT_QUOTES);
    $brand = htmlspecialchars(!empty($p['brand']) ? $p['brand'] : 'BANDAI');
    $stock = htmlspecialchars(!empty($p['stock_status']) ? $p['stock_status'] : 'IN-STOCK');
    $price = number_format((float)($p['price'] ?? 0), 2);
    $sold  = number_format((int)($p['sold_count'] ?? 0));
    $url   = $detailsPrefix . 'product-details.php?id=' . $id;

    return <<<HTML
<a href="{$url}" class="productCard">
    <div class="productImgContainer">
        <img src="{$img}" alt="{$name}" onerror="this.src='{$promotionalPath}/Asset 8.png'">
    </div>
    <div class="productDetails">
        <div class="productBadges">
            <span class="productBadge">{$brand}</span>
            <span class="productBadge">{$stock}</span>
        </div>
        <h3 class="productTitle">{$name}</h3>
        <div class="productFooter">
            <span class="productPrice">₱ {$price}</span>
            <span class="productSold">SOLD {$sold}</span>
        </div>
    </div>
</a>
HTML;
}

/**
 * Global Grade Alias Map
 */
function getGradeAliases(): array {
    return [
        'master grade'  => 'MG',
        'mastergrade'   => 'MG',
        'real grade'    => 'RG',
        'realgrade'     => 'RG',
        'perfect grade' => 'PG',
        'perfectgrade'  => 'PG',
        'high grade'    => 'HG',
        'highgrade'     => 'HG',
        'metal build'   => 'METAL BUILD',
        'metalbuild'    => 'METAL BUILD'
    ];
}


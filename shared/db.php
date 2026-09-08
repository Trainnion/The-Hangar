<?php
// THE HANGAR - GUND-ORDER SYSTEM
// UNIFIED DATABASE CONNECTION, SCHEMA MIGRATION & SEED DATA
// Single source of truth for all database operations across admin/, login/, homepage/

// Default fallback image constant — used when no image is uploaded for a product or slider
define('HANGAR_DEFAULT_IMAGE', 'cut-out metal build.webp');

// ---------------------------------------------------------------------------
// DATABASE CONNECTION
// The connection logic (hangarDatabaseConfig() + getDBConnection()) now lives in
// database/config.php. Loading that file here exposes the shared PDO connection
// to every module that requires this file.
// ---------------------------------------------------------------------------
require_once __DIR__ . '/../database/config.php';

function initDatabaseTables($pdo) {
    // 1. Products Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `grade` VARCHAR(50) DEFAULT 'MG',
            `scale` VARCHAR(50) DEFAULT '1/100',
            `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            `sold_count` INT NOT NULL DEFAULT 0,
            `brand` VARCHAR(100) DEFAULT 'BANDAI',
            `stock_status` VARCHAR(50) DEFAULT 'IN-STOCK',
            `image_url` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `is_new_release` TINYINT(1) DEFAULT 0,
            `is_best_seller` TINYINT(1) DEFAULT 0,
            `is_model_kit` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Sliders Table (For Section 1, Section 2, Section 7)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sliders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `section_key` VARCHAR(50) NOT NULL, -- 'section1', 'section2', 'section7'
            `title` VARCHAR(255) NOT NULL,
            `subtitle` VARCHAR(255) NULL,
            `badge` VARCHAR(100) NULL,
            `quote` TEXT NULL,
            `author` VARCHAR(100) NULL,
            `image_url` VARCHAR(255) NOT NULL,
            `button_text` VARCHAR(100) DEFAULT 'ORDER NOW!',
            `product_id` INT NULL,
            `custom_url` VARCHAR(255) NULL,
            `sort_order` INT DEFAULT 1,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Users Table (Role-based authentication)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'user',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Orders Table (Phase 1 — server-side checkout)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `order_code` VARCHAR(20) NOT NULL UNIQUE,
            `subtotal` DECIMAL(10,2) NOT NULL,
            `discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `shipping` DECIMAL(10,2) NOT NULL,
            `total` DECIMAL(10,2) NOT NULL,
            `promo_code` VARCHAR(50) NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
            -- Payment & fulfilment capture (Phase 2 — GCash-ready checkout)
            `payment_method` VARCHAR(30) NULL,
            `payment_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
            `payment_ref` VARCHAR(100) NULL,
            `gcash_ref` VARCHAR(100) NULL,
            `customer_name` VARCHAR(150) NULL,
            `customer_email` VARCHAR(255) NULL,
            `customer_phone` VARCHAR(30) NULL,
            `shipping_address` TEXT NULL,
            `logistics` VARCHAR(50) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Order Items Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `name_snapshot` VARCHAR(255) NOT NULL,
            `price_snapshot` DECIMAL(10,2) NOT NULL,
            `quantity` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 6. Promo Codes Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `promo_codes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `code` VARCHAR(50) NOT NULL UNIQUE,
            `type` ENUM('percent','fixed') NOT NULL,
            `value` DECIMAL(10,2) NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 7. Settings Table (simple key/value store — e.g. GCash QR image path)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `skey` VARCHAR(64) NOT NULL PRIMARY KEY,
            `svalue` TEXT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // --- Seed initial data if tables are empty ---

    // Seed users
    $checkUsers = $pdo->query("SELECT COUNT(*) AS total FROM `users`")->fetch();
    if ($checkUsers && (int)$checkUsers['total'] === 0) {
        seedInitialUsers($pdo);
    }

    // Seed products
    $check = $pdo->query("SELECT COUNT(*) AS total FROM `products`")->fetch();
    if ($check && (int)$check['total'] === 0) {
        seedInitialProducts($pdo);
    }

    // Seed sliders
    $checkSliders = $pdo->query("SELECT COUNT(*) AS total FROM `sliders`")->fetch();
    if ($checkSliders && (int)$checkSliders['total'] === 0) {
        seedInitialSliders($pdo);
    }

    // Seed promo codes
    $checkPromos = $pdo->query("SELECT COUNT(*) AS total FROM `promo_codes`")->fetch();
    if ($checkPromos && (int)$checkPromos['total'] === 0) {
        seedInitialPromoCodes($pdo);
    }
}

/**
 * Idempotent migration for EXISTING databases created before payment capture existed.
 * Ensures the `orders` table carries the Phase 2 payment/customer columns, adding any
 * missing ones via ALTER (MySQL pre-8 does not support "ADD COLUMN IF NOT EXISTS", so we
 * ignore the duplicate-column error when a column already exists).
 * Safe to call on every connection open.
 */
function ensureOrderPaymentColumns($pdo) {
    if (!$pdo) return;
    $migrations = [
        "ALTER TABLE `orders` ADD COLUMN `payment_method` VARCHAR(30) NULL AFTER `status`",
        "ALTER TABLE `orders` ADD COLUMN `payment_status` VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER `payment_method`",
        "ALTER TABLE `orders` ADD COLUMN `payment_ref` VARCHAR(100) NULL AFTER `payment_status`",
        "ALTER TABLE `orders` ADD COLUMN `gcash_ref` VARCHAR(100) NULL AFTER `payment_ref`",
        "ALTER TABLE `orders` ADD COLUMN `customer_name` VARCHAR(150) NULL AFTER `promo_code`",
        "ALTER TABLE `orders` ADD COLUMN `customer_email` VARCHAR(255) NULL AFTER `customer_name`",
        "ALTER TABLE `orders` ADD COLUMN `customer_phone` VARCHAR(30) NULL AFTER `customer_email`",
        "ALTER TABLE `orders` ADD COLUMN `shipping_address` TEXT NULL AFTER `customer_phone`",
        "ALTER TABLE `orders` ADD COLUMN `logistics` VARCHAR(50) NULL AFTER `shipping_address`"
    ];
    foreach ($migrations as $stmt) {
        try {
            $pdo->exec($stmt);
        } catch (Exception $e) {
            // Column already present (or table layout differs) — non-fatal
        }
    }

    // Ensure the settings key/value table exists (idempotent)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
                `skey` VARCHAR(64) NOT NULL PRIMARY KEY,
                `svalue` TEXT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (Exception $e) {
        // non-fatal
    }
}

function seedInitialUsers($pdo) {
    $insert = $pdo->prepare("
        INSERT INTO `users` (`username`, `email`, `password`, `role`)
        VALUES (:username, :email, :password, :role)
    ");
    $insert->execute([
        ':username' => 'admin',
        ':email'    => 'admin@thehangar.ph',
        ':password' => password_hash('hangar2026', PASSWORD_DEFAULT),
        ':role'     => 'admin',
    ]);
    $insert->execute([
        ':username' => 'Amuro_Ray',
        ':email'    => 'amuro@thehangar.ph',
        ':password' => password_hash('pilot2026', PASSWORD_DEFAULT),
        ':role'     => 'user',
    ]);
    $insert->execute([
        ':username' => 'Char_Aznable',
        ':email'    => 'char@thehangar.ph',
        ':password' => password_hash('redcomet', PASSWORD_DEFAULT),
        ':role'     => 'user',
    ]);
}

function seedInitialProducts($pdo) {
    $initialProducts = [
        // New Releases
        [
            'name' => 'MG ASW-G-XX Gundam Vidar',
            'grade' => 'MG',
            'scale' => '1/100',
            'price' => 4620.00,
            'sold_count' => 250,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => 'mg vidar.webp',
            'is_new_release' => 1,
            'is_best_seller' => 0,
            'is_model_kit' => 1
        ],
        [
            'name' => 'MG ASW-G-08 Gundam Barbatos Lupus',
            'grade' => 'MG',
            'scale' => '1/100',
            'price' => 3035.25,
            'sold_count' => 1089,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => '156_3280_s_6ywqxydrxl8rgy6xgjcmsl8jdtkh_clipped_rev_2.webp',
            'is_new_release' => 1,
            'is_best_seller' => 0,
            'is_model_kit' => 1
        ],
        [
            'name' => 'RG XXXG-00W0 Wing Gundam Zero',
            'grade' => 'RG',
            'scale' => '1/144',
            'price' => 2850.00,
            'sold_count' => 420,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => '153_3280_s_exe330sblcz1upy6isq1jrltmqqe_clipped_rev_1.webp',
            'is_new_release' => 1,
            'is_best_seller' => 0,
            'is_model_kit' => 1
        ],
        [
            'name' => 'PG RX-93 ν Gundam',
            'grade' => 'PG',
            'scale' => '1/60',
            'price' => 40760.00,
            'sold_count' => 70,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => 'PG NU GUNDAM.webp',
            'is_new_release' => 1,
            'is_best_seller' => 0,
            'is_model_kit' => 1
        ],

        // Best Sellers
        [
            'name' => 'RG RX-93 Nu Gundam',
            'grade' => 'RG',
            'scale' => '1/144',
            'price' => 4620.00,
            'sold_count' => 10420,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => '192_3280_s_9jvhk8pffn141y7xazrrd1sxbgay_clipped_rev_1.webp',
            'is_new_release' => 0,
            'is_best_seller' => 1,
            'is_model_kit' => 1
        ],
        [
            'name' => 'MG FREEDOM GUNDAM 2.0',
            'grade' => 'MG',
            'scale' => '1/100',
            'price' => 4880.00,
            'sold_count' => 9080,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => 'bann04883_0.jpg',
            'is_new_release' => 0,
            'is_best_seller' => 1,
            'is_model_kit' => 1
        ],
        [
            'name' => 'MG JUSTICE GUNDAM 2.0',
            'grade' => 'MG',
            'scale' => '1/100',
            'price' => 4880.00,
            'sold_count' => 8041,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => 'BAN216382-1.jpg',
            'is_new_release' => 0,
            'is_best_seller' => 1,
            'is_model_kit' => 1
        ],
        [
            'name' => 'RG HI-NU GUNDAM',
            'grade' => 'RG',
            'scale' => '1/144',
            'price' => 3709.00,
            'sold_count' => 8021,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => 'BAN230363-2.webp',
            'is_new_release' => 0,
            'is_best_seller' => 1,
            'is_model_kit' => 1
        ],

        // Additional Model Kits
        [
            'name' => 'RG MSN-06S Sinanju',
            'grade' => 'RG',
            'scale' => '1/144',
            'price' => 2450.00,
            'sold_count' => 4500,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => 'ban994380_0.webp',
            'is_new_release' => 0,
            'is_best_seller' => 0,
            'is_model_kit' => 1
        ],
        [
            'name' => 'RG MSN-04 Sazabi "Chars Counterattack"',
            'grade' => 'RG',
            'scale' => '1/144',
            'price' => 3100.00,
            'sold_count' => 6700,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => 'rg-msn-04-sazabi-pa_clipped_rev_1_1024x1024_15dfa31b-379d-43dd-ac43-f418b5eb60d1.webp',
            'is_new_release' => 0,
            'is_best_seller' => 0,
            'is_model_kit' => 1
        ],
        [
            'name' => 'MG MSN-04 Sazabi "Ver. Ka"',
            'grade' => 'MG',
            'scale' => '1/100',
            'price' => 5900.00,
            'sold_count' => 7400,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => 'BAS5055457-6.jpg',
            'is_new_release' => 0,
            'is_best_seller' => 1,
            'is_model_kit' => 1
        ],
        [
            'name' => 'FA-78 Full Armor Gundam "Ver. Ka"',
            'grade' => 'MG',
            'scale' => '1/100',
            'price' => 4950.00,
            'sold_count' => 3200,
            'brand' => 'BANDAI',
            'stock_status' => 'IN-STOCK',
            'image_url' => '578079302143824449.jpg',
            'is_new_release' => 0,
            'is_best_seller' => 0,
            'is_model_kit' => 1
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `products` 
        (`name`, `grade`, `scale`, `price`, `sold_count`, `brand`, `stock_status`, `image_url`, `is_new_release`, `is_best_seller`, `is_model_kit`) 
        VALUES 
        (:name, :grade, :scale, :price, :sold_count, :brand, :stock_status, :image_url, :is_new_release, :is_best_seller, :is_model_kit)
    ");

    foreach ($initialProducts as $p) {
        $stmt->execute($p);
    }
}

function seedInitialSliders($pdo) {
    // Section 1: Top Hero
    $sec1 = [
        [
            'section_key' => 'section1',
            'title' => 'RX-98-ν2 Hi-ν GUNDAM',
            'subtitle' => 'HYPER MEGA BAZOOKA LAUNCER',
            'badge' => 'METALBUILD',
            'quote' => null,
            'author' => null,
            'image_url' => 'cut-out metal build.webp',
            'button_text' => 'ORDER NOW!',
            'product_id' => 4,
            'sort_order' => 1
        ],
        [
            'section_key' => 'section1',
            'title' => 'ZGMF-X10A STRIKE FREEDOM',
            'subtitle' => 'PREMIUM TITANIUM FINISH',
            'badge' => 'METALBUILD',
            'quote' => null,
            'author' => null,
            'image_url' => 'rWKgWU4OCaLNzEFg20z6P7AroZR9iKXl66hhP6DL.jpg',
            'button_text' => 'ORDER NOW!',
            'product_id' => 6,
            'sort_order' => 2
        ],
        [
            'section_key' => 'section1',
            'title' => 'ZGMF-X42S DESTINY GUNDAM',
            'subtitle' => 'SPECIAL WINGS OF LIGHT ED.',
            'badge' => 'METALBUILD',
            'quote' => null,
            'author' => null,
            'image_url' => 'METAL BUILD ZGMF-X42S Destiny Gundam.jpg',
            'button_text' => 'ORDER NOW!',
            'product_id' => 7,
            'sort_order' => 3
        ],
        [
            'section_key' => 'section1',
            'title' => 'ZGMF-X09A JUSTICE GUNDAM 2.0',
            'subtitle' => 'LIMITED PRODUCTION RUN',
            'badge' => 'MASTER GRADE',
            'quote' => null,
            'author' => null,
            'image_url' => 'BAN216382-1.jpg',
            'button_text' => 'ORDER NOW!',
            'product_id' => 7,
            'sort_order' => 4
        ]
    ];

    // Section 2: Reprint Run Carousel
    $sec2 = [
        [
            'section_key' => 'section2',
            'title' => 'MSN-04 Sazabi<br>"Ver. Ka"',
            'subtitle' => 'MASTER GRADE',
            'badge' => 'REPRINT RUN!',
            'quote' => 'The Crimson Comet\'s masterpiece, engineered with unprecedented detail and psycho-frame expansion mechanics.',
            'author' => '-Anaheim Electronics',
            'image_url' => 'BAS5055457-6.jpg',
            'button_text' => 'ORDER NOW!',
            'product_id' => 11,
            'sort_order' => 1
        ],
        [
            'section_key' => 'section2',
            'title' => 'ZGMF-X10A Freedom Gundam<br>Ver. 2.0',
            'subtitle' => 'MASTER GRADE',
            'badge' => 'REPRINT RUN!',
            'quote' => 'Even so, there is still a future we must protect! Unprecedented wings articulation and beam weaponry.',
            'author' => '-Kira Yamato',
            'image_url' => 'bann04883_0.jpg',
            'button_text' => 'ORDER NOW!',
            'product_id' => 6,
            'sort_order' => 2
        ],
        [
            'section_key' => 'section2',
            'title' => 'ZGMF-X09A Justice Gundam<br>Ver. 2.0',
            'subtitle' => 'MASTER GRADE',
            'badge' => 'REPRINT RUN!',
            'quote' => 'Equipped with the Fatum-00 sub-flight lifter unit, engineered for agile multi-range warfare.',
            'author' => '-Athrun Zala',
            'image_url' => 'BAN216382-1.jpg',
            'button_text' => 'ORDER NOW!',
            'product_id' => 7,
            'sort_order' => 3
        ],
        [
            'section_key' => 'section2',
            'title' => 'FA-78 Full Armor Gundam<br>"Ver. Ka" (Thunderbolt Ver.)',
            'subtitle' => 'MASTER GRADE',
            'badge' => 'REPRINT RUN!',
            'quote' => 'This is a heavily armored highly maneuverable Mobile Suit and unfortunately, this is exactly what the Living Dead Division is least equipped to handle',
            'author' => '-Murroughs',
            'image_url' => '578079302143824449.jpg',
            'button_text' => 'ORDER NOW!',
            'product_id' => 12,
            'sort_order' => 4
        ],
        [
            'section_key' => 'section2',
            'title' => 'RG RX-93-ν2<br>Hi-ν Gundam',
            'subtitle' => 'REAL GRADE',
            'badge' => 'REPRINT RUN!',
            'quote' => 'Featuring realistic multi-joint armor sliding mechanics and fully articulated fin funnels.',
            'author' => '-Amuro Ray',
            'image_url' => 'BAN230363-2.webp',
            'button_text' => 'ORDER NOW!',
            'product_id' => 8,
            'sort_order' => 5
        ],
        [
            'section_key' => 'section2',
            'title' => 'PG RX-93<br>ν Gundam',
            'subtitle' => 'PERFECT GRADE',
            'badge' => 'REPRINT RUN!',
            'quote' => 'The pinnacle of 1/60 engineering, delivering authentic internal frame exposure and die-cast stability.',
            'author' => '-E.F.S.F. Londo Bell',
            'image_url' => 'PG NU GUNDAM.webp',
            'button_text' => 'ORDER NOW!',
            'product_id' => 4,
            'sort_order' => 6
        ],
        [
            'section_key' => 'section2',
            'title' => 'ZGMF-X42S Destiny Gundam<br>Special Edition',
            'subtitle' => 'METAL BUILD',
            'badge' => 'REPRINT RUN!',
            'quote' => 'Composite die-cast frame paired with dynamic photon wings of light for ultimate display presence.',
            'author' => '-ZAFT Armory',
            'image_url' => 'METAL BUILD ZGMF-X42S Destiny Gundam.jpg',
            'button_text' => 'ORDER NOW!',
            'product_id' => 7,
            'sort_order' => 7
        ]
    ];

    // Section 7: Cinematic Top Banner Slider
    $sec7 = [
        [
            'section_key' => 'section7',
            'title' => 'SEED DESTINY WINGS OF LIGHT',
            'subtitle' => 'CINEMATIC ANNIVERSARY RELEASE',
            'badge' => 'FEATURED CAMPAIGN',
            'quote' => null,
            'author' => null,
            'image_url' => 'SEED_kv_main001(2012Mecha)_base_withLogo.png',
            'button_text' => 'EXPLORE',
            'product_id' => 6,
            'sort_order' => 1
        ],
        [
            'section_key' => 'section7',
            'title' => 'STRIKE FREEDOM SPECIAL EDITION',
            'subtitle' => 'TITANIUM FINISH COMMEMORATIVE RUN',
            'badge' => 'FEATURED CAMPAIGN',
            'quote' => null,
            'author' => null,
            'image_url' => 'rWKgWU4OCaLNzEFg20z6P7AroZR9iKXl66hhP6DL.jpg',
            'button_text' => 'EXPLORE',
            'product_id' => 6,
            'sort_order' => 2
        ],
        [
            'section_key' => 'section7',
            'title' => 'THUNDERBOLT SECTOR COMBAT RECORD',
            'subtitle' => 'FULL ARMOR VER. KA CAMPAIGN',
            'badge' => 'FEATURED CAMPAIGN',
            'quote' => null,
            'author' => null,
            'image_url' => '578079302143824449.jpg',
            'button_text' => 'EXPLORE',
            'product_id' => 12,
            'sort_order' => 3
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `sliders` 
        (`section_key`, `title`, `subtitle`, `badge`, `quote`, `author`, `image_url`, `button_text`, `product_id`, `sort_order`) 
        VALUES 
        (:section_key, :title, :subtitle, :badge, :quote, :author, :image_url, :button_text, :product_id, :sort_order)
    ");

    foreach (array_merge($sec1, $sec2, $sec7) as $s) {
        $stmt->execute($s);
    }
}

function seedInitialPromoCodes($pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO `promo_codes` (`code`, `type`, `value`, `active`)
        VALUES (:code, :type, :value, :active)
    ");
    // Migrated from the original client-side ACTIVE_PROMOS in cart.js
    $stmt->execute([':code' => 'PILOT10',    ':type' => 'percent', ':value' => 10,  ':active' => 1]);
    $stmt->execute([':code' => 'GUNDAM2026', ':type' => 'fixed',   ':value' => 500, ':active' => 1]);
}

// Compatibility wrapper for modules expecting getConnection()
function getConnection(): ?PDO {
    return getDBConnection();
}

/**
 * The list of logistics / courier partners offered at checkout and editable in the admin
 * Order Dispatch panel. Kept as the single source of truth so the storefront, checkout API,
 * admin detail view, and receipt render the exact same options.
 *
 * @return array<string>
 */
function hangarCourierOptions(): array {
    return ['J&T Express', 'NinjaVan'];
}

/**
 * Read a value from the `settings` key/value store.
 * Returns $default when unset, DB unavailable, or empty.
 */
function getSetting($pdo, string $key, $default = null) {
    if (!$pdo) return $default;
    try {
        $stmt = $pdo->prepare('SELECT `svalue` FROM `settings` WHERE `skey` = :k LIMIT 1');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch();
        return ($row && $row['svalue'] !== null && trim($row['svalue']) !== '') ? $row['svalue'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Write a value into the `settings` key/value store (upsert).
 */
function setSetting($pdo, string $key, $value) {
    if (!$pdo) return;
    try {
        $stmt = $pdo->prepare('INSERT INTO `settings` (`skey`, `svalue`) VALUES (:k, :v) ON DUPLICATE KEY UPDATE `svalue` = :v');
        $stmt->execute(['k' => $key, 'v' => $value]);
    } catch (Exception $e) {
        // non-fatal
    }
}


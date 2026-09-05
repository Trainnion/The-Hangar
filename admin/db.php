<?php
// THE HANGAR - GUND-ORDER SYSTEM DATABASE CONNECTION & AUTO-MIGRATION

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $dbname = 'the_hangar_db';
    $charset = 'utf8mb4';

    try {
        // Connect to MySQL server first without selecting DB to check/create it
        $rootPdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Create database if it does not exist
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Now connect to the specific database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Run migrations & initial seeders
        initDatabaseTables($pdo);

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

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

    // Check if users table is empty, then seed initial admin and user accounts
    $checkUsers = $pdo->query("SELECT COUNT(*) AS total FROM `users`")->fetch();
    if ($checkUsers && (int)$checkUsers['total'] === 0) {
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

    // Check if products table is empty, then seed initial data
    $check = $pdo->query("SELECT COUNT(*) AS total FROM `products`")->fetch();
    if ($check && (int)$check['total'] === 0) {
        seedInitialProducts($pdo);
    }

    // Check if sliders table is empty, then seed initial data
    $checkSliders = $pdo->query("SELECT COUNT(*) AS total FROM `sliders`")->fetch();
    if ($checkSliders && (int)$checkSliders['total'] === 0) {
        seedInitialSliders($pdo);
    }
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
            'name' => 'MG MSN-04 Sazabi “Ver. Ka”',
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
            'name' => 'FA-78 Full Armor Gundam “Ver. Ka”',
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
            'title' => 'MSN-04 Sazabi<br>“Ver. Ka”',
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
            'title' => 'FA-78 Full Armor Gundam<br>“Ver. Ka” (Thunderbolt Ver.)',
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

<?php
// THE HANGAR - PUBLIC STOREFRONT DATABASE HELPER

require_once __DIR__ . '/../admin/db.php';

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
            $data['modelKits'] = $pdo->query("SELECT * FROM `products` WHERE `is_model_kit` = 1 ORDER BY `id` DESC")->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to query storefront data: " . $e->getMessage());
        }
    }

    return $data;
}

<?php
// THE HANGAR - GUND-ORDER SYSTEM
// REST API ENDPOINT: LIVE SEARCH & TARGET ACQUISITION

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../../admin/db.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode([
            'success' => false,
            'message' => 'Database offline: Radar connection could not be established.',
            'count'   => 0,
            'results' => []
        ]);
        exit;
    }

    $rawQuery = trim($_GET['q'] ?? '');
    $rawGrade = trim($_GET['grade'] ?? '');
    $limit    = isset($_GET['limit']) ? min(max((int)$_GET['limit'], 1), 50) : 8;

    // Normalize grade aliases if typed into query
    $gradeAliases = [
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

    $detectedGrade = '';
    $lowerQuery = strtolower($rawQuery);
    foreach ($gradeAliases as $alias => $gCode) {
        if ($lowerQuery === $alias) {
            $detectedGrade = $gCode;
            break;
        }
    }

    $filterGrade = !empty($rawGrade) ? strtoupper($rawGrade) : $detectedGrade;

    $params = [];
    $sql = "SELECT `id`, `name`, `grade`, `scale`, `price`, `sold_count`, `brand`, `stock_status`, `image_url`, `is_new_release`, `is_best_seller`
            FROM `products`
            WHERE 1=1";

    if (!empty($filterGrade)) {
        $sql .= " AND UPPER(`grade`) = :filter_grade";
        $params['filter_grade'] = $filterGrade;
    }

    if (!empty($rawQuery) && empty($detectedGrade)) {
        $sql .= " AND (
            `name` LIKE :q 
            OR `grade` LIKE :q 
            OR `scale` LIKE :q 
            OR `brand` LIKE :q 
            OR IFNULL(`description`, '') LIKE :q
        )";
        $params['q'] = "%$rawQuery%";

        // Intelligent relevance ranking
        $sql .= " ORDER BY 
            CASE 
                WHEN `name` LIKE :exact_start THEN 1
                WHEN `name` LIKE :exact_word THEN 2
                WHEN UPPER(`grade`) = :exact_grade THEN 3
                ELSE 4
            END ASC,
            `sold_count` DESC,
            `id` DESC";
        $params['exact_start'] = "$rawQuery%";
        $params['exact_word']  = "%$rawQuery%";
        $params['exact_grade'] = strtoupper($rawQuery);
    } else {
        // If no text query or only grade, order by popularity & newness
        $sql .= " ORDER BY `is_new_release` DESC, `sold_count` DESC, `id` DESC";
    }

    $sql .= " LIMIT " . (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Determine paths
    $promotionalDir = is_dir(__DIR__ . '/promotional') ? 'promotional' : '../promotional';

    $results = [];
    foreach ($products as $p) {
        $imgFile = $p['image_url'];
        $results[] = [
            'id'              => (int)$p['id'],
            'name'            => $p['name'],
            'grade'           => $p['grade'],
            'scale'           => $p['scale'],
            'price'           => (float)$p['price'],
            'formatted_price' => '₱ ' . number_format((float)$p['price'], 2),
            'sold_count'      => (int)$p['sold_count'],
            'brand'           => $p['brand'],
            'stock_status'    => $p['stock_status'],
            'image_url'       => $imgFile,
            'image_full_path' => $promotionalDir . '/' . $imgFile,
            'is_new_release'  => (bool)$p['is_new_release'],
            'is_best_seller'  => (bool)$p['is_best_seller'],
            'url'             => 'product-details.php?id=' . (int)$p['id']
        ];
    }

    echo json_encode([
        'success'      => true,
        'query'        => $rawQuery,
        'filter_grade' => $filterGrade,
        'count'        => count($results),
        'results'      => $results
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'count'   => 0,
        'results' => []
    ]);
}

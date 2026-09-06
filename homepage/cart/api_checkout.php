<?php
// THE HANGAR - GUND-ORDER SYSTEM
// REST API ENDPOINT: REAL-TIME CHECKOUT & ORDER DISPATCH PROCESSOR
// Authoritative server-side price calculation, promo validation, and order persistence

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../shared/db.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Logistics offline.']);
        exit;
    }

    // Read input (support both JSON payload and standard form POST)
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $action = $data['action'] ?? 'checkout';

    // 1. PROMO VALIDATION ACTION
    if ($action === 'validate_promo') {
        $code = trim(strtoupper($data['code'] ?? ''));
        if ($code === '') {
            echo json_encode(['success' => false, 'message' => 'Please enter a pilot clearance code.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT `code`, `type`, `value`, `active` FROM `promo_codes` WHERE `code` = :code AND `active` = 1 LIMIT 1");
        $stmt->execute([':code' => $code]);
        $promo = $stmt->fetch();

        if ($promo) {
            $label = ($promo['type'] === 'percent')
                ? ((float)$promo['value'] . '% Pilot Clearance Discount')
                : ('₱' . number_format((float)$promo['value'], 2) . ' Gund-Format Requisition Credit');

            echo json_encode([
                'success' => true,
                'promo' => [
                    'code'  => $promo['code'],
                    'type'  => $promo['type'],
                    'value' => (float)$promo['value'],
                    'label' => $label
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired clearance code.']);
        }
        exit;
    }

    // 2. CHECKOUT ACTION
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed. POST required.']);
        exit;
    }

    $rawItems = $data['items'] ?? [];
    if (!is_array($rawItems) || count($rawItems) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Manifest is empty. Requisition Mobile Suit units before dispatching.']);
        exit;
    }

    // Sanitize and consolidate items by product_id
    // Client only provides product_id (or id) and quantity. NEVER client price.
    $consolidated = [];
    foreach ($rawItems as $item) {
        $pid = isset($item['product_id']) ? (int)$item['product_id'] : (isset($item['id']) ? (int)$item['id'] : 0);
        $qty = isset($item['quantity']) ? (int)$item['quantity'] : (isset($item['qty']) ? (int)$item['qty'] : 1);

        if ($pid <= 0 || $qty <= 0) {
            continue;
        }

        if (isset($consolidated[$pid])) {
            $consolidated[$pid] += $qty;
        } else {
            $consolidated[$pid] = $qty;
        }
    }

    if (count($consolidated) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid units found in order manifest.']);
        exit;
    }

    // Prepare product fetch statement to guarantee authoritative server-side prices
    $productStmt = $pdo->prepare("SELECT `id`, `name`, `price`, `stock_status` FROM `products` WHERE `id` = :id LIMIT 1");

    $orderItems = [];
    $subtotal = 0.00;

    foreach ($consolidated as $pid => $qty) {
        $productStmt->execute([':id' => $pid]);
        $prod = $productStmt->fetch();

        if (!$prod) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Unit ID #{$pid} could not be located in database inventory."]);
            exit;
        }

        $price = (float)$prod['price'];
        $lineTotal = round($price * $qty, 2);
        $subtotal += $lineTotal;

        $orderItems[] = [
            'product_id'     => (int)$prod['id'],
            'name_snapshot'  => $prod['name'],
            'price_snapshot' => $price,
            'quantity'       => $qty
        ];
    }

    // Server-side promo code validation & discount calculation
    $promoCode = trim(strtoupper($data['promo_code'] ?? ''));
    $discount = 0.00;
    $appliedPromo = null;

    if ($promoCode !== '') {
        $promoStmt = $pdo->prepare("SELECT `code`, `type`, `value` FROM `promo_codes` WHERE `code` = :code AND `active` = 1 LIMIT 1");
        $promoStmt->execute([':code' => $promoCode]);
        $promoRow = $promoStmt->fetch();

        if ($promoRow) {
            $appliedPromo = $promoRow['code'];
            $val = (float)$promoRow['value'];
            if ($promoRow['type'] === 'percent') {
                $discount = round(($subtotal * $val) / 100, 2);
            } elseif ($promoRow['type'] === 'fixed') {
                $discount = min($val, $subtotal);
            }
        }
    }

    // Flat shipping fee of ₱150.00 if order has items
    $shipping = count($orderItems) > 0 ? 150.00 : 0.00;

    // Calculate final total
    $total = max(0.00, round($subtotal - $discount + $shipping, 2));

    // Resolve authenticated user ID if logged in
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // Generate unique order code (HGR-XXXXXX)
    $checkCodeStmt = $pdo->prepare("SELECT `id` FROM `orders` WHERE `order_code` = :code LIMIT 1");
    $orderCode = '';
    do {
        $orderCode = 'HGR-' . str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $checkCodeStmt->execute([':code' => $orderCode]);
    } while ($checkCodeStmt->fetch());

    // Begin transaction for order and order items
    $pdo->beginTransaction();

    $orderInsert = $pdo->prepare("
        INSERT INTO `orders` 
        (`user_id`, `order_code`, `subtotal`, `discount`, `shipping`, `total`, `promo_code`, `status`, `created_at`) 
        VALUES 
        (:user_id, :order_code, :subtotal, :discount, :shipping, :total, :promo_code, 'pending', NOW())
    ");
    $orderInsert->execute([
        ':user_id'    => $userId,
        ':order_code' => $orderCode,
        ':subtotal'   => $subtotal,
        ':discount'   => $discount,
        ':shipping'   => $shipping,
        ':total'      => $total,
        ':promo_code' => $appliedPromo
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $itemInsert = $pdo->prepare("
        INSERT INTO `order_items` 
        (`order_id`, `product_id`, `name_snapshot`, `price_snapshot`, `quantity`) 
        VALUES 
        (:order_id, :product_id, :name_snapshot, :price_snapshot, :quantity)
    ");

    foreach ($orderItems as $item) {
        $itemInsert->execute([
            ':order_id'       => $orderId,
            ':product_id'     => $item['product_id'],
            ':name_snapshot'  => $item['name_snapshot'],
            ':price_snapshot' => $item['price_snapshot'],
            ':quantity'       => $item['quantity']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success'    => true,
        'order_code' => $orderCode,
        'subtotal'   => $subtotal,
        'discount'   => $discount,
        'shipping'   => $shipping,
        'total'      => $total,
        'promo_code' => $appliedPromo,
        'message'    => "Sortie Dispatch Authorized. Manifest Order #{$orderCode} confirmed."
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during order dispatch processing: ' . $e->getMessage()
    ]);
}

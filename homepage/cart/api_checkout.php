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
    $productStmt = $pdo->prepare("SELECT `id`, `name`, `price`, `stock_status`, `stock` FROM `products` WHERE `id` = :id LIMIT 1");

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

        // Availability check — never allow selling more units than remain in stock.
        $availStock = (int)($prod['stock'] ?? 0);
        if ($availStock < $qty) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Only {$availStock} unit(s) of '{$prod['name']}' left in stock. Please reduce the quantity to {$availStock} or remove it from your manifest."
            ]);
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

    // PROFILE COMPLETENESS GATE (server-authoritative): logged-in pilots must
    // have full name, phone, and address on file before an order can be placed.
    if ($userId !== null) {
        $profileStmt = $pdo->prepare("SELECT full_name, phone, address FROM users WHERE id = :id LIMIT 1");
        $profileStmt->execute([':id' => $userId]);
        $profileRow = $profileStmt->fetch(PDO::FETCH_ASSOC);
        if ($profileRow && !isProfileComplete($profileRow)) {
            echo json_encode([
                'success' => false,
                'message' => 'Your pilot profile is incomplete. Please add your full name, mobile number, and delivery address in your profile before placing an order.',
                'redirect' => '../profile/profile.php?edit=1',
            ]);
            exit;
        }
    }

    // Generate unique order code (HGR-XXXXXX)
    $checkCodeStmt = $pdo->prepare("SELECT `id` FROM `orders` WHERE `order_code` = :code LIMIT 1");
    $orderCode = '';
    do {
        $orderCode = 'HGR-' . str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $checkCodeStmt->execute([':code' => $orderCode]);
    } while ($checkCodeStmt->fetch());

    // GCash-ready payment capture (Phase 2 — QR scan-to-pay / manual verification)
    $paymentCfg = hangarPaymentConfig();
    $payMode = trim($paymentCfg['mode'] ?? 'qr');
    if (!in_array($payMode, ['qr', 'stub', 'live'], true)) $payMode = 'qr';

    $paymentMethod = trim(strtolower($data['payment_method'] ?? 'gcash'));
    if (!in_array($paymentMethod, ['gcash', 'cod'], true)) $paymentMethod = 'gcash';

    $customerName  = trim($data['customer_name']  ?? '');
    $customerEmail = trim($data['customer_email'] ?? '');
    $customerPhone = trim($data['customer_phone'] ?? '');
    $shippingAddr  = trim($data['shipping_address'] ?? '');
    $gcashRef      = trim($data['gcash_ref'] ?? '');

    // Logistics / courier selection — sanitized against the shared whitelist.
    // Defaults to the first supported courier if missing or unrecognized (never trusted from the client).
    $logistics = trim($data['logistics'] ?? '');
    $couriers = hangarCourierOptions();
    if ($logistics === '' || !in_array($logistics, $couriers, true)) {
        $logistics = (count($couriers) > 0) ? $couriers[0] : 'J&T Express';
    }

    // Determine payment outcome based on the payment method and configured mode.
    if ($paymentMethod === 'cod') {
        // Cash on Delivery: collect the exact total from the customer at the door.
        // No online payment to pre-verify — the order is ready for fulfilment, and
        // payment is settled when the courier delivers.
        $paymentStatus = 'cod';
        $paymentRef    = null;
    } elseif ($payMode === 'qr') {
        // QR scan-to-pay: NOT auto-confirmed. The customer pays in their GCash app and
        // provides the transaction reference; the order waits for admin verification.
        $paymentStatus = 'payment_pending';
        $paymentRef    = null;
        if ($gcashRef === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Enter the GCash transaction reference after paying via the QR code.']);
            exit;
        }
    } elseif ($payMode === 'stub') {
        // Dev-only simulation: immediate approval.
        $paymentStatus = 'paid';
        $paymentRef    = 'GCASH-STUB-' . $orderCode;
    } else { // 'live'
        // Reserved: real automated GCash/PSP integration (webhook confirmation).
        $paymentStatus = 'pending';
        $paymentRef    = null;
    }

    // Begin transaction for order and order items
    $pdo->beginTransaction();

    $orderInsert = $pdo->prepare("
        INSERT INTO `orders`
        (`user_id`, `order_code`, `subtotal`, `discount`, `shipping`, `total`, `promo_code`, `status`,
         `payment_method`, `payment_status`, `payment_ref`, `gcash_ref`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `logistics`, `created_at`)
        VALUES
        (:user_id, :order_code, :subtotal, :discount, :shipping, :total, :promo_code, 'pending',
         :payment_method, :payment_status, :payment_ref, :gcash_ref, :customer_name, :customer_email, :customer_phone, :shipping_address, :logistics, NOW())
    ");
    $orderInsert->execute([
        ':user_id'          => $userId,
        ':order_code'       => $orderCode,
        ':subtotal'         => $subtotal,
        ':discount'         => $discount,
        ':shipping'         => $shipping,
        ':total'            => $total,
        ':promo_code'       => $appliedPromo,
        ':payment_method'   => $paymentMethod,
        ':payment_status'   => $paymentStatus,
        ':payment_ref'      => $paymentRef,
        ':gcash_ref'        => $gcashRef,
        ':customer_name'    => $customerName,
        ':customer_email'   => $customerEmail,
        ':customer_phone'   => $customerPhone,
        ':shipping_address' => $shippingAddr,
        ':logistics'        => $logistics
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

    // Authoritative stock decrement — guarded so a concurrent purchase that drains
    // stock mid-checkout aborts this order (rollback via the outer catch) rather
    // than overselling. Sold counter moves in lock-step with the stock drop.
    $stockStmt = $pdo->prepare("UPDATE `products` SET `stock` = `stock` - :q, `sold_count` = `sold_count` + :q WHERE `id` = :pid AND `stock` >= :q");
    foreach ($orderItems as $item) {
        $stockStmt->execute([
            ':q'   => $item['quantity'],
            ':pid' => $item['product_id'],
        ]);
        if ($stockStmt->rowCount() !== 1) {
            throw new Exception("Insufficient stock for '{$item['name_snapshot']}' — units were claimed by another order. Please reduce the quantity and try again.");
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'         => true,
        'order_code'      => $orderCode,
        'subtotal'        => $subtotal,
        'discount'        => $discount,
        'shipping'        => $shipping,
        'total'           => $total,
        'promo_code'      => $appliedPromo,
        'payment_method'  => $paymentMethod,
        'payment_status'  => $paymentStatus,
        'payment_ref'     => $paymentRef,
        'gcash_ref'       => $gcashRef,
        'pay_mode'        => $payMode,
        'message'         => ($paymentStatus === 'paid')
            ? "GCash payment authorized. Manifest Order #{$orderCode} confirmed."
            : (($paymentStatus === 'payment_pending')
                ? "Order #{$orderCode} logged. Payment is pending GCash verification — we'll confirm shortly."
                : (($paymentStatus === 'cod')
                    ? "Order #{$orderCode} confirmed via Cash on Delivery. Pay the exact total to the courier upon delivery."
                    : "Sortie Dispatch Authorized. Manifest Order #{$orderCode} pending payment confirmation."))
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Throwable $rb) {
            error_log('Checkout rollback failed: ' . $rb->getMessage());
        }
    }
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    // Never expose raw DB/system error text. Only the deliberate, user-actionable
    // business message (insufficient stock) is surfaced; everything else gets a
    // generic friendly message while technical detail stays in the server log.
    $safeMsg = (strpos($e->getMessage(), 'Insufficient stock for') !== false)
        ? $e->getMessage()
        : 'Sorry, we could not process your order right now. The hangar crew has been alerted — please try again shortly.';
    echo json_encode([
        'success' => false,
        'message' => $safeMsg
    ]);
}

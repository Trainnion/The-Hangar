<?php
// THE HANGAR - G.O.S ADMIN
// ORDER DISPATCH CONTROL PANEL
// List, inspect, and advance order fulfilment status (pending -> processing -> shipped -> completed / cancelled)
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

$pdo = getDBConnection();

// Allowed lifecycle. Payment confirmation (Task 2) will gate this further.
$ALLOWED_STATUS = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

// 1. Handle POST actions (CSRF-validated)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!csrfValid() || !in_array($_POST['form_action'] ?? '', ['verify', 'status', 'logistics'], true)) {
        header('Location: orders.php?msg=csrf');
        exit;
    }
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['form_action'];
    $redirect = 'orders.php?msg=updated';
    if (!empty($_POST['view'])) $redirect .= '&view=' . (int)$_POST['view'];

    // ---- 1a. VERIFY GCASH PAYMENT (manual confirmation of QR scan-to-pay) ----
    if ($action === 'verify' && $orderId > 0) {
        $gStmt = $pdo->prepare('SELECT `gcash_ref` FROM `orders` WHERE `id` = :id');
        $gStmt->execute(['id' => $orderId]);
        $gRow = $gStmt->fetch();
        if ($gRow) {
            $verifiedRef = trim((string)($gRow['gcash_ref'] ?? ''));
            if ($verifiedRef === '') $verifiedRef = 'GCASH-VERIFIED';
            $uStmt = $pdo->prepare("UPDATE `orders` SET `payment_status` = 'paid', `payment_ref` = :ref WHERE `id` = :id AND `payment_status` = 'payment_pending'");
            $uStmt->execute(['ref' => $verifiedRef, 'id' => $orderId]);
        }
        $redirect = 'orders.php?msg=paid';
        if (!empty($_POST['view'])) $redirect .= '&view=' . (int)$_POST['view'];
        header('Location: ' . $redirect);
        exit;
    }

    // ---- 1a2. UPDATE DELIVERY LOGISTICS (admin corrects/adjusts the courier on the order) ----
    if ($action === 'logistics' && $orderId > 0) {
        $newLogistics = trim($_POST['logistics'] ?? '');
        $couriers = hangarCourierOptions();
        if ($newLogistics !== '' && in_array($newLogistics, $couriers, true)) {
            $lgStmt = $pdo->prepare('UPDATE `orders` SET `logistics` = :l WHERE `id` = :id');
            $lgStmt->execute(['l' => $newLogistics, 'id' => $orderId]);
        }
        header('Location: ' . $redirect);
        exit;
    }

    // ---- 1b. ADVANCE FULFILMENT STATUS (gated on confirmed payment) ----
    // COD orders ship without online verification &#8212; payment is collected at the door.
    $newStatus = trim($_POST['status'] ?? '');
    $allowed = ($orderId > 0 && in_array($newStatus, $ALLOWED_STATUS, true));
    if ($allowed && in_array($newStatus, ['processing', 'shipped', 'completed'], true)) {
        $payStmt = $pdo->prepare('SELECT `payment_status` FROM `orders` WHERE `id` = :id');
        $payStmt->execute(['id' => $orderId]);
        $payRow = $payStmt->fetch();
        $payRowStatus = $payRow ? trim($payRow['payment_status'] ?? '') : '';
        if (!$payRow || !in_array($payRowStatus, ['paid', 'cod'], true)) {
            $allowed = false;
            $redirect = 'orders.php?msg=unpaid';
            if (!empty($_POST['view'])) $redirect .= '&view=' . (int)$_POST['view'];
        }
    }

    if ($allowed) {
        $stmt = $pdo->prepare('UPDATE `orders` SET `status` = :s WHERE `id` = :id');
        $stmt->execute(['s' => $newStatus, 'id' => $orderId]);
    }
    header('Location: ' . $redirect);
    exit;
}

// 2. Filters
$search = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
if ($filterStatus !== '' && !in_array($filterStatus, $ALLOWED_STATUS, true)) {
    $filterStatus = '';
}

// 3. Collect summary metrics
$totalOrders = 0;
$pendingCount = 0;
$processingCount = 0;
$completedCount = 0;
$cancelledCount = 0;

if ($pdo) {
    $totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM `orders`')->fetchColumn();
    $pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'pending'")->fetchColumn();
    $processingCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'processing'")->fetchColumn();
    $completedCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'completed'")->fetchColumn();
    $cancelledCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'cancelled'")->fetchColumn();
}

// 4. Fetch orders list (join customer info from users)
$orders = [];
$itemCounts = [];
if ($pdo) {
    $sql = "SELECT o.*, u.username AS customer, u.email AS customer_email
            FROM `orders` o
            LEFT JOIN `users` u ON o.user_id = u.id
            WHERE 1 = 1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (o.order_code LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
        $params['search'] = "%$search%";
    }
    if (!empty($filterStatus)) {
        $sql .= " AND o.status = :status";
        $params['status'] = $filterStatus;
    }

    $sql .= " ORDER BY o.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Quantity per order (sum of line quantities)
    $cntStmt = $pdo->query('SELECT `order_id`, SUM(`quantity`) AS qty FROM `order_items` GROUP BY `order_id`')->fetchAll();
    foreach ($cntStmt as $row) {
        $itemCounts[(int)$row['order_id']] = (int)$row['qty'];
    }
}

// 5. Order currently open in the detail modal (?view=ID)
$viewOrder = null;
$viewItems = [];
if (isset($_GET['view']) && $pdo) {
    $oid = (int)$_GET['view'];
    $vStmt = $pdo->prepare("SELECT o.*, u.username AS customer, u.email AS customer_email
                            FROM `orders` o
                            LEFT JOIN `users` u ON o.user_id = u.id
                            WHERE o.id = :id LIMIT 1");
    $vStmt->execute(['id' => $oid]);
    $viewOrder = $vStmt->fetch();
    if ($viewOrder) {
        $iStmt = $pdo->prepare('SELECT * FROM `order_items` WHERE `order_id` = :oid');
        $iStmt->execute(['oid' => $oid]);
        $viewItems = $iStmt->fetchAll();
    }
}

// Helper: status badge CSS class + label
function orderStatusBadge(string $status) {
    switch ($status) {
        case 'processing': return ['badge-grade', 'PROCESSING'];
        case 'shipped':    return ['badge-bs', 'SHIPPED'];
        case 'completed':  return ['badge-active', 'COMPLETED'];
        case 'cancelled':  return ['badge-inactive', 'CANCELLED'];
        default:           return ['badge-nr', 'PENDING'];
    }
}

function paymentStatusBadge(string $status) {
    switch ($status) {
        case 'paid':               return ['badge-active', 'PAID'];
        case 'failed':             return ['badge-inactive', 'FAILED'];
        case 'payment_pending':    return ['badge-bs', 'AWAITING VERIFY'];
        case 'cod':                return ['badge-grade', 'CASH ON DELIVERY'];
        default:                   return ['badge-nr', 'PENDING'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Dispatch Control | THE HANGAR ADMIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="adminMain">
        <header class="topBar">
            <div class="topBarTitle">
                <span>//</span> ORDER DISPATCH CONTROL
            </div>
            <div class="topBarRight">
                <span class="pilotTag">Fulfilment Queue &amp; Status</span>
            </div>
        </header>

        <div class="contentArea">
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong>
                    <?php
                        if ($_GET['msg'] === 'updated') echo 'Order status successfully updated.';
                        elseif ($_GET['msg'] === 'paid') echo 'GCash payment confirmed &#8212; this order is now processable.';
                        elseif ($_GET['msg'] === 'csrf') echo 'Request rejected: invalid security token.';
                        elseif ($_GET['msg'] === 'unpaid') echo 'Fulfilment locked: payment has not been confirmed for this order.';
                    ?>
                </div>
            <?php endif; ?>

            <!-- Stat Grid -->
            <div class="statGrid">
                <div class="statCard">
                    <span class="statLabel">TOTAL ORDERS</span>
                    <span class="statValue"><?php echo $totalOrders; ?></span>
                    <span class="statSub">Registered Manifests</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">PENDING</span>
                    <span class="statValue"><?php echo $pendingCount; ?></span>
                    <span class="statSub">Awaiting Processing</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">PROCESSING</span>
                    <span class="statValue"><?php echo $processingCount; ?></span>
                    <span class="statSub">In Fulfilment Queue</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">COMPLETED</span>
                    <span class="statValue"><?php echo $completedCount; ?></span>
                    <span class="statSub">Delivered &amp; Closed</span>
                </div>
                <div class="statCard">
                    <span class="statLabel">CANCELLED</span>
                    <span class="statValue"><?php echo $cancelledCount; ?></span>
                    <span class="statSub">Terminated Manifests</span>
                </div>
            </div>

            <!-- Filter / Search Bar -->
            <div class="adminCard">
                <form method="GET" action="orders.php" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex-grow: 1; min-width: 250px;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by order code, pilot, or email..." style="width: 100%; padding: 0.75rem 1rem; background: rgba(14, 17, 21, 0.85); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                    </div>
                    <div>
                        <select name="status" style="padding: 0.75rem 1rem; background: rgba(14, 17, 21, 0.85); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                            <option value="">All Statuses</option>
                            <?php foreach ($ALLOWED_STATUS as $s): ?>
                                <option value="<?php echo $s; ?>" <?php if ($filterStatus === $s) echo 'selected'; ?>><?php echo strtoupper($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btnSecondary">FILTER</button>
                    <?php if (!empty($search) || !empty($filterStatus)): ?>
                        <a href="orders.php" class="btnSecondary" style="text-decoration: none;">RESET</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Orders Data Table -->
            <div class="adminCard">
                <div class="cardHeader">
                    <h2 class="cardTitle">ORDER MANIFESTS (<?php echo count($orders); ?>)</h2>
                </div>

                <div style="overflow-x: auto;">
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>ORDER CODE</th>
                                <th>DATE</th>
                                <th>PILOT / CUSTOMER</th>
                                <th>UNITS</th>
                                <th>TOTAL</th>
                                <th>STATUS</th>
                                <th>PAYMENT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 3rem;">No orders match your query.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <?php $badgeInfo = orderStatusBadge($o['status']); ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--brand-cyan);"><?php echo htmlspecialchars($o['order_code']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($o['created_at'] ?? '-'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($o['customer'] ?? 'Guest'); ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;"><?php echo htmlspecialchars($o['customer_email'] ?? ''); ?></div>
                                        </td>
                                        <td><?php echo $itemCounts[(int)$o['id']] ?? 0; ?></td>
                                        <td>&#8369;<?php echo number_format((float)$o['total'], 2); ?></td>
                                        <td><span class="badge <?php echo $badgeInfo[0]; ?>"><?php echo $badgeInfo[1]; ?></span></td>
                                        <?php $pBadge = paymentStatusBadge($o['payment_status']); ?>
                                        <td><span class="badge <?php echo $pBadge[0]; ?>"><?php echo $pBadge[1]; ?></span></td>
                                        <td>
                                            <div class="actionBtns">
                                                <a href="orders.php?view=<?php echo (int)$o['id']; ?>" class="btnSecondary btnSmall">VIEW</a>
                                                <a href="receipt.php?order_id=<?php echo (int)$o['id']; ?>" class="btnSecondary btnSmall" title="Print / download receipt">PRINT</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php if ($viewOrder): ?>
        <?php $vBadgeInfo = orderStatusBadge($viewOrder['status']); ?>
        <?php $pBadgeInfo = paymentStatusBadge($viewOrder['payment_status']); ?>
        <!-- Order Detail Modal -->
        <div class="modalOverlay active" id="orderDetailModal">
            <div class="modalBox">
                <div class="modalHeader">
                    <h3 class="modalTitle">ORDER <?php echo htmlspecialchars($viewOrder['order_code']); ?></h3>
                    <a href="orders.php" class="closeModalBtn">&times;</a>
                </div>

                <div class="formGroup">
                    <label>STATUS</label>
                    <div><span class="badge <?php echo $vBadgeInfo[0]; ?>"><?php echo $vBadgeInfo[1]; ?></span></div>
                </div>

                <div class="formGroup">
                    <label>CUSTOMER</label>
                    <div><?php echo htmlspecialchars($viewOrder['customer'] ?? 'Guest'); ?> <span style="color: var(--text-muted);">(<?php echo htmlspecialchars($viewOrder['customer_email'] ?? 'no email on file'); ?>)</span></div>
                </div>

                <div class="formGroup">
                    <label>PAYMENT</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                        <span class="badge <?php echo $pBadgeInfo[0]; ?>"><?php echo $pBadgeInfo[1]; ?></span>
                        <span class="badge badge-grade"><?php echo htmlspecialchars(strtoupper($viewOrder['payment_method'] ?? '&#8212;')); ?></span>
                        <?php if (!empty($viewOrder['payment_ref'])): ?>
                            <span class="badge badge-bs"><?php echo htmlspecialchars($viewOrder['payment_ref']); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($viewOrder['gcash_ref'])): ?>
                        <div style="margin-top: 0.5rem; color: var(--text-sub); font-size: 0.82rem;">
                            Customer GCash reference: <strong style="color: var(--brand-cyan);"><?php echo htmlspecialchars($viewOrder['gcash_ref']); ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if (($viewOrder['payment_status'] ?? '') === 'payment_pending'): ?>
                        <div style="margin-top: 0.6rem; padding: 0.6rem 0.9rem; background: rgba(255, 193, 7, 0.10); border: 1px solid rgba(255,193,7,0.35); border-radius: 4px; font-size: 0.8rem; color: var(--text-sub);">
                            Check this reference against your GCash app, then confirm the payment to release the order for fulfilment.
                            <form method="POST" action="orders.php" style="display: inline-block; margin-top: 0.5rem;">
                                <input type="hidden" name="form_action" value="verify">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="order_id" value="<?php echo (int)$viewOrder['id']; ?>">
                                <input type="hidden" name="view" value="<?php echo (int)$viewOrder['id']; ?>">
                                <button type="submit" class="btnPrimary">VERIFY GCASH PAYMENT</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="formGroup">
                    <label>DELIVERY</label>
                    <div><?php echo htmlspecialchars($viewOrder['customer_phone'] ?? 'No mobile number on file.'); ?></div>
                    <div style="white-space: pre-line; margin-top: 0.35rem; color: var(--text-sub);"><?php echo htmlspecialchars($viewOrder['shipping_address'] ?? ''); ?></div>
                </div>

                <div class="formGroup">
                    <label>LOGISTICS (COURIER)</label>
                    <form method="POST" action="orders.php" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="hidden" name="form_action" value="logistics">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="order_id" value="<?php echo (int)$viewOrder['id']; ?>">
                        <input type="hidden" name="view" value="<?php echo (int)$viewOrder['id']; ?>">
                        <select name="logistics" style="flex-grow: 1;">
                            <?php foreach (hangarCourierOptions() as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>" <?php if (($viewOrder['logistics'] ?? '') === $c) echo 'selected'; ?>><?php echo htmlspecialchars($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btnSecondary btnSmall">UPDATE COURIER</button>
                    </form>
                    <?php if (empty($viewOrder['logistics'])): ?>
                        <div style="margin-top: 0.4rem; color: var(--text-muted); font-size: 0.78rem;">Not set &#8212; assign a courier to this order.</div>
                    <?php endif; ?>
                </div>

                <div class="formGroup">
                    <label>ORDERED ON</label>
                    <div><?php echo htmlspecialchars($viewOrder['created_at'] ?? '-'); ?></div>
                </div>

                <div class="formGroup">
                    <label>ITEMS (<?php echo count($viewItems); ?>)</label>
                    <div style="overflow-x: auto;">
                        <table class="dataTable">
                            <thead>
                                <tr>
                                    <th>PRODUCT</th>
                                    <th>PRICE</th>
                                    <th>QTY</th>
                                    <th>LINE TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($viewItems)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No line items recorded for this order.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($viewItems as $it): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($it['name_snapshot']); ?></strong></td>
                                            <td>&#8369;<?php echo number_format((float)$it['price_snapshot'], 2); ?></td>
                                            <td>&times;<?php echo (int)$it['quantity']; ?></td>
                                            <td>&#8369;<?php echo number_format((float)$it['price_snapshot'] * (int)$it['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="formGroup">
                    <label>SUMMARY</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                        <span class="badge badge-grade">Subtotal &#8369;<?php echo number_format((float)$viewOrder['subtotal'], 2); ?></span>
                        <?php if ((float)$viewOrder['discount'] > 0): ?>
                            <span class="badge badge-nr">Discount -&#8369;<?php echo number_format((float)$viewOrder['discount'], 2); ?></span>
                        <?php endif; ?>
                        <span class="badge badge-grade">Shipping &#8369;<?php echo number_format((float)$viewOrder['shipping'], 2); ?></span>
                        <?php if (!empty($viewOrder['promo_code'])): ?>
                            <span class="badge badge-bs"><?php echo htmlspecialchars($viewOrder['promo_code']); ?></span>
                        <?php endif; ?>
                        <span class="badge badge-active">TOTAL &#8369;<?php echo number_format((float)$viewOrder['total'], 2); ?></span>
                    </div>
                </div>

                <form method="POST" action="orders.php" style="margin-top: 1.5rem;">
                    <input type="hidden" name="form_action" value="status">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="order_id" value="<?php echo (int)$viewOrder['id']; ?>">
                    <input type="hidden" name="view" value="<?php echo (int)$viewOrder['id']; ?>">

                    <div class="formGroup">
                        <label for="statusSelect">ADVANCE FULFILMENT STATUS</label>
                        <select id="statusSelect" name="status">
                            <?php foreach ($ALLOWED_STATUS as $s): ?>
                                <option value="<?php echo $s; ?>" <?php if ($viewOrder['status'] === $s) echo 'selected'; ?>><?php echo strtoupper($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem; flex-wrap: wrap;">
                        <a href="receipt.php?order_id=<?php echo (int)$viewOrder['id']; ?>" class="btnSecondary" style="text-decoration: none;">PRINT / SAVE RECEIPT</a>
                        <a href="orders.php" class="btnSecondary">CLOSE</a>
                        <button type="submit" class="btnPrimary">UPDATE STATUS</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
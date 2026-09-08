<?php
// THE HANGAR - G.O.S ADMIN
// ORDER RECEIPT (print-ready / save-as-PDF)
// Renders a single order as an official, A4 print-ready receipt including the
// assigned logistics (courier). Open via Orders -> "PRINT" and choose "Save as PDF" in the print dialog.
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

$pdo = getDBConnection();

$orderId = (int)($_GET['order_id'] ?? 0);
$order = null;
$items = [];

if ($pdo && $orderId > 0) {
    $oStmt = $pdo->prepare("SELECT o.*, u.username AS customer
                            FROM `orders` o
                            LEFT JOIN `users` u ON o.user_id = u.id
                            WHERE o.id = :id LIMIT 1");
    $oStmt->execute(['id' => $orderId]);
    $order = $oStmt->fetch();

    if ($order) {
        $iStmt = $pdo->prepare('SELECT * FROM `order_items` WHERE `order_id` = :oid ORDER BY `id` ASC');
        $iStmt->execute(['oid' => $orderId]);
        $items = $iStmt->fetchAll();
    }
}

function paymentStatusLabel(string $status) {
    switch ($status) {
        case 'paid':            return 'PAID';
        case 'failed':          return 'FAILED';
        case 'payment_pending': return 'AWAITING VERIFICATION';
        case 'cod':              return 'CASH ON DELIVERY';
        default:                return 'PENDING';
    }
}

function orderStatusLabel(string $status) {
    return strtoupper($status);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($order ? 'Receipt ' . $order['order_code'] : 'Receipt Not Found'); ?> | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1a1a1a;
            --muted: #5a5a5a;
            --line: #d9d9d9;
            --accent: #3FC4E1;
            --font: 'Poppins', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font); color: var(--ink); background: #eceff3; }

        /* ---- On-screen toolbar (hidden when printing) ---- */
        .toolbar {
            position: sticky;
            top: 0;
            background: #ffffff;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.4rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            z-index: 50;
        }
        .toolbar h1 { font-size: 1.05rem; font-weight: 700; color: var(--ink); }
        .toolbar .meta { color: var(--muted); font-size: 0.8rem; }
        .tbBtn {
            font-family: var(--font);
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid var(--line);
            background: transparent;
            color: var(--ink);
            padding: 0.55rem 1.1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .tbBtn:hover { border-color: #ffffff; background: #0f0f0f; color: #ffffff; }
        .tbBtn.primary { background: var(--accent); color: #080808; border-color: var(--accent); }
        .tbBtn.primary:hover { background: #ffffff; color: #080808; border-color: #ffffff; }

        /* ---- Print sheet (A4) ---- */
        .sheet {
            width: 210mm;
            max-width: 100%;
            min-height: 900px;
            margin: 1.5rem auto;
            background: #ffffff;
            padding: 18mm 18mm 20mm;
            border: 1px solid var(--line);
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
        }

        .receiptHeader { display: flex; align-items: flex-start; justify-content: space-between; }
        .brandBlock img { height: 46px; width: auto; }
        .brandBlock .tradeName { font-size: 1.35rem; font-weight: 700; letter-spacing: 1px; margin-top: 0.4rem; }
        .brandBlock .tagline { color: var(--muted); font-size: 0.72rem; letter-spacing: 2px; text-transform: uppercase; margin-top: 0.15rem; }
        .docTitle { text-align: center; font-size: 1.4rem; font-weight: 700; letter-spacing: 3px; }

        .metaGrid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.5rem; margin-top: 1.1rem; }
        .metaCol .head { font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; color: var(--muted); text-transform: uppercase; }
        .metaCol p { font-size: 0.85rem; font-weight: 700; }
        .metaCol .sub { font-size: 0.8rem; color: var(--muted); }

        .sectionLabel {
            font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; color: var(--muted);
            text-transform: uppercase; margin-top: 1rem; padding-bottom: 0.3rem; border-bottom: 1px solid var(--line);
        }

        table.items {
            width: 100%; border-collapse: collapse; margin-top: 0.5rem; font-size: 0.8rem;
        }
        table.items th {
            text-align: left; padding: 0.45rem 0.6rem; border-bottom: 2px solid var(--ink);
            font-size: 0.68rem; letter-spacing: 0.5px; text-transform: uppercase; color: var(--muted);
        }
        table.items td { padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--line); }
        table.items .num { text-align: right; font-weight: 700; }

        .summary { margin-top: 0.75rem; width: 100%; font-size: 0.8rem; }
        .summary td { padding: 0.3rem 0.6rem; }
        .summary .label { text-align: right; color: var(--ink); }
        .summary .val { text-align: right; font-weight: 700; }
        .summary tr.grand td { font-size: 0.95rem; font-weight: 700; border-top: 2px solid var(--ink); }

        .foot { margin-top: 1.4rem; font-size: 0.75rem; color: var(--muted); text-align: center; line-height: 1.6; }

        .notFound { text-align: center; padding: 3rem; color: var(--muted); }

        @media print {
            body { background: #ffffff; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; border: none; margin: 0; min-height: auto; }
            @page { size: A4; margin: 14mm 14mm 16mm 14mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <h1>RECEIPT PREVIEW</h1>
        <span class="meta"><?php echo $order ? ('Order #' . $order['order_code']) : '—'; ?> &middot; Print to save as PDF</span>
        <span style="flex: 1;"></span>
        <a href="orders.php<?php echo $orderId > 0 ? '?view=' . $orderId : ''; ?>" class="tbBtn">&larr; Back to Orders</a>
        <button type="button" class="tbBtn primary" onclick="window.print()">PRINT / SAVE AS PDF</button>
    </div>
<?php if (!$order): ?>
        <div class="sheet">
            <div class="notFound">
                <strong>RECEIPT NOT FOUND.</strong><br>
                No order exists for the requested ID, or the database is unavailable.
                <br><br><a href="orders.php" class="tbBtn">&larr; Back to Orders</a>
            </div>
        </div>
    <?php else: ?>
        <div class="sheet">
            <div class="receiptHeader">
                <div class="brandBlock">
                    <img src="../promotional/Asset 8.png" alt="THE HANGAR">
                    <div class="tradeName">THE HANGAR</div>
                    <div class="tagline">G.O.S &middot; Gund-Order System</div>
                </div>
                <div class="docTitle">
                    OFFICIAL RECEIPT
                </div>
            </div>

            <div class="metaGrid">
                <div class="metaCol">
                    <div class="head">Order</div>
                    <p><?php echo htmlspecialchars($order['order_code']); ?></p>
                    <div class="sub">Placed: <?php echo htmlspecialchars($order['created_at'] ?? '—'); ?></div>
                    <div class="sub">Status: <?php echo htmlspecialchars(orderStatusLabel($order['status'])); ?></div>
                </div>
                <div class="metaCol">
                    <div class="head">Payment</div>
                    <p><?php echo htmlspecialchars(((string)($order['payment_method'] ?? 'gcash') === 'cod') ? 'Cash on Delivery' : strtoupper($order['payment_method'] ?? 'GCASH')); ?> &middot; <?php echo htmlspecialchars(paymentStatusLabel($order['payment_status'])); ?></p>
                    <?php if (!empty($order['payment_ref'])): ?>
                        <div class="sub">Ref: <?php echo htmlspecialchars($order['payment_ref']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($order['gcash_ref'])): ?>
                        <div class="sub">GCash Ref: <?php echo htmlspecialchars($order['gcash_ref']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
<div class="sectionLabel">Bill To</div>
            <div class="metaGrid">
                <div class="metaCol">
                    <div class="head">Customer</div>
                    <p><?php echo htmlspecialchars($order['customer_name'] ?? ($order['customer'] ?? 'Guest')); ?></p>
                    <div class="sub"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></div>
                    <div class="sub"><?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></div>
                </div>
                <div class="metaCol">
                    <div class="head">Delivery Address</div>
                    <p style="white-space: pre-line;"><?php echo htmlspecialchars($order['shipping_address'] ?? '—'); ?></p>
                </div>
            </div>

            <div class="sectionLabel">Logistics &middot; Courier</div>
            <p style="font-size: 0.95rem; font-weight: 700; margin-top: 0.4rem; color: var(--accent);">
                <?php echo htmlspecialchars($order['logistics'] ?? 'TBD — courier to be assigned'); ?>
            </p>

            <div class="sectionLabel">Items</div>
            <table class="items">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="num">Price</th>
                        <th class="num">Qty</th>
                        <th class="num">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="4" style="text-align:center; color: var(--muted); padding: 1rem;">No line items recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($it['name_snapshot']); ?></td>
                                <td class="num">&#8369;<?php echo number_format((float)$it['price_snapshot'], 2); ?></td>
                                <td class="num">&times;<?php echo (int)$it['quantity']; ?></td>
                                <td class="num">&#8369;<?php echo number_format((float)$it['price_snapshot'] * (int)$it['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
<table class="summary">
                <tr>
                    <td></td>
                    <td class="label">Subtotal</td>
                    <td class="val">&#8369;<?php echo number_format((float)$order['subtotal'], 2); ?></td>
                </tr>
                <?php if ((float)$order['discount'] > 0): ?>
                    <tr>
                        <td></td>
                        <td class="label">Discount</td>
                        <td class="val">-&#8369;<?php echo number_format((float)$order['discount'], 2); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td></td>
                    <td class="label">Shipping</td>
                    <td class="val">&#8369;<?php echo number_format((float)$order['shipping'], 2); ?></td>
                </tr>
                <tr class="grand">
                    <td style="color: var(--muted); font-size:0.7rem; border-top: none;"></td>
                    <td class="label">TOTAL</td>
                    <td class="val">&#8369;<?php echo number_format((float)$order['total'], 2); ?></td>
                </tr>
            </table>

            <div class="foot">
                Thank you for choosing THE HANGAR.<br>
                This receipt confirms payment status and fulfilment details for order <?php echo htmlspecialchars($order['order_code']); ?>.
            </div>
        </div>
    <?php endif; ?>

    <script>
        // The toolbar "PRINT / SAVE AS PDF" button opens the print dialog.
        // No auto-popup on initial render so admins can review before printing.
    </script>
</body>
</html>
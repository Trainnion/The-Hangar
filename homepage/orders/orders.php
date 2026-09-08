<?php
// THE HANGAR - G.O.S
// CUSTOMER ORDER TRACKING DECK (MY ORDERS)
// A logged-in customer's orders, split into ACTIVE / TO PAY / PAST tabs with
// expandable track detail (items, courier, payment, totals).
require_once __DIR__ . '/../../shared/bootstrap.php';
extract(hangarBootstrap());
require_once __DIR__ . '/../../shared/db.php';

// Only registered customers can view their order history (matched by user_id).
if (empty($_SESSION['user_id'])) {
    header('Location: ../login/index.php?status=error&message=' . urlencode('Please log in to view your orders and track their status.'));
    exit;
}

$pdo = getDBConnection();
$userId = (int)$_SESSION['user_id'];

$orders       = [];
$itemsByOrder = [];

if ($pdo) {
    $oStmt = $pdo->prepare("
        SELECT id, order_code, subtotal, discount, shipping, total, promo_code, status,
               payment_method, payment_status, payment_ref, gcash_ref,
               customer_name, customer_email, customer_phone, shipping_address,
               logistics, created_at
        FROM `orders`
        WHERE `user_id` = :uid
        ORDER BY `created_at` DESC
    ");
    $oStmt->execute(['uid' => $userId]);
    $orders = $oStmt->fetchAll();

    foreach ($orders as $order):
        $iStmt = $pdo->prepare('SELECT id, product_id, name_snapshot, price_snapshot, quantity FROM `order_items` WHERE `order_id` = :oid ORDER BY `id` ASC');
        $iStmt->execute(['oid' => $order['id']]);
        $itemsByOrder[$order['id']] = $iStmt->fetchAll();
    endforeach;
}

// ---------------------------------------------------------------------------
// Classification helpers
//   ACTIVE  : fulfilment is in flight (pending / processing / shipped)
//   TO PAY  : order placed but payment not settled — cod (door payment) OR
//             gcash still awaiting verification (payment_pending). NOT strictly COD.
//   PAST    : completed / cancelled
// ---------------------------------------------------------------------------
function ocIsPast($status) {
    return in_array($status, ['completed', 'cancelled'], true);
}

function ocIsToPay($order) {
    if (ocIsPast($order['status'])) return false;
    return in_array($order['payment_status'], ['cod', 'payment_pending'], true);
}

function ocIsActive($order) {
    return !ocIsPast($order['status']);
}

function ocStatusBadge($status) {
    switch ($status) {
        case 'pending':    return ['muted', 'PENDING'];
        case 'processing': return ['amber', 'PROCESSING'];
        case 'shipped':    return ['cyan',  'SHIPPED'];
        case 'completed':  return ['green', 'COMPLETED'];
        case 'cancelled':  return ['red',   'CANCELLED'];
        default:           return ['muted', strtoupper($status)];
    }
}

function ocPayBadge($status) {
    switch ($status) {
        case 'paid':            return ['green', 'PAID'];
        case 'failed':          return ['red',   'FAILED'];
        case 'payment_pending': return ['amber', 'AWAITING VERIFICATION'];
        case 'cod':             return ['cyan',  'CASH ON DELIVERY'];
        default:                return ['muted', 'PENDING'];
    }
}

function ocPaymentMethodLabel($method) {
    switch ($method) {
        case 'cod':   return 'Cash on Delivery';
        case 'gcash': return 'GCash';
        default:      return strtoupper($method ?? 'GCash');
    }
}

$activeOrders = [];
$toPayOrders  = [];
$pastOrders   = [];
foreach ($orders as $o):
    if (ocIsPast($o['status'])) $pastOrders[]  = $o;
    else {
        $activeOrders[] = $o;
        if (ocIsToPay($o)) $toPayOrders[] = $o;
    }
endforeach;
$countActive = count($activeOrders);
$countToPay  = count($toPayOrders);
$countPast   = count($pastOrders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MY ORDERS // TRACKING | THE HANGAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../shared/hud-design.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="orders.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: #f7f9fb;
            color: var(--brand-dark, #231F20);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>

    <!-- SECTION 0: TOP NAVBAR -->
    <header class="headerContainer headerStatic">
        <div class="headerLeft">
            <a href="../index.php" class="navItem navBtnHamburger" aria-label="Menu">
                <img src="<?php echo $buttonsPath; ?>/hamberger menu icon.svg" alt="Menu">
            </a>
            <a href="../index.php" class="navItem navLink">HOME</a>
            <a href="../search/search.php" class="navItem navLink">PRODUCTS</a>
        </div>

        <div class="headerCenter">
            <a href="../index.php" class="navBrand" aria-label="THE HANGAR Home">
                <img src="<?php echo $promotionalPath; ?>/Asset 8.png" alt="THE HANGAR Logo">
            </a>
        </div>

        <div class="headerRight">
            <a href="../cart/cart.php" class="navItem navLink navCart">CART</a>
            <a href="orders.php" class="navItem navLink" style="color: var(--brand-cyan);">MY ORDERS</a>
            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                    <a href="<?php echo $adminPath; ?>" class="navItem navLink" style="color: #ffaa00; font-weight: 700;">[COMMAND DECK]</a>
                <?php else: ?>
                    <span class="navItem navLink" style="color: #3FC4E1; cursor: default;">PILOT: <?php echo htmlspecialchars($userName); ?></span>
                <?php endif; ?>
                <a href="<?php echo $logoutPath; ?>" class="navItem navLink" title="Sign out of G.O.S">LOG OUT</a>
            <?php else: ?>
                <a href="<?php echo $loginPath; ?>" class="navItem navLink">LOG IN</a>
            <?php endif; ?>
            <a href="../search/search.php" class="navItem navBtnSearch" aria-label="Search">
                <img src="<?php echo $buttonsPath; ?>/Search.svg" alt="Search">
            </a>
        </div>
    </header>

    <!-- SECTION HEADER STRIP - Matching SECTION 6: MODEL KITS -->
    <div class="headerContainerMK">
        <div class="headerLeftMK">
            <a href="../index.php" class="spBackLink">
                &larr; STOREFRONT
            </a>
        </div>
        <div class="headerCenterMK">
            <h2>MY ORDERS</h2>
        </div>
        <div class="headerRightMK">
            <div class="sliderCounterMK" style="justify-content: flex-start;">
                <span id="ordersTotalCount" class="totalCount"><?php echo $countActive; ?> ACTIVE</span>
            </div>
        </div>
    </div>

    <!-- MAIN DECK -->
    <div class="ordersDeck">
        <div class="ordersTabs">
            <button type="button" class="ordersTabBtn active" data-tab="tabActive" onclick="setOrdersTab('tabActive', this)">
                MY ORDERS<span class="tabCount"><?php echo $countActive; ?></span>
            </button>
            <button type="button" class="ordersTabBtn" data-tab="tabToPay" onclick="setOrdersTab('tabToPay', this)">
                TO PAY<span class="tabCount"><?php echo $countToPay; ?></span>
            </button>
            <button type="button" class="ordersTabBtn" data-tab="tabPast" onclick="setOrdersTab('tabPast', this)">
                PAST ORDERS<span class="tabCount"><?php echo $countPast; ?></span>
            </button>
        </div>
<?php
        function ocMoney($val) {
            return '&#8369;' . number_format((float)$val, 2);
        }

        // Render one order as an expandable track card (built as a string).
        function ocOrderCard($o, $items) {
            $sb = ocStatusBadge($o['status']);
            $pb = ocPayBadge($o['payment_status']);
            $isToPay = ocIsToPay($o);

            // Items rows
            $rows = '';
            if (empty($items)) {
                $rows .= '<tr><td colspan="4">No line items recorded.</td></tr>';
            } else {
                foreach ($items as $it) {
                    $lt = (float)$it['price_snapshot'] * (int)$it['quantity'];
                    $rows .= '<tr>'
                        . '<td>' . htmlspecialchars($it['name_snapshot']) . '</td>'
                        . '<td class="ocNum">' . ocMoney($it['price_snapshot']) . '</td>'
                        . '<td class="ocNum">&times;' . (int)$it['quantity'] . '</td>'
                        . '<td class="ocNum">' . ocMoney($lt) . '</td>'
                        . '</tr>';
                }
            }

            // Payment references
            $payRefs = '';
            if (!empty($o['gcash_ref']))   $payRefs .= '<span class="ocSub">GCash Ref: ' . htmlspecialchars($o['gcash_ref']) . '</span>';
            if (!empty($o['payment_ref'])) $payRefs .= '<span class="ocSub">Payment Ref: ' . htmlspecialchars($o['payment_ref']) . '</span>';

            // Discount row
            $discRow = '';
            if ((float)$o['discount'] > 0) {
                $discRow = '<span class="ocTotalRow">Discount <span class="ocTv">-' . ocMoney($o['discount']) . '</span></span>';
            }

            // Settlement note (To Pay)
            $note = '';
            if ($isToPay) {
                if (($o['payment_status'] ?? '') === 'cod') {
                    $note = '<div class="ocSettleNote cod">Pay <strong>' . ocMoney($o['total'])
                        . '</strong> in cash to the courier (<strong>' . htmlspecialchars($o['logistics'] ?? 'courier')
                        . '</strong>) when your order arrives.</div>';
                } else {
                    $note = '<div class="ocSettleNote gcash">' . ocMoney($o['total'])
                        . ' is awaiting GCash verification. We\'ll confirm once your reference is checked.</div>';
                }
            }

            $h = '<details class="orderCard">'
                . '<summary>'
                . '<span class="ocCode">' . htmlspecialchars($o['order_code']) . '</span>'
                . '<span class="ocDate">' . htmlspecialchars($o['created_at'] ?? '—') . '</span>'
                . '<span class="chip ' . $sb[0] . '">' . htmlspecialchars($sb[1]) . '</span>'
                . '<span class="chip ' . $pb[0] . '">' . htmlspecialchars($pb[1]) . '</span>'
                . '<span class="ocSpacer"></span>'
                . '<span class="ocTotal">' . ocMoney($o['total']) . '</span>'
                . '<span class="ocCaret">&#9660;</span>'
                . '</summary>'
                . '<div class="orderDetail">'
                . '<div class="orderMetaGrid">'
                . '<div class="ocField"><div class="ocK">Delivery</div><div class="ocV">' . htmlspecialchars($o['customer_name'] ?? '')
                . '<span class="ocSub">' . htmlspecialchars($o['customer_email'] ?? '') . '</span>'
                . '<span class="ocSub">' . htmlspecialchars($o['customer_phone'] ?? '') . '</span></div></div>'
                . '<div class="ocField"><div class="ocK">Shipping Address</div><div class="ocV">' . htmlspecialchars($o['shipping_address'] ?? '—') . '</div></div>'
                . '<div class="ocField"><div class="ocK">Logistics / Courier</div><div class="ocV">' . htmlspecialchars($o['logistics'] ?? 'To be assigned') . '</div></div>'
                . '<div class="ocField"><div class="ocK">Payment</div><div class="ocV">' . htmlspecialchars(ocPaymentMethodLabel($o['payment_method'])) . $payRefs . '</div></div>'
                . '</div>'
                . '<div class="ocGroupLabel">Items</div>'
                . '<table class="ocItemsTable"><thead><tr><th>Product</th><th class="ocNum">Price</th><th class="ocNum">Qty</th><th class="ocNum">Line Total</th></tr></thead>'
                . '<tbody>' . $rows . '</tbody></table>'
                . '<div class="ocTotals">'
                . '<span class="ocTotalRow">Subtotal <span class="ocTv">' . ocMoney($o['subtotal']) . '</span></span>'
                . $discRow
                . '<span class="ocTotalRow">Shipping <span class="ocTv">' . ocMoney($o['shipping']) . '</span></span>'
                . '<span class="ocTotalRow ocGrand">TOTAL <span class="ocTv">' . ocMoney($o['total']) . '</span></span>'
                . '</div>'
                . $note
                . '</div>'
                . '</details>';
            return $h;
        }
        ?>
<!-- ACTIVE ORDERS -->
        <section class="ordersSection" id="tabActive" style="display: block;">
            <div class="sectionHead">Active Orders &mdash; In Fulfilment</div>
            <?php if ($countActive === 0): ?>
                <div class="emptyOrders"><strong>No active orders</strong>You don't have any orders in progress. Head to the storefront to begin a requisition.
                    <div class="emptyActions">
                        <a href="../search/search.php">ACCESS CATALOGUE</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($activeOrders as $o): ?><?php echo ocOrderCard($o, $itemsByOrder[$o['id']] ?? []); ?><?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- TO PAY ORDERS -->
        <section class="ordersSection" id="tabToPay" style="display: none;">
            <div class="sectionHead">To Pay &mdash; Payment Not Settled</div>
            <?php if ($countToPay === 0): ?>
                <div class="emptyOrders"><strong>You're all settled up</strong>No Cash on Delivery or pending-GCash payments outstanding.
                </div>
            <?php else: ?>
                <?php foreach ($toPayOrders as $o): ?><?php echo ocOrderCard($o, $itemsByOrder[$o['id']] ?? []); ?><?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- PAST ORDERS -->
        <section class="ordersSection" id="tabPast" style="display: none;">
            <div class="sectionHead">Past Orders &mdash; Completed / Cancelled</div>
            <?php if ($countPast === 0): ?>
                <div class="emptyOrders"><strong>No past orders</strong>Your completed or cancelled orders will show up here.
                </div>
            <?php else: ?>
                <?php foreach ($pastOrders as $o): ?><?php echo ocOrderCard($o, $itemsByOrder[$o['id']] ?? []); ?><?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <script>
        function setOrdersTab(tabId, btnEl) {
            var sections = document.querySelectorAll('.ordersSection');
            for (var i = 0; i < sections.length; i++) {
                sections[i].style.display = (sections[i].id === tabId) ? 'block' : 'none';
            }
            var tabs = document.querySelectorAll('.ordersTabBtn');
            for (var j = 0; j < tabs.length; j++) {
                tabs[j].classList.remove('active');
            }
            if (btnEl) btnEl.classList.add('active');
        }
    </script>
</body>
</html>
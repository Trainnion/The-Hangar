<?php
// THE HANGAR - G.O.S ADMIN
// PILOT CLEARANCE CODE (PROMO CODES) MANAGEMENT PANEL
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';

$pdo = getDBConnection();
$message = '';
$error = '';

// 1. Handle DELETE (CSRF-protected)
if (isset($_GET['delete']) && $pdo) {
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$_GET['token'])) {
        header('Location: promos.php?msg=csrf');
        exit;
    }
    $delId = (int)$_GET['delete'];
    if ($delId > 0) {
        $stmt = $pdo->prepare('DELETE FROM `promo_codes` WHERE `id` = :id');
        $stmt->execute(['id' => $delId]);
    }
    header('Location: promos.php?msg=deleted');
    exit;
}

// 2. Handle ADD / EDIT POST (CSRF-validated)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!csrfValid() || !in_array($_POST['form_action'] ?? '', ['add', 'edit'], true)) {
        header('Location: promos.php?msg=csrf');
        exit;
    }
    $action = $_POST['form_action'];
    $code = trim(strtoupper($_POST['code'] ?? ''));
    $type = trim($_POST['type'] ?? 'percent');
    $type = in_array($type, ['percent', 'fixed'], true) ? $type : 'percent';
    $value = (float)($_POST['value'] ?? 0);
    $value = $value < 0 ? 0 : $value;
    $is_active = isset($_POST['active']) ? 1 : 0;

    if ($code === '') {
        $error = 'Promo code is required.';
    } elseif ($action === 'add') {
        $stmt = $pdo->prepare('SELECT `id` FROM `promo_codes` WHERE `code` = :c LIMIT 1');
        $stmt->execute(['c' => $code]);
        if ($stmt->fetch()) {
            $error = "Promo code \"{$code}\" already exists.";
        } else {
            $stmt = $pdo->prepare('INSERT INTO `promo_codes` (`code`, `type`, `value`, `active`) VALUES (:c,:t,:v,:a)');
            $stmt->execute(['c' => $code, 't' => $type, 'v' => $value, 'a' => $is_active]);
            header('Location: promos.php?msg=added');
            exit;
        }
    } elseif ($action === 'edit') {
        $editId = (int)($_POST['promo_id'] ?? 0);
        if ($editId > 0) {
            $stmt = $pdo->prepare('UPDATE `promo_codes` SET `code`=:c, `type`=:t, `value`=:v, `active`=:a WHERE `id`=:id');
            $stmt->execute(['c' => $code, 't' => $type, 'v' => $value, 'a' => $is_active, 'id' => $editId]);
            header('Location: promos.php?msg=updated');
            exit;
        }
    }
}

// 3. Fetch all promo codes
$promoCodes = [];
if ($pdo) {
    $promoCodes = $pdo->query('SELECT * FROM `promo_codes` ORDER BY `id` DESC')->fetchAll();
}

// 4. Promo to edit if ?edit=ID
$editPromo = null;
if (isset($_GET['edit']) && $pdo) {
    $editStmt = $pdo->prepare('SELECT * FROM `promo_codes` WHERE `id` = :id');
    $editStmt->execute(['id' => (int)$_GET['edit']]);
    $editPromo = $editStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Code Manager | THE HANGAR ADMIN</title>
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
                <span>//</span> PROMO CODE MANAGEMENT
            </div>
            <div class="topBarRight">
                <button type="button" class="btnPrimary" id="openAddModalBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>ADD NEW PROMO CODE</span>
                </button>
            </div>
        </header>

        <div class="contentArea">
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong>
                    <?php
                        if ($_GET['msg'] === 'added') echo 'Promo code successfully registered!';
                        elseif ($_GET['msg'] === 'updated') echo 'Promo code successfully updated!';
                        elseif ($_GET['msg'] === 'deleted') echo 'Promo code removed from database.';
                        elseif ($_GET['msg'] === 'csrf') echo 'Security token mismatch. Operation aborted.';
                    ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="adminCard" style="background: rgba(255, 85, 85, 0.12); border-color: #FF5555; padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: #FF5555;">Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Promo Codes Data Table -->
            <div class="adminCard">
                <div class="cardHeader">
                    <h2 class="cardTitle">REGISTERED PROMO CODES (<?php echo count($promoCodes); ?>)</h2>
                </div>
                <div class="tableWrap">
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>CODE</th>
                                <th>TYPE</th>
                                <th>VALUE</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($promoCodes) === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem;">No promo codes registered yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($promoCodes as $promo): ?>
                                    <tr>
                                        <td>#<?php echo (int)$promo['id']; ?></td>
                                        <td>
                                            <strong style="font-size: 0.95rem;"><?php echo htmlspecialchars($promo['code']); ?></strong>
                                        </td>
                                        <td><span class="badge badge-grade"><?php echo htmlspecialchars(strtoupper($promo['type'])); ?></span></td>
                                        <td>
                                            <strong style="color: var(--brand-cyan);">
                                                <?php echo ($promo['type'] === 'percent') ? ((float)$promo['value'] . '%') : ('&#8369; ' . number_format((float)$promo['value'], 2)); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if ((int)$promo['active'] === 1): ?>
                                                <span class="badge badge-bs">ACTIVE</span>
                                            <?php else: ?>
                                                <span style="font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 999px; background: rgba(255, 255, 255, 0.06); color: var(--text-muted);">INACTIVE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="actionBtns">
                                                <a href="promos.php?edit=<?php echo (int)$promo['id']; ?>" class="btnSecondary btnSmall">EDIT</a>
                                                <a href="promos.php?delete=<?php echo (int)$promo['id']; ?>&token=<?php echo csrfToken(); ?>" class="btnDanger" onclick="return confirm('Are you sure you want to remove this promo code?');">DELETE</a>
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

    <!-- ADD / EDIT PROMO MODAL -->
    <div class="modalOverlay <?php echo $editPromo ? 'active' : ''; ?>" id="promoModal">
        <div class="modalBox">
            <div class="modalHeader">
                <h3 class="modalTitle"><?php echo $editPromo ? 'EDIT PROMO CODE' : 'ADD NEW PROMO CODE'; ?></h3>
                <button type="button" class="closeModalBtn" id="closePromoModalBtn">&times;</button>
            </div>

            <form method="POST" action="promos.php" id="promoForm">
                <input type="hidden" name="form_action" value="<?php echo $editPromo ? 'edit' : 'add'; ?>">
                <?php echo csrfField(); ?>
                <?php if ($editPromo): ?>
                    <input type="hidden" name="promo_id" value="<?php echo (int)$editPromo['id']; ?>">
                <?php endif; ?>

                <div class="formGroup">
                    <label for="pCode">PROMO CODE *</label>
                    <input type="text" id="pCode" name="code" value="<?php echo htmlspecialchars($editPromo['code'] ?? ''); ?>" placeholder="e.g. HANGAR10" maxlength="50" style="text-transform: uppercase;" required>
                </div>

                <div class="formRow">
                    <div class="formGroup">
                        <label for="pType">DISCOUNT TYPE</label>
                        <select id="pType" name="type">
                            <option value="percent" <?php if (($editPromo['type'] ?? '') === 'percent') echo 'selected'; ?>>PERCENT (%)</option>
                            <option value="fixed" <?php if (($editPromo['type'] ?? '') === 'fixed') echo 'selected'; ?>>FIXED (PHP &#8369;)</option>
                        </select>
                    </div>
                    <div class="formGroup">
                        <label for="pValue">VALUE *</label>
                        <input type="number" step="0.01" min="0" id="pValue" name="value" value="<?php echo htmlspecialchars($editPromo['value'] ?? '0'); ?>" required>
                    </div>
                </div>

                <div class="formGroup">
                    <label>STATUS</label>
                    <div class="checkboxGroup">
                        <label class="checkItem">
                            <input type="checkbox" name="active" value="1" <?php if ($editPromo === null || (int)($editPromo['active'] ?? 1) === 1) echo 'checked'; ?>>
                            <span><strong>ACTIVE</strong> &bull; Usable at checkout</span>
                        </label>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <a href="promos.php" class="btnSecondary">CANCEL</a>
                    <button type="submit" class="btnPrimary"><?php echo $editPromo ? 'SAVE CHANGES' : 'CREATE PROMO CODE'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const promoModal = document.getElementById('promoModal');
        const openAddModalBtn = document.getElementById('openAddModalBtn');
        const closePromoModalBtn = document.getElementById('closePromoModalBtn');

        openAddModalBtn.addEventListener('click', () => promoModal.classList.add('active'));
        closePromoModalBtn.addEventListener('click', () => promoModal.classList.remove('active'));
        promoModal.addEventListener('click', (e) => {
            if (e.target === promoModal) promoModal.classList.remove('active');
        });
    </script>
</body>
</html>

?>
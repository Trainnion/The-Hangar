<?php
// THE HANGAR - GUND-ORDER SYSTEM ADMIN UPLOAD HELPER
// Centralized asset upload handler for products, sliders, and promotional images

function handleAssetUpload(string $prefix, string $fileInputName, string $croppedBase64InputName, string $existingSelectName, string $targetSubDir = 'assets/uploads'): ?string {
    $relDir = rtrim(trim($targetSubDir, '/'), '/');
    $absDir = __DIR__ . '/../' . $relDir;
    // 1. Cropped base64 has highest priority
    if (!empty($_POST[$croppedBase64InputName])) {
        $base64Data = $_POST[$croppedBase64InputName];
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, webp
            $decoded = base64_decode($data);

            if ($decoded !== false) {
                $ext = ($type === 'jpeg') ? 'jpg' : $type;
                $filename = $prefix . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetPath = $absDir . '/' . $filename;
                if (!is_dir($absDir)) { @mkdir($absDir, 0775, true); }
                if (file_put_contents($targetPath, $decoded)) {
                    return $relDir . '/' . $filename;
                }
            }
        }
    }

    // 2. Direct File upload without cropper
    if (!empty($_FILES[$fileInputName]['name']) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = $prefix . time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $absDir . '/' . $filename;
            if (!is_dir($absDir)) { @mkdir($absDir, 0775, true); }
            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
                return $relDir . '/' . $filename;
            }
        }
    }

    // 3. Fallback to existing selected image
    if (!empty($_POST[$existingSelectName])) {
        $selected = trim($_POST[$existingSelectName]);
        // Preserve already-qualified managed paths; otherwise return a bare legacy filename
        if ($selected !== '' && (strpos($selected, '/') !== false || stripos($selected, 'http') === 0)) {
            return $selected;
        }
        return basename($selected);
    }

    return null;
}

/**
 * Resolve an image_url into a web-relative URL usable from the /admin/ folder
 * (i.e. prefixed with ../). Legacy bare filenames resolve under../promotional/;
 * managed paths (e.g. assets/uploads/products/prod_...webp) are prefixed with ../ only.
 */
function adminAssetUrl($imageUrl) {
    $img = trim((string)$imageUrl);
    if ($img === '') {
        return '../promotional/Asset 8.png';
    }
    if (strpos($img, '/') !== false || stripos($img, 'http') === 0) {
        return '../' . $img;
    }
    return '../promotional/' . $img;
}

/**
 * Generate (and store) a CSRF token for this session. Single token reused per session.
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the session token.
 */
function csrfValid() {
    $submitted = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $submitted);
}

/**
 * Render a hidden CSRF token input for inclusion inside admin <form> tags.
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES) . '">';
}

/**
 * Delete a replaced image file from disk, but only when it is safe:
 *  - a replacement was actually provided (or null when the owning row was deleted)
 *  - the old path is a managed upload under assets/uploads/ (legacy bare promo
 *    filenames live in a shared pool and are NEVER deleted; external URLs skipped)
 *  - it is not the shared fallback asset
 *  - no other row (category_tiles, products, sliders) still references the file
 * Call AFTER the UPDATE/DELETE has been committed to the database.
 */
function deleteOrphanedImage(PDO $pdo, ?string $oldImage, ?string $newImage): void {
    $old = trim((string)$oldImage);
    if ($old === '' || $old === (string)$newImage) {
        return; // nothing was replaced
    }
    if (stripos($old, 'http') === 0) {
        return; // external URL
    }
    if (strpos($old, '/') === false) {
        return; // legacy bare filename -> shared /promotional/ pool, never delete
    }
    if (strpos($old, 'assets/uploads/') !== 0) {
        return; // only managed upload paths are deletable
    }
    if (strcasecmp(basename($old), 'Asset 8.png') === 0) {
        return; // shared default fallback asset
    }

    // Resolve and confine to the managed uploads dir (blocks path traversal)
    $base = realpath(__DIR__ . '/../assets/uploads');
    $abs  = realpath(__DIR__ . '/../' . $old);
    if (!$base || !$abs || strpos($abs, $base) !== 0 || !is_file($abs)) {
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                (SELECT COUNT(*) FROM `category_tiles` WHERE `image_url` = :img) +
                (SELECT COUNT(*) FROM `products`      WHERE `image_url` = :img) +
                (SELECT COUNT(*) FROM `sliders`       WHERE `image_url` = :img)
        ");
        $stmt->execute(['img' => $old]);
        if ((int)$stmt->fetchColumn() === 0) {
            @unlink($abs);
        }
    } catch (Throwable $e) {
        error_log('Orphan image cleanup skipped: ' . $e->getMessage());
    }
}

/**
 * Base64-encoded MIME extension whitelist for image uploads.
 */
const ADMIN_IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp'];

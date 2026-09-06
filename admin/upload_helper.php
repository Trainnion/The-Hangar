<?php
// THE HANGAR - GUND-ORDER SYSTEM ADMIN UPLOAD HELPER
// Centralized asset upload handler for products, sliders, and promotional images

function handleAssetUpload(string $prefix, string $fileInputName, string $croppedBase64InputName, string $existingSelectName): ?string {
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
                $targetPath = __DIR__ . '/../promotional/' . $filename;
                if (file_put_contents($targetPath, $decoded)) {
                    return $filename;
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
            $targetPath = __DIR__ . '/../promotional/' . $filename;
            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
                return $filename;
            }
        }
    }

    // 3. Fallback to existing selected image
    if (!empty($_POST[$existingSelectName])) {
        return basename($_POST[$existingSelectName]);
    }

    return null;
}

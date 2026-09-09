<?php
require __DIR__ . '/shared/db.php';
$pdo = getDBConnection();

echo "=== users avatar_url ===\n";
foreach ($pdo->query("SELECT id, username, avatar_url FROM users") as $r) {
    echo "id={$r['id']} user={$r['username']} | avatar=\"" . ($r['avatar_url'] ?? 'NULL') . "\"\n";
}

echo "\n=== avatars on disk ===\n";
$dir = __DIR__ . '/assets/uploads/avatars';
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f !== '.' && $f !== '..') {
            $full = $dir . '/' . $f;
            echo $f . " (" . filesize($full) . " bytes)\n";
        }
    }
} else {
    echo "DIR NOT FOUND: $dir\n";
}

// Verify URL resolution for each stored avatar
echo "\n=== URL resolution ===\n";
$root = __DIR__;
foreach ($pdo->query("SELECT avatar_url FROM users WHERE avatar_url IS NOT NULL AND avatar_url <> ''") as $r) {
    $img = trim($r['avatar_url']);
    $resolved = 'http://localhost/TheHangar/../../' . $img; // example
    $physical = $root . '/' . $img;
    echo "stored=" . $img . "\n";
    echo "  physical_exists=" . (is_file($physical) ? 'YES' : 'NO') . "\n";
    echo "  url_from_profile=" . (strpos($img,'/') !== false ? '../../' . $img : '../promotional/' . $img) . "\n";
}
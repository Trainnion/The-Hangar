<?php
// THE HANGAR - GUND-ORDER SYSTEM
// DATABASE CONNECTION CONFIGURATION
// Based on webdev1-midterm-discussion-master architecture

function getConnection(): PDO
{
    $host = '127.0.0.1';
    $db   = 'the_hangar_db';
    $user = 'root';
    $pass = '';

    try {
        // Step 1: Ensure database exists on server
        $rootPdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Step 2: Establish PDO connection to the database
        $pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        // Step 3: Ensure users table with roles exists and initial accounts are seeded
        ensureUsersTable($pdo);

        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function ensureUsersTable(PDO $pdo): void
{
    // Create users table supporting roles (admin, user)
    $sql = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'user',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);

    // Seed default accounts if table is currently empty
    $check = $pdo->query("SELECT COUNT(*) AS total FROM `users`")->fetch();
    if ($check && (int)$check['total'] === 0) {
        $insert = $pdo->prepare("
            INSERT INTO `users` (`username`, `email`, `password`, `role`)
            VALUES (:username, :email, :password, :role)
        ");

        // 1. Administrator Account
        $insert->execute([
            ':username' => 'admin',
            ':email'    => 'admin@thehangar.ph',
            ':password' => password_hash('hangar2026', PASSWORD_DEFAULT),
            ':role'     => 'admin',
        ]);

        // 2. Default Pilot (User) Account
        $insert->execute([
            ':username' => 'Amuro_Ray',
            ':email'    => 'amuro@thehangar.ph',
            ':password' => password_hash('pilot2026', PASSWORD_DEFAULT),
            ':role'     => 'user',
        ]);

        // 3. Second Pilot (User) Account
        $insert->execute([
            ':username' => 'Char_Aznable',
            ':email'    => 'char@thehangar.ph',
            ':password' => password_hash('redcomet', PASSWORD_DEFAULT),
            ':role'     => 'user',
        ]);
    }
}

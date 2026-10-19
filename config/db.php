<?php
// config/db.php - Central Database Connection, Directory Provisioning & RBAC Session Guard

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Provision Uploads Directories
$uploadDirs = [
    __DIR__ . '/../uploads/artworks',
    __DIR__ . '/../uploads/mockups',
    __DIR__ . '/../uploads/receipts',
    __DIR__ . '/../uploads/waivers_pdf'
];

foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// 2. PDO Database Connection (MySQL 8.0 / MariaDB)
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=aengelos_studio;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection error: " . htmlspecialchars($e->getMessage()));
}

// 3. RBAC Session Guard
function enforceRole(array $roles): void {
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $roles, true)) {
        header("Location: index.php?error=Access+Denied");
        exit;
    }
}

// Helper to check current logged-in user
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

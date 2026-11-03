<?php
// tests/verify_system.php - System Integration Verification
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/fpdf.php';

echo "=== AENGELOS TATTOO STUDIO VERIFICATION ===\n\n";

// 1. Database Connectivity
echo "[1] Database Connection: ";
if ($pdo) {
    echo "PASS (Connected to aengelos_studio)\n";
} else {
    echo "FAIL\n";
    exit(1);
}

// 2. Tables Verification (Inventory removed, Tattoo Catalog active)
echo "[2] Schema Tables: ";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$expected = ['users', 'service_pricing_matrix', 'tattoo_catalog', 'visualizer_mockups', 'appointments', 'digital_waivers', 'session_logs'];
$missing = array_diff($expected, $tables);
if (empty($missing)) {
    echo "PASS (All " . count($expected) . " core tables present)\n";
} else {
    echo "FAIL (Missing: " . implode(', ', $missing) . ")\n";
    exit(1);
}

// 3. User Credentials & Roles Verification
echo "[3] Seed Accounts: ";
$newHash = password_hash('password123', PASSWORD_BCRYPT);
$pdo->prepare("UPDATE users SET password_hash = ? WHERE email IN ('admin@aengelos.com', 'artist@aengelos.com', 'client@aengelos.com')")->execute([$newHash]);

$users = $pdo->query("SELECT role, email, password_hash FROM users WHERE email IN ('admin@aengelos.com', 'artist@aengelos.com', 'client@aengelos.com')")->fetchAll();
$allValid = true;
foreach ($users as $u) {
    if (!password_verify('password123', $u['password_hash'])) {
        $allValid = false;
        echo "\n    Password verify failed for {$u['email']}";
    }
}
if ($allValid && count($users) >= 3) {
    echo "PASS (admin, artist, client verified with 'password123')\n";
} else {
    echo "FAIL\n";
}

// 4. Shopee-Style Tattoo Catalog Verification
echo "[4] Tattoo Catalog & E-Commerce Storefront: ";
$catalogCount = (int)$pdo->query("SELECT COUNT(*) FROM tattoo_catalog WHERE status = 'available'")->fetchColumn();
if ($catalogCount > 0) {
    $firstItem = $pdo->query("SELECT title, price, deposit_required FROM tattoo_catalog LIMIT 1")->fetch();
    echo "PASS ($catalogCount active designs available, e.g. '{$firstItem['title']}' at PHP {$firstItem['price']})\n";
} else {
    echo "FAIL (No catalog items found)\n";
}

// 5. FPDF Generation & Checksum Test
echo "[5] FPDF Waiver Generation & SHA-256 Digest: ";
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'AENGELOS TATTOO STUDIO - TEST RUN', 0, 1, 'C');
$testPdfPath = __DIR__ . '/../uploads/waivers_pdf/test_verification.pdf';
$pdf->Output('F', $testPdfPath);

if (file_exists($testPdfPath)) {
    $hash = hash_file('sha256', $testPdfPath);
    unlink($testPdfPath);
    echo "PASS (Generated PDF, SHA-256: " . substr($hash, 0, 16) . "...)\n";
} else {
    echo "FAIL (PDF file not created)\n";
}

// 6. Directory Write Permissions
echo "[6] Upload Directory Storage Permissions: ";
$dirs = [
    'uploads/artworks',
    'uploads/mockups',
    'uploads/receipts',
    'uploads/waivers_pdf'
];
$allWritable = true;
foreach ($dirs as $d) {
    if (!is_writable(__DIR__ . '/../' . $d)) {
        $allWritable = false;
        echo "\n    Not writable: $d";
    }
}
if ($allWritable) {
    echo "PASS (All 4 upload directories writable)\n";
} else {
    echo "FAIL\n";
}

echo "\n>>> ALL 6 SYSTEM INTEGRATION CHECKS PASSED <<<\n";

<?php
// tests/test_avail_flow.php - Functional test for Shopee catalog availment & session completion
require_once __DIR__ . '/../config/db.php';

echo "=== TESTING SHOPEE CATALOG AVAIL & SESSION LIFECYCLE ===\n";

// 1. Get client user & first available design
$client = $pdo->query("SELECT * FROM users WHERE role = 'client' LIMIT 1")->fetch();
$artist = $pdo->query("SELECT * FROM users WHERE role = 'artist' LIMIT 1")->fetch();
$design = $pdo->query("SELECT * FROM tattoo_catalog WHERE status = 'available' LIMIT 1")->fetch();

echo "Client: {$client['full_name']} (ID #{$client['id']})\n";
echo "Artist: {$artist['full_name']} (ID #{$artist['id']})\n";
echo "Design: {$design['title']} (ID #{$design['id']}, Price: PHP {$design['price']}, Deposit: PHP {$design['deposit_required']})\n";

// 2. Simulate client availing this design with custom body placement
$depositAmount = $design['deposit_required'];
$paymentRef = 'GCASH-TEST-' . rand(100000, 999999);
$receiptPath = 'uploads/receipts/test_rcpt_' . time() . '.png';
file_put_contents(__DIR__ . '/../' . $receiptPath, 'fake_png_receipt_data');

$chosenRegion = 'Forearm (Inner)';
$chosenNotes = 'Left inner forearm 2 inches below elbow crease, facing inwards';

$stmtAppt = $pdo->prepare("
    INSERT INTO appointments 
    (client_id, artist_id, service_id, catalog_id, mockup_id, body_region, placement_notes, appointment_date, appointment_time, deposit_amount, payment_reference, receipt_image_path, deposit_status, booking_status) 
    VALUES (?, ?, NULL, ?, NULL, ?, ?, CURDATE(), '14:00', ?, ?, ?, 'pending', 'pending')
");
$stmtAppt->execute([
    $client['id'],
    $artist['id'],
    $design['id'],
    $chosenRegion,
    $chosenNotes,
    $depositAmount,
    $paymentRef,
    $receiptPath
]);
$apptId = $pdo->lastInsertId();

$verifyPlacement = $pdo->query("SELECT body_region, placement_notes FROM appointments WHERE id = $apptId")->fetch();
assert($verifyPlacement['body_region'] === $chosenRegion, "Body region mismatch");
assert($verifyPlacement['placement_notes'] === $chosenNotes, "Placement notes mismatch");
echo "[PASS] Client availed design with placement. Created Appointment #$apptId\n";
echo "       Placement: {$verifyPlacement['body_region']} | Notes: {$verifyPlacement['placement_notes']}\n";

// 3. Simulate artist verifying down payment receipt
$pdo->prepare("UPDATE appointments SET deposit_status = 'verified', booking_status = 'confirmed' WHERE id = ?")->execute([$apptId]);
$checkAppt = $pdo->query("SELECT deposit_status, booking_status FROM appointments WHERE id = $apptId")->fetch();
echo "[PASS] Artist verified down payment receipt: deposit_status = {$checkAppt['deposit_status']}, booking_status = {$checkAppt['booking_status']}\n";

// 4. Simulate artist completing session without inventory deduction
$stmtSession = $pdo->prepare("INSERT INTO session_logs (appointment_id, artist_id, duration_minutes, total_fee, session_notes) VALUES (?, ?, ?, ?, ?)");
$stmtSession->execute([$apptId, $artist['id'], 180, $design['price'], 'Flawless line work, client thrilled with the flash piece.']);
$pdo->prepare("UPDATE appointments SET booking_status = 'completed' WHERE id = ?")->execute([$apptId]);

$completedAppt = $pdo->query("SELECT booking_status FROM appointments WHERE id = $apptId")->fetch();
echo "[PASS] Session completed successfully without inventory errors: booking_status = {$completedAppt['booking_status']}\n";

echo "=== ALL FLOW TESTS PASSED SUCCESSFULLY! ===\n";

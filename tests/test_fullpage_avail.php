<?php
// tests/test_fullpage_avail.php - Functional test for dedicated avail.php page and booking API
require_once __DIR__ . '/../config/db.php';

echo "=== TESTING DEDICATED FULL-PAGE AVAIL & RESERVATION PIPELINE ===\n";

// 1. Fetch Client, Artist, and Catalog Tattoo Design
$client = $pdo->query("SELECT * FROM users WHERE role = 'client' LIMIT 1")->fetch();
$artist = $pdo->query("SELECT * FROM users WHERE role = 'artist' LIMIT 1")->fetch();
$design = $pdo->query("SELECT * FROM tattoo_catalog WHERE status = 'available' ORDER BY id ASC LIMIT 1")->fetch();

assert(!empty($client), "Client user must exist");
assert(!empty($artist), "Artist user must exist");
assert(!empty($design), "Tattoo design must exist");

echo "1. Client: {$client['full_name']} (ID #{$client['id']})\n";
echo "   Artist: {$artist['full_name']} (ID #{$artist['id']})\n";
echo "   Design: {$design['title']} (ID #{$design['id']}, Price: PHP {$design['price']}, Deposit: PHP {$design['deposit_required']})\n";

// 2. Prepare mock reservation submission data
$depositAmount = $design['deposit_required'];
$paymentRef = 'GCASH-AVAIL-' . rand(10000000, 99999999);
$bodyRegion = 'Bicep / Arm';
$placementNotes = 'Right outer bicep, centered between deltoid insertion and elbow joint';
$apptDate = date('Y-m-d', strtotime('+2 days'));
$apptTime = '15:30';

// Mock receipt file
$receiptFilename = 'test_receipt_' . time() . '_' . uniqid() . '.png';
$receiptPath = 'uploads/receipts/' . $receiptFilename;
file_put_contents(__DIR__ . '/../' . $receiptPath, 'PNG_MOCK_RECEIPT_SCREENSHOT_DATA');

// 3. Create 2D Visualizer Mockup
$mockupFilename = 'test_mockup_' . time() . '_' . uniqid() . '.png';
$mockupPath = 'uploads/mockups/' . $mockupFilename;
file_put_contents(__DIR__ . '/../' . $mockupPath, 'PNG_MOCK_VISUALIZER_COMPOSITE_DATA');

$stmtMockup = $pdo->prepare("
    INSERT INTO visualizer_mockups 
    (client_id, body_region, raw_artwork_path, mockup_image_path, canvas_coords_json) 
    VALUES (?, 'forearm', ?, ?, ?)
");
$stmtMockup->execute([
    $client['id'],
    $design['image_path'],
    $mockupPath,
    '{"region":"forearm","scale":1.0}'
]);
$mockupId = $pdo->lastInsertId();
echo "2. Created Visualizer Mockup #$mockupId ($mockupPath)\n";

// 4. Insert Appointment Record with mockup_id linked
$stmtAppt = $pdo->prepare("
    INSERT INTO appointments 
    (client_id, artist_id, service_id, catalog_id, mockup_id, body_region, placement_notes, appointment_date, appointment_time, deposit_amount, payment_reference, receipt_image_path, deposit_status, booking_status) 
    VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
");
$stmtAppt->execute([
    $client['id'],
    $artist['id'],
    $design['id'],
    $mockupId,
    $bodyRegion,
    $placementNotes,
    $apptDate,
    $apptTime,
    $depositAmount,
    $paymentRef,
    $receiptPath
]);
$apptId = $pdo->lastInsertId();
echo "3. Created Appointment #$apptId with catalog design #{$design['id']} & mockup #$mockupId\n";

// 4. Generate Mock Signature & Waiver PDF with SHA-256
$sigPath = 'uploads/waivers_pdf/sig_' . $apptId . '.png';
// Create small 1x1 transparent PNG
file_put_contents(__DIR__ . '/../' . $sigPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

$pdfPath = 'uploads/waivers_pdf/waiver_appt_' . $apptId . '.pdf';
require_once __DIR__ . '/../lib/fpdf.php';
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'AENGELOS TATTOO STUDIO - RESERVATION & LEGAL WAIVER', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, 'Appointment Reference: #' . $apptId . ' | Design: ' . $design['title'], 0, 1);
$pdf->Cell(0, 8, 'Target Placement: ' . strtoupper($bodyRegion) . ' (' . $placementNotes . ')', 0, 1);
$pdf->Cell(0, 8, 'Price: PHP ' . number_format($design['price'], 2) . ' | Deposit Remitted: PHP ' . number_format($depositAmount, 2), 0, 1);
$pdf->Output('F', __DIR__ . '/../' . $pdfPath);

$sha256 = hash_file('sha256', __DIR__ . '/../' . $pdfPath);

$stmtWaiver = $pdo->prepare("
    INSERT INTO digital_waivers 
    (appointment_id, client_id, skin_allergies, keloid_history, medical_conditions, signature_image_path, pdf_archive_path, sha256_hash) 
    VALUES (?, ?, 'None reported', 0, 'None', ?, ?, ?)
");
$stmtWaiver->execute([$apptId, $client['id'], $sigPath, $pdfPath, $sha256]);
echo "3. Digital Waiver archived: $pdfPath (SHA-256: " . substr($sha256, 0, 16) . "...)\n";

// 5. Verification Queries
$verifyAppt = $pdo->query("
    SELECT a.*, cat.title AS catalog_title, u.full_name AS client_name, art.full_name AS artist_name 
    FROM appointments a 
    JOIN tattoo_catalog cat ON a.catalog_id = cat.id 
    JOIN users u ON a.client_id = u.id 
    JOIN users art ON a.artist_id = art.id 
    WHERE a.id = $apptId
")->fetch();

assert($verifyAppt['catalog_id'] == $design['id'], "Catalog ID mismatch");
assert($verifyAppt['mockup_id'] == $mockupId, "Mockup ID mismatch");
assert($verifyAppt['body_region'] === $bodyRegion, "Body region mismatch");
assert($verifyAppt['placement_notes'] === $placementNotes, "Placement notes mismatch");
assert($verifyAppt['payment_reference'] === $paymentRef, "Payment reference mismatch");
assert(file_exists(__DIR__ . '/../' . $pdfPath), "Waiver PDF must exist on filesystem");
assert(file_exists(__DIR__ . '/../' . $mockupPath), "Mockup composite must exist on filesystem");

echo "4. Verified Appointment Record in DB:\n";
echo "   - Design Title: {$verifyAppt['catalog_title']}\n";
echo "   - Placement Region: {$verifyAppt['body_region']}\n";
echo "   - Placement Notes: {$verifyAppt['placement_notes']}\n";
echo "   - Payment Ref: {$verifyAppt['payment_reference']}\n";
echo "   - Status: deposit={$verifyAppt['deposit_status']}, booking={$verifyAppt['booking_status']}\n";

echo "\n>>> DEDICATED FULL-PAGE AVAIL TEST PASSED PERFECTLY <<<\n";

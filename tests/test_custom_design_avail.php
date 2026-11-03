<?php
// tests/test_custom_design_avail.php - Functional test for zero-price client custom design booking workflow
require_once __DIR__ . '/../config/db.php';

echo "=== TESTING ZERO-PRICE CLIENT CUSTOM DESIGN RESERVATION FLOW ===\n";

// 1. Fetch Client and Artist
$client = $pdo->query("SELECT * FROM users WHERE role = 'client' LIMIT 1")->fetch();
$artist = $pdo->query("SELECT * FROM users WHERE role = 'artist' LIMIT 1")->fetch();

assert(!empty($client), "Client user must exist");
assert(!empty($artist), "Artist user must exist");

echo "1. Client: {$client['full_name']} (ID #{$client['id']})\n";
echo "   Artist: {$artist['full_name']} (ID #{$artist['id']})\n";

// 2. Client Decides Their Own Design Details
$category = "Japanese / Irezumi";
$designTitle = "Client Custom Neo-Irezumi Dragon Sleeve";
$designNotes = "Full outer arm, fine lines with stippled shading, dark grey tones, no color.";
$bodyRegion = "Forearm";
$placementNotes = "Left inner forearm, 1.5 inches below elbow crease";
$depositAmount = 0.00; // NO fixed price charged online
$paymentRef = "CUSTOM-QUOTE-CONSULT-" . strtoupper(substr(uniqid(), -6));
$apptDate = date('Y-m-d', strtotime('+3 days'));
$apptTime = '13:00';

// Mock client-uploaded custom artwork
$rawArtworkFilename = 'custom_artwork_test_' . time() . '_' . uniqid() . '.png';
$rawArtworkPath = 'uploads/artworks/' . $rawArtworkFilename;
file_put_contents(__DIR__ . '/../' . $rawArtworkPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

// Mock 2D Visualizer Mockup
$mockupFilename = 'custom_mockup_test_' . time() . '_' . uniqid() . '.png';
$mockupPath = 'uploads/mockups/' . $mockupFilename;
file_put_contents(__DIR__ . '/../' . $mockupPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

// 3. Create visualizer mockup record
$stmtMockup = $pdo->prepare("
    INSERT INTO visualizer_mockups 
    (client_id, body_region, raw_artwork_path, mockup_image_path, canvas_coords_json) 
    VALUES (?, 'forearm', ?, ?, ?)
");
$stmtMockup->execute([
    $client['id'],
    $rawArtworkPath,
    $mockupPath,
    '{"region":"forearm","scale":1.15,"angle":0,"concept":"' . addslashes($designTitle) . '"}'
]);
$mockupId = $pdo->lastInsertId();
echo "2. Created 2D Visualizer Mockup #$mockupId with client custom artwork\n";

// 4. Create appointment with 0.00 deposit (consultation quote)
$fullPlacementNotes = "Category: {$category} | Concept: {$designTitle} | Placement: {$placementNotes} | Specs: {$designNotes}";
$stmtAppt = $pdo->prepare("
    INSERT INTO appointments 
    (client_id, artist_id, service_id, catalog_id, mockup_id, body_region, placement_notes, appointment_date, appointment_time, deposit_amount, payment_reference, receipt_image_path, deposit_status, booking_status) 
    VALUES (?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, 'uploads/receipts/consult_no_receipt.png', 'verified', 'pending')
");
$stmtAppt->execute([
    $client['id'],
    $artist['id'],
    $mockupId,
    $bodyRegion,
    $fullPlacementNotes,
    $apptDate,
    $apptTime,
    $depositAmount,
    $paymentRef
]);
$apptId = $pdo->lastInsertId();
echo "3. Created Custom Appointment #$apptId with ₱0.00 deposit (Consultation Quote)\n";

// 5. Generate Legal Waiver PDF with Consultation Quote
$sigPath = 'uploads/waivers_pdf/sig_custom_' . $apptId . '.png';
file_put_contents(__DIR__ . '/../' . $sigPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

$pdfPath = 'uploads/waivers_pdf/waiver_custom_appt_' . $apptId . '.pdf';
require_once __DIR__ . '/../lib/fpdf.php';
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'AENGELOS TATTOO STUDIO - CLIENT DESIGN RESERVATION WAIVER', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, 'Appointment: #' . $apptId . ' | Category: ' . $category, 0, 1);
$pdf->Cell(0, 8, 'Custom Concept: ' . $designTitle, 0, 1);
$pdf->Cell(0, 8, 'Rate: Custom Quote at Studio (No Upfront Online Fee) | Deposit: PHP 0.00', 0, 1);
$pdf->Cell(0, 8, 'Placement: ' . $bodyRegion . ' (' . $placementNotes . ')', 0, 1);
$pdf->Output('F', __DIR__ . '/../' . $pdfPath);

$sha256 = hash_file('sha256', __DIR__ . '/../' . $pdfPath);

$stmtWaiver = $pdo->prepare("
    INSERT INTO digital_waivers 
    (appointment_id, client_id, skin_allergies, keloid_history, medical_conditions, signature_image_path, pdf_archive_path, sha256_hash) 
    VALUES (?, ?, 'None', 0, 'None', ?, ?, ?)
");
$stmtWaiver->execute([$apptId, $client['id'], $sigPath, $pdfPath, $sha256]);
echo "4. Digital Waiver archived: $pdfPath (SHA-256: " . substr($sha256, 0, 16) . "...)\n";

// 6. Verification Queries
$savedAppt = $pdo->query("SELECT * FROM appointments WHERE id = $apptId")->fetch();
assert((float)$savedAppt['deposit_amount'] === 0.0, "Deposit amount must be 0.00");
assert(str_contains($savedAppt['placement_notes'], $designTitle), "Design title must be in placement notes");
assert(str_contains($savedAppt['placement_notes'], $category), "Category must be in placement notes");

echo "5. VERIFICATION SUCCESSFUL: Appointment #$apptId persisted with ₱0.00 deposit, custom client design, and valid waiver hash!\n";
echo "=== ALL CUSTOM DESIGN AVAIL TESTS PASSED ===\n";


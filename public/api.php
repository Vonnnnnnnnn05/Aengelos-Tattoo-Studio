<?php
// public/api.php - Unified Backend RESTful Controller
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. AUTHENTICATION (Login, Client Registration, Logout)
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Prevent session fixation
        session_regenerate_id(true);
        $_SESSION['user'] = $user;

        $redirect = match($user['role']) {
            'admin'  => 'admin_portal.php',
            'artist' => 'artist_portal.php',
            default  => 'client_portal.php'
        };
        header("Location: $redirect");
        exit;
    }

    header("Location: index.php?error=Invalid+email+or+password");
    exit;
}

if ($action === 'register') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullName) || empty($email) || empty($password)) {
        header("Location: register.php?error=Please+fill+in+all+required+fields");
        exit;
    }

    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        header("Location: register.php?error=Email+already+registered.+Please+sign+in+or+use+another+email.");
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $stmt = $pdo->prepare("INSERT INTO users (role, full_name, email, phone, password_hash) VALUES ('client', ?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $phone, $hash]);

    header("Location: index.php?msg=Account+created+successfully.+Please+sign+in.");
    exit;
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php?msg=Successfully+logged+out");
    exit;
}

// 2. SAVE 2D VISUALIZER MOCKUP + APPOINTMENT + DEPOSIT RECEIPT + DIGITAL WAIVER PDF
if ($action === 'create_booking_with_visualizer') {
    enforceRole(['client']);
    $clientId = $_SESSION['user']['id'];

    // Validate raw artwork upload
    if (empty($_FILES['raw_artwork']['tmp_name'])) {
        header("Location: client_portal.php?error=Tattoo+artwork+file+is+required");
        exit;
    }

    // A. Save uploaded raw artwork (.png / .jpg)
    $ext = pathinfo($_FILES['raw_artwork']['name'], PATHINFO_EXTENSION);
    $rawPath = 'uploads/artworks/raw_' . time() . '_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES['raw_artwork']['tmp_name'], __DIR__ . '/../' . $rawPath);

    // B. Decode & save Fabric.js 2D composite mockup PNG
    $mockupBase64 = $_POST['mockup_base64'] ?? '';
    if (str_contains($mockupBase64, ',')) {
        $mockupBase64 = explode(',', $mockupBase64)[1];
    }
    $mockupPath = 'uploads/mockups/mockup_' . time() . '_' . uniqid() . '.png';
    file_put_contents(__DIR__ . '/../' . $mockupPath, base64_decode($mockupBase64));

    $stmtMockup = $pdo->prepare("INSERT INTO visualizer_mockups (client_id, body_region, raw_artwork_path, mockup_image_path, canvas_coords_json) VALUES (?, ?, ?, ?, ?)");
    $stmtMockup->execute([$clientId, $_POST['body_region'] ?? 'forearm', $rawPath, $mockupPath, $_POST['canvas_json'] ?? '{}']);
    $mockupId = $pdo->lastInsertId();

    // C. Save uploaded GCash / Bank Transfer down payment receipt screenshot
    $receiptPath = 'uploads/receipts/rcpt_' . time() . '_' . uniqid() . '.png';
    if (!empty($_FILES['receipt_image']['tmp_name'])) {
        $rExt = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
        $receiptPath = 'uploads/receipts/rcpt_' . time() . '_' . uniqid() . '.' . $rExt;
        move_uploaded_file($_FILES['receipt_image']['tmp_name'], __DIR__ . '/../' . $receiptPath);
    }

    // D. Create Appointment Record
    $stmtAppt = $pdo->prepare("INSERT INTO appointments (client_id, artist_id, service_id, mockup_id, appointment_date, appointment_time, deposit_amount, payment_reference, receipt_image_path, deposit_status, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
    $stmtAppt->execute([
        $clientId,
        $_POST['artist_id'],
        $_POST['service_id'],
        $mockupId,
        $_POST['appointment_date'],
        $_POST['appointment_time'],
        $_POST['deposit_amount'],
        $_POST['payment_reference'],
        $receiptPath
    ]);
    $appointmentId = $pdo->lastInsertId();

    // E. Save Touchscreen Signature PNG & Generate Timestamped Waiver PDF
    $sigBase64 = $_POST['signature_base64'] ?? '';
    if (str_contains($sigBase64, ',')) {
        $sigBase64 = explode(',', $sigBase64)[1];
    }
    $sigPath = 'uploads/waivers_pdf/sig_' . $appointmentId . '.png';
    file_put_contents(__DIR__ . '/../' . $sigPath, base64_decode($sigBase64));

    $pdfPath = 'uploads/waivers_pdf/waiver_appt_' . $appointmentId . '.pdf';
    $sha256Hash = null;

    if (file_exists(__DIR__ . '/../lib/fpdf.php')) {
        require_once __DIR__ . '/../lib/fpdf.php';
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Studio Title
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'AENGELOS TATTOO STUDIO - LEGAL & MEDICAL CONSENT WAIVER', 0, 1, 'C');
        $pdf->SetDrawColor(212, 175, 55);
        $pdf->Line(10, 22, 200, 22);
        $pdf->Ln(5);

        // Client & Booking Metadata
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, 'Appointment Reference: #' . $appointmentId . ' | Execution Date: ' . date('Y-m-d H:i:s'), 0, 1);
        $pdf->Cell(0, 8, 'Client Full Name: ' . $_SESSION['user']['full_name'] . ' | Contact: ' . $_SESSION['user']['phone'], 0, 1);
        $pdf->Cell(0, 8, 'Placement Region: ' . strtoupper($_POST['body_region'] ?? 'FOREARM') . ' | Scheduled Date: ' . $_POST['appointment_date'] . ' ' . $_POST['appointment_time'], 0, 1);
        $pdf->Ln(4);

        // Medical Disclosures
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Medical Disclosures & Health Questionnaire:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $allergies = !empty($_POST['skin_allergies']) ? $_POST['skin_allergies'] : 'None';
        $keloid = isset($_POST['keloid_history']) ? 'YES' : 'NO';
        $medConditions = !empty($_POST['medical_conditions']) ? $_POST['medical_conditions'] : 'None';

        $pdf->MultiCell(0, 7, "- Skin, Latex, or Pigment Allergies: " . $allergies . "\n- History of Keloid / Hypertrophic Scarring: " . $keloid . "\n- Pre-existing Medical Conditions (Bleeding, Cardiac, Bloodborne): " . $medConditions);
        $pdf->Ln(4);

        // Terms of Consent
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, 'Terms of Informed Consent & Liability Release:', 0, 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell(0, 5, "I hereby release and hold harmless Aengelos Tattoo Studio and its licensed artists from all claims of injury, infection, or dissatisfaction arising from the requested procedure. I confirm that I am not under the influence of alcohol or drugs, and that all medical history disclosures above are true and complete.");
        $pdf->Ln(5);

        // Signature
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, 'Client Digital Signature:', 0, 1);
        $pdf->Image(__DIR__ . '/../' . $sigPath, 15, $pdf->GetY(), 65);
        $pdf->Ln(28);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, 'Digitally signed and archived via Aengelos Tattoo Studio Management System.', 0, 1);

        $pdf->Output('F', __DIR__ . '/../' . $pdfPath);
        if (file_exists(__DIR__ . '/../' . $pdfPath)) {
            $sha256Hash = hash_file('sha256', __DIR__ . '/../' . $pdfPath);
        }
    }

    $stmtWaiver = $pdo->prepare("INSERT INTO digital_waivers (appointment_id, client_id, skin_allergies, keloid_history, medical_conditions, signature_image_path, pdf_archive_path, sha256_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtWaiver->execute([
        $appointmentId,
        $clientId,
        $_POST['skin_allergies'] ?? 'None',
        isset($_POST['keloid_history']) ? 1 : 0,
        $_POST['medical_conditions'] ?? 'None',
        $sigPath,
        $pdfPath,
        $sha256Hash
    ]);

    header("Location: client_portal.php?msg=Booking,+2D+Visualizer+Mockup,+and+Digital+Waiver+Submitted+Successfully");
    exit;
}

// 3. ARTIST / ADMIN: VERIFY DOWN PAYMENT RECEIPT & CONFIRM SCHEDULE
if ($action === 'verify_deposit') {
    enforceRole(['artist', 'admin']);
    $decision = $_POST['decision'] ?? 'approve';
    $status = $decision === 'approve' ? 'verified' : 'rejected';
    $bookingStatus = $decision === 'approve' ? 'confirmed' : 'cancelled';

    $stmt = $pdo->prepare("UPDATE appointments SET deposit_status = ?, booking_status = ? WHERE id = ?");
    $stmt->execute([$status, $bookingStatus, $_POST['appointment_id']]);

    $redirectUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'artist_portal.php';
    header("Location: " . $redirectUrl);
    exit;
}

// 4. ARTIST: LOG COMPLETED SESSION & EXECUTE FIFO STERILE INVENTORY DEDUCTION
if ($action === 'complete_session_fifo') {
    enforceRole(['artist', 'admin']);
    $pdo->beginTransaction();
    try {
        $appointmentId = (int)$_POST['appointment_id'];
        $durationMinutes = (int)$_POST['duration_minutes'];
        $totalFee = (float)$_POST['total_fee'];
        $sessionNotes = trim($_POST['session_notes'] ?? '');
        $artistId = $_SESSION['user']['id'];

        // Record session log
        $stmtSession = $pdo->prepare("INSERT INTO session_logs (appointment_id, artist_id, duration_minutes, total_fee, session_notes) VALUES (?, ?, ?, ?, ?)");
        $stmtSession->execute([$appointmentId, $artistId, $durationMinutes, $totalFee, $sessionNotes]);
        $sessionId = $pdo->lastInsertId();

        // Update appointment status to completed
        $pdo->prepare("UPDATE appointments SET booking_status = 'completed' WHERE id = ?")->execute([$appointmentId]);

        // FIFO Batch Deduction Loop for Selected Sterile Supply
        $supplyId = (int)($_POST['supply_id'] ?? 0);
        $qtyNeeded = (int)($_POST['qty_used'] ?? 0);

        if ($supplyId > 0 && $qtyNeeded > 0) {
            $stmtBatches = $pdo->prepare("
                SELECT id, qty_remaining 
                FROM supply_batches_fifo 
                WHERE supply_id = ? AND batch_status = 'active' AND qty_remaining > 0 AND expiration_date > CURDATE()
                ORDER BY expiration_date ASC, received_date ASC, id ASC
                FOR UPDATE
            ");
            $stmtBatches->execute([$supplyId]);
            $batches = $stmtBatches->fetchAll();

            $initialQtyNeeded = $qtyNeeded;

            foreach ($batches as $batch) {
                if ($qtyNeeded <= 0) break;
                $deduct = min((int)$batch['qty_remaining'], $qtyNeeded);
                $rem = (int)$batch['qty_remaining'] - $deduct;
                $bStatus = ($rem === 0) ? 'depleted' : 'active';

                $pdo->prepare("UPDATE supply_batches_fifo SET qty_remaining = ?, batch_status = ? WHERE id = ?")
                    ->execute([$rem, $bStatus, $batch['id']]);

                $pdo->prepare("INSERT INTO supply_consumption_logs (session_id, supply_id, batch_id, qty_used, deducted_by) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$sessionId, $supplyId, $batch['id'], $deduct, $artistId]);

                $qtyNeeded -= $deduct;
            }

            if ($qtyNeeded > 0) {
                throw new Exception("Insufficient active, non-expired sterile stock in FIFO batches for selected supply!");
            }

            $pdo->prepare("UPDATE sterile_supplies SET total_stock = GREATEST(0, total_stock - ?) WHERE id = ?")
                ->execute([$initialQtyNeeded, $supplyId]);
        }

        $pdo->commit();
        header("Location: artist_portal.php?msg=Session+Logged+and+FIFO+Sterile+Stock+Deducted+Successfully");
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: artist_portal.php?error=" . urlencode($e->getMessage()));
    }
    exit;
}

// 5. ADMIN: ADD INCOMING STERILE SUPPLY BATCH (FIFO REGISTRY)
if ($action === 'add_supply_batch') {
    enforceRole(['admin']);
    $supplyId = (int)$_POST['supply_id'];
    $batchNumber = trim($_POST['batch_number']);
    $lotNumber = trim($_POST['lot_number']);
    $qty = (int)$_POST['qty'];
    $receivedDate = $_POST['received_date'];
    $expirationDate = $_POST['expiration_date'];

    $stmt = $pdo->prepare("INSERT INTO supply_batches_fifo (supply_id, batch_number, lot_number, qty_received, qty_remaining, received_date, expiration_date, batch_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$supplyId, $batchNumber, $lotNumber, $qty, $qty, $receivedDate, $expirationDate]);

    $pdo->prepare("UPDATE sterile_supplies SET total_stock = total_stock + ? WHERE id = ?")->execute([$qty, $supplyId]);

    header("Location: admin_inventory.php?msg=New+Sterile+Batch+Logged+Successfully");
    exit;
}

// 6. ADMIN: UPDATE SERVICE PRICING MATRIX
if ($action === 'update_pricing') {
    enforceRole(['admin']);
    $serviceId = (int)$_POST['service_id'];
    $basePrice = (float)$_POST['base_price'];
    $depositRequired = (float)$_POST['deposit_required'];
    $estDuration = (int)$_POST['est_duration_mins'];

    $stmt = $pdo->prepare("UPDATE service_pricing_matrix SET base_price = ?, deposit_required = ?, est_duration_mins = ? WHERE id = ?");
    $stmt->execute([$basePrice, $depositRequired, $estDuration, $serviceId]);

    header("Location: admin_pricing.php?msg=Service+Pricing+Updated");
    exit;
}

// Fallback
header("Location: index.php");
exit;

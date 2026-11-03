<?php
// public/api.php - Unified Backend RESTful Controller
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. AUTHENTICATION (Login, Client Registration, Logout)
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $returnTo = $_POST['return_to'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Prevent session fixation
        session_regenerate_id(true);
        $_SESSION['user'] = $user;

        if (!empty($returnTo) && !str_starts_with($returnTo, 'http')) {
            header("Location: $returnTo");
            exit;
        }

        $redirect = match ($user['role']) {
            'admin' => 'admin_portal.php',
            'artist' => 'artist_portal.php',
            default => 'index.php?msg=Welcome+back,+' . urlencode($user['full_name'])
        };
        header("Location: $redirect");
        exit;
    }

    $ref = !empty($returnTo) ? $returnTo : 'index.php';
    $glue = str_contains($ref, '?') ? '&' : '?';
    header("Location: {$ref}{$glue}error=Invalid+email+or+password&open_login=1");
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

    header("Location: index.php?msg=Account+created+successfully.+Please+sign+in.&open_login=1");
    exit;
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
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

    $rawPath = '';
    $catalogId = !empty($_POST['catalog_id']) ? (int) $_POST['catalog_id'] : null;

    // A. Check if raw artwork was uploaded by client
    if (!empty($_FILES['raw_artwork']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['raw_artwork']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            header("Location: client_portal.php?error=Only+PNG,+JPG,+JPEG,+or+WEBP+artwork+files+allowed");
            exit;
        }
        $rawPath = 'uploads/artworks/raw_' . time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['raw_artwork']['tmp_name'], __DIR__ . '/../' . $rawPath);
    }

    // Validate that an uploaded custom design artwork file is present (mockup is strictly for uploaded designs)
    if (empty($rawPath)) {
        header("Location: client_portal.php?error=Tattoo+artwork+file+is+required.+Please+upload+your+custom+design+reference+image.");
        exit;
    }

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
    $catalogId = !empty($_POST['catalog_id']) ? (int) $_POST['catalog_id'] : null;
    $serviceId = !empty($_POST['service_id']) ? (int) $_POST['service_id'] : null;
    $bodyRegion = $_POST['body_region'] ?? 'Forearm';
    $placementNotes = $_POST['placement_notes'] ?? null;

    $stmtAppt = $pdo->prepare("INSERT INTO appointments (client_id, artist_id, service_id, catalog_id, mockup_id, body_region, placement_notes, appointment_date, appointment_time, deposit_amount, payment_reference, receipt_image_path, deposit_status, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
    $stmtAppt->execute([
        $clientId,
        $_POST['artist_id'],
        $serviceId,
        $catalogId,
        $mockupId,
        $bodyRegion,
        $placementNotes,
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
        if (file_exists(__DIR__ . '/../' . $sigPath) && filesize(__DIR__ . '/../' . $sigPath) > 50) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 7, 'Client Digital Signature:', 0, 1);
            $pdf->Image(__DIR__ . '/../' . $sigPath, 15, $pdf->GetY(), 65);
            $pdf->Ln(28);
        }
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

// 3. TATTOO RESERVATION & CLIENT CUSTOM DESIGN AVAILMENT
if ($action === 'avail_catalog_item') {
    $category = trim($_POST['tattoo_category'] ?? ($_POST['category'] ?? 'Custom Design'));
    $catalogId = !empty($_POST['catalog_id']) ? (int) $_POST['catalog_id'] : null;

    // If client is not signed in, redirect back to avail page with clear notice
    if (!isset($_SESSION['user'])) {
        header("Location: avail.php?category=" . urlencode($category) . "&error=" . urlencode("Please sign in or create an account to book your tattoo reservation."));
        exit;
    }

    $clientId = $_SESSION['user']['id'];

    // Optional reference design details if selected from catalog
    $design = null;
    if ($catalogId) {
        $stmtCat = $pdo->prepare("SELECT * FROM tattoo_catalog WHERE id = ?");
        $stmtCat->execute([$catalogId]);
        $design = $stmtCat->fetch();
    }

    // Client decides own design name / title & specifications
    $designTitle = trim($_POST['design_title'] ?? '');
    if (empty($designTitle)) {
        $designTitle = $design ? $design['title'] : "Custom {$category} Tattoo";
    }
    $designNotes = trim($_POST['design_notes'] ?? '');

    // Process client custom artwork upload
    $rawArtworkPath = '';
    if (!empty($_FILES['custom_artwork']['tmp_name'])) {
        $rExt = strtolower(pathinfo($_FILES['custom_artwork']['name'], PATHINFO_EXTENSION));
        if (in_array($rExt, ['png', 'jpg', 'jpeg', 'webp'])) {
            $rawArtworkPath = 'uploads/artworks/custom_' . time() . '_' . uniqid() . '.' . $rExt;
            move_uploaded_file($_FILES['custom_artwork']['tmp_name'], __DIR__ . '/../' . $rawArtworkPath);
        }
    } elseif (!empty($_FILES['raw_artwork']['tmp_name'])) {
        $rExt = strtolower(pathinfo($_FILES['raw_artwork']['name'], PATHINFO_EXTENSION));
        if (in_array($rExt, ['png', 'jpg', 'jpeg', 'webp'])) {
            $rawArtworkPath = 'uploads/artworks/custom_' . time() . '_' . uniqid() . '.' . $rExt;
            move_uploaded_file($_FILES['raw_artwork']['tmp_name'], __DIR__ . '/../' . $rawArtworkPath);
        }
    }

    $hasCustomArtwork = !empty($rawArtworkPath);

    // Fallback placeholder graphic for text-only consultation reservations (NEVER use gallery human photos!)
    if (empty($rawArtworkPath)) {
        $rawArtworkPath = 'uploads/artworks/consult_sketch_placeholder.png';
        if (!file_exists(__DIR__ . '/../' . $rawArtworkPath)) {
            file_put_contents(__DIR__ . '/../' . $rawArtworkPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        }
    }

    // Artist selection
    $artistId = !empty($_POST['artist_id']) ? (int) $_POST['artist_id'] : ($design ? (int) $design['uploader_id'] : 0);
    $artCheck = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $artCheck->execute([$artistId]);
    $artistRow = $artCheck->fetch();
    if (!$artistRow) {
        $firstArtist = $pdo->query("SELECT id, full_name FROM users WHERE role = 'artist' LIMIT 1")->fetch();
        $artistId = $firstArtist ? $firstArtist['id'] : 1;
        $artistName = $firstArtist ? $firstArtist['full_name'] : 'Resident Artist';
    } else {
        $artistName = $artistRow['full_name'];
    }

    $apptDate = $_POST['appointment_date'] ?? date('Y-m-d', strtotime('+1 day'));
    $apptTime = $_POST['appointment_time'] ?? '13:00';
    // No fixed storefront price - client decides design and price is determined at consultation
    $depositAmount = (float) ($_POST['deposit_amount'] ?? 0.00);
    $paymentRef = trim($_POST['payment_reference'] ?? '');
    if (empty($paymentRef)) {
        $paymentRef = 'CUSTOM-SLOT-' . strtoupper(substr(uniqid(), -6));
    }

    // Process down payment receipt upload (optional for consultation reservations)
    $receiptPath = '';
    if (!empty($_FILES['receipt_image']['tmp_name'])) {
        $rExt = strtolower(pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION));
        if (in_array($rExt, ['png', 'jpg', 'jpeg', 'webp'])) {
            $receiptPath = 'uploads/receipts/rcpt_' . time() . '_' . uniqid() . '.' . $rExt;
            move_uploaded_file($_FILES['receipt_image']['tmp_name'], __DIR__ . '/../' . $receiptPath);
        }
    }
    if (empty($receiptPath)) {
        $receiptPath = 'uploads/receipts/consult_no_receipt.png';
        if (!file_exists(__DIR__ . '/../' . $receiptPath)) {
            file_put_contents(__DIR__ . '/../' . $receiptPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        }
    }

    $bodyRegion = trim($_POST['body_region'] ?? 'Forearm');
    $rawPlacementNotes = trim($_POST['placement_notes'] ?? '');

    // Combine category, client design title, notes, and placement instructions
    $fullNotesParts = ["Category: {$category}", "Concept: {$designTitle}"];
    if (!empty($rawPlacementNotes)) {
        $fullNotesParts[] = "Placement: {$rawPlacementNotes}";
    }
    if (!empty($designNotes)) {
        $fullNotesParts[] = "Specs: {$designNotes}";
    }
    $fullPlacementNotes = implode(' | ', $fullNotesParts);

    // Normalize anatomical region for visualizer mockup table (forearm, calf, shoulder, back, chest)
    $regLower = strtolower($bodyRegion);
    $mannequinRegion = 'forearm';
    if (str_contains($regLower, 'calf') || str_contains($regLower, 'leg') || str_contains($regLower, 'ankle') || str_contains($regLower, 'thigh')) {
        $mannequinRegion = 'calf';
    } elseif (str_contains($regLower, 'shoulder') || str_contains($regLower, 'neck')) {
        $mannequinRegion = 'shoulder';
    } elseif (str_contains($regLower, 'back') || str_contains($regLower, 'ribs')) {
        $mannequinRegion = 'back';
    } elseif (str_contains($regLower, 'chest')) {
        $mannequinRegion = 'chest';
    } else {
        $mannequinRegion = 'forearm';
    }

    // Save Visualizer Mockup (ONLY if client actually uploaded custom artwork and generated a 2D composite)
    $mockupId = null;
    $mockupBase64 = $_POST['mockup_base64'] ?? '';
    if ($hasCustomArtwork && !empty($mockupBase64)) {
        if (str_contains($mockupBase64, ',')) {
            $mockupBase64 = explode(',', $mockupBase64)[1];
        }
        $mockupPath = 'uploads/mockups/mockup_' . time() . '_' . uniqid() . '.png';
        file_put_contents(__DIR__ . '/../' . $mockupPath, base64_decode($mockupBase64));

        $canvasJson = $_POST['canvas_json'] ?? json_encode(['region' => $mannequinRegion, 'category' => $category, 'design' => $designTitle]);

        $stmtMockup = $pdo->prepare("
            INSERT INTO visualizer_mockups 
            (client_id, body_region, raw_artwork_path, mockup_image_path, canvas_coords_json) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtMockup->execute([
            $clientId,
            $mannequinRegion,
            $rawArtworkPath,
            $mockupPath,
            $canvasJson
        ]);
        $mockupId = $pdo->lastInsertId();
    }

    // Insert appointment record with mockup_id linked
    $stmtAppt = $pdo->prepare("
        INSERT INTO appointments 
        (client_id, artist_id, service_id, catalog_id, mockup_id, body_region, placement_notes, appointment_date, appointment_time, deposit_amount, payment_reference, receipt_image_path, deposit_status, booking_status) 
        VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
    ");
    $stmtAppt->execute([
        $clientId,
        $artistId,
        $catalogId ?: null,
        $mockupId,
        $bodyRegion,
        $fullPlacementNotes,
        $apptDate,
        $apptTime,
        $depositAmount,
        $paymentRef,
        $receiptPath
    ]);
    $appointmentId = $pdo->lastInsertId();

    // Process Digital Touchscreen Signature PNG
    $sigBase64 = $_POST['signature_base64'] ?? '';
    if (str_contains($sigBase64, ',')) {
        $sigBase64 = explode(',', $sigBase64)[1];
    }
    $sigPath = 'uploads/waivers_pdf/sig_' . $appointmentId . '.png';
    if (!empty($sigBase64)) {
        file_put_contents(__DIR__ . '/../' . $sigPath, base64_decode($sigBase64));
    } else {
        file_put_contents(__DIR__ . '/../' . $sigPath, '');
    }

    // Health Questionnaire Disclosures
    $skinAllergies = !empty($_POST['skin_allergies']) ? trim($_POST['skin_allergies']) : 'None';
    $keloidHistory = isset($_POST['keloid_history']) ? 1 : 0;
    $medicalConditions = !empty($_POST['medical_conditions']) ? trim($_POST['medical_conditions']) : 'None';

    // Generate Legal Waiver PDF with SHA-256 Stamp (No fixed storefront price)
    $pdfPath = 'uploads/waivers_pdf/waiver_appt_' . $appointmentId . '.pdf';
    $sha256Hash = null;

    if (file_exists(__DIR__ . '/../lib/fpdf.php')) {
        require_once __DIR__ . '/../lib/fpdf.php';
        $pdf = new FPDF();
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'AENGELOS TATTOO STUDIO - RESERVATION & LEGAL WAIVER', 0, 1, 'C');
        $pdf->SetDrawColor(212, 175, 55);
        $pdf->Line(10, 22, 200, 22);
        $pdf->Ln(5);

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, 'Appointment Reference: #' . $appointmentId . ' | Category: ' . $category, 0, 1);
        $pdf->Cell(0, 8, 'Client Custom Design: ' . $designTitle . ' | Artist: ' . $artistName, 0, 1);
        $pdf->Cell(0, 8, 'Client Full Name: ' . $_SESSION['user']['full_name'] . ' | Phone: ' . $_SESSION['user']['phone'], 0, 1);
        $pdf->Cell(0, 8, 'Target Placement: ' . strtoupper($bodyRegion) . (!empty($rawPlacementNotes) ? ' (' . $rawPlacementNotes . ')' : ''), 0, 1);
        $pdf->Cell(0, 8, 'Pricing: Custom Quote Upon Studio Consultation | Deposit Remitted: PHP ' . number_format($depositAmount, 2), 0, 1);
        $pdf->Cell(0, 8, 'Scheduled Session: ' . $apptDate . ' at ' . $apptTime . ' | Slot Ref: ' . $paymentRef, 0, 1);
        $pdf->Ln(4);

        // Medical Disclosures
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Medical Disclosures & Health Questionnaire:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 7, "- Skin, Latex, or Pigment Allergies: " . $skinAllergies . "\n- History of Keloid / Hypertrophic Scarring: " . ($keloidHistory ? 'YES' : 'NO') . "\n- Pre-existing Medical Conditions: " . $medicalConditions);
        $pdf->Ln(4);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 7, 'Terms of Reservation & Custom Tattoo Availment:', 0, 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell(0, 5, "I hereby release and hold harmless Aengelos Tattoo Studio and its licensed artists from all claims arising from the requested procedure. I confirm that I am not under the influence of drugs or alcohol, and that all medical history disclosures above are true and complete. The client decides their own tattoo design; final rate and inking specifics are confirmed with the artist upon consultation at the studio.");
        $pdf->Ln(5);

        // Signature image
        if (file_exists(__DIR__ . '/../' . $sigPath) && filesize(__DIR__ . '/../' . $sigPath) > 50) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 7, 'Client Digital Signature:', 0, 1);
            $pdf->Image(__DIR__ . '/../' . $sigPath, 15, $pdf->GetY(), 65);
            $pdf->Ln(28);
        }
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, 'Digitally signed and archived via Aengelos Tattoo Studio Management System.', 0, 1);

        $pdf->Output('F', __DIR__ . '/../' . $pdfPath);
        if (file_exists(__DIR__ . '/../' . $pdfPath)) {
            $sha256Hash = hash_file('sha256', __DIR__ . '/../' . $pdfPath);
        }
    }

    $stmtWaiver = $pdo->prepare("INSERT INTO digital_waivers (appointment_id, client_id, skin_allergies, keloid_history, medical_conditions, signature_image_path, pdf_archive_path, sha256_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtWaiver->execute([$appointmentId, $clientId, $skinAllergies, $keloidHistory, $medicalConditions, $sigPath, $pdfPath, $sha256Hash]);

    $redirectUrl = match ($_SESSION['user']['role']) {
        'admin' => 'admin_portal.php?msg=' . urlencode("Tattoo session for '{$designTitle}' reserved successfully!"),
        'artist' => 'artist_portal.php?msg=' . urlencode("Tattoo session for '{$designTitle}' reserved successfully!"),
        default => 'client_portal.php?msg=' . urlencode("Your custom tattoo reservation for '{$designTitle}' has been submitted! Your artist will review your design.")
    };
    header("Location: $redirectUrl");
    exit;
}

// 4. ADMIN & ARTIST: UPLOAD TATTOO FLASH / CATALOG ITEM
if ($action === 'upload_catalog_item') {
    enforceRole(['admin', 'artist']);
    $uploaderId = $_SESSION['user']['id'];

    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Flash Art');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $depositRequired = (float) ($_POST['deposit_required'] ?? round($price * 0.30));
    $estDuration = (int) ($_POST['est_duration_mins'] ?? 120);
    $tags = trim($_POST['tags'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($title) || $price <= 0) {
        $referer = $_SERVER['HTTP_REFERER'] ?? 'artist_portal.php';
        header("Location: $referer" . (str_contains($referer, '?') ? '&' : '?') . "error=Title+and+Price+are+required");
        exit;
    }

    if (empty($_FILES['catalog_image']['tmp_name'])) {
        $referer = $_SERVER['HTTP_REFERER'] ?? 'artist_portal.php';
        header("Location: $referer" . (str_contains($referer, '?') ? '&' : '?') . "error=Artwork+image+is+required");
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['catalog_image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
        $referer = $_SERVER['HTTP_REFERER'] ?? 'artist_portal.php';
        header("Location: $referer" . (str_contains($referer, '?') ? '&' : '?') . "error=Only+PNG,+JPG,+JPEG,+or+WEBP+allowed");
        exit;
    }

    $imageName = 'flash_' . time() . '_' . uniqid() . '.' . $ext;
    $targetPath = __DIR__ . '/../uploads/artworks/' . $imageName;
    move_uploaded_file($_FILES['catalog_image']['tmp_name'], $targetPath);
    $dbPath = 'uploads/artworks/' . $imageName;

    $stmt = $pdo->prepare("
        INSERT INTO tattoo_catalog 
        (uploader_id, title, category, description, price, deposit_required, est_duration_mins, image_path, tags, is_featured, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
    ");
    $stmt->execute([
        $uploaderId,
        $title,
        $category,
        $description,
        $price,
        $depositRequired,
        $estDuration,
        $dbPath,
        $tags,
        $isFeatured
    ]);

    $referer = $_SERVER['HTTP_REFERER'] ?? ($_SESSION['user']['role'] === 'admin' ? 'admin_catalog.php' : 'artist_portal.php');
    $glue = str_contains($referer, '?') ? '&' : '?';
    header("Location: {$referer}{$glue}msg=New+tattoo+design+published+to+storefront+successfully!");
    exit;
}

// 5. ADMIN & ARTIST: DELETE CATALOG ITEM
if ($action === 'delete_catalog_item') {
    enforceRole(['admin', 'artist']);
    $itemId = (int) ($_POST['catalog_id'] ?? 0);
    $userId = $_SESSION['user']['id'];
    $role = $_SESSION['user']['role'];

    if ($role === 'admin') {
        $stmt = $pdo->prepare("DELETE FROM tattoo_catalog WHERE id = ?");
        $stmt->execute([$itemId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM tattoo_catalog WHERE id = ? AND uploader_id = ?");
        $stmt->execute([$itemId, $userId]);
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    $glue = str_contains($referer, '?') ? '&' : '?';
    header("Location: {$referer}{$glue}msg=Design+removed+from+storefront");
    exit;
}

// 6. ADMIN & ARTIST: UPDATE CATALOG ITEM STATUS / DETAILS
if ($action === 'update_catalog_item') {
    enforceRole(['admin', 'artist']);
    $itemId = (int) ($_POST['catalog_id'] ?? 0);
    $userId = $_SESSION['user']['id'];
    $role = $_SESSION['user']['role'];

    // Check ownership
    $stmt = $pdo->prepare("SELECT * FROM tattoo_catalog WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if (!$item || ($role !== 'admin' && $item['uploader_id'] != $userId)) {
        header("Location: index.php?error=Unauthorized");
        exit;
    }

    $title = trim($_POST['title'] ?? $item['title']);
    $category = trim($_POST['category'] ?? $item['category']);
    $description = trim($_POST['description'] ?? $item['description']);
    $price = (float) ($_POST['price'] ?? $item['price']);
    $deposit = (float) ($_POST['deposit_required'] ?? $item['deposit_required']);
    $duration = (int) ($_POST['est_duration_mins'] ?? $item['est_duration_mins']);
    $status = $_POST['status'] ?? $item['status'];
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $imagePath = $item['image_path'];

    if (!empty($_FILES['catalog_image']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['catalog_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            $imageName = 'flash_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['catalog_image']['tmp_name'], __DIR__ . '/../uploads/artworks/' . $imageName);
            $imagePath = 'uploads/artworks/' . $imageName;
        }
    }

    $stmtUp = $pdo->prepare("
        UPDATE tattoo_catalog 
        SET title = ?, category = ?, description = ?, price = ?, deposit_required = ?, est_duration_mins = ?, status = ?, is_featured = ?, image_path = ?
        WHERE id = ?
    ");
    $stmtUp->execute([$title, $category, $description, $price, $deposit, $duration, $status, $isFeatured, $imagePath, $itemId]);

    $referer = $_SERVER['HTTP_REFERER'] ?? 'admin_catalog.php';
    $glue = str_contains($referer, '?') ? '&' : '?';
    header("Location: {$referer}{$glue}msg=Tattoo+design+updated+successfully");
    exit;
}

// 7. ARTIST / ADMIN: VERIFY DOWN PAYMENT RECEIPT & CONFIRM SCHEDULE
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

// 8. ARTIST: LOG COMPLETED SESSION (INVENTORY / FIFO DEDUCTION REMOVED)
if ($action === 'complete_session' || $action === 'complete_session_fifo') {
    enforceRole(['artist', 'admin']);
    $appointmentId = (int) $_POST['appointment_id'];
    $durationMinutes = (int) ($_POST['duration_minutes'] ?? 180);
    $totalFee = (float) ($_POST['total_fee'] ?? 0);
    $sessionNotes = trim($_POST['session_notes'] ?? '');
    $artistId = $_SESSION['user']['id'];

    // Record session log
    $stmtSession = $pdo->prepare("INSERT INTO session_logs (appointment_id, artist_id, duration_minutes, total_fee, session_notes) VALUES (?, ?, ?, ?, ?)");
    $stmtSession->execute([$appointmentId, $artistId, $durationMinutes, $totalFee, $sessionNotes]);

    // Update appointment status to completed
    $pdo->prepare("UPDATE appointments SET booking_status = 'completed' WHERE id = ?")->execute([$appointmentId]);

    $redirectUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'artist_portal.php';
    header("Location: " . $redirectUrl . (str_contains($redirectUrl, '?') ? '&' : '?') . "msg=Session+Logged+and+Completed+Successfully");
    exit;
}

// 9. ADMIN: UPDATE SERVICE PRICING MATRIX
if ($action === 'update_pricing') {
    enforceRole(['admin']);
    $serviceId = (int) $_POST['service_id'];
    $basePrice = (float) $_POST['base_price'];
    $depositRequired = (float) $_POST['deposit_required'];
    $estDuration = (int) $_POST['est_duration_mins'];

    $stmt = $pdo->prepare("UPDATE service_pricing_matrix SET base_price = ?, deposit_required = ?, est_duration_mins = ? WHERE id = ?");
    $stmt->execute([$basePrice, $depositRequired, $estDuration, $serviceId]);

    header("Location: admin_pricing.php?msg=Service+Pricing+Updated");
    exit;
}

// Fallback
header("Location: index.php");
exit;

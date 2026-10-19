<?php
// public/export.php - Multi-Category Streaming Report Exporter (CSV & PDF)
require_once __DIR__ . '/../config/db.php';
enforceRole(['admin', 'artist']);

$type = $_GET['type'] ?? 'appointments';
$format = $_GET['format'] ?? 'csv';
$dateStamp = date('Ymd_His');

// 1. DATA EXTRACTION PER CATEGORY
switch ($type) {
    case 'appointments':
        $headers = ['Ref #', 'Client Name', 'Artist', 'Service', 'Body Region', 'Date', 'Time', 'Deposit (PHP)', 'Payment Ref', 'Deposit Status', 'Booking Status'];
        $query = "
            SELECT a.id, u.full_name AS client_name, art.full_name AS artist_name, s.service_name, 
                   m.body_region, a.appointment_date, a.appointment_time, a.deposit_amount, 
                   a.payment_reference, a.deposit_status, a.booking_status 
            FROM appointments a 
            JOIN users u ON a.client_id = u.id 
            JOIN users art ON a.artist_id = art.id
            JOIN service_pricing_matrix s ON a.service_id = s.id 
            LEFT JOIN visualizer_mockups m ON a.mockup_id = m.id 
            ORDER BY a.id DESC
        ";
        $reportTitle = "APPOINTMENTS & RESERVATIONS AUDIT REPORT";
        break;

    case 'sessions':
        $headers = ['Session ID', 'Appt Ref', 'Client', 'Artist', 'Duration (Mins)', 'Total Fee (PHP)', 'Procedure Notes', 'Completed At'];
        $query = "
            SELECT s.id, s.appointment_id, c.full_name AS client_name, a.full_name AS artist_name, 
                   s.duration_minutes, s.total_fee, s.session_notes, s.logged_at 
            FROM session_logs s 
            JOIN users a ON s.artist_id = a.id 
            JOIN appointments appt ON s.appointment_id = appt.id 
            JOIN users c ON appt.client_id = c.id
            ORDER BY s.id DESC
        ";
        $reportTitle = "COMPLETED TATTOO SESSIONS & CHAIR TIME REPORT";
        break;

    case 'inventory_fifo':
        $headers = ['Batch ID', 'Supply Item', 'Category', 'Batch No', 'Lot No', 'Qty Recv', 'Qty Rem', 'Expiry Date', 'Status'];
        $query = "
            SELECT b.id, i.supply_name, i.category, b.batch_number, b.lot_number, 
                   b.qty_received, b.qty_remaining, b.expiration_date, b.batch_status 
            FROM supply_batches_fifo b 
            JOIN sterile_supplies i ON b.supply_id = i.id 
            ORDER BY b.expiration_date ASC, b.id ASC
        ";
        $reportTitle = "STERILE SUPPLY FIFO INVENTORY & BATCH LOT REPORT";
        break;

    case 'waivers':
        $headers = ['Waiver ID', 'Appt #', 'Client Name', 'Skin Allergies', 'Keloid', 'Medical Conditions', 'SHA-256 Digest', 'Signed At'];
        $query = "
            SELECT w.id, w.appointment_id, u.full_name, w.skin_allergies, 
                   IF(w.keloid_history=1, 'YES', 'NO') AS keloid, w.medical_conditions, 
                   COALESCE(w.sha256_hash, 'ARCHIVED') AS hash_digest, w.signed_at 
            FROM digital_waivers w 
            JOIN users u ON w.client_id = u.id 
            ORDER BY w.id DESC
        ";
        $reportTitle = "IMMUTABLE DIGITAL MEDICAL WAIVER AUDIT ARCHIVE";
        break;

    case 'payments':
        $headers = ['Appt #', 'Client Name', 'Amount (PHP)', 'Reference Code', 'Receipt File', 'Deposit Status', 'Booked Date'];
        $query = "
            SELECT a.id, u.full_name, a.deposit_amount, a.payment_reference, 
                   a.receipt_image_path, a.deposit_status, a.created_at 
            FROM appointments a 
            JOIN users u ON a.client_id = u.id 
            ORDER BY a.id DESC
        ";
        $reportTitle = "CLIENT RESERVATION DEPOSIT & PAYMENT AUDIT";
        break;

    default:
        die("Invalid report category requested.");
}

$rows = $pdo->query($query)->fetchAll(PDO::FETCH_NUM);

// 2. CSV STREAMING EXPORT
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="aengelos_report_' . $type . '_' . $dateStamp . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Output UTF-8 BOM for Microsoft Excel compatibility
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);

    foreach ($rows as $row) {
        fputcsv($out, $row);
    }

    fclose($out);
    exit;
}

// 3. PDF STREAMING EXPORT (FPDF)
if ($format === 'pdf' && file_exists(__DIR__ . '/../lib/fpdf.php')) {
    require_once __DIR__ . '/../lib/fpdf.php';

    class StudioPDF extends FPDF {
        public $titleString;
        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 8, 'AENGELOS TATTOO STUDIO - AUDIT EXPORT', 0, 1, 'L');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, $this->titleString . ' | Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
            $this->SetDrawColor(200, 200, 200);
            $this->Line(10, 24, 287, 24);
            $this->Ln(6);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Confidential Internal Studio Audit Record', 0, 0, 'C');
        }
    }

    $pdf = new StudioPDF('L', 'mm', 'A4');
    $pdf->titleString = $reportTitle;
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 9);

    // Calculate column widths based on column count
    $colCount = count($headers);
    $colWidth = floor(275 / $colCount);

    // Table Header
    $pdf->SetFillColor(24, 24, 27);
    $pdf->SetTextColor(255, 255, 255);
    foreach ($headers as $h) {
        $pdf->Cell($colWidth, 8, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table Rows
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $fill = false;
    $pdf->SetFillColor(245, 245, 245);

    foreach ($rows as $row) {
        foreach ($row as $cell) {
            $text = substr((string)$cell, 0, 28);
            $pdf->Cell($colWidth, 7, $text, 1, 0, 'L', $fill);
        }
        $pdf->Ln();
        $fill = !$fill;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="aengelos_report_' . $type . '_' . $dateStamp . '.pdf"');
    $pdf->Output('I', 'aengelos_report_' . $type . '_' . $dateStamp . '.pdf');
    exit;
}

header("Location: admin_portal.php");
exit;

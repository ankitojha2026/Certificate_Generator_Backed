<?php
header('Content-Type: application/pdf');
require_once '../config/database.php';
require_once '../helpers/certificate_helper.php';
require_once '../lib/fpdf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = filter_var($data['user_id'] ?? '', FILTER_VALIDATE_INT);
$payment_id = validateInput($data['payment_id'] ?? '');

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT u.name, u.course, p.payment_id 
        FROM users u 
        JOIN payments p ON u.id = p.user_id 
        WHERE u.id = :user_id AND p.payment_id = :payment_id AND p.status = 'completed'
    ");
    $stmt->execute([':user_id' => $user_id, ':payment_id' => $payment_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user or payment']);
        exit;
    }
    
    $certificate_id = generateCertificateId();
    $date = date('Y-m-d');
    
    // Generate PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->Image('../templates/certificate_template.png', 0, 0, 210, 297);
    
    // Set font
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor(0, 0, 0);
    
    // Add text to certificate
    $pdf->SetXY(20, 100);
    $pdf->MultiCell(170, 10, "Certificate of Completion", 0, 'C');
    
    $pdf->SetFont('Arial', '', 16);
    $pdf->SetXY(20, 120);
    $pdf->MultiCell(170, 10, "This certifies that", 0, 'C');
    
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetXY(20, 140);
    $pdf->MultiCell(170, 10, $result['name'], 0, 'C');
    
    $pdf->SetFont('Arial', '', 16);
    $pdf->SetXY(20, 160);
    $pdf->MultiCell(170, 10, "has successfully completed the course", 0, 'C');
    
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(20, 180);
    $pdf->MultiCell(170, 10, $result['course'], 0, 'C');
    
    $pdf->SetFont('Arial', '', 14);
    $pdf->SetXY(20, 200);
    $pdf->MultiCell(170, 10, "Date: $date", 0, 'C');
    
    $pdf->SetXY(20, 220);
    $pdf->MultiCell(170, 10, "Certificate ID: $certificate_id", 0, 'C');
    
    // Save certificate details
    $stmt = $conn->prepare("INSERT INTO certificates (user_id, course, certificate_id, issue_date) VALUES (:user_id, :course, :certificate_id, :issue_date)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':course' => $result['course'],
        ':certificate_id' => $certificate_id,
        ':issue_date' => $date
    ]);
    
    $pdf->Output('D', 'certificate_' . $certificate_id . '.pdf');
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Certificate generation failed: ' . $e->getMessage()]);
}
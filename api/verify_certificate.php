<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$certificate_id = validateInput($_GET['certificate_id'] ?? '');

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT u.name, u.course, c.issue_date 
        FROM certificates c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.certificate_id = :certificate_id
    ");
    $stmt->execute([':certificate_id' => $certificate_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'name' => $result['name'],
            'course' => $result['course'],
            'issue_date' => $result['issue_date']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Certificate not found']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
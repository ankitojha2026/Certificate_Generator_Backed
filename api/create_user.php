<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../helpers/certificate_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$name = validateInput($data['name'] ?? '');
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$course = validateInput($data['course'] ?? '');

if (!$name || !$email || !$course) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO users (name, email, course) VALUES (:name, :email, :course)");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':course' => $course
    ]);
    
    echo json_encode([
        'success' => true,
        'user_id' => $conn->lastInsertId()
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
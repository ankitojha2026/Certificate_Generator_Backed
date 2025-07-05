<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../vendor/razorpay/razorpay-php/Razorpay.php';

use Razorpay\Api\Api;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = filter_var($data['user_id'] ?? '', FILTER_VALIDATE_INT);
$course = validateInput($data['course'] ?? '');
$amount = $data['amount'] ?? 0;

$course_prices = [
    'Data Analytics' => 1000,
    'Web Dev' => 1000,
    'Cyber Security' => 1500,
    'Prompt Engineering' => 2000
];

if (!$user_id || !array_key_exists($course, $course_prices) || $amount !== $course_prices[$course]) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payment data']);
    exit;
}

$api = new Api('YOUR_RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY_SECRET');

try {
    $orderData = [
        'amount' => $amount * 100, // in paise
        'currency' => 'INR',
        'payment_capture' => 1
    ];
    
    $order = $api->order->create($orderData);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO payments (user_id, course, amount, order_id, status) VALUES (:user_id, :course, :amount, :order_id, 'created')");
    $stmt->execute([
        ':user_id' => $user_id,
        ':course' => $course,
        ':amount' => $amount,
        ':order_id' => $order['id']
    ]);
    
    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'amount' => $amount,
        'currency' => 'INR',
        'key' => 'YOUR_RAZORPAY_KEY_ID'
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Payment creation failed: ' . $e->getMessage()]);
}
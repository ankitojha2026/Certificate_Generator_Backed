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
$order_id = $data['razorpay_order_id'] ?? '';
$payment_id = $data['razorpay_payment_id'] ?? '';
$signature = $data['razorpay_signature'] ?? '';

$api = new Api('YOUR_RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY "YOUR_RAZORPAY_KEY_SECRET"');

try {
    $attributes = [
        'razorpay_order_id' => $order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE payments SET payment_id = :payment_id, status = 'completed' WHERE order_id = :order_id");
    $stmt->execute([
        ':payment_id' => $payment_id,
        ':order_id' => $order_id
    ]);
    
    echo json_encode(['success' => true]);
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment verification failed: ' . $e->getMessage()]);
}
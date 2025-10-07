<?php
require_once 'config/config.php';

$token = $_GET['token'] ?? '';
$orderId = $_GET['order_id'] ?? '';

if (!$token || $token !== ADMIN_TOKEN) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'order_id required']);
    exit;
}

// Симулируем успешную оплату
$testPaymentData = [
    'payment_id' => 'test_' . time(),
    'order_id' => $orderId,
    'payment_status' => 'finished',
    'actually_paid' => 15.0,
    'pay_currency' => 'usdttrc20',
    'price_amount' => 15.0,
    'price_currency' => 'usd'
];

require_once 'src/PaymentHandler.php';
$paymentHandler = new PaymentHandler('ru');

try {
    $result = $paymentHandler->handleSuccessfulPayment($testPaymentData);
    echo json_encode(['success' => true, 'result' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

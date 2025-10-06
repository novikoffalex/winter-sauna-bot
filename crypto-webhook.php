<?php
/**
 * Webhook для обработки криптоплатежек CryptoPay
 */

require_once 'config/config.php';
require_once 'src/PaymentHandler.php';

// Получаем данные webhook
$input = file_get_contents('php://input');
$signature = $_SERVER['HTTP_CRYPTO_PAY_SIGNATURE'] ?? '';

// Создаем обработчик платежей
$paymentHandler = new PaymentHandler();

// Валидируем webhook (CoinGate не требует подписи)
// if (!$paymentHandler->validateWebhook($input, $signature)) {
//     error_log("Invalid CoinGate webhook signature");
//     http_response_code(401);
//     echo json_encode(['error' => 'Invalid signature']);
//     exit;
// }

// Парсим данные
$data = json_decode($input, true);

if (!$data) {
    error_log("Invalid JSON in CryptoPay webhook");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

error_log("CryptoPay webhook received: " . json_encode($data));

// Обрабатываем событие
try {
    // CoinGate отправляет данные в формате order
    if (isset($data['id']) && isset($data['status'])) {
        $orderId = $data['order_id'];
        $status = $data['status'];
        
        if ($status === 'paid') {
            $result = $paymentHandler->handleSuccessfulPayment($data);
            
            if ($result['success']) {
                error_log("Payment processed successfully: " . $result['ticket_id']);
                http_response_code(200);
                echo json_encode(['status' => 'success']);
            } else {
                error_log("Payment processing failed: " . $result['error']);
                http_response_code(500);
                echo json_encode(['error' => $result['error']]);
            }
        } elseif ($status === 'canceled' || $status === 'expired') {
            error_log("Payment failed for order: " . $orderId . " Status: " . $status);
            http_response_code(200);
            echo json_encode(['status' => 'acknowledged']);
        } else {
            error_log("Unknown CoinGate status: " . $status);
            http_response_code(200);
            echo json_encode(['status' => 'ignored']);
        }
    } else {
        error_log("Invalid CoinGate webhook data: " . json_encode($data));
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
    }
    
} catch (Exception $e) {
    error_log("CryptoPay webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

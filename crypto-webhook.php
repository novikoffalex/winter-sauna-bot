<?php
/**
 * Webhook для обработки криптоплатежек CryptoPay
 */

require_once 'config/config.php';
require_once 'src/PaymentHandler.php';

// Получаем данные webhook
$input = file_get_contents('php://input');
$signature = $_SERVER['HTTP_CRYPTO_PAY_SIGNATURE'] ?? '';

// Создаем обработчик платежей с русским языком по умолчанию
$paymentHandler = new PaymentHandler('ru');

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
    // NOWPayments отправляет данные в формате payment
    if (isset($data['payment_id']) && isset($data['payment_status'])) {
        // NOWPayments может прислать order_id в разных местах
        $orderId = $data['order_id'] ?? ($data['payload']['order_id'] ?? ($data['order']['id'] ?? null));
        error_log("NOWPayments webhook order_id detected: " . json_encode($orderId));
        $status = $data['payment_status'];
        
        if ($status === 'finished') {
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
        } elseif ($status === 'failed' || $status === 'refunded') {
            error_log("Payment failed for order: " . $orderId . " Status: " . $status);
            http_response_code(200);
            echo json_encode(['status' => 'acknowledged']);
        } elseif ($status === 'waiting') {
            // Для статуса "waiting" проверяем, есть ли уже заказ и обрабатываем его
            error_log("Payment waiting for order: " . $orderId . " - checking if order exists");
            
            // Проверяем, есть ли заказ в базе данных
            $orderFile = 'data/orders.json';
            if (file_exists($orderFile)) {
                $orders = json_decode(file_get_contents($orderFile), true);
                if (isset($orders[$orderId])) {
                    error_log("Order found for waiting payment, processing as successful");
                    $result = $paymentHandler->handleSuccessfulPayment($data);
                    
                    if ($result['success']) {
                        error_log("Waiting payment processed successfully: " . $result['ticket_id']);
                        http_response_code(200);
                        echo json_encode(['status' => 'success']);
                    } else {
                        error_log("Waiting payment processing failed: " . $result['error']);
                        http_response_code(500);
                        echo json_encode(['error' => $result['error']]);
                    }
                } else {
                    error_log("Order not found for waiting payment: " . $orderId);
                    http_response_code(200);
                    echo json_encode(['status' => 'ignored']);
                }
            } else {
                error_log("Orders file not found");
                http_response_code(200);
                echo json_encode(['status' => 'ignored']);
            }
        } else {
            error_log("Unknown NOWPayments status: " . $status);
            http_response_code(200);
            echo json_encode(['status' => 'ignored']);
        }
    } else {
        error_log("Invalid NOWPayments webhook data: " . json_encode($data));
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
    }
    
} catch (Exception $e) {
    error_log("CryptoPay webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

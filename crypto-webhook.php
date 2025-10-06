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

// Валидируем webhook
if (!$paymentHandler->validateWebhook($input, $signature)) {
    error_log("Invalid CryptoPay webhook signature");
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

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
    switch ($data['update_type']) {
        case 'invoice_paid':
            $result = $paymentHandler->handleSuccessfulPayment($data['invoice']);
            
            if ($result['success']) {
                error_log("Payment processed successfully: " . $result['ticket_id']);
                http_response_code(200);
                echo json_encode(['status' => 'success']);
            } else {
                error_log("Payment processing failed: " . $result['error']);
                http_response_code(500);
                echo json_encode(['error' => $result['error']]);
            }
            break;
            
        case 'invoice_failed':
            error_log("Payment failed for invoice: " . $data['invoice']['id']);
            // Можно отправить уведомление пользователю
            http_response_code(200);
            echo json_encode(['status' => 'acknowledged']);
            break;
            
        default:
            error_log("Unknown CryptoPay update type: " . $data['update_type']);
            http_response_code(200);
            echo json_encode(['status' => 'ignored']);
    }
    
} catch (Exception $e) {
    error_log("CryptoPay webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

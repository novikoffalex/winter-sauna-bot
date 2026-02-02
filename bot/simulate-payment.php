<?php
/**
 * Симуляция успешной оплаты для тестирования
 */

require_once 'config/config.php';
require_once 'src/PaymentHandler.php';

// Получаем параметры
$orderId = $_GET['order_id'] ?? 'ORDER-TEST-' . time();
$chatId = $_GET['chat_id'] ?? '1062522109';

echo "<h1>Симуляция успешной оплаты</h1>";
echo "<p>Order ID: {$orderId}</p>";
echo "<p>Chat ID: {$chatId}</p>";

try {
    $paymentHandler = new PaymentHandler();
    
    // Симулируем данные от NOWPayments
    $paymentData = [
        'payment_id' => 'test-payment-' . time(),
        'payment_status' => 'finished',
        'order_id' => $orderId,
        'amount' => 15,
        'currency' => 'USDT',
        'created_at' => time()
    ];
    
    echo "<p>🔄 Обрабатываем симулированную оплату...</p>";
    
    $result = $paymentHandler->handleSuccessfulPayment($paymentData);
    
    if ($result['success']) {
        echo "<p>✅ Билет создан и отправлен!</p>";
        echo "<p>Ticket ID: {$result['ticket_id']}</p>";
        
        // Показываем ссылку на билет
        $ticketUrl = "https://winter-sauna-bot-phuket-f79605d5d044.herokuapp.com/qr-ticket.php?order_id=" . urlencode($orderId);
        echo "<p><a href='{$ticketUrl}' target='_blank'>🌐 Открыть билет в браузере</a></p>";
    } else {
        echo "<p>❌ Ошибка: " . htmlspecialchars($result['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

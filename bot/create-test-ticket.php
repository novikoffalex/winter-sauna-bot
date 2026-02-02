<?php
/**
 * Создание тестового билета без оплаты
 */

require_once 'config/config.php';
require_once 'src/PaymentHandler.php';
require_once 'src/TelegramService.php';
require_once 'src/TicketService.php';

// Получаем параметры
$chatId = $_GET['chat_id'] ?? '1062522109'; // Ваш chat_id
$service = $_GET['service'] ?? 'test';
$amount = $_GET['amount'] ?? 15;
$currency = $_GET['currency'] ?? 'USDT';

echo "<h1>Создание тестового билета</h1>";
echo "<p>Chat ID: {$chatId}</p>";
echo "<p>Услуга: {$service}</p>";
echo "<p>Сумма: {$amount} {$currency}</p>";

try {
    // Создаем тестовый заказ
    $orderId = 'ORDER-TEST-' . time();
    
    $paymentHandler = new PaymentHandler();
    $paymentHandler->saveOrderInfo($orderId, $chatId, $service, $amount, $currency, 'test-invoice-' . time());
    
    // Обновляем статус на "оплачен"
    $paymentHandler->updateOrderStatus($orderId, 'paid');
    
    echo "<p>✅ Тестовый заказ создан: {$orderId}</p>";
    
    // Создаем билет
    $ticketService = new TicketService();
    $ticketData = $ticketService->createTicket(
        $orderId,
        $service,
        $amount,
        $currency,
        ['id' => 'test-invoice-' . time()]
    );
    
    echo "<p>🎫 Билет создан: {$ticketData['ticket_id']}</p>";
    
    // Генерируем QR
    $qrData = $ticketService->generateTicketQR($ticketData);
    
    echo "<p>📱 QR-код сгенерирован</p>";
    
    // Отправляем билет пользователю
    $paymentHandler->sendTicketToUser($chatId, $ticketData, $qrData);
    
    echo "<p>📤 Билет отправлен в Telegram!</p>";
    
    // Показываем ссылку на билет
    $ticketUrl = "https://winter-sauna-bot-phuket-f79605d5d044.herokuapp.com/qr-ticket.php?order_id=" . urlencode($orderId);
    echo "<p><a href='{$ticketUrl}' target='_blank'>🌐 Открыть билет в браузере</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

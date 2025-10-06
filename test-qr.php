<?php
/**
 * Тестирование QR-кода без реального платежа
 */

require_once 'config/config.php';
require_once 'src/PaymentHandler.php';
require_once 'src/TicketService.php';

// Создаем тестовый заказ
$testOrderId = 'ORDER-TEST-' . time();
$testChatId = 'YOUR_TELEGRAM_CHAT_ID'; // Замените на ваш chat_id

// Создаем тестовые данные заказа
$orderData = [
    'order_id' => $testOrderId,
    'chat_id' => $testChatId,
    'service' => 'test',
    'amount' => 15,
    'currency' => 'USDT',
    'invoice_id' => 'test-invoice-' . time(),
    'status' => 'paid',
    'created_at' => time()
];

// Сохраняем заказ
$ordersFile = 'data/orders.json';
if (!file_exists('data')) {
    mkdir('data', 0755, true);
}

$orders = [];
if (file_exists($ordersFile)) {
    $orders = json_decode(file_get_contents($ordersFile), true) ?: [];
}

$orders[$testOrderId] = $orderData;
file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));

echo "✅ Тестовый заказ создан: {$testOrderId}\n";

// Создаем билет и QR
$ticketService = new TicketService();
$ticket = $ticketService->createTicket(
    $testOrderId,
    'test',
    15,
    'USDT',
    ['id' => 'test-invoice-' . time()]
);

$qr = $ticketService->generateTicketQR($ticket);

echo "🎫 Билет создан: {$ticket['ticket_id']}\n";
echo "📱 QR URL: {$qr['qr_image_url']}\n";
echo "🔗 QR Content: {$qr['qr_content']}\n\n";

// Отправляем в Telegram (если указан chat_id)
if ($testChatId !== 'YOUR_TELEGRAM_CHAT_ID') {
    $handler = new PaymentHandler('en');
    $ref = new ReflectionClass($handler);
    $sendTicket = $ref->getMethod('sendTicketToUser');
    $sendTicket->setAccessible(true);
    $sendTicket->invoke($handler, $testChatId, $ticket, $qr);
    echo "📤 QR отправлен в Telegram чат {$testChatId}\n";
} else {
    echo "⚠️ Укажите ваш chat_id в переменной \$testChatId для отправки в Telegram\n";
    echo "💡 Или используйте админ-эндпоинт:\n";
    echo "https://winter-sauna-bot-phuket-f79605d5d044.herokuapp.com/?admin=send-qr&token=YOUR_ADMIN_TOKEN&order_id={$testOrderId}\n";
}
?>

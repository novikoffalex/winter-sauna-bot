<?php
/**
 * Отправка тестового QR в Telegram
 */

require_once 'config/config.php';
require_once 'src/PaymentHandler.php';
require_once 'src/TicketService.php';

// Укажите ваш chat_id здесь (замените на ваш реальный ID)
$testChatId = '1062522109'; // Ваш chat_id

if ($testChatId === 'YOUR_CHAT_ID_HERE') {
    echo "❌ Сначала укажите ваш chat_id в переменной \$testChatId\n";
    echo "💡 Чтобы узнать chat_id:\n";
    echo "1. Напишите боту @userinfobot в Telegram\n";
    echo "2. Или напишите что-нибудь нашему боту @winter_sauna_bot\n";
    echo "3. Или используйте админ-эндпоинт с известным chat_id\n";
    exit;
}

// Создаем тестовый заказ
$testOrderId = 'ORDER-TEST-' . time();

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
echo "📱 QR URL: {$qr['qr_image_url']}\n\n";

// Отправляем в Telegram
try {
    $handler = new PaymentHandler('en');
    $ref = new ReflectionClass($handler);
    $sendTicket = $ref->getMethod('sendTicketToUser');
    $sendTicket->setAccessible(true);
    $sendTicket->invoke($handler, $testChatId, $ticket, $qr);
    
    echo "📤 QR отправлен в Telegram чат {$testChatId}\n";
    echo "✅ Проверьте бота @winter_sauna_bot в Telegram!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка отправки: " . $e->getMessage() . "\n";
    echo "💡 Проверьте правильность chat_id\n";
}
?>

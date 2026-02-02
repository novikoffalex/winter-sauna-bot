<?php
/**
 * Простой эндпоинт для тестирования QR без платежа
 */

require_once 'config/config.php';
require_once 'src/PaymentHandler.php';
require_once 'src/TicketService.php';

// Создаем тестовый заказ
$testOrderId = 'ORDER-TEST-' . time();
$testChatId = '1062522109';

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

// Отправляем в Telegram
$handler = new PaymentHandler('en');
$ref = new ReflectionClass($handler);
$sendTicket = $ref->getMethod('sendTicketToUser');
$sendTicket->setAccessible(true);
$sendTicket->invoke($handler, $testChatId, $ticket, $qr);

echo "✅ QR отправлен в Telegram для заказа: {$testOrderId}";
?>

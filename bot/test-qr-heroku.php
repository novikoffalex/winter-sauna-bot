<?php
/**
 * Тест QR на Heroku
 */

require_once 'config/config.php';
require_once 'src/PaymentHandler.php';
require_once 'src/TicketService.php';

$testChatId = '1062522109';

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
echo "📱 QR файл: {$qr['qr_image_url']}\n";

// Проверяем, что файл существует
if (file_exists($qr['qr_image_url'])) {
    echo "✅ QR файл создан успешно\n";
    echo "📏 Размер файла: " . filesize($qr['qr_image_url']) . " байт\n";
    
    // Показываем URL для доступа к QR
    $qrUrl = "https://winter-sauna-bot-phuket-f79605d5d044.herokuapp.com/" . $qr['qr_image_url'];
    echo "🔗 URL QR: {$qrUrl}\n";
} else {
    echo "❌ QR файл не создан\n";
}
?>

<?php
/**
 * Отправка QR как текста в Telegram
 */

require_once 'config/config.php';
require_once 'src/TelegramService.php';
require_once 'src/CryptoPaymentService.php';
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

// Отправляем QR как изображение
$telegram = new TelegramService();
$telegram->initialize();

$message = "🎫 **Билет создан!**\n\n";
$message .= "📋 **ID билета:** `{$ticket['ticket_id']}`\n";
$message .= "🏊‍♀️ **Услуга:** test\n";
$message .= "💰 **Сумма:** 15 USDT\n";
$message .= "⏰ **Действует до:** " . date('Y-m-d H:i', $ticket['expires_at']) . "\n\n";
$message .= "📱 **QR-код для входа:**\n";
$message .= "`{$qr['qr_content']}`\n\n";
$message .= "⚠️ Покажите QR-код на входе в сауну!";

try {
    // Отправляем QR как изображение
    $telegram->sendPhoto($testChatId, $qr['qr_image_url'], $message);
    echo "📤 QR отправлен как изображение в Telegram чат {$testChatId}\n";
    echo "✅ Проверьте бота @winter_sauna_bot в Telegram!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка отправки: " . $e->getMessage() . "\n";
}
?>

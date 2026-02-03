<?php
/**
 * Тест генерации QR-кодов
 */

require_once 'config/config.php';
require_once 'src/CryptoPaymentService.php';
require_once 'src/TicketService.php';

echo "🧪 Тест генерации QR-кодов\n\n";

try {
    $ticketService = new TicketService();
    
    // Создаем тестовый билет
    $testTicket = [
        'ticket_id' => 'ZIMA-TEST-' . time(),
        'order_id' => 'ORDER-TEST-123',
        'service' => 'massage',
        'amount' => 15.0,
        'currency' => 'USDT',
        'payment_id' => 'test_payment_123',
        'created_at' => time(),
        'expires_at' => time() + (24 * 60 * 60),
        'status' => 'active',
        'access_code' => 'TEST1234'
    ];
    
    echo "✅ Тестовый билет создан:\n";
    echo "   Ticket ID: {$testTicket['ticket_id']}\n";
    echo "   Order ID: {$testTicket['order_id']}\n";
    echo "   Service: {$testTicket['service']}\n\n";
    
    // Сохраняем билет через createTicket
    echo "💾 Сохранение билета...\n";
    $savedTicket = $ticketService->createTicket(
        $testTicket['order_id'],
        $testTicket['service'],
        $testTicket['amount'],
        $testTicket['currency'],
        ['payment_id' => $testTicket['payment_id']]
    );
    echo "   ✅ Билет сохранен: {$savedTicket['ticket_id']}\n\n";
    
    // Генерируем QR-код
    echo "📱 Генерация QR-кода...\n";
    $qrData = $ticketService->generateTicketQR($savedTicket);
    
    echo "✅ QR-код сгенерирован:\n";
    echo "   QR Content: " . substr($qrData['qr_content'], 0, 100) . "...\n";
    echo "   QR Image URL: {$qrData['qr_image_url']}\n";
    echo "   Ticket ID: {$qrData['ticket_id']}\n";
    echo "   Expires at: " . date('Y-m-d H:i:s', $qrData['expires_at']) . "\n\n";
    
    // Проверяем валидацию
    echo "🔍 Тест валидации QR-кода...\n";
    $validation = $ticketService->validateTicket($qrData['qr_content']);
    
    if ($validation['valid']) {
        echo "✅ QR-код валиден!\n";
        echo "   Ticket ID: {$validation['ticket_id']}\n";
        echo "   Order ID: {$validation['order_id']}\n";
    } else {
        echo "❌ QR-код невалиден: {$validation['error']}\n";
    }
    
    // Проверяем сохранение билета
    echo "\n💾 Проверка сохранения билета...\n";
    $savedTicket = $ticketService->getTicket($testTicket['ticket_id']);
    
    if ($savedTicket) {
        echo "✅ Билет сохранен в базе данных\n";
    } else {
        echo "❌ Билет не найден в базе данных\n";
    }
    
    echo "\n✅ Все тесты QR-генерации пройдены!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

<?php
/**
 * Полный тест функциональности бота
 */

require_once 'config/config.php';

echo "🧪 Полный тест функциональности бота\n";
echo str_repeat("=", 50) . "\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// Тест 1: Конфигурация
echo "1️⃣ Проверка конфигурации...\n";
$tests['config'] = [
    'TELEGRAM_BOT_TOKEN' => !empty(TELEGRAM_BOT_TOKEN),
    'GEMINI_API_KEY' => !empty(GEMINI_API_KEY),
    'NOWPAYMENTS_API_KEY' => !empty(NOWPAYMENTS_API_KEY),
];

foreach ($tests['config'] as $key => $value) {
    echo "   " . ($value ? "✅" : "❌") . " $key: " . ($value ? "OK" : "НЕ УСТАНОВЛЕН") . "\n";
    if ($value) $passed++; else $failed++;
}
echo "\n";

// Тест 2: QR генерация
echo "2️⃣ Тест генерации QR-кодов...\n";
try {
    require_once 'src/TicketService.php';
    $ticketService = new TicketService();
    
    $testTicket = [
        'ticket_id' => 'ZIMA-TEST-' . time(),
        'order_id' => 'ORDER-TEST',
        'service' => 'test',
        'amount' => 15.0,
        'currency' => 'USDT',
        'payment_id' => 'test',
        'created_at' => time(),
        'expires_at' => time() + 86400,
        'status' => 'active',
        'access_code' => 'TEST1234'
    ];
    
    $qrData = $ticketService->generateTicketQR($testTicket);
    echo "   ✅ QR-код сгенерирован\n";
    echo "   ✅ QR Image URL: " . substr($qrData['qr_image_url'], 0, 60) . "...\n";
    $passed += 2;
} catch (Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
    $failed += 2;
}
echo "\n";

// Тест 3: NOWPayments подключение
echo "3️⃣ Тест подключения к NOWPayments...\n";
if (empty(NOWPAYMENTS_API_KEY)) {
    echo "   ⚠️  NOWPAYMENTS_API_KEY не установлен - пропуск теста\n";
} else {
    try {
        require_once 'src/CryptoPaymentService.php';
        $cryptoService = new CryptoPaymentService();
        $currencies = $cryptoService->getCurrencies();
        if (is_array($currencies)) {
            echo "   ✅ Подключение к NOWPayments успешно\n";
            echo "   ✅ Доступно валют: " . count($currencies) . "\n";
            $passed += 2;
        } else {
            echo "   ⚠️  Подключение работает, но формат ответа неожиданный\n";
            $passed++;
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ❌ Ошибка подключения: " . $e->getMessage() . "\n";
        $failed += 2;
    }
}
echo "\n";

// Тест 4: Gemini API
echo "4️⃣ Тест Gemini API...\n";
try {
    require_once 'src/GeminiService.php';
    $geminiService = new GeminiService('ru');
    $geminiService->initialize();
    echo "   ✅ Gemini Service инициализирован\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
    $failed++;
}
echo "\n";

// Тест 5: Telegram Service
echo "5️⃣ Тест Telegram Service...\n";
try {
    require_once 'src/TelegramService.php';
    $telegramService = new TelegramService();
    $telegramService->initialize();
    echo "   ✅ Telegram Service инициализирован\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
    $failed++;
}
echo "\n";

// Тест 6: Payment Handler
echo "6️⃣ Тест Payment Handler...\n";
try {
    require_once 'src/PaymentHandler.php';
    $paymentHandler = new PaymentHandler('ru');
    echo "   ✅ Payment Handler инициализирован\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
    $failed++;
}
echo "\n";

// Итоги
echo str_repeat("=", 50) . "\n";
echo "📊 Итоги тестирования:\n";
echo "   ✅ Пройдено: $passed\n";
echo "   ❌ Провалено: $failed\n";
echo "   📈 Успешность: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n\n";

if ($failed === 0) {
    echo "🎉 Все тесты пройдены успешно!\n";
    exit(0);
} else {
    echo "⚠️  Некоторые тесты провалены. Проверьте конфигурацию.\n";
    exit(1);
}

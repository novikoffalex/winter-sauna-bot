<?php
/**
 * Тестирование бота на продакшене (Laravel Cloud)
 */

require_once 'config/config.php';

echo "🧪 Тестирование бота на продакшене\n";
echo str_repeat("=", 50) . "\n\n";

$appUrl = $_ENV['APP_URL'] ?? 'https://winter-sauna-bot-main-gn1t4l.laravel.cloud';
$webhookUrl = $appUrl . '/bot/webhook.php';
$cryptoWebhookUrl = $appUrl . '/bot/crypto-webhook.php';

$tests = [];
$passed = 0;
$failed = 0;

// Тест 1: Конфигурация
echo "1️⃣ Проверка конфигурации...\n";
$tests['config'] = [
    'TELEGRAM_BOT_TOKEN' => !empty(TELEGRAM_BOT_TOKEN),
    'GEMINI_API_KEY' => !empty(GEMINI_API_KEY),
    'APP_URL' => !empty($appUrl),
];

foreach ($tests['config'] as $key => $value) {
    echo "   " . ($value ? "✅" : "❌") . " $key: " . ($value ? "OK" : "НЕ УСТАНОВЛЕН") . "\n";
    if ($value) $passed++; else $failed++;
}
echo "\n";

// Тест 2: Доступность URL
echo "2️⃣ Проверка доступности URL...\n";
try {
    $ch = curl_init($appUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 400) {
        echo "   ✅ Главная страница доступна (HTTP $httpCode)\n";
        echo "   📍 URL: $appUrl\n";
        $passed++;
    } else {
        echo "   ❌ Главная страница недоступна (HTTP $httpCode)\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка проверки: " . $e->getMessage() . "\n";
    $failed++;
}
echo "\n";

// Тест 3: Webhook endpoint
echo "3️⃣ Проверка webhook endpoint...\n";
try {
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Webhook должен возвращать 400 для GET запроса (это нормально)
    if ($httpCode == 400 || $httpCode == 405) {
        echo "   ✅ Webhook endpoint доступен (HTTP $httpCode - нормально для GET)\n";
        echo "   📍 URL: $webhookUrl\n";
        $passed++;
    } elseif ($httpCode >= 200 && $httpCode < 400) {
        echo "   ✅ Webhook endpoint доступен (HTTP $httpCode)\n";
        echo "   📍 URL: $webhookUrl\n";
        $passed++;
    } else {
        echo "   ⚠️  Webhook endpoint вернул HTTP $httpCode\n";
        echo "   📍 URL: $webhookUrl\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка проверки: " . $e->getMessage() . "\n";
    $failed++;
}
echo "\n";

// Тест 4: Crypto webhook endpoint
echo "4️⃣ Проверка crypto webhook endpoint...\n";
try {
    $ch = curl_init($cryptoWebhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 400 || $httpCode == 405 || ($httpCode >= 200 && $httpCode < 400)) {
        echo "   ✅ Crypto webhook endpoint доступен (HTTP $httpCode)\n";
        echo "   📍 URL: $cryptoWebhookUrl\n";
        $passed++;
    } else {
        echo "   ⚠️  Crypto webhook endpoint вернул HTTP $httpCode\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка проверки: " . $e->getMessage() . "\n";
    $failed++;
}
echo "\n";

// Тест 5: Telegram webhook status
echo "5️⃣ Проверка статуса webhook в Telegram...\n";
if (!empty(TELEGRAM_BOT_TOKEN)) {
    try {
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getWebhookInfo";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && isset($data['ok']) && $data['ok']) {
            $webhook = $data['result'];
            echo "   ✅ Webhook настроен в Telegram\n";
            echo "   📍 URL: " . ($webhook['url'] ?? 'не установлен') . "\n";
            if (isset($webhook['pending_update_count'])) {
                echo "   📬 Ожидающих обновлений: " . $webhook['pending_update_count'] . "\n";
            }
            if (isset($webhook['last_error_message'])) {
                echo "   ⚠️  Последняя ошибка: " . $webhook['last_error_message'] . "\n";
            }
            $passed++;
        } else {
            echo "   ❌ Не удалось получить информацию о webhook\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
        $failed++;
    }
} else {
    echo "   ⚠️  TELEGRAM_BOT_TOKEN не установлен\n";
    $failed++;
}
echo "\n";

// Тест 6: Gemini API
echo "6️⃣ Проверка Gemini API...\n";
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

// Тест 7: NOWPayments (если настроен)
echo "7️⃣ Проверка NOWPayments...\n";
if (!empty(NOWPAYMENTS_API_KEY)) {
    try {
        require_once 'src/CryptoPaymentService.php';
        $cryptoService = new CryptoPaymentService();
        echo "   ✅ CryptoPaymentService инициализирован\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ⚠️  Ошибка: " . $e->getMessage() . "\n";
        $failed++;
    }
} else {
    echo "   ⚠️  NOWPayments не настроен (пропуск)\n";
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
    echo "\n📱 Теперь протестируйте бота в Telegram:\n";
    echo "   1. Откройте: @ZimaSaunaBot\n";
    echo "   2. Отправьте: /start\n";
    echo "   3. Отправьте: как оплатить?\n";
    echo "   4. Проверьте, что бот отвечает\n";
    exit(0);
} else {
    echo "⚠️  Некоторые тесты провалены. Проверьте конфигурацию.\n";
    exit(1);
}

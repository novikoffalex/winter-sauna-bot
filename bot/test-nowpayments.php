<?php
/**
 * Тест подключения к NOWPayments API
 */

require_once 'config/config.php';
require_once 'src/CryptoPaymentService.php';

echo "🧪 Тест подключения к NOWPayments API\n\n";

// Проверка конфигурации
if (empty(NOWPAYMENTS_API_KEY)) {
    echo "❌ NOWPAYMENTS_API_KEY не установлен в .env\n";
    echo "   Добавьте в .env:\n";
    echo "   NOWPAYMENTS_API_KEY=ваш_ключ\n";
    echo "   NOWPAYMENTS_PUBLIC_KEY=ваш_публичный_ключ\n";
    exit(1);
}

if (empty(NOWPAYMENTS_PUBLIC_KEY)) {
    echo "⚠️  NOWPAYMENTS_PUBLIC_KEY не установлен (опционально)\n\n";
}

echo "✅ API ключи найдены:\n";
echo "   API Key: " . substr(NOWPAYMENTS_API_KEY, 0, 10) . "...\n";
if (NOWPAYMENTS_PUBLIC_KEY) {
    echo "   Public Key: " . substr(NOWPAYMENTS_PUBLIC_KEY, 0, 10) . "...\n";
}
echo "\n";

try {
    $cryptoService = new CryptoPaymentService();
    
    // Тест 1: Получение доступных валют
    echo "1️⃣ Тест получения доступных валют...\n";
    try {
        $currencies = $cryptoService->getCurrencies();
        if (is_array($currencies) && !empty($currencies)) {
            echo "✅ Доступные валюты получены (" . count($currencies) . " валют)\n";
            echo "   Примеры: " . implode(', ', array_slice($currencies, 0, 5)) . "...\n";
        } else {
            echo "⚠️  Список валют пуст или в неожиданном формате\n";
        }
    } catch (Exception $e) {
        echo "❌ Ошибка получения валют: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Тест 2: Создание тестового инвойса
    echo "2️⃣ Тест создания инвойса...\n";
    try {
        $testOrderId = 'TEST-' . time();
        $invoice = $cryptoService->createInvoice(
            15.0,  // $15
            'USDT',
            'Zima SPA Wellness - Test Payment',
            $testOrderId,
            'https://t.me/ZimaSaunaBot'
        );
        
        echo "✅ Инвойс создан:\n";
        echo "   Order ID: {$testOrderId}\n";
        if (isset($invoice['id'])) {
            echo "   Invoice ID: {$invoice['id']}\n";
        }
        if (isset($invoice['invoice_url'])) {
            echo "   Invoice URL: {$invoice['invoice_url']}\n";
        }
        if (isset($invoice['pay_url'])) {
            echo "   Pay URL: {$invoice['pay_url']}\n";
        }
        
        // Показываем полный ответ для отладки
        echo "\n   Полный ответ API:\n";
        echo "   " . json_encode($invoice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
    } catch (Exception $e) {
        echo "❌ Ошибка создания инвойса: " . $e->getMessage() . "\n";
        echo "   Проверьте:\n";
        echo "   - Правильность API ключа\n";
        echo "   - Доступность API NOWPayments\n";
        echo "   - Настройки проекта в NOWPayments dashboard\n";
    }
    echo "\n";
    
    // Тест 3: Получение курсов валют
    echo "3️⃣ Тест получения курсов валют...\n";
    try {
        $rates = $cryptoService->getExchangeRates();
        if ($rates && !isset($rates['code'])) {
            echo "✅ Курсы валют получены\n";
            echo "   Примеры курсов:\n";
            $count = 0;
            foreach ($rates as $key => $value) {
                if ($count++ < 3) {
                    echo "   - $key: $value\n";
                }
            }
        } else {
            echo "⚠️  Курсы валют не получены или в неожиданном формате\n";
        }
    } catch (Exception $e) {
        echo "❌ Ошибка получения курсов: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "✅ Тесты NOWPayments завершены!\n";
    echo "\n";
    echo "📝 Следующие шаги:\n";
    echo "   1. Убедитесь, что webhook URL настроен в NOWPayments dashboard\n";
    echo "   2. Webhook URL должен быть: https://ваш-домен/crypto-webhook.php\n";
    echo "   3. Для локального тестирования используйте ngrok или localtunnel\n";
    
} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

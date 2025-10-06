<?php
/**
 * Тестовый скрипт для проверки работы Telegram бота
 */

require_once 'config/config.php';
require_once 'src/TelegramService.php';
require_once 'src/AIService.php';

echo "🧖‍♀️ Winter Sauna Bot - Тест конфигурации\n";
echo "==========================================\n\n";

// Проверяем конфигурацию
echo "1. Проверка конфигурации:\n";
echo "   Telegram Bot Token: " . (TELEGRAM_BOT_TOKEN ? "✅ Установлен" : "❌ Не установлен") . "\n";
echo "   OpenAI API Key: " . (OPENAI_API_KEY ? "✅ Установлен" : "❌ Не установлен") . "\n";
echo "   OpenAI Model: " . OPENAI_MODEL . "\n";
echo "   Sauna Name: " . SAUNA_NAME . "\n";
echo "   Sauna Location: " . SAUNA_LOCATION . "\n";
echo "   Working Hours: " . SAUNA_WORKING_HOURS . "\n";
echo "   Phone: " . SAUNA_PHONE . "\n\n";

// Проверяем инициализацию сервисов
echo "2. Инициализация сервисов:\n";
try {
    $telegramService = new TelegramService();
    $telegramService->initialize();
    echo "   ✅ Telegram Service инициализирован\n";
} catch (Exception $e) {
    echo "   ❌ Ошибка Telegram Service: " . $e->getMessage() . "\n";
}

try {
    $aiService = new AIService();
    $aiService->initialize();
    echo "   ✅ AI Service инициализирован\n";
} catch (Exception $e) {
    echo "   ❌ Ошибка AI Service: " . $e->getMessage() . "\n";
}

echo "\n3. Тест AI обработки:\n";
try {
    $testMessage = "Привет! Хочу забронировать баню на завтра в 19:00 для 2 человек";
    $aiResponse = $aiService->processMessage($testMessage, ['senderId' => 'test_user']);
    echo "   Тестовое сообщение: $testMessage\n";
    echo "   AI ответ: $aiResponse\n";
    echo "   ✅ AI обработка работает\n";
} catch (Exception $e) {
    echo "   ❌ Ошибка AI обработки: " . $e->getMessage() . "\n";
}

echo "\n4. Тест анализа намерений:\n";
$testIntents = [
    'Хочу забронировать баню' => 'booking',
    'Какие у вас услуги?' => 'services',
    'Сколько стоит?' => 'prices',
    'Где вы находитесь?' => 'location',
    'Какой у вас телефон?' => 'contact'
];

foreach ($testIntents as $message => $expectedIntent) {
    $detectedIntent = $aiService->analyzeIntent($message);
    $status = ($detectedIntent === $expectedIntent) ? "✅" : "⚠️";
    echo "   $status '$message' -> $detectedIntent (ожидалось: $expectedIntent)\n";
}

echo "\n5. Рекомендации для запуска:\n";
echo "   1. Убедитесь, что все переменные в .env файле заполнены\n";
echo "   2. Создайте Telegram бота через @BotFather\n";
echo "   3. Запустите ngrok: ngrok http 8000\n";
echo "   4. Установите webhook: curl -X POST \"https://api.telegram.org/bot<TOKEN>/setWebhook\" -d \"url=https://your-ngrok-url.ngrok.io/webhook.php\"\n";
echo "   5. Запустите сервер: php -S 0.0.0.0:8000\n";
echo "   6. Протестируйте бота в Telegram\n\n";

echo "🎉 Тест завершен!\n";

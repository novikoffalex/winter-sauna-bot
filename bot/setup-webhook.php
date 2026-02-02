<?php
/**
 * Скрипт для настройки webhook Telegram бота
 */

require_once 'config/config.php';

echo "🔧 Настройка webhook для Winter Sauna Bot\n";
echo "=========================================\n\n";

if (empty(TELEGRAM_BOT_TOKEN)) {
    echo "❌ Ошибка: TELEGRAM_BOT_TOKEN не установлен в .env файле\n";
    exit(1);
}

if (empty(TELEGRAM_WEBHOOK_URL)) {
    echo "❌ Ошибка: TELEGRAM_WEBHOOK_URL не установлен в .env файле\n";
    exit(1);
}

echo "📋 Текущие настройки:\n";
echo "   Bot Token: " . substr(TELEGRAM_BOT_TOKEN, 0, 10) . "...\n";
echo "   Webhook URL: " . TELEGRAM_WEBHOOK_URL . "\n\n";

// Получаем информацию о боте
echo "🤖 Получение информации о боте...\n";
$botInfoUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getMe";
$botInfo = file_get_contents($botInfoUrl);
$botData = json_decode($botInfo, true);

if ($botData['ok']) {
    $bot = $botData['result'];
    echo "   ✅ Бот найден: @" . $bot['username'] . " (" . $bot['first_name'] . ")\n";
} else {
    echo "   ❌ Ошибка получения информации о боте: " . $botData['description'] . "\n";
    exit(1);
}

// Проверяем текущий webhook
echo "\n🔍 Проверка текущего webhook...\n";
$webhookInfoUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getWebhookInfo";
$webhookInfo = file_get_contents($webhookInfoUrl);
$webhookData = json_decode($webhookInfo, true);

if ($webhookData['ok']) {
    $webhook = $webhookData['result'];
    echo "   Текущий URL: " . ($webhook['url'] ?: 'не установлен') . "\n";
    echo "   Количество ошибок: " . $webhook['pending_update_count'] . "\n";
    
    if ($webhook['last_error_message']) {
        echo "   Последняя ошибка: " . $webhook['last_error_message'] . "\n";
    }
}

// Устанавливаем новый webhook
echo "\n⚙️ Установка webhook...\n";
$setWebhookUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setWebhook";
$postData = json_encode(['url' => TELEGRAM_WEBHOOK_URL]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$result = file_get_contents($setWebhookUrl, false, $context);
$response = json_decode($result, true);

if ($response['ok']) {
    echo "   ✅ Webhook успешно установлен!\n";
} else {
    echo "   ❌ Ошибка установки webhook: " . $response['description'] . "\n";
}

// Настраиваем команды бота
echo "\n📝 Настройка команд бота...\n";
$setCommandsUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setMyCommands";

$commands = [
    ['command' => 'start', 'description' => 'Начать работу с ботом'],
    ['command' => 'services', 'description' => 'Посмотреть услуги бани'],
    ['command' => 'booking', 'description' => 'Забронировать время'],
    ['command' => 'prices', 'description' => 'Узнать цены'],
    ['command' => 'contact', 'description' => 'Контактная информация'],
    ['command' => 'help', 'description' => 'Помощь']
];

$postData = json_encode(['commands' => $commands]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$result = file_get_contents($setCommandsUrl, false, $context);
$response = json_decode($result, true);

if ($response['ok']) {
    echo "   ✅ Команды бота настроены!\n";
} else {
    echo "   ❌ Ошибка настройки команд: " . $response['description'] . "\n";
}

echo "\n🎉 Настройка завершена!\n";
echo "Теперь вы можете протестировать бота в Telegram:\n";
echo "   https://t.me/" . $bot['username'] . "\n\n";

echo "📋 Полезные команды для тестирования:\n";
echo "   /start - Приветствие и главное меню\n";
echo "   /services - Список услуг\n";
echo "   /booking - Бронирование\n";
echo "   /prices - Цены\n";
echo "   /contact - Контакты\n";
echo "   /help - Справка\n\n";

echo "💡 Также можете просто написать боту любое сообщение - он ответит через AI!\n";

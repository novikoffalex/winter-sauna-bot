<?php
/**
 * Получение chat_id пользователя
 */

require_once 'config/config.php';
require_once 'src/TelegramService.php';

$telegram = new TelegramService();
$telegram->initialize();

// Получаем последние обновления
$updates = $telegram->getUpdates();

echo "📱 Последние сообщения в боте:\n\n";

if (empty($updates['result'])) {
    echo "❌ Нет сообщений. Напишите боту @winter_sauna_bot что-нибудь и запустите скрипт снова.\n";
    exit;
}

foreach (array_slice($updates['result'], -5) as $update) {
    if (isset($update['message'])) {
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $firstName = $message['from']['first_name'] ?? 'Unknown';
        $text = $message['text'] ?? '[фото/стикер/другое]';
        $date = date('Y-m-d H:i:s', $message['date']);
        
        echo "👤 {$firstName} (ID: {$chatId})\n";
        echo "💬 {$text}\n";
        echo "🕐 {$date}\n";
        echo "---\n";
    }
}

echo "\n✅ Скопируйте нужный chat_id и используйте в test-qr.php\n";
?>

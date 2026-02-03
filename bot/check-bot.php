<?php
/**
 * Скрипт для проверки конфигурации бота
 */

require_once 'config/config.php';

echo "🔍 Проверка конфигурации бота...\n\n";

$errors = [];
$warnings = [];

// Проверка Telegram
if (empty(TELEGRAM_BOT_TOKEN)) {
    $errors[] = "TELEGRAM_BOT_TOKEN не установлен";
} else {
    echo "✅ TELEGRAM_BOT_TOKEN: установлен\n";
}

// Проверка Gemini
if (empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'your_gemini_api_key_here') {
    $errors[] = "GEMINI_API_KEY не установлен или содержит заглушку";
    echo "❌ GEMINI_API_KEY: НЕ установлен или содержит заглушку\n";
} else {
    echo "✅ GEMINI_API_KEY: установлен\n";
}

// Проверка директорий
$requiredDirs = ['data', 'data/conversations'];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        $warnings[] = "Директория $dir не существует";
        if (!mkdir($dir, 0755, true)) {
            $errors[] = "Не удалось создать директорию $dir";
        } else {
            echo "✅ Создана директория: $dir\n";
        }
    }
}

// Проверка файлов
$requiredFiles = ['webhook.php', 'src/GeminiService.php', 'src/TelegramWebhookHandlerLocalized.php'];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $errors[] = "Файл $file не найден";
    }
}

// Проверка webhook URL
if (empty(TELEGRAM_WEBHOOK_URL) || strpos(TELEGRAM_WEBHOOK_URL, 'localhost') !== false) {
    $warnings[] = "TELEGRAM_WEBHOOK_URL указывает на localhost. Для продакшена нужен публичный URL";
}

echo "\n";

if (!empty($warnings)) {
    echo "⚠️  Предупреждения:\n";
    foreach ($warnings as $warning) {
        echo "   - $warning\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ Ошибки конфигурации:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
    echo "\n";
    echo "📝 Решение:\n";
    echo "1. Откройте файл bot/.env\n";
    echo "2. Замените 'your_gemini_api_key_here' на реальный API ключ\n";
    echo "3. Получите ключ на https://makersuite.google.com/app/apikey\n";
    exit(1);
} else {
    echo "✅ Все проверки пройдены!\n";
    echo "\n";
    echo "🚀 Для запуска бота:\n";
    echo "   php -S localhost:8000\n";
    echo "\n";
    echo "📡 Для настройки webhook используйте ngrok:\n";
    echo "   ngrok http 8000\n";
    echo "   Затем: curl -X POST \"https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setWebhook\" -d \"url=https://ваш-ngrok-url.ngrok.io/webhook.php\"\n";
}

<?php
/**
 * Конфигурация приложения для Winter Sauna Bot
 */

// Загружаем переменные окружения из .env файла
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Конфигурация Telegram Bot
define('TELEGRAM_BOT_TOKEN', $_ENV['TELEGRAM_BOT_TOKEN'] ?? '');
define('TELEGRAM_WEBHOOK_URL', $_ENV['TELEGRAM_WEBHOOK_URL'] ?? '');
define('BOT_USERNAME', $_ENV['BOT_USERNAME'] ?? 'ZimaSaunaBot');

// Конфигурация OpenAI
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');
define('OPENAI_MODEL', $_ENV['OPENAI_MODEL'] ?? 'gpt-4');

// Информация о бане
define('SAUNA_NAME', $_ENV['SAUNA_NAME'] ?? 'Зима');
define('SAUNA_LOCATION', $_ENV['SAUNA_LOCATION'] ?? 'Пхукет, Таиланд');
define('SAUNA_WORKING_HOURS', $_ENV['SAUNA_WORKING_HOURS'] ?? '10:00-22:00');
define('SAUNA_PHONE', $_ENV['SAUNA_PHONE'] ?? '+66-XX-XXX-XXXX');

// Конфигурация криптоплатежек (NOWPayments)
define('NOWPAYMENTS_API_KEY', $_ENV['NOWPAYMENTS_API_KEY'] ?? '');
define('NOWPAYMENTS_PUBLIC_KEY', $_ENV['NOWPAYMENTS_PUBLIC_KEY'] ?? '');
define('NOWPAYMENTS_WEBHOOK_SECRET', $_ENV['NOWPAYMENTS_WEBHOOK_SECRET'] ?? '');

// Админ-токен для служебных операций (ручная выдача QR)
define('ADMIN_TOKEN', $_ENV['ADMIN_TOKEN'] ?? '');

// Конфигурация сервера
define('WEBHOOK_VERIFICATION_TOKEN', $_ENV['WEBHOOK_VERIFICATION_TOKEN'] ?? '');
define('NODE_ENV', $_ENV['NODE_ENV'] ?? 'development');

// Проверяем обязательные переменные
$required_vars = ['TELEGRAM_BOT_TOKEN', 'OPENAI_API_KEY'];
foreach ($required_vars as $var) {
    if (empty(constant($var))) {
        error_log("Missing required environment variable: $var");
    }
}

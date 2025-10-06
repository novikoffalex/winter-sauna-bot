<?php
/**
 * Webhook endpoint для Telegram
 */

require_once 'config/config.php';
require_once 'src/TelegramWebhookHandler.php';

// Создаем обработчик и обрабатываем webhook
$handler = new TelegramWebhookHandler();
$handler->handleWebhook();

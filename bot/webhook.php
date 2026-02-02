<?php
/**
 * Webhook endpoint для Telegram с поддержкой локализации
 */

require_once 'config/config.php';
require_once 'src/TelegramWebhookHandlerLocalized.php';

// Создаем обработчик и обрабатываем webhook
$handler = new TelegramWebhookHandlerLocalized();
$handler->handleWebhook();

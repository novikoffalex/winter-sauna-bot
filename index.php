<?php
/**
 * Winter Sauna Telegram Bot
 * Главный файл приложения
 */

require_once 'config/config.php';

// Если это POST запрос, обрабатываем webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'src/TelegramWebhookHandlerLocalized.php';
    $handler = new TelegramWebhookHandlerLocalized();
    $handler->handleWebhook();
    exit;
}

// Иначе показываем информацию о боте
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winter Sauna Bot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 1.2em;
        }
        .status {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
        }
        .info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .error {
            background: #ffebee;
            border: 1px solid #f44336;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(45deg, #2196f3, #21cbf3);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            margin: 5px;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .feature {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #2196f3;
        }
        .feature h4 {
            margin-top: 0;
            color: #2c3e50;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧖‍♀️ Winter Sauna Bot</h1>
        <p class="subtitle">Интеллектуальный Telegram бот для бани "Зима" на Пхукете</p>
        
        <?php
        // Проверяем конфигурацию
        $configOk = !empty(TELEGRAM_BOT_TOKEN) && !empty(OPENAI_API_KEY);
        
        if ($configOk) {
            echo '<div class="status">✅ Бот настроен и готов к работе!</div>';
        } else {
            echo '<div class="error">❌ Бот не настроен. Проверьте переменные окружения в .env файле.</div>';
        }
        ?>
        
        <div class="info">
            <h3>📍 Информация о бане "Зима"</h3>
            <p><strong>Местоположение:</strong> <?= SAUNA_LOCATION ?></p>
            <p><strong>Время работы:</strong> <?= SAUNA_WORKING_HOURS ?></p>
            <p><strong>Телефон:</strong> <?= SAUNA_PHONE ?></p>
        </div>
        
        <div class="info">
            <h3>🤖 Возможности бота</h3>
            <div class="features">
                <div class="feature">
                    <h4>📅 Бронирование</h4>
                    <p>Легкое бронирование услуг бани с помощью AI-ассистента</p>
                </div>
                <div class="feature">
                    <h4>💰 Цены</h4>
                    <p>Актуальная информация о стоимости всех услуг</p>
                </div>
                <div class="feature">
                    <h4>🏊‍♀️ Услуги</h4>
                    <p>Подробное описание всех банных процедур</p>
                </div>
                <div class="feature">
                    <h4>💬 AI-Консультант</h4>
                    <p>Умные ответы на любые вопросы о бане</p>
                </div>
            </div>
        </div>
        
        <?php if ($configOk): ?>
        <div style="text-align: center;">
            <a href="test-telegram-bot.php" class="btn">🧪 Тест конфигурации</a>
            <a href="setup-webhook.php" class="btn">⚙️ Настройка webhook</a>
        </div>
        <?php endif; ?>
        
        <div class="info">
            <h3>📖 Инструкции по запуску</h3>
            <ol>
                <li>Убедитесь, что все переменные в <code>.env</code> файле заполнены</li>
                <li>Создайте Telegram бота через @BotFather</li>
                <li>Запустите ngrok: <code>ngrok http 8000</code></li>
                <li>Обновите <code>TELEGRAM_WEBHOOK_URL</code> в .env файле</li>
                <li>Запустите <a href="setup-webhook.php">настройку webhook</a></li>
                <li>Запустите сервер: <code>php -S 0.0.0.0:8000</code></li>
                <li>Протестируйте бота в Telegram</li>
            </ol>
        </div>
        
        <div style="text-align: center; margin-top: 30px; color: #7f8c8d;">
            <p>Создано на основе проекта "staff-helper" | Адаптировано для бани "Зима"</p>
        </div>
    </div>
</body>
</html>
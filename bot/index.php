<?php
/**
 * Winter Sauna Telegram Bot
 * Главный файл приложения
 */

require_once 'config/config.php';

// Тестовый endpoint для симуляции оплаты
if (isset($_GET['admin']) && $_GET['admin'] === 'test-payment') {
    $token = $_GET['token'] ?? '';
    $orderId = $_GET['order_id'] ?? '';
    
    if (!$token || $token !== ADMIN_TOKEN) {
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }
    
    if (!$orderId) {
        http_response_code(400);
        echo 'order_id required';
        exit;
    }
    
    // Симулируем успешную оплату
    $testPaymentData = [
        'payment_id' => 'test_' . time(),
        'order_id' => $orderId,
        'payment_status' => 'finished',
        'actually_paid' => 15.0,
        'pay_currency' => 'usdttrc20',
        'price_amount' => 15.0,
        'price_currency' => 'usd'
    ];
    
    require_once 'src/PaymentHandler.php';
    $paymentHandler = new PaymentHandler('ru');
    
    try {
        $result = $paymentHandler->handleSuccessfulPayment($testPaymentData);
        echo json_encode(['success' => true, 'result' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Если это POST запрос, обрабатываем webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'src/TelegramWebhookHandlerLocalized.php';
    $handler = new TelegramWebhookHandlerLocalized();
    $handler->handleWebhook();
    exit;
}

// Служебный эндпоинт для ручной отправки QR по существующему заказу
if (isset($_GET['admin']) && $_GET['admin'] === 'send-qr') {
    $token = $_GET['token'] ?? '';
    $orderId = $_GET['order_id'] ?? '';
    if (!$token || $token !== ADMIN_TOKEN) {
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }
    if (!$orderId) {
        http_response_code(400);
        echo 'order_id required';
        exit;
    }

    require_once 'src/PaymentHandler.php';
    require_once 'src/TicketService.php';

    $handler = new PaymentHandler('en');
    // Доступ к приватному getOrderInfo
    $ref = new ReflectionClass($handler);
    $getOrder = $ref->getMethod('getOrderInfo');
    $getOrder->setAccessible(true);
    $order = $getOrder->invoke($handler, $orderId);
    if (!$order) {
        http_response_code(404);
        echo 'Order not found';
        exit;
    }

    $ticketService = new TicketService();
    $ticket = $ticketService->createTicket(
        $order['order_id'],
        $order['service'],
        $order['amount'],
        $order['currency'],
        ['id' => $order['invoice_id']]
    );
    $qr = $ticketService->generateTicketQR($ticket);

    $sendTicket = $ref->getMethod('sendTicketToUser');
    $sendTicket->setAccessible(true);
    $sendTicket->invoke($handler, $order['chat_id'], $ticket, $qr);

    echo 'QR sent';
    exit;
}

// Эндпоинт для обновления инструкций ассистента OpenAI
if (isset($_GET['admin']) && $_GET['admin'] === 'update-assistant') {
    $token = $_GET['token'] ?? '';
    if (!$token || $token !== ADMIN_TOKEN) {
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }

    $assistantId = 'asst_XCmDp6s1aj9DhOnHVwrzQZXI';
    $apiKey = OPENAI_API_KEY;
    
    if (empty($apiKey)) {
        http_response_code(500);
        echo 'OpenAI API key not configured';
        exit;
    }

    // Загружаем русские инструкции
    $prompts = json_decode(file_get_contents('ai_prompts.json'), true);
    $instructions = $prompts['ru'] ?? 'You are Zima SPA Wellness Assistant.';

    // Обновляем ассистента через OpenAI API
    $url = "https://api.openai.com/v1/assistants/{$assistantId}";
    $data = [
        'instructions' => $instructions,
        'model' => 'gpt-4o-mini',
        'name' => 'Zima SPA Wellness Assistant',
        'description' => 'AI Assistant for Zima SPA Wellness in Phuket - helps with booking, payments, and QR tickets'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'OpenAI-Beta: assistants=v2'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        http_response_code(500);
        echo 'cURL error: ' . $error;
        exit;
    }

    if ($httpCode >= 400) {
        http_response_code($httpCode);
        echo 'OpenAI API error: ' . $response;
        exit;
    }

    $result = json_decode($response, true);
    echo 'Assistant updated successfully! ID: ' . ($result['id'] ?? 'unknown');
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
        $configOk = !empty(TELEGRAM_BOT_TOKEN) && !empty(GEMINI_API_KEY);
        
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
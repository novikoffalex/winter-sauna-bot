<?php
/**
 * Веб-страница с QR-билетом
 */

require_once 'config/config.php';
require_once 'src/TicketService.php';

// Получаем параметры
$orderId = $_GET['order_id'] ?? '';
$chatId = $_GET['chat_id'] ?? '1062522109';

if (empty($orderId)) {
    // Создаем тестовый заказ
    $orderId = 'ORDER-TEST-' . time();
    
    $orderData = [
        'order_id' => $orderId,
        'chat_id' => $chatId,
        'service' => 'test',
        'amount' => 15,
        'currency' => 'USDT',
        'invoice_id' => 'test-invoice-' . time(),
        'status' => 'paid',
        'created_at' => time()
    ];

    // Сохраняем заказ
    $ordersFile = 'data/orders.json';
    if (!file_exists('data')) {
        mkdir('data', 0755, true);
    }

    $orders = [];
    if (file_exists($ordersFile)) {
        $orders = json_decode(file_get_contents($ordersFile), true) ?: [];
    }

    $orders[$orderId] = $orderData;
    file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));
}

// Создаем билет и QR
$ticketService = new TicketService();
$ticket = $ticketService->createTicket(
    $orderId,
    'test',
    15,
    'USDT',
    ['id' => 'test-invoice-' . time()]
);

$qr = $ticketService->generateTicketQR($ticket);

// Генерируем QR как изображение для веб-страницы
$qrCode = new \SimpleSoftwareIO\QrCode\Generator();
$qrCode->size(300)->margin(10);
$qrImage = $qrCode->generate($qr['qr_content']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Билет Zima SPA Wellness</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .ticket {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        .header {
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .qr-code {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .qr-image {
            max-width: 300px;
            height: auto;
            border: 2px solid #ddd;
            border-radius: 10px;
        }
        .ticket-info {
            text-align: left;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .info-value {
            color: #666;
        }
        .instructions {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .print-btn {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }
        .print-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>🧖‍♀️ Zima SPA Wellness</h1>
            <h2>🎫 Билет на услуги</h2>
        </div>

        <div class="qr-code">
            <h3>📱 QR-код для входа</h3>
            <div style="display: inline-block;">
                <?php echo $qrImage; ?>
            </div>
            <p><small>Отсканируйте этот код на входе в сауну</small></p>
        </div>

        <div class="ticket-info">
            <div class="info-row">
                <span class="info-label">ID билета:</span>
                <span class="info-value"><?= htmlspecialchars($ticket['ticket_id']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Услуга:</span>
                <span class="info-value"><?= htmlspecialchars($ticket['service']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Сумма:</span>
                <span class="info-value"><?= htmlspecialchars($ticket['amount']) ?> <?= htmlspecialchars($ticket['currency']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Действует до:</span>
                <span class="info-value"><?= date('Y-m-d H:i', $ticket['expires_at']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Код доступа:</span>
                <span class="info-value"><?= htmlspecialchars($ticket['access_code']) ?></span>
            </div>
        </div>

        <div class="instructions">
            <h4>💡 Как использовать билет:</h4>
            <ol>
                <li>Покажите этот QR-код на входе в сауну</li>
                <li>Сотрудник отсканирует код</li>
                <li>Система проверит подпись и срок действия</li>
                <li>Вход разрешен!</li>
            </ol>
        </div>

        <div class="warning">
            <strong>⚠️ Важно:</strong> Билет действителен только в течение 24 часов с момента создания.
        </div>

        <button class="print-btn" onclick="window.print()">🖨️ Печать билета</button>
        <button class="print-btn" onclick="downloadPDF()">📄 Скачать PDF</button>
    </div>

    <script>
        function downloadPDF() {
            // Простая генерация PDF через браузер
            window.print();
        }
    </script>
</body>
</html>

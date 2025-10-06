<?php
/**
 * Упрощенная веб-страница с QR-билетом для локального тестирования
 */

// Простая генерация QR-кода без внешних зависимостей
function generateSimpleQR($content) {
    // Используем Google Charts API для простоты
    $size = '300x300';
    $url = "https://chart.googleapis.com/chart?chs={$size}&cht=qr&chl=" . urlencode($content);
    return $url;
}

// Создаем тестовые данные
$orderId = $_GET['order_id'] ?? 'ORDER-TEST-' . time();
$ticketId = 'ZIMA-' . $orderId . '-' . time();
$service = 'test';
$amount = 15;
$currency = 'USDT';
$expiresAt = time() + (24 * 60 * 60); // 24 часа
$accessCode = strtoupper(substr(md5($ticketId), 0, 8));

// Создаем QR-данные
$qrData = [
    'ticket_id' => $ticketId,
    'order_id' => $orderId,
    'service' => $service,
    'access_code' => $accessCode,
    'expires_at' => $expiresAt,
    'signature' => hash('sha256', $ticketId . $accessCode . $expiresAt . 'secret_key')
];

$qrContent = json_encode($qrData);
$qrImageUrl = generateSimpleQR($qrContent);
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
        .qr-data {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
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
            <img src="<?= htmlspecialchars($qrImageUrl) ?>" alt="QR Code" class="qr-image">
            <p><small>Отсканируйте этот код на входе в сауну</small></p>
        </div>

        <div class="ticket-info">
            <div class="info-row">
                <span class="info-label">ID билета:</span>
                <span class="info-value"><?= htmlspecialchars($ticketId) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Услуга:</span>
                <span class="info-value"><?= htmlspecialchars($service) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Сумма:</span>
                <span class="info-value"><?= htmlspecialchars($amount) ?> <?= htmlspecialchars($currency) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Действует до:</span>
                <span class="info-value"><?= date('Y-m-d H:i', $expiresAt) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Код доступа:</span>
                <span class="info-value"><?= htmlspecialchars($accessCode) ?></span>
            </div>
        </div>

        <div class="qr-data">
            <strong>QR-данные (JSON):</strong><br>
            <code><?= htmlspecialchars($qrContent) ?></code>
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

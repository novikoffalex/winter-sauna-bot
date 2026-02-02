<?php
/**
 * Веб-страница с QR-билетом для Zima SPA Wellness
 */

require_once 'vendor/autoload.php';

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

// Генерируем QR как встраиваемый SVG
$qrSvg = '';
try {
    $qrCode = new \SimpleSoftwareIO\QrCode\Generator();
    $qrCode->size(300)->margin(10);
    $qrSvg = $qrCode->generate($qrContent); // Возвращает SVG строку
} catch (Exception $e) {
    // Fallback на Google Charts API
    $size = '300x300';
    $url = "https://chart.googleapis.com/chart?chs={$size}&cht=qr&chl=" . urlencode($qrContent);
    $qrSvg = "<img src=\"{$url}\" alt=\"QR Code\" style=\"max-width: 300px; height: auto;\">";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zima SPA Wellness - Билет</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .ticket-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 30px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .ticket-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .ticket-header img {
            height: 40px;
            margin-right: 10px;
        }
        .ticket-header h1 {
            font-size: 2em;
            color: #007bff;
            margin: 0;
        }
        .ticket-header h2 {
            font-size: 1.2em;
            color: #555;
            margin-top: 5px;
        }
        .qr-section {
            margin-bottom: 20px;
        }
        .qr-section h3 {
            font-size: 1.3em;
            color: #444;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-section h3 svg {
            margin-right: 8px;
        }
        .qr-code {
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 15px;
        }
        .qr-code svg {
            display: block;
        }
        .qr-code img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        .qr-instruction {
            font-size: 0.9em;
            color: #777;
            margin-bottom: 20px;
        }
        .ticket-details {
            text-align: left;
            margin-bottom: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .ticket-details p {
            margin: 8px 0;
            font-size: 1.05em;
            line-height: 1.4;
        }
        .ticket-details strong {
            color: #007bff;
            display: inline-block;
            width: 120px;
        }
        .instructions-box {
            background-color: #e6f7ff;
            border: 1px solid #91d5ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
            color: #0056b3;
        }
        .instructions-box h4 {
            margin-top: 0;
            color: #007bff;
            display: flex;
            align-items: center;
        }
        .instructions-box h4 svg {
            margin-right: 8px;
        }
        .instructions-box ol {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .instructions-box li {
            margin-bottom: 5px;
        }
        .important-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #856404;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .important-notice svg {
            margin-right: 8px;
        }
        .action-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .action-buttons button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .action-buttons button:hover {
            background-color: #0056b3;
        }
        @media (max-width: 600px) {
            .ticket-container {
                padding: 20px;
                border-radius: 10px;
            }
            .ticket-header h1 {
                font-size: 1.5em;
            }
            .ticket-header h2 {
                font-size: 1em;
            }
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            .action-buttons button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9ImN1cnJlbnRDb2xvciIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiIGNsYXNzPSJsdWNpZGUgbHVjaWRlLXNhdW5hIj48cGF0aCBkPSJNMTIgMmE2IDYgMCAwIDAgNiA2YzAgNC0yIDYtMyA3bC0zIDMtMy0zYy0xLTEtMy0zLTMtN2E2IDYgMCAwIDAgNi02eiIvPjxwYXRoIGQ9Ik0xMiAxMWEzIDMgMCAxIDAgMC02IDMgMyAwIDAgMCAwIDZ6Ii8+PC9zdmc+" alt="Sauna Icon">
            <div>
                <h1>Zima SPA Wellness</h1>
                <h2>Билет на услуги</h2>
            </div>
        </div>

        <div class="qr-section">
            <h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-qr-code"><rect width="4" height="4" x="2" y="2"/><rect width="4" height="4" x="18" y="2"/><rect width="4" height="4" x="2" y="18"/><path d="M8 2h4"/><path d="M15 2h2"/><path d="M22 8v4"/><path d="M22 15v2"/><path d="M15 22h2"/><path d="M8 22h4"/><path d="M2 8v4"/><path d="M2 15v2"/><rect width="4" height="4" x="8" y="8"/><rect width="4" height="4" x="12" y="12"/><rect width="4" height="4" x="12" y="8"/><rect width="4" height="4" x="8" y="12"/><rect width="4" height="4" x="16" y="16"/><rect width="4" height="4" x="18" y="18"/><path d="M18 12h.01"/><path d="M12 18h.01"/></svg>
                QR-код для входа
            </h3>
            <div class="qr-code">
                <?php echo $qrSvg; ?>
            </div>
            <p class="qr-instruction">Отсканируйте этот код на входе в сауну</p>
        </div>

        <div class="ticket-details">
            <p><strong>ID билета:</strong> <?php echo htmlspecialchars($ticketId); ?></p>
            <p><strong>Услуга:</strong> <?php echo htmlspecialchars($service); ?></p>
            <p><strong>Сумма:</strong> <?php echo htmlspecialchars($amount . ' ' . $currency); ?></p>
            <p><strong>Действует до:</strong> <?php echo date('Y-m-d H:i', $expiresAt); ?></p>
            <p><strong>Код доступа:</strong> <?php echo htmlspecialchars($accessCode); ?></p>
        </div>

        <div class="instructions-box">
            <h4>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1.3.5 2.6 1.5 3.5.8.8 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/><path d="M11 18v4"/><path d="M7.9 7.9c.9-1.8 3-3.2 3.2-3.2"/><path d="M4 16c-1.1 0-2-.9-2-2 0-.9.5-1.7 1.4-2.2"/><path d="M20 14c1.1 0 2 .9 2 2 0 .9-.5 1.7-1.4 2.2"/></svg>
                Как использовать билет:
            </h4>
            <ol>
                <li>Покажите этот QR-код на входе в сауну</li>
                <li>Сотрудник отсканирует код</li>
                <li>Система проверит подпись и срок действия</li>
                <li>Вход разрешен!</li>
            </ol>
        </div>

        <div class="important-notice">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            Важно: Билет действителен только в течение 24 часов с момента создания.
        </div>

        <div class="action-buttons">
            <button onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-printer"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Печать билета
            </button>
            <button onclick="generatePdf()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                Скачать PDF
            </button>
        </div>
    </div>

    <script>
        function generatePdf() {
            alert('Функция скачивания PDF пока не реализована.');
            // Здесь можно использовать библиотеку jsPDF или аналогичную для генерации PDF
            // Или просто предложить пользователю распечатать в PDF через системный диалог печати
            // window.print();
        }
    </script>
</body>
</html>
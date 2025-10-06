<?php
/**
 * Сервис для работы с криптоплатежками
 * Поддерживает CryptoPay (официальный сервис Telegram)
 */

class CryptoPaymentService
{
    private $apiKey;
    private $baseUrl;
    private $webhookSecret;

    public function __construct()
    {
        $this->apiKey = CRYPTOPAY_API_KEY ?? '';
        $this->baseUrl = 'https://pay.crypt.bot/api';
        $this->webhookSecret = CRYPTOPAY_WEBHOOK_SECRET ?? '';
    }

    /**
     * Создание инвойса для оплаты
     */
    public function createInvoice($amount, $currency, $description, $orderId, $returnUrl = null)
    {
        $url = $this->baseUrl . '/createInvoice';
        
        $data = [
            'asset' => $currency, // USDT, BTC, ETH, etc.
            'amount' => $amount,
            'description' => $description,
            'hidden_message' => "Zima SPA Wellness - Order #{$orderId}",
            'paid_btn_name' => 'View Ticket',
            'paid_btn_url' => $returnUrl ?: 'https://t.me/' . BOT_USERNAME,
            'payload' => json_encode([
                'order_id' => $orderId,
                'service' => 'zima_sauna',
                'timestamp' => time()
            ])
        ];

        return $this->makeRequest($url, $data);
    }

    /**
     * Получение информации об инвойсе
     */
    public function getInvoice($invoiceId)
    {
        $url = $this->baseUrl . '/getInvoices';
        $data = ['invoice_ids' => $invoiceId];
        
        return $this->makeRequest($url, $data);
    }

    /**
     * Получение баланса
     */
    public function getBalance()
    {
        $url = $this->baseUrl . '/getBalance';
        return $this->makeRequest($url);
    }

    /**
     * Получение курсов валют
     */
    public function getExchangeRates()
    {
        $url = $this->baseUrl . '/getExchangeRates';
        return $this->makeRequest($url);
    }

    /**
     * Валидация webhook от CryptoPay
     */
    public function validateWebhook($data, $signature)
    {
        $expectedSignature = hash_hmac('sha256', $data, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Обработка успешной оплаты
     */
    public function handleSuccessfulPayment($paymentData)
    {
        $payload = json_decode($paymentData['payload'] ?? '{}', true);
        $orderId = $payload['order_id'] ?? null;
        
        if (!$orderId) {
            throw new Exception('Invalid payment payload');
        }

        // Генерируем QR-код для турникета
        $qrData = $this->generateTicketQR($orderId, $paymentData);
        
        return [
            'order_id' => $orderId,
            'payment_id' => $paymentData['id'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['asset'],
            'qr_data' => $qrData,
            'expires_at' => time() + (24 * 60 * 60) // 24 часа
        ];
    }

    /**
     * Генерация QR-кода для турникета
     */
    private function generateTicketQR($orderId, $paymentData)
    {
        $ticketData = [
            'ticket_id' => 'ZIMA-' . $orderId . '-' . time(),
            'order_id' => $orderId,
            'service' => 'zima_sauna',
            'payment_id' => $paymentData['id'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['asset'],
            'issued_at' => time(),
            'expires_at' => time() + (24 * 60 * 60),
            'access_code' => $this->generateAccessCode($orderId)
        ];

        // Создаем QR-код с данными билета
        $qrContent = json_encode($ticketData);
        
        return [
            'qr_content' => $qrContent,
            'qr_image_url' => $this->generateQRImage($qrContent),
            'ticket_id' => $ticketData['ticket_id'],
            'expires_at' => $ticketData['expires_at']
        ];
    }

    /**
     * Генерация QR-изображения
     */
    private function generateQRImage($content)
    {
        // Используем Google Charts API для генерации QR-кода
        $size = '300x300';
        $url = "https://chart.googleapis.com/chart?chs={$size}&cht=qr&chl=" . urlencode($content);
        
        return $url;
    }

    /**
     * Генерация кода доступа
     */
    private function generateAccessCode($orderId)
    {
        return strtoupper(substr(md5($orderId . time() . 'zima_secret'), 0, 8));
    }

    /**
     * Валидация билета (для турникета)
     */
    public function validateTicket($qrContent)
    {
        $ticketData = json_decode($qrContent, true);
        
        if (!$ticketData) {
            return ['valid' => false, 'error' => 'Invalid QR code'];
        }

        // Проверяем срок действия
        if (time() > $ticketData['expires_at']) {
            return ['valid' => false, 'error' => 'Ticket expired'];
        }

        // Проверяем подпись (можно добавить криптографическую подпись)
        if (!$this->verifyTicketSignature($ticketData)) {
            return ['valid' => false, 'error' => 'Invalid ticket signature'];
        }

        return [
            'valid' => true,
            'ticket_id' => $ticketData['ticket_id'],
            'order_id' => $ticketData['order_id'],
            'expires_at' => $ticketData['expires_at']
        ];
    }

    /**
     * Проверка подписи билета
     */
    private function verifyTicketSignature($ticketData)
    {
        // Простая проверка, можно улучшить с помощью HMAC
        $expectedAccessCode = $this->generateAccessCode($ticketData['order_id']);
        return $ticketData['access_code'] === $expectedAccessCode;
    }

    /**
     * Выполнение HTTP запроса к CryptoPay API
     */
    private function makeRequest($url, $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Crypto-Pay-API-Token: ' . $this->apiKey,
            'User-Agent: Zima-Sauna-Bot/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("CryptoPay cURL error: $error");
            throw new Exception("CryptoPay API error: $error");
        }

        if ($httpCode >= 400) {
            error_log("CryptoPay HTTP error $httpCode: $response");
            throw new Exception("CryptoPay API error $httpCode: $response");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("CryptoPay invalid JSON response: $response");
            throw new Exception("CryptoPay invalid JSON response");
        }

        if (!$decoded['ok']) {
            throw new Exception("CryptoPay API error: " . ($decoded['error']['name'] ?? 'Unknown error'));
        }

        return $decoded['result'];
    }
}

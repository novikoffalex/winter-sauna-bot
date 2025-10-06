<?php
/**
 * Сервис для работы с билетами и QR-кодами
 */

require_once __DIR__ . '/../vendor/autoload.php';

class TicketService
{
    private $cryptoPayment;

    public function __construct()
    {
        $this->cryptoPayment = new CryptoPaymentService();
    }

    /**
     * Создание билета после успешной оплаты
     */
    public function createTicket($orderId, $service, $amount, $currency, $paymentData)
    {
        $ticketData = [
            'ticket_id' => 'ZIMA-' . $orderId . '-' . time(),
            'order_id' => $orderId,
            'service' => $service,
            'amount' => $amount,
            'currency' => $currency,
            'payment_id' => $paymentData['id'],
            'created_at' => time(),
            'expires_at' => time() + (24 * 60 * 60), // 24 часа
            'status' => 'active',
            'access_code' => $this->generateAccessCode($orderId)
        ];

        // Сохраняем билет в базу данных (или файл)
        $this->saveTicket($ticketData);

        return $ticketData;
    }

    /**
     * Генерация QR-кода для билета
     */
    public function generateTicketQR($ticketData)
    {
        $qrContent = json_encode([
            'ticket_id' => $ticketData['ticket_id'],
            'order_id' => $ticketData['order_id'],
            'service' => $ticketData['service'],
            'access_code' => $ticketData['access_code'],
            'expires_at' => $ticketData['expires_at'],
            'signature' => $this->generateTicketSignature($ticketData)
        ]);

        return [
            'qr_content' => $qrContent,
            'qr_image_url' => $this->generateQRImage($qrContent),
            'ticket_id' => $ticketData['ticket_id'],
            'expires_at' => $ticketData['expires_at']
        ];
    }

    /**
     * Валидация билета (для турникета)
     */
    public function validateTicket($qrContent)
    {
        $ticketData = json_decode($qrContent, true);
        
        if (!$ticketData) {
            return ['valid' => false, 'error' => 'Invalid QR code format'];
        }

        // Проверяем срок действия
        if (time() > $ticketData['expires_at']) {
            return ['valid' => false, 'error' => 'Ticket has expired'];
        }

        // Проверяем подпись
        if (!$this->verifyTicketSignature($ticketData)) {
            return ['valid' => false, 'error' => 'Invalid ticket signature'];
        }

        // Проверяем статус в базе данных
        $storedTicket = $this->getTicket($ticketData['ticket_id']);
        if (!$storedTicket || $storedTicket['status'] !== 'active') {
            return ['valid' => false, 'error' => 'Ticket not found or inactive'];
        }

        return [
            'valid' => true,
            'ticket_id' => $ticketData['ticket_id'],
            'order_id' => $ticketData['order_id'],
            'service' => $ticketData['service'],
            'expires_at' => $ticketData['expires_at']
        ];
    }

    /**
     * Использование билета (прохождение через турникет)
     */
    public function useTicket($ticketId)
    {
        $ticket = $this->getTicket($ticketId);
        
        if (!$ticket) {
            return ['success' => false, 'error' => 'Ticket not found'];
        }

        if ($ticket['status'] !== 'active') {
            return ['success' => false, 'error' => 'Ticket already used or inactive'];
        }

        if (time() > $ticket['expires_at']) {
            return ['success' => false, 'error' => 'Ticket has expired'];
        }

        // Помечаем билет как использованный
        $ticket['status'] = 'used';
        $ticket['used_at'] = time();
        $this->saveTicket($ticket);

        return [
            'success' => true,
            'ticket_id' => $ticketId,
            'service' => $ticket['service'],
            'used_at' => $ticket['used_at']
        ];
    }

    /**
     * Получение информации о билете
     */
    public function getTicket($ticketId)
    {
        $ticketsFile = 'data/tickets.json';
        
        if (!file_exists($ticketsFile)) {
            return null;
        }

        $tickets = json_decode(file_get_contents($ticketsFile), true);
        return $tickets[$ticketId] ?? null;
    }

    /**
     * Сохранение билета
     */
    private function saveTicket($ticketData)
    {
        $ticketsFile = 'data/tickets.json';
        
        // Создаем директорию если не существует
        if (!file_exists('data')) {
            mkdir('data', 0755, true);
        }

        $tickets = [];
        if (file_exists($ticketsFile)) {
            $tickets = json_decode(file_get_contents($ticketsFile), true) ?: [];
        }

        $tickets[$ticketData['ticket_id']] = $ticketData;
        
        file_put_contents($ticketsFile, json_encode($tickets, JSON_PRETTY_PRINT));
    }

    /**
     * Генерация кода доступа
     */
    private function generateAccessCode($orderId)
    {
        return strtoupper(substr(md5($orderId . time() . 'zima_secret_key'), 0, 8));
    }

    /**
     * Генерация подписи билета
     */
    private function generateTicketSignature($ticketData)
    {
        $signatureData = $ticketData['ticket_id'] . $ticketData['order_id'] . $ticketData['access_code'];
        return hash_hmac('sha256', $signatureData, 'zima_ticket_secret');
    }

    /**
     * Проверка подписи билета
     */
    private function verifyTicketSignature($ticketData)
    {
        $expectedSignature = $this->generateTicketSignature($ticketData);
        return hash_equals($expectedSignature, $ticketData['signature']);
    }

    /**
     * Генерация QR-изображения
     */
    private function generateQRImage($content)
    {
        try {
            // Используем Simple QR Code для генерации QR-кода
            $qrCode = new \SimpleSoftwareIO\QrCode\Generator();
            $qrCode->size(300)->margin(10);
            
            // Сохраняем QR-код во временный файл
            $tempFile = 'data/qr_' . time() . '.png';
            if (!file_exists('data')) {
                mkdir('data', 0755, true);
            }
            
            $qrCode->generate($content, $tempFile);
            
            // Возвращаем путь к файлу
            return $tempFile;
            
        } catch (Exception $e) {
            error_log("QR generation error: " . $e->getMessage());
            // Fallback на Google Charts API
            $size = '300x300';
            $url = "https://chart.googleapis.com/chart?chs={$size}&cht=qr&chl=" . urlencode($content);
            return $url;
        }
    }

    /**
     * Получение статистики билетов
     */
    public function getTicketStats()
    {
        $ticketsFile = 'data/tickets.json';
        
        if (!file_exists($ticketsFile)) {
            return [
                'total' => 0,
                'active' => 0,
                'used' => 0,
                'expired' => 0
            ];
        }

        $tickets = json_decode(file_get_contents($ticketsFile), true) ?: [];
        $stats = [
            'total' => count($tickets),
            'active' => 0,
            'used' => 0,
            'expired' => 0
        ];

        $currentTime = time();
        foreach ($tickets as $ticket) {
            if ($ticket['status'] === 'used') {
                $stats['used']++;
            } elseif ($ticket['expires_at'] < $currentTime) {
                $stats['expired']++;
            } else {
                $stats['active']++;
            }
        }

        return $stats;
    }
}

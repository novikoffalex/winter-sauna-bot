<?php
/**
 * Обработчик криптоплатежек и билетов
 */

require_once 'CryptoPaymentService.php';
require_once 'TicketService.php';
require_once 'TelegramService.php';
require_once 'LocalizationService.php';

class PaymentHandler
{
    private $cryptoPayment;
    private $ticketService;
    private $telegramService;
    private $localization;

    public function __construct($userLanguage = 'en')
    {
        $this->cryptoPayment = new CryptoPaymentService();
        $this->ticketService = new TicketService();
        $this->telegramService = new TelegramService();
        $this->localization = new LocalizationService($userLanguage);
    }

    /**
     * Создание инвойса для оплаты услуги
     */
    public function createPaymentInvoice($chatId, $service, $amount, $currency = 'USDT')
    {
        $orderId = 'ORDER-' . time() . '-' . rand(1000, 9999);
        
        // Получаем описание услуги
        $serviceDescription = $this->getServiceDescription($service);
        
        $description = "Zima SPA Wellness - {$serviceDescription}";
        $returnUrl = "https://t.me/" . BOT_USERNAME . "?start=payment_success";
        
        try {
            $invoice = $this->cryptoPayment->createInvoice(
                $amount,
                $currency,
                $description,
                $orderId,
                $returnUrl
            );

            // Парсим invoice_id для сохранения
            $invoiceId = $invoice['invoice_id'] ?? $invoice['id'] ?? $invoice['payment_id'] ?? 'unknown';
            
            // Сохраняем информацию о заказе
            $this->saveOrderInfo($orderId, $chatId, $service, $amount, $currency, $invoiceId);

            // Парсим ответ от NOWPayments
            $invoiceId = $invoice['invoice_id'] ?? $invoice['id'] ?? $invoice['payment_id'] ?? 'unknown';
            $payUrl = $invoice['invoice_url'] ?? $invoice['pay_url'] ?? $invoice['payment_url'] ?? $invoice['checkout_url'] ?? $invoice['url'] ?? '';
            
            error_log("Parsed invoice data: " . json_encode([
                'invoice_id' => $invoiceId,
                'pay_url' => $payUrl,
                'full_response' => $invoice
            ]));

            return [
                'success' => true,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'pay_url' => $payUrl,
                'amount' => $amount,
                'currency' => $currency
            ];

        } catch (Exception $e) {
            error_log("Payment creation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Обработка успешной оплаты
     */
    public function handleSuccessfulPayment($paymentData)
    {
        try {
            $orderInfo = $this->getOrderInfo($paymentData['payload']['order_id']);
            
            if (!$orderInfo) {
                throw new Exception('Order not found');
            }

            // Создаем билет
            $ticketData = $this->ticketService->createTicket(
                $orderInfo['order_id'],
                $orderInfo['service'],
                $orderInfo['amount'],
                $orderInfo['currency'],
                $paymentData
            );

            // Генерируем QR-код
            $qrData = $this->ticketService->generateTicketQR($ticketData);

            // Отправляем билет пользователю
            $this->sendTicketToUser($orderInfo['chat_id'], $ticketData, $qrData);

            return [
                'success' => true,
                'ticket_id' => $ticketData['ticket_id'],
                'qr_data' => $qrData
            ];

        } catch (Exception $e) {
            error_log("Payment handling error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Отправка билета пользователю
     */
    private function sendTicketToUser($chatId, $ticketData, $qrData)
    {
        $message = "🎫 **" . $this->localization->t('ticket_created') . "!**\n\n";
        $message .= "📋 **" . $this->localization->t('ticket_id') . ":** `{$ticketData['ticket_id']}`\n";
        $message .= "🏊‍♀️ **" . $this->localization->t('service') . ":** {$ticketData['service']}\n";
        $message .= "💰 **" . $this->localization->t('amount') . ":** {$ticketData['amount']} {$ticketData['currency']}\n";
        $message .= "⏰ **" . $this->localization->t('expires_at') . ":** " . date('Y-m-d H:i', $ticketData['expires_at']) . "\n\n";
        $message .= "📱 **" . $this->localization->t('how_to_use') . ":**\n";
        $message .= "1. " . $this->localization->t('show_qr_at_entrance') . "\n";
        $message .= "2. " . $this->localization->t('scan_qr_code') . "\n";
        $message .= "3. " . $this->localization->t('enjoy_service') . "\n\n";
        $message .= "⚠️ " . $this->localization->t('ticket_valid_24h');

        // Отправляем сообщение с QR-кодом
        $this->telegramService->sendPhoto(
            $chatId,
            $qrData['qr_image_url'],
            $message
        );

        // Отправляем дополнительную информацию
        $infoMessage = "🔍 **" . $this->localization->t('ticket_details') . ":**\n\n";
        $infoMessage .= "**" . $this->localization->t('access_code') . ":** `{$ticketData['access_code']}`\n";
        $infoMessage .= "**" . $this->localization->t('location') . ":** " . SAUNA_LOCATION . "\n";
        $infoMessage .= "**" . $this->localization->t('working_hours') . ":** " . SAUNA_WORKING_HOURS . "\n\n";
        $infoMessage .= "❓ " . $this->localization->t('if_questions_contact');

        $this->telegramService->sendMessage($chatId, $infoMessage);
    }

    /**
     * Получение описания услуги
     */
    private function getServiceDescription($service)
    {
        $descriptions = [
            'massage' => $this->localization->t('massage'),
            'treatment' => $this->localization->t('treatment'),
            'spa' => $this->localization->t('spa'),
            'wellness' => $this->localization->t('wellness')
        ];

        return $descriptions[$service] ?? $service;
    }

    /**
     * Сохранение информации о заказе
     */
    private function saveOrderInfo($orderId, $chatId, $service, $amount, $currency, $invoiceId)
    {
        $orderData = [
            'order_id' => $orderId,
            'chat_id' => $chatId,
            'service' => $service,
            'amount' => $amount,
            'currency' => $currency,
            'invoice_id' => $invoiceId,
            'status' => 'pending',
            'created_at' => time()
        ];

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

    /**
     * Получение информации о заказе
     */
    private function getOrderInfo($orderId)
    {
        $ordersFile = 'data/orders.json';
        
        if (!file_exists($ordersFile)) {
            return null;
        }

        $orders = json_decode(file_get_contents($ordersFile), true);
        return $orders[$orderId] ?? null;
    }

    /**
     * Обновление статуса заказа
     */
    public function updateOrderStatus($orderId, $status)
    {
        $ordersFile = 'data/orders.json';
        
        if (!file_exists($ordersFile)) {
            return false;
        }

        $orders = json_decode(file_get_contents($ordersFile), true);
        
        if (isset($orders[$orderId])) {
            $orders[$orderId]['status'] = $status;
            $orders[$orderId]['updated_at'] = time();
            file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));
            return true;
        }

        return false;
    }

    /**
     * Получение статистики платежей
     */
    public function getPaymentStats()
    {
        $ordersFile = 'data/orders.json';
        
        if (!file_exists($ordersFile)) {
            return [
                'total_orders' => 0,
                'pending' => 0,
                'paid' => 0,
                'total_revenue' => 0
            ];
        }

        $orders = json_decode(file_get_contents($ordersFile), true) ?: [];
        
        $stats = [
            'total_orders' => count($orders),
            'pending' => 0,
            'paid' => 0,
            'total_revenue' => 0
        ];

        foreach ($orders as $order) {
            if ($order['status'] === 'pending') {
                $stats['pending']++;
            } elseif ($order['status'] === 'paid') {
                $stats['paid']++;
                $stats['total_revenue'] += $order['amount'];
            }
        }

        return $stats;
    }
}

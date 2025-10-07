<?php
/**
 * Локализованный сервис для работы с OpenAI API
 */

require_once 'LocalizationService.php';
require_once 'ConversationStore.php';

class AIServiceLocalized
{
    private $apiKey;
    private $model;
    private $baseUrl;
    private $assistantId;
    private $localization;
    private $store;

    public function __construct($userLanguage = 'en')
    {
        $this->apiKey = OPENAI_API_KEY;
        $this->model = OPENAI_MODEL;
        $this->baseUrl = 'https://api.openai.com/v1';
        $this->assistantId = $_ENV['OPENAI_ASSISTANT_ID'] ?? 'asst_XCmDp6s1aj9DhOnHVwrzQZXI';
        $this->localization = new LocalizationService($userLanguage);
        $this->store = new ConversationStore();
    }

    /**
     * Инициализация сервиса
     */
    public function initialize()
    {
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key is required');
        }

        error_log('AI service initialized with model: ' . $this->model . ', language: ' . $this->localization->getLanguage() . ', assistant: ' . $this->assistantId);
    }

    /**
     * Получение системного промпта на текущем языке
     */
    private function getSystemPrompt()
    {
        return $this->localization->getAIPrompt();
    }

    /**
     * Обработка сообщения пользователя
     */
    public function processMessage($userMessage, $context = [])
    {
        if (empty($this->apiKey)) {
            throw new Exception('AI service not initialized');
        }

        try {
            error_log('Processing message with AI in language: ' . $this->localization->getLanguage());

            // Используем Assistant API вместо Chat Completions
            $chatId = $context['chat_id'] ?? null;
            $threadId = $this->getOrCreateThread($chatId);
            
            error_log("Using thread ID: " . $threadId . " for chat: " . $chatId);

            // Добавляем сообщение в тред
            $this->addMessageToThread($threadId, $userMessage);

            // Запускаем ассистента
            $runId = $this->createRun($threadId);

            // Ждем завершения
            $this->waitForRunCompletion($threadId, $runId);

            // Получаем ответ
            $aiResponse = $this->getLastAssistantMessage($threadId);
            
            error_log("Assistant response: " . $aiResponse);

            // Сохраняем в локальную историю для совместимости
            if ($chatId) {
                $this->store->append($chatId, 'user', $userMessage);
                $this->store->append($chatId, 'assistant', $aiResponse);
            }

            error_log('AI response generated in ' . $this->localization->getLanguage());
            return $this->formatResponse($aiResponse, $context);

        } catch (Exception $e) {
            error_log('AI processing error: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'insufficient_quota') !== false) {
                return $this->localization->t('api_quota_exceeded', ['ru' => 'Извините, у меня закончились лимиты API. Попробуйте позже.', 'en' => 'Sorry, I have exceeded API limits. Please try later.']);
            }
            
            if (strpos($e->getMessage(), 'rate_limit_exceeded') !== false) {
                return $this->localization->t('rate_limit_exceeded', ['ru' => 'Слишком много запросов. Подождите немного и попробуйте снова.', 'en' => 'Too many requests. Please wait a moment and try again.']);
            }
            
            return $this->localization->t('processing_error', ['ru' => 'Извините, произошла ошибка при обработке вашего запроса. Попробуйте переформулировать вопрос.', 'en' => 'Sorry, an error occurred while processing your request. Please try rephrasing your question.']);
        }
    }

    /**
     * Форматирование сообщения пользователя с контекстом
     */
    private function formatUserMessage($message, $context)
    {
        $formattedMessage = $message;
        
        if (!empty($context['senderId'])) {
            $formattedMessage = "Пользователь (ID: {$context['senderId']}): $message";
        }
        
        return $formattedMessage;
    }

    /**
     * Форматирование ответа ИИ
     */
    private function formatResponse($response, $context)
    {
        $formattedResponse = $response;
        
        // Добавляем эмодзи в зависимости от языка
        if ($this->localization->getLanguage() === 'ru') {
            // Русские эмодзи
            if (stripos($response, 'забронировать') !== false || 
                stripos($response, 'запись') !== false ||
                stripos($response, 'время') !== false) {
                $formattedResponse = "📅 $formattedResponse";
            }
            
            if (stripos($response, 'массаж') !== false || 
                stripos($response, 'спа') !== false ||
                stripos($response, 'лечение') !== false) {
                $formattedResponse = "💆‍♀️ $formattedResponse";
            }
            
            if (stripos($response, 'цена') !== false || 
                stripos($response, 'стоимость') !== false ||
                stripos($response, 'бат') !== false) {
                $formattedResponse = "💰 $formattedResponse";
            }
        } else {
            // English emojis
            if (stripos($response, 'book') !== false || 
                stripos($response, 'booking') !== false ||
                stripos($response, 'time') !== false) {
                $formattedResponse = "📅 $formattedResponse";
            }
            
            if (stripos($response, 'massage') !== false || 
                stripos($response, 'spa') !== false ||
                stripos($response, 'treatment') !== false) {
                $formattedResponse = "💆‍♀️ $formattedResponse";
            }
            
            if (stripos($response, 'price') !== false || 
                stripos($response, 'cost') !== false ||
                stripos($response, 'thb') !== false) {
                $formattedResponse = "💰 $formattedResponse";
            }
        }
        
        return $formattedResponse;
    }

    /**
     * Анализ намерений пользователя
     */
    public function analyzeIntent($message)
    {
        $language = $this->localization->getLanguage();
        
        if ($language === 'ru') {
            $intents = [
                'booking' => ['забронировать', 'бронирование', 'записаться', 'запись', 'время', 'свободно'],
                'services' => ['услуги', 'массаж', 'спа', 'лечение', 'что есть'],
                'prices' => ['цена', 'стоимость', 'сколько стоит', 'прайс', 'тариф'],
                'schedule' => ['работаете', 'время работы', 'открыто', 'закрыто', 'график'],
                'location' => ['где', 'адрес', 'как добраться', 'местоположение', 'найти'],
                'contact' => ['телефон', 'связаться', 'контакты', 'позвонить'],
                'preparation' => ['подготовиться', 'что взять', 'что нужно', 'советы'],
                'question' => ['что', 'как', 'где', 'когда', 'почему', 'зачем'],
                'help' => ['помощь', 'помоги', 'как', 'что делать']
            ];
        } else {
            $intents = [
                'booking' => ['book', 'booking', 'appointment', 'reservation', 'time', 'available'],
                'services' => ['services', 'massage', 'spa', 'treatment', 'what do you have'],
                'prices' => ['price', 'cost', 'how much', 'rates', 'pricing'],
                'schedule' => ['working', 'hours', 'open', 'closed', 'schedule'],
                'location' => ['where', 'address', 'how to get', 'location', 'find'],
                'contact' => ['phone', 'contact', 'call', 'reach'],
                'preparation' => ['prepare', 'what to bring', 'what do I need', 'tips'],
                'question' => ['what', 'how', 'where', 'when', 'why'],
                'help' => ['help', 'assist', 'how', 'what to do']
            ];
        }

        $lowerMessage = strtolower($message);
        
        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($lowerMessage, $keyword) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'general';
    }

    /**
     * Получение локализации
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * Получение или создание треда для чата
     */
    private function getOrCreateThread($chatId)
    {
        $threadFile = 'data/conversations/thread_' . $chatId . '.json';
        if (file_exists($threadFile)) {
            $data = json_decode(file_get_contents($threadFile), true);
            return $data['thread_id'] ?? $this->createNewThread($chatId);
        }
        return $this->createNewThread($chatId);
    }

    /**
     * Создание нового треда
     */
    private function createNewThread($chatId)
    {
        $response = $this->makeRequest('/threads', [], 'POST');
        $threadId = $response['id'];
        
        $threadFile = 'data/conversations/thread_' . $chatId . '.json';
        if (!file_exists('data/conversations')) {
            mkdir('data/conversations', 0755, true);
        }
        file_put_contents($threadFile, json_encode(['thread_id' => $threadId]));
        
        return $threadId;
    }

    /**
     * Добавление сообщения в тред
     */
    private function addMessageToThread($threadId, $content)
    {
        error_log("Adding message to thread {$threadId}: " . $content);
        $this->makeRequest("/threads/{$threadId}/messages", [
            'role' => 'user',
            'content' => $content
        ], 'POST');
    }

    /**
     * Создание запуска ассистента
     */
    private function createRun($threadId)
    {
        $response = $this->makeRequest("/threads/{$threadId}/runs", [
            'assistant_id' => $this->assistantId
        ], 'POST');
        return $response['id'];
    }

    /**
     * Ожидание завершения запуска
     */
    private function waitForRunCompletion($threadId, $runId)
    {
        $maxAttempts = 30;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            $response = $this->makeRequest("/threads/{$threadId}/runs/{$runId}");
            $status = $response['status'];
            
            if ($status === 'completed') {
                return;
            }
            
            if ($status === 'requires_action') {
                // Обрабатываем функции
                $this->handleFunctionCalls($threadId, $runId, $response);
                continue;
            }
            
            if ($status === 'failed' || $status === 'cancelled') {
                throw new Exception("Run failed with status: $status");
            }
            
            sleep(1);
            $attempt++;
        }
        
        throw new Exception("Run timeout after $maxAttempts attempts");
    }

    /**
     * Обработка вызовов функций
     */
    private function handleFunctionCalls($threadId, $runId, $runResponse)
    {
        $toolCalls = $runResponse['required_action']['submit_tool_outputs']['tool_calls'] ?? [];
        $toolOutputs = [];
        
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);
            $callId = $toolCall['id'];
            
            error_log("Handling function call: $functionName with args: " . json_encode($arguments));
            
            try {
                $result = $this->executeFunction($functionName, $arguments);
                $toolOutputs[] = [
                    'tool_call_id' => $callId,
                    'output' => $result
                ];
            } catch (Exception $e) {
                error_log("Function execution error: " . $e->getMessage());
                $toolOutputs[] = [
                    'tool_call_id' => $callId,
                    'output' => 'Error: ' . $e->getMessage()
                ];
            }
        }
        
        // Отправляем результаты обратно
        $this->makeRequest("/threads/{$threadId}/runs/{$runId}/submit_tool_outputs", [
            'tool_outputs' => $toolOutputs
        ], 'POST');
    }

    /**
     * Выполнение функций
     */
    private function executeFunction($functionName, $arguments)
    {
        switch ($functionName) {
            case 'create_payment_link':
                return $this->createPaymentLink($arguments['service_name'], $arguments['price_thb']);
            
            case 'create_qr_ticket':
                return $this->createQrTicket($arguments['order_id']);
            
            default:
                throw new Exception("Unknown function: $functionName");
        }
    }

    /**
     * Создание ссылки на оплату
     */
    private function createPaymentLink($serviceName, $priceThb)
    {
        // Конвертируем THB в USD
        $priceUsd = $priceThb * 0.027; // Примерный курс
        
        // Минимальная сумма 15 USD
        if ($priceUsd < 15) {
            $priceUsd = 15;
        }
        
        // Создаем ссылку через NOWPayments API
        $orderId = 'zima_' . time() . '_' . rand(1000, 9999);
        
        try {
            // Используем реальный NOWPayments API для создания ссылки
            $paymentUrl = $this->createNOWPaymentsInvoice($orderId, $priceUsd, $serviceName);
            
            // Сохраняем информацию о заказе
            $this->saveOrderInfo($orderId, $serviceName, $priceThb, $priceUsd);
            
            return "Вот ссылка для оплаты: $paymentUrl\n\nУслуга: $serviceName\nСумма: $priceUsd USDT (USDTTRC20)\n\nПосле оплаты, пожалуйста, дайте мне знать, чтобы я мог создать QR-билет для входа!";
            
        } catch (Exception $e) {
            error_log("Error creating payment link: " . $e->getMessage());
            
            // Fallback к простой ссылке
            $paymentUrl = "https://nowpayments.io/payment?iid=" . $orderId . "&amount=" . $priceUsd . "&currency=USDTTRC20";
            $this->saveOrderInfo($orderId, $serviceName, $priceThb, $priceUsd);
            
            return "Ссылка для оплаты: $paymentUrl\nУслуга: $serviceName\nСумма: $priceUsd USDT (USDTTRC20)";
        }
    }

    /**
     * Создание инвойса через NOWPayments API
     */
    private function createNOWPaymentsInvoice($orderId, $amountUsd, $serviceName)
    {
        $apiKey = NOWPAYMENTS_API_KEY;
        $publicKey = NOWPAYMENTS_PUBLIC_KEY;
        
        if (empty($apiKey) || empty($publicKey)) {
            throw new Exception("NOWPayments API keys not configured");
        }
        
        $data = [
            'price_amount' => $amountUsd,
            'price_currency' => 'usd',
            'pay_currency' => 'usdttrc20',
            'order_id' => $orderId,
            'order_description' => $serviceName,
            'ipn_callback_url' => 'https://winter-sauna-bot-phuket-f79605d5d044.herokuapp.com/crypto-webhook.php',
            'case' => 'success'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.nowpayments.io/v1/invoice');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'x-public-key: ' . $publicKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("NOWPayments API error $httpCode: $response");
        }
        
        $result = json_decode($response, true);
        
        // Возвращаем ссылку на оплату
        if (isset($result['invoice_url'])) {
            return $result['invoice_url'];
        } elseif (isset($result['pay_url'])) {
            return $result['pay_url'];
        } else {
            throw new Exception("No payment URL in response: " . $response);
        }
    }

    /**
     * Создание QR-билета
     */
    private function createQrTicket($orderId)
    {
        // Здесь можно добавить логику создания QR-билета
        return "QR-билет создан для заказа: $orderId";
    }

    /**
     * Сохранение информации о заказе
     */
    private function saveOrderInfo($orderId, $serviceName, $priceThb, $priceUsd)
    {
        $orderData = [
            'order_id' => $orderId,
            'service_name' => $serviceName,
            'price_thb' => $priceThb,
            'price_usd' => $priceUsd,
            'currency' => 'USDTTRC20',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $ordersFile = __DIR__ . '/../data/orders.json';
        $orders = [];
        
        if (file_exists($ordersFile)) {
            $orders = json_decode(file_get_contents($ordersFile), true) ?: [];
        }
        
        $orders[$orderId] = $orderData;
        
        if (!is_dir(dirname($ordersFile))) {
            mkdir(dirname($ordersFile), 0755, true);
        }
        
        file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));
    }

    /**
     * Получение последнего сообщения ассистента
     */
    private function getLastAssistantMessage($threadId)
    {
        $response = $this->makeRequest("/threads/{$threadId}/messages?order=desc&limit=10");
        $messages = $response['data'] ?? [];
        
        error_log("Thread {$threadId} has " . count($messages) . " messages (ordered desc)");
        
        // Ищем первое сообщение от ассистента (последнее по времени)
        foreach ($messages as $message) {
            if ($message['role'] === 'assistant') {
                $content = $message['content'][0]['text']['value'] ?? '';
                error_log("Found latest assistant message: " . $content);
                return $content;
            }
        }
        
        throw new Exception("No assistant message found");
    }

    /**
     * Выполнение HTTP запроса к OpenAI API
     */
    private function makeRequest($endpoint, $data = [], $method = 'GET')
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'OpenAI-Beta: assistants=v2',
            'User-Agent: Zima-SPA-Bot/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? "HTTP error $httpCode: $response";
            throw new Exception($errorMessage);
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: $response");
        }

        return $decoded;
    }
}

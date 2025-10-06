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
        $this->assistantId = 'asst_XCmDp6s1aj9DhOnHVwrzQZXI';
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

        error_log('AI service initialized with model: ' . $this->model . ', language: ' . $this->localization->getLanguage());
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

            // Добавляем сообщение в тред
            $this->addMessageToThread($threadId, $userMessage);

            // Запускаем ассистента
            $runId = $this->createRun($threadId);

            // Ждем завершения
            $this->waitForRunCompletion($threadId, $runId);

            // Получаем ответ
            $aiResponse = $this->getLastAssistantMessage($threadId);

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
            
            if ($status === 'failed' || $status === 'cancelled') {
                throw new Exception("Run failed with status: $status");
            }
            
            sleep(1);
            $attempt++;
        }
        
        throw new Exception("Run timeout after $maxAttempts attempts");
    }

    /**
     * Получение последнего сообщения ассистента
     */
    private function getLastAssistantMessage($threadId)
    {
        $response = $this->makeRequest("/threads/{$threadId}/messages");
        $messages = $response['data'] ?? [];
        
        // Ищем последнее сообщение от ассистента
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ($messages[$i]['role'] === 'assistant') {
                $content = $messages[$i]['content'][0]['text']['value'] ?? '';
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

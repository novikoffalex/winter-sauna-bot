<?php
/**
 * Сервис для работы с Google Gemini API
 */

require_once 'LocalizationService.php';
require_once 'ConversationStore.php';

class GeminiService
{
    private $apiKey;
    private $model;
    private $baseUrl;
    private $localization;
    private $store;
    private $currentChatId;

    public function __construct($userLanguage = 'en')
    {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
        // Используем правильное имя модели для v1beta API
        // Доступные модели: gemini-2.5-flash, gemini-2.0-flash, gemini-1.5-flash-latest
        $this->model = $_ENV['GEMINI_MODEL'] ?? 'gemini-2.0-flash';
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
        $this->localization = new LocalizationService($userLanguage);
        $this->store = new ConversationStore();
    }

    /**
     * Инициализация сервиса
     */
    public function initialize()
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is required');
        }

        error_log('Gemini service initialized with model: ' . $this->model . ', language: ' . $this->localization->getLanguage());
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
            throw new Exception('Gemini service not initialized');
        }

        try {
            error_log('Processing message with Gemini in language: ' . $this->localization->getLanguage());

            $chatId = $context['chat_id'] ?? null;
            $this->currentChatId = $chatId;

            // Получаем историю разговора
            $history = $this->store->getHistory($chatId);
            
            // Формируем промпт с системными инструкциями
            $systemPrompt = $this->getSystemPrompt();
            
            // Формируем контекст для Gemini
            $messages = [];
            
            // Добавляем системный промпт как первое сообщение
            $messages[] = [
                'role' => 'user',
                'parts' => [['text' => $systemPrompt]]
            ];
            $messages[] = [
                'role' => 'model',
                'parts' => [['text' => 'Понял, я буду помогать клиентам бани "Зима" с бронированием и консультациями.']]
            ];

            // Добавляем историю разговора (если есть)
            if (!empty($history) && is_array($history)) {
                foreach ($history as $msg) {
                    if (isset($msg['role']) && isset($msg['content'])) {
                        $role = $msg['role'] === 'user' ? 'user' : 'model';
                        $messages[] = [
                            'role' => $role,
                            'parts' => [['text' => $msg['content']]]
                        ];
                    }
                }
            }

            // Добавляем текущее сообщение пользователя
            $messages[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]]
            ];

            // Вызываем Gemini API
            // Формируем запрос в правильном формате для Gemini
            $requestData = [
                'contents' => $messages,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ]
            ];
            
            // Используем правильный формат для модели Gemini
            // Формат: models/gemini-1.5-flash-latest или models/gemini-pro
            $modelName = $this->model;
            if (!str_starts_with($modelName, 'models/')) {
                $modelName = 'models/' . $modelName;
            }
            
            $response = $this->makeRequest('/' . $modelName . ':generateContent', $requestData, 'POST');

            // Извлекаем ответ
            $aiResponse = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            if (empty($aiResponse)) {
                throw new Exception('Empty response from Gemini API');
            }

            error_log("Gemini response: " . $aiResponse);

            // Сохраняем в историю
            if ($chatId) {
                $this->store->append($chatId, 'user', $userMessage);
                $this->store->append($chatId, 'assistant', $aiResponse);
            }

            error_log('AI response generated in ' . $this->localization->getLanguage());
            return $this->formatResponse($aiResponse, $context);

        } catch (Exception $e) {
            error_log('Gemini processing error: ' . $e->getMessage());
            
            // Проверка на квоту и лимиты
            if (strpos($e->getMessage(), 'quota') !== false || 
                strpos($e->getMessage(), 'Quota exceeded') !== false ||
                strpos($e->getMessage(), '429') !== false) {
                return $this->localization->getLanguage() === 'ru' 
                    ? '⚠️ Извините, превышен лимит запросов к API. Попробуйте через несколько секунд.'
                    : '⚠️ Sorry, API quota exceeded. Please try again in a few seconds.';
            }
            
            if (strpos($e->getMessage(), 'rate_limit') !== false || 
                strpos($e->getMessage(), 'Please retry in') !== false) {
                // Извлекаем время ожидания из сообщения
                if (preg_match('/Please retry in ([\d.]+)s/', $e->getMessage(), $matches)) {
                    $waitTime = ceil((float)$matches[1]);
                    return $this->localization->getLanguage() === 'ru'
                        ? "⏳ Слишком много запросов. Подождите {$waitTime} секунд и попробуйте снова."
                        : "⏳ Too many requests. Please wait {$waitTime} seconds and try again.";
                }
                return $this->localization->getLanguage() === 'ru'
                    ? '⏳ Слишком много запросов. Подождите немного и попробуйте снова.'
                    : '⏳ Too many requests. Please wait a moment and try again.';
            }
            
            // Общая ошибка обработки
            return $this->localization->getLanguage() === 'ru'
                ? '❌ Извините, произошла ошибка при обработке вашего запроса. Попробуйте переформулировать вопрос.'
                : '❌ Sorry, an error occurred while processing your request. Please try rephrasing your question.';
        }
    }

    /**
     * Форматирование ответа ИИ
     */
    private function formatResponse($response, $context)
    {
        $formattedResponse = $response;
        
        // Добавляем эмодзи в зависимости от языка
        if ($this->localization->getLanguage() === 'ru') {
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
     * Выполнение HTTP запроса к Gemini API
     */
    private function makeRequest($endpoint, $data = [], $method = 'GET')
    {
        $url = $this->baseUrl . $endpoint . '?key=' . $this->apiKey;

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

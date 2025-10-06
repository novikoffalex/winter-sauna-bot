<?php
/**
 * Сервис для работы с OpenAI API
 */

class AIService
{
    private $apiKey;
    private $model;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = OPENAI_API_KEY;
        $this->model = OPENAI_MODEL;
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    /**
     * Инициализация сервиса
     */
    public function initialize()
    {
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key is required');
        }

        error_log('AI service initialized with model: ' . $this->model);
    }

    /**
     * Системный промпт для бота бани "Зима"
     */
    private function getSystemPrompt()
    {
        return "Ты - AI-ассистент бани \'Зима\' на Пхукете, Таиланд. Твоя задача - помогать клиентам с бронированием, консультациями и информацией об услугах.

Основные функции:
1. Бронирование услуг бани (русская баня, финская сауна, массаж)
2. Информация о ценах и пакетах услуг
3. Консультации по банным процедурам и их пользе
4. Информация о времени работы и местоположении
5. Рекомендации по подготовке к посещению бани

Информация о бане:
- Название: Зима
- Местоположение: 83/14 Moo 2, Wiset Rd, Rawai, Phuket 83100, Thailand
- Время работы: 10:00-22:00 ежедневно
- Услуги: Русская баня, финская сауна, гидромассаж, массаж, спа-процедуры, травяной пар, ледяная ванна
- Особенности: Зона отдыха с травяным чаем, прохладительные напитки
- Район: Rawai - популярный район на юге Пхукета, рядом с пляжами

Конкуренты в районе:
- Khao Rang Herbal Steam (травяной пар)
- Ice Bath Club (ледяные ванны)
- Rawai Massage & Sauna (массаж и сауна)

Стиль общения:
- Теплый и гостеприимный
- Профессиональный, но дружелюбный
- Используй эмодзи для создания уютной атмосферы (🧖‍♀️🏊‍♀️🌿💆‍♀️❄️)
- Предлагай конкретные услуги и время
- Всегда интересуйся количеством гостей и предпочтениями
- Упоминай преимущества расположения в Rawai (близко к пляжам)

Если клиент хочет забронировать, уточни: дату, время, количество человек, желаемые услуги.
Если вопрос неясен, вежливо уточни детали и предложи варианты.";
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
            error_log('Processing message with AI...');

            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt()
                ],
                [
                    'role' => 'user',
                    'content' => $this->formatUserMessage($userMessage, $context)
                ]
            ];

            $response = $this->makeRequest('/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'presence_penalty' => 0.1,
                'frequency_penalty' => 0.1
            ]);

            $aiResponse = $response['choices'][0]['message']['content'];
            
            error_log('AI response generated');
            return $this->formatResponse($aiResponse, $context);

        } catch (Exception $e) {
            error_log('AI processing error: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'insufficient_quota') !== false) {
                return 'Извините, у меня закончились лимиты API. Попробуйте позже.';
            }
            
            if (strpos($e->getMessage(), 'rate_limit_exceeded') !== false) {
                return 'Слишком много запросов. Подождите немного и попробуйте снова.';
            }
            
            return 'Извините, произошла ошибка при обработке вашего запроса. Попробуйте переформулировать вопрос.';
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
     * Форматирование ответа ИИ для бани
     */
    private function formatResponse($response, $context)
    {
        // Добавляем эмодзи для лучшего восприятия
        $formattedResponse = $response;
        
        // Если это бронирование
        if (stripos($response, 'забронировать') !== false || 
            stripos($response, 'запись') !== false ||
            stripos($response, 'время') !== false) {
            $formattedResponse = "📅 $formattedResponse";
        }
        
        // Если это услуги бани
        if (stripos($response, 'баня') !== false || 
            stripos($response, 'сауна') !== false ||
            stripos($response, 'массаж') !== false) {
            $formattedResponse = "🧖‍♀️ $formattedResponse";
        }
        
        // Если это цены
        if (stripos($response, 'цена') !== false || 
            stripos($response, 'стоимость') !== false ||
            stripos($response, 'руб') !== false) {
            $formattedResponse = "💰 $formattedResponse";
        }
        
        // Если это контакты или местоположение
        if (stripos($response, 'телефон') !== false || 
            stripos($response, 'адрес') !== false ||
            stripos($response, 'найти') !== false) {
            $formattedResponse = "📍 $formattedResponse";
        }
        
        // Если это советы или рекомендации
        if (stripos($response, 'совет') !== false || 
            stripos($response, 'рекоменд') !== false ||
            stripos($response, 'подготовиться') !== false) {
            $formattedResponse = "💡 $formattedResponse";
        }
        
        return $formattedResponse;
    }

    /**
     * Анализ намерений пользователя для бани
     */
    public function analyzeIntent($message)
    {
        $intents = [
            'booking' => ['забронировать', 'бронирование', 'записаться', 'запись', 'время', 'свободно'],
            'services' => ['услуги', 'баня', 'сауна', 'массаж', 'процедуры', 'что есть'],
            'prices' => ['цена', 'стоимость', 'сколько стоит', 'прайс', 'тариф'],
            'schedule' => ['работаете', 'время работы', 'открыто', 'закрыто', 'график'],
            'location' => ['где', 'адрес', 'как добраться', 'местоположение', 'найти'],
            'contact' => ['телефон', 'связаться', 'контакты', 'позвонить'],
            'preparation' => ['подготовиться', 'что взять', 'что нужно', 'советы'],
            'question' => ['что', 'как', 'где', 'когда', 'почему', 'зачем'],
            'help' => ['помощь', 'помоги', 'как', 'что делать']
        ];

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
     * Выполнение HTTP запроса к OpenAI API
     */
    private function makeRequest($endpoint, $data)
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'User-Agent: Lark-AI-Bot/1.0'
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

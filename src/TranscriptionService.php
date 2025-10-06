<?php
/**
 * Сервис для транскрибации голосовых сообщений
 * Использует OpenAI Whisper API
 */

class TranscriptionService
{
    private $apiKey;
    private $baseUrl;
    private $telegramService;

    public function __construct()
    {
        $this->apiKey = OPENAI_API_KEY;
        $this->baseUrl = 'https://api.openai.com/v1';
        $this->telegramService = new TelegramService();
    }

    /**
     * Получение файла голосового сообщения от Telegram
     */
    public function getVoiceFile($fileId)
    {
        try {
            error_log("Getting voice file with ID: " . $fileId);
            
            // Получаем информацию о файле
            $fileInfo = $this->telegramService->getFile($fileId);
            
            error_log("File info received: " . json_encode($fileInfo));
            
            if (!$fileInfo || !isset($fileInfo['file_path'])) {
                throw new Exception('File not found or invalid file info');
            }

            // Скачиваем файл
            $fileUrl = 'https://api.telegram.org/file/bot' . TELEGRAM_BOT_TOKEN . '/' . $fileInfo['file_path'];
            error_log("Downloading file from: " . $fileUrl);
            
            $audioData = file_get_contents($fileUrl);
            
            if (!$audioData) {
                throw new Exception('Failed to download audio file from: ' . $fileUrl);
            }

            error_log("Audio file downloaded successfully, size: " . strlen($audioData) . " bytes");
            return $audioData;

        } catch (Exception $e) {
            error_log("Error getting voice file: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Транскрибация аудио через OpenAI Whisper
     */
    public function transcribeAudio($audioData, $language = 'auto')
    {
        try {
            $url = $this->baseUrl . '/audio/transcriptions';
            
            // Создаем временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'voice_');
            file_put_contents($tempFile, $audioData);
            
            $postData = [
                'file' => new CURLFile($tempFile, 'audio/ogg', 'voice.ogg'),
                'model' => 'whisper-1',
                'language' => $language === 'auto' ? null : $language,
                'response_format' => 'json'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'User-Agent: Zima-Sauna-Bot/1.0'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Удаляем временный файл
            unlink($tempFile);

            if ($error) {
                throw new Exception("cURL error: $error");
            }

            if ($httpCode >= 400) {
                throw new Exception("HTTP error $httpCode: $response");
            }

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON response: $response");
            }

            return $decoded['text'] ?? '';

        } catch (Exception $e) {
            error_log("Transcription error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Анализ транскрипции для извлечения информации о бронировании
     */
    public function analyzeTranscription($text, $userLanguage = 'ru')
    {
        $intent = $this->detectBookingIntent($text, $userLanguage);
        
        if ($intent['is_booking']) {
            return [
                'is_booking' => true,
                'service' => $intent['service'],
                'date' => $intent['date'],
                'time' => $intent['time'],
                'guests' => $intent['guests'],
                'original_text' => $text,
                'confidence' => $intent['confidence']
            ];
        }

        return [
            'is_booking' => false,
            'original_text' => $text,
            'intent' => $intent['intent']
        ];
    }

    /**
     * Определение намерения бронирования из текста
     */
    private function detectBookingIntent($text, $userLanguage)
    {
        $textLower = mb_strtolower($text, 'UTF-8');
        
        // Ключевые слова для бронирования
        $bookingKeywords = [
            'ru' => ['записаться', 'забронировать', 'запись', 'хочу', 'нужно', 'можно', 'прийти', 'приехать'],
            'en' => ['book', 'reserve', 'appointment', 'want', 'need', 'can', 'come', 'visit']
        ];

        $serviceKeywords = [
            'ru' => [
                'массаж' => 'massage',
                'лечение' => 'treatment', 
                'спа' => 'spa',
                'баня' => 'sauna',
                'сауна' => 'sauna'
            ],
            'en' => [
                'massage' => 'massage',
                'treatment' => 'treatment',
                'spa' => 'spa',
                'sauna' => 'sauna'
            ]
        ];

        $timeKeywords = [
            'ru' => ['завтра', 'послезавтра', 'сегодня', 'утром', 'днем', 'вечером', 'ночью'],
            'en' => ['tomorrow', 'today', 'morning', 'afternoon', 'evening', 'night']
        ];

        $isBooking = false;
        $service = null;
        $date = null;
        $time = null;
        $guests = 1;
        $confidence = 0;

        // Проверяем ключевые слова бронирования
        $keywords = $bookingKeywords[$userLanguage] ?? $bookingKeywords['en'];
        foreach ($keywords as $keyword) {
            if (mb_strpos($textLower, $keyword) !== false) {
                $isBooking = true;
                $confidence += 0.3;
                break;
            }
        }

        // Определяем услугу
        $serviceKeywordsLang = $serviceKeywords[$userLanguage] ?? $serviceKeywords['en'];
        foreach ($serviceKeywordsLang as $keyword => $serviceKey) {
            if (mb_strpos($textLower, $keyword) !== false) {
                $service = $serviceKey;
                $confidence += 0.2;
                break;
            }
        }

        // Определяем время
        $timeKeywordsLang = $timeKeywords[$userLanguage] ?? $timeKeywords['en'];
        foreach ($timeKeywordsLang as $keyword) {
            if (mb_strpos($textLower, $keyword) !== false) {
                $date = $this->extractDate($text, $userLanguage);
                $time = $this->extractTime($text, $userLanguage);
                $confidence += 0.2;
                break;
            }
        }

        // Определяем количество гостей
        $guests = $this->extractGuests($text, $userLanguage);
        if ($guests > 1) {
            $confidence += 0.1;
        }

        return [
            'is_booking' => $isBooking && $confidence > 0.3,
            'service' => $service,
            'date' => $date,
            'time' => $time,
            'guests' => $guests,
            'confidence' => min($confidence, 1.0),
            'intent' => $isBooking ? 'booking' : 'general'
        ];
    }

    /**
     * Извлечение даты из текста
     */
    private function extractDate($text, $userLanguage)
    {
        $textLower = mb_strtolower($text, 'UTF-8');
        
        if (mb_strpos($textLower, 'завтра') !== false || strpos($textLower, 'tomorrow') !== false) {
            return date('Y-m-d', strtotime('+1 day'));
        }
        
        if (mb_strpos($textLower, 'послезавтра') !== false || strpos($textLower, 'day after tomorrow') !== false) {
            return date('Y-m-d', strtotime('+2 days'));
        }
        
        if (mb_strpos($textLower, 'сегодня') !== false || strpos($textLower, 'today') !== false) {
            return date('Y-m-d');
        }

        // Поиск конкретных дат
        if (preg_match('/(\d{1,2})[.\-\/](\d{1,2})[.\-\/]?(\d{2,4})?/', $text, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = isset($matches[3]) ? $matches[3] : date('Y');
            
            if (strlen($year) == 2) {
                $year = '20' . $year;
            }
            
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return null;
    }

    /**
     * Извлечение времени из текста
     */
    private function extractTime($text, $userLanguage)
    {
        // Поиск времени в формате HH:MM
        if (preg_match('/(\d{1,2}):(\d{2})/', $text, $matches)) {
            return $matches[1] . ':' . $matches[2];
        }

        // Поиск времени в формате HH MM
        if (preg_match('/(\d{1,2})\s+(\d{2})/', $text, $matches)) {
            return $matches[1] . ':' . $matches[2];
        }

        // Поиск времени словами
        $textLower = mb_strtolower($text, 'UTF-8');
        
        $timeMap = [
            'ru' => [
                'утром' => '09:00',
                'днем' => '14:00',
                'вечером' => '19:00',
                'ночью' => '22:00'
            ],
            'en' => [
                'morning' => '09:00',
                'afternoon' => '14:00',
                'evening' => '19:00',
                'night' => '22:00'
            ]
        ];

        $timeMapLang = $timeMap[$userLanguage] ?? $timeMap['en'];
        foreach ($timeMapLang as $keyword => $time) {
            if (mb_strpos($textLower, $keyword) !== false) {
                return $time;
            }
        }

        return null;
    }

    /**
     * Извлечение количества гостей из текста
     */
    private function extractGuests($text, $userLanguage)
    {
        // Поиск чисел в тексте
        if (preg_match('/(\d+)\s*(человек|чел|гост|человека|people|person|guest)/i', $text, $matches)) {
            return (int)$matches[1];
        }

        // Поиск просто чисел
        if (preg_match('/\b(\d+)\b/', $text, $matches)) {
            $number = (int)$matches[1];
            if ($number >= 1 && $number <= 10) {
                return $number;
            }
        }

        return 1;
    }

    /**
     * Форматирование результата бронирования
     */
    public function formatBookingResult($result, $userLanguage = 'ru')
    {
        if (!$result['is_booking']) {
            return null;
        }

        $service = $result['service'] ?? 'услуга';
        $date = $result['date'] ?? 'дата не указана';
        $time = $result['time'] ?? 'время не указано';
        $guests = $result['guests'] ?? 1;

        if ($userLanguage === 'ru') {
            return "📅 **Бронирование по голосовому сообщению:**\n\n" .
                   "🏊‍♀️ **Услуга:** {$service}\n" .
                   "📅 **Дата:** {$date}\n" .
                   "⏰ **Время:** {$time}\n" .
                   "👥 **Гостей:** {$guests}\n\n" .
                   "✅ Подтвердите бронирование или внесите изменения:";
        } else {
            return "📅 **Voice Booking:**\n\n" .
                   "🏊‍♀️ **Service:** {$service}\n" .
                   "📅 **Date:** {$date}\n" .
                   "⏰ **Time:** {$time}\n" .
                   "👥 **Guests:** {$guests}\n\n" .
                   "✅ Please confirm booking or make changes:";
        }
    }
}

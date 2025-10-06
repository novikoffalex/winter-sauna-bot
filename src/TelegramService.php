<?php
/**
 * Сервис для работы с Telegram Bot API
 */

class TelegramService
{
    private $botToken;
    private $baseUrl;

    public function __construct()
    {
        $this->botToken = TELEGRAM_BOT_TOKEN;
        $this->baseUrl = 'https://api.telegram.org/bot' . $this->botToken;
    }

    /**
     * Инициализация сервиса
     */
    public function initialize()
    {
        if (empty($this->botToken)) {
            throw new Exception('Telegram Bot Token is required');
        }
        
        error_log('Telegram service initialized');
    }

    /**
     * Отправка сообщения в чат
     */
    public function sendMessage($chatId, $text, $replyToMessageId = null, $parseMode = 'HTML')
    {
        try {
            $messageData = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode
            ];

            // Если это ответ на сообщение, добавляем reply
            if ($replyToMessageId) {
                $messageData['reply_to_message_id'] = $replyToMessageId;
            }

            $response = $this->makeRequest('POST', '/sendMessage', $messageData);

            error_log('Message sent successfully to chat: ' . $chatId);
            return $response;
        } catch (Exception $e) {
            error_log('Failed to send message: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Отправка сообщения с клавиатурой
     */
    public function sendMessageWithKeyboard($chatId, $text, $keyboard = null, $replyToMessageId = null)
    {
        try {
            $messageData = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];

            if ($replyToMessageId) {
                $messageData['reply_to_message_id'] = $replyToMessageId;
            }

            if ($keyboard) {
                // Проверяем, что это inline клавиатура
                if (isset($keyboard['inline_keyboard'])) {
                    $messageData['reply_markup'] = json_encode($keyboard);
                } else {
                    // Если передали просто массив кнопок, оборачиваем в inline_keyboard
                    $messageData['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
                }
            }

            $response = $this->makeRequest('POST', '/sendMessage', $messageData);

            error_log('Message with keyboard sent successfully to chat: ' . $chatId);
            return $response;
        } catch (Exception $e) {
            error_log('Failed to send message with keyboard: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Создание inline клавиатуры
     */
    public function createInlineKeyboard($buttons)
    {
        return [
            'inline_keyboard' => $buttons
        ];
    }

    /**
     * Создание обычной клавиатуры
     */
    public function createReplyKeyboard($buttons, $resizeKeyboard = true, $oneTimeKeyboard = false)
    {
        return [
            'keyboard' => $buttons,
            'resize_keyboard' => $resizeKeyboard,
            'one_time_keyboard' => $oneTimeKeyboard
        ];
    }

    /**
     * Удаление клавиатуры
     */
    public function removeKeyboard($chatId, $text = 'Клавиатура скрыта')
    {
        try {
            $messageData = [
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => json_encode(['remove_keyboard' => true])
            ];

            $response = $this->makeRequest('POST', '/sendMessage', $messageData);
            return $response;
        } catch (Exception $e) {
            error_log('Failed to remove keyboard: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ответ на callback query (для inline кнопок)
     */
    public function answerCallbackQuery($callbackQueryId, $text = '', $showAlert = false)
    {
        try {
            $messageData = [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => $showAlert
            ];

            $response = $this->makeRequest('POST', '/answerCallbackQuery', $messageData);
            return $response;
        } catch (Exception $e) {
            error_log('Failed to answer callback query: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Редактирование сообщения
     */
    public function editMessageText($chatId, $messageId, $text, $inlineKeyboard = null)
    {
        try {
            $messageData = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];

            if ($inlineKeyboard) {
                $messageData['reply_markup'] = json_encode($inlineKeyboard);
            }

            $response = $this->makeRequest('POST', '/editMessageText', $messageData);
            return $response;
        } catch (Exception $e) {
            error_log('Failed to edit message: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получение информации о боте
     */
    public function getMe()
    {
        try {
            $response = $this->makeRequest('GET', '/getMe');
            return $response;
        } catch (Exception $e) {
            error_log('Failed to get bot info: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Установка webhook
     */
    public function setWebhook($url)
    {
        try {
            $messageData = [
                'url' => $url
            ];

            $response = $this->makeRequest('POST', '/setWebhook', $messageData);
            return $response;
        } catch (Exception $e) {
            error_log('Failed to set webhook: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получение информации о webhook
     */
    public function getWebhookInfo()
    {
        try {
            $response = $this->makeRequest('GET', '/getWebhookInfo');
            return $response;
        } catch (Exception $e) {
            error_log('Failed to get webhook info: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Отправка фото с подписью
     */
    public function sendPhoto($chatId, $photo, $caption = null, $replyToMessageId = null)
    {
        try {
            // Если это локальный файл, отправляем как multipart/form-data
            if (file_exists($photo)) {
                $messageData = [
                    'chat_id' => $chatId,
                    'photo' => new CURLFile($photo, 'image/png', basename($photo))
                ];

                if ($caption) {
                    $messageData['caption'] = $caption;
                    $messageData['parse_mode'] = 'HTML';
                }

                if ($replyToMessageId) {
                    $messageData['reply_to_message_id'] = $replyToMessageId;
                }

                $response = $this->makeRequest('POST', '/sendPhoto', $messageData, true);
                return $response;
            } else {
                // Если это URL, отправляем как обычно
                $messageData = [
                    'chat_id' => $chatId,
                    'photo' => $photo
                ];

                if ($caption) {
                    $messageData['caption'] = $caption;
                    $messageData['parse_mode'] = 'HTML';
                }

                if ($replyToMessageId) {
                    $messageData['reply_to_message_id'] = $replyToMessageId;
                }

                $response = $this->makeRequest('POST', '/sendPhoto', $messageData);
                return $response;
            }
        } catch (Exception $e) {
            error_log('Failed to send photo: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получение информации о файле
     */
    public function getFile($fileId)
    {
        try {
            $url = $this->baseUrl . '/getFile?file_id=' . urlencode($fileId);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Zima-Sauna-Bot/1.0'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

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

            if (!$decoded['ok']) {
                throw new Exception("Telegram API error: " . ($decoded['description'] ?? 'Unknown error'));
            }

            return $decoded['result'];

        } catch (Exception $e) {
            error_log('Failed to get file: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получение обновлений (для получения chat_id)
     */
    public function getUpdates($offset = null, $limit = 10)
    {
        try {
            $url = $this->baseUrl . '/getUpdates';
            $params = [];
            if ($offset !== null) {
                $params['offset'] = $offset;
            }
            $params['limit'] = $limit;
            
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Zima-Sauna-Bot/1.0'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("cURL error: $error");
            }

            if ($httpCode >= 400) {
                throw new Exception("HTTP error $httpCode: $response");
            }

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON response");
            }

            return $decoded;
        } catch (Exception $e) {
            error_log('Failed to get updates: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Отправка статуса "печатает"
     */
    public function sendTypingAction($chatId)
    {
        try {
            $data = [
                'chat_id' => $chatId,
                'action' => 'typing'
            ];
            
            $this->makeRequest('POST', '/sendChatAction', $data);
        } catch (Exception $e) {
            error_log('Failed to send typing action: ' . $e->getMessage());
        }
    }

    /**
     * Выполнение HTTP запроса
     */
    private function makeRequest($method, $endpoint, $data = [], $isMultipart = false)
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($isMultipart) {
            if ($method === 'POST' && !empty($data)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: Winter-Sauna-Bot/1.0'
            ]);

            if ($method === 'POST' && !empty($data)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

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

        return $decoded;
    }
}

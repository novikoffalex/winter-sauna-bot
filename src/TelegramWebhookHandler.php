<?php
/**
 * Обработчик webhook событий от Telegram
 */

require_once 'TelegramService.php';
require_once 'AIService.php';

class TelegramWebhookHandler
{
    private $telegramService;
    private $aiService;

    public function __construct()
    {
        $this->telegramService = new TelegramService();
        $this->aiService = new AIService();
        
        $this->telegramService->initialize();
        $this->aiService->initialize();
    }

    /**
     * Обработка входящего webhook
     */
    public function handleWebhook()
    {
        try {
            $input = file_get_contents('php://input');
            $update = json_decode($input, true);

            if (!$update) {
                error_log('Invalid JSON received');
                http_response_code(400);
                return;
            }

            error_log('Received update: ' . $input);

            // Обработка разных типов обновлений
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }

            http_response_code(200);
            echo 'OK';

        } catch (Exception $e) {
            error_log('Webhook handling error: ' . $e->getMessage());
            http_response_code(500);
        }
    }

    /**
     * Обработка обычного сообщения
     */
    private function handleMessage($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $messageId = $message['message_id'];
        $from = $message['from'];

        error_log("Processing message from chat {$chatId}: {$text}");

        // Обработка команд
        if (strpos($text, '/') === 0) {
            $this->handleCommand($chatId, $text, $messageId);
            return;
        }

        // Обработка обычного сообщения через AI
        $this->handleAIMessage($chatId, $text, $messageId, $from);
    }

    /**
     * Обработка команд
     */
    private function handleCommand($chatId, $command, $messageId)
    {
        switch ($command) {
            case '/start':
                $this->sendWelcomeMessage($chatId, $messageId);
                break;
                
            case '/services':
                $this->sendServicesInfo($chatId, $messageId);
                break;
                
            case '/booking':
                $this->startBookingProcess($chatId, $messageId);
                break;
                
            case '/prices':
                $this->sendPricesInfo($chatId, $messageId);
                break;
                
            case '/contact':
                $this->sendContactInfo($chatId, $messageId);
                break;
                
            case '/help':
                $this->sendHelpMessage($chatId, $messageId);
                break;
                
            default:
                $this->telegramService->sendMessage(
                    $chatId, 
                    "Неизвестная команда. Используйте /help для списка доступных команд.",
                    $messageId
                );
        }
    }

    /**
     * Обработка сообщения через AI
     */
    private function handleAIMessage($chatId, $text, $messageId, $from)
    {
        try {
            $context = [
                'senderId' => $from['id'],
                'firstName' => $from['first_name'] ?? '',
                'username' => $from['username'] ?? ''
            ];

            $aiResponse = $this->aiService->processMessage($text, $context);
            
            $this->telegramService->sendMessage($chatId, $aiResponse, $messageId);

        } catch (Exception $e) {
            error_log('AI processing error: ' . $e->getMessage());
            $this->telegramService->sendMessage(
                $chatId, 
                "Извините, произошла ошибка при обработке вашего сообщения. Попробуйте позже.",
                $messageId
            );
        }
    }

    /**
     * Обработка callback query (нажатие на inline кнопки)
     */
    private function handleCallbackQuery($callbackQuery)
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'];
        $callbackQueryId = $callbackQuery['id'];

        // Отвечаем на callback query
        $this->telegramService->answerCallbackQuery($callbackQueryId);

        // Обрабатываем данные кнопки
        switch ($data) {
            case 'book_russian_sauna':
                $this->sendBookingForm($chatId, 'Русская баня');
                break;
            case 'book_finnish_sauna':
                $this->sendBookingForm($chatId, 'Финская сауна');
                break;
            case 'book_massage':
                $this->sendBookingForm($chatId, 'Массаж');
                break;
            case 'show_prices':
                $this->sendPricesInfo($chatId);
                break;
            case 'show_services':
                $this->sendServicesInfo($chatId);
                break;
        }
    }

    /**
     * Отправка приветственного сообщения
     */
    private function sendWelcomeMessage($chatId, $messageId = null)
    {
        $message = "🧖‍♀️ <b>Добро пожаловать в баню 'Зима'!</b>\n\n";
        $message .= "Я помогу вам с бронированием и ответами на вопросы о наших услугах.\n\n";
        $message .= "Выберите, что вас интересует:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🏊‍♀️ Наши услуги', 'callback_data' => 'show_services'],
                    ['text' => '💰 Цены', 'callback_data' => 'show_prices']
                ],
                [
                    ['text' => '📅 Забронировать', 'callback_data' => 'start_booking'],
                    ['text' => '📍 Контакты', 'callback_data' => 'show_contacts']
                ]
            ]
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $message, $keyboard, $messageId);
    }

    /**
     * Отправка информации об услугах
     */
    private function sendServicesInfo($chatId, $messageId = null)
    {
        $message = "🏊‍♀️ <b>Наши услуги:</b>\n\n";
        $message .= "🧖‍♀️ <b>Русская баня</b>\n";
        $message .= "Классическая баня с паром и березовыми вениками\n\n";
        $message .= "🔥 <b>Финская сауна</b>\n";
        $message .= "Сухая сауна с высокой температурой\n\n";
        $message .= "💆‍♀️ <b>Массаж и спа-процедуры</b>\n";
        $message .= "Расслабляющий массаж и уходовые процедуры\n\n";
        $message .= "🌿 <b>Зона отдыха</b>\n";
        $message .= "Комфортная зона с травяным чаем и прохладительными напитками";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📅 Забронировать русскую баню', 'callback_data' => 'book_russian_sauna'],
                    ['text' => '📅 Забронировать финскую сауну', 'callback_data' => 'book_finnish_sauna']
                ],
                [
                    ['text' => '📅 Забронировать массаж', 'callback_data' => 'book_massage']
                ]
            ]
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $message, $keyboard, $messageId);
    }

    /**
     * Отправка информации о ценах
     */
    private function sendPricesInfo($chatId, $messageId = null)
    {
        $message = "💰 <b>Наши цены:</b>\n\n";
        $message .= "🧖‍♀️ <b>Русская баня:</b>\n";
        $message .= "• 1-2 человека: 1500 бат/час\n";
        $message .= "• 3-4 человека: 2000 бат/час\n";
        $message .= "• 5+ человек: 2500 бат/час\n\n";
        $message .= "🔥 <b>Финская сауна:</b>\n";
        $message .= "• 1-2 человека: 1200 бат/час\n";
        $message .= "• 3-4 человека: 1600 бат/час\n\n";
        $message .= "💆‍♀️ <b>Массаж:</b>\n";
        $message .= "• Тайский массаж: 800 бат/час\n";
        $message .= "• Расслабляющий: 1200 бат/час\n";
        $message .= "• Спа-процедуры: от 1500 бат\n\n";
        $message .= "📅 <i>Минимальное время бронирования: 2 часа</i>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📅 Забронировать сейчас', 'callback_data' => 'start_booking']
                ]
            ]
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $message, $keyboard, $messageId);
    }

    /**
     * Отправка контактной информации
     */
    private function sendContactInfo($chatId, $messageId = null)
    {
        $message = "📍 <b>Контакты бани 'Зима':</b>\n\n";
        $message .= "🏠 <b>Адрес:</b> Пхукет, Таиланд\n";
        $message .= "📞 <b>Телефон:</b> +66-XX-XXX-XXXX\n";
        $message .= "🕒 <b>Время работы:</b> 10:00-22:00 ежедневно\n\n";
        $message .= "🚗 <b>Как добраться:</b>\n";
        $message .= "Мы находимся в центре Пхукета, рядом с основными отелями и пляжами. Более подробную информацию о местоположении я могу предоставить при бронировании.";

        $this->telegramService->sendMessage($chatId, $message, $messageId);
    }

    /**
     * Отправка справки
     */
    private function sendHelpMessage($chatId, $messageId = null)
    {
        $message = "❓ <b>Помощь по использованию бота:</b>\n\n";
        $message .= "<b>Доступные команды:</b>\n";
        $message .= "/start - Начать работу с ботом\n";
        $message .= "/services - Посмотреть услуги\n";
        $message .= "/booking - Забронировать время\n";
        $message .= "/prices - Узнать цены\n";
        $message .= "/contact - Контактная информация\n";
        $message .= "/help - Эта справка\n\n";
        $message .= "<b>Также вы можете:</b>\n";
        $message .= "• Просто написать вопрос - я отвечу через AI\n";
        $message .= "• Использовать кнопки для быстрого доступа\n";
        $message .= "• Спросить о подготовке к посещению бани\n\n";
        $message .= "Если у вас есть вопросы, просто напишите мне!";

        $this->telegramService->sendMessage($chatId, $message, $messageId);
    }

    /**
     * Начало процесса бронирования
     */
    private function startBookingProcess($chatId, $messageId = null)
    {
        $message = "📅 <b>Бронирование услуг</b>\n\n";
        $message .= "Выберите услугу, которую хотите забронировать:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🧖‍♀️ Русская баня', 'callback_data' => 'book_russian_sauna'],
                    ['text' => '🔥 Финская сауна', 'callback_data' => 'book_finnish_sauna']
                ],
                [
                    ['text' => '💆‍♀️ Массаж', 'callback_data' => 'book_massage']
                ]
            ]
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $message, $keyboard, $messageId);
    }

    /**
     * Отправка формы бронирования
     */
    private function sendBookingForm($chatId, $service)
    {
        $message = "📅 <b>Бронирование: {$service}</b>\n\n";
        $message .= "Для завершения бронирования мне нужна следующая информация:\n\n";
        $message .= "1. <b>Дата</b> (например: завтра, 15 января)\n";
        $message .= "2. <b>Время</b> (например: 19:00)\n";
        $message .= "3. <b>Количество человек</b>\n";
        $message .= "4. <b>Дополнительные услуги</b> (если нужны)\n\n";
        $message .= "Просто напишите эту информацию в следующем сообщении, и я помогу вам с бронированием!";

        $this->telegramService->sendMessage($chatId, $message);
    }
}

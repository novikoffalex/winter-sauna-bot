<?php
/**
 * Локализованный обработчик webhook событий от Telegram
 */

require_once 'TelegramService.php';
require_once 'AIServiceLocalized.php';
require_once 'LocalizationService.php';

class TelegramWebhookHandlerLocalized
{
    private $telegramService;
    private $aiService;
    private $localization;

    public function __construct()
    {
        $this->telegramService = new TelegramService();
        $this->telegramService->initialize();
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
            // Не возвращаем 500 для callback query ошибок
            if (strpos($e->getMessage(), 'query is too old') !== false) {
                http_response_code(200);
                echo 'OK';
            } else {
                http_response_code(500);
            }
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

        // Определяем язык пользователя
        $userLanguage = $this->detectUserLanguage($from);
        
        // Инициализируем локализацию и AI сервис
        $this->localization = new LocalizationService($userLanguage);
        $this->aiService = new AIServiceLocalized($userLanguage);
        $this->aiService->initialize();

        error_log("Processing message from chat {$chatId} in language {$userLanguage}: {$text}");
        error_log("Localization language: " . $this->localization->getLanguage());

        // Обработка команд
        if (strpos($text, '/') === 0) {
            $this->handleCommand($chatId, $text, $messageId);
            return;
        }

        // Обработка обычного сообщения через AI
        $this->handleAIMessage($chatId, $text, $messageId, $from);
    }

    /**
     * Определение языка пользователя
     */
    private function detectUserLanguage($from)
    {
        // Логируем информацию о пользователе для отладки
        error_log("User data for language detection: " . json_encode($from));
        
        // Проверяем language_code в настройках пользователя
        if (isset($from['language_code'])) {
            $langCode = $from['language_code'];
            error_log("Detected language_code: " . $langCode);
            
            // Если русский язык - используем русский
            if (strpos($langCode, 'ru') === 0) {
                error_log("Language set to: ru (Russian interface detected)");
                return 'ru';
            }
            
            // Если английский язык - используем английский
            if (strpos($langCode, 'en') === 0) {
                error_log("Language set to: en (English interface detected)");
                return 'en';
            }
        }
        
        // По умолчанию английский (для международных туристов на Пхукете)
        error_log("Language set to default: en (for international tourists)");
        return 'en';
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
                
            case '/menu':
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
                    $this->localization->t('unknown_command') . ' ' . $this->localization->t('use_help'),
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
                'username' => $from['username'] ?? '',
                'language' => $this->localization->getLanguage()
            ];

            $aiResponse = $this->aiService->processMessage($text, $context);
            
            $this->telegramService->sendMessage($chatId, $aiResponse, $messageId);

        } catch (Exception $e) {
            error_log('AI processing error: ' . $e->getMessage());
            $this->telegramService->sendMessage(
                $chatId, 
                $this->localization->t('processing_error'),
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
        $from = $callbackQuery['from'];

        // Инициализируем локализацию для callback query
        $userLanguage = $this->detectUserLanguage($from);
        $this->localization = new LocalizationService($userLanguage);
        $this->aiService = new AIServiceLocalized($userLanguage);
        $this->aiService->initialize();

        error_log("Processing callback query from chat {$chatId} in language {$userLanguage}: {$data}");

        // Отвечаем на callback query (игнорируем ошибки для старых запросов)
        try {
            $this->telegramService->answerCallbackQuery($callbackQueryId);
        } catch (Exception $e) {
            error_log("Callback query answer failed (probably too old): " . $e->getMessage());
            // Продолжаем выполнение, даже если не удалось ответить на callback
        }

        // Обрабатываем данные кнопки
        switch ($data) {
            case 'book_massage':
                $this->sendBookingForm($chatId, $this->localization->t('massage'));
                break;
            case 'book_treatment':
                $this->sendBookingForm($chatId, $this->localization->t('treatment'));
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
        $message = $this->localization->getWelcomeMessage();

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🏊‍♀️ ' . $this->localization->t('view_services'), 'callback_data' => 'show_services'],
                    ['text' => '💰 ' . $this->localization->t('view_prices'), 'callback_data' => 'show_prices']
                ],
                [
                    ['text' => '📅 ' . $this->localization->t('book_now'), 'callback_data' => 'start_booking'],
                    ['text' => '📍 ' . $this->localization->t('contact_info'), 'callback_data' => 'show_contacts']
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
        $message = $this->localization->getServicesMessage();

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💆‍♀️ ' . $this->localization->t('massage'), 'callback_data' => 'book_massage'],
                    ['text' => '🌿 ' . $this->localization->t('treatment'), 'callback_data' => 'book_treatment']
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
        $message = $this->localization->getPricesMessage();

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📅 ' . $this->localization->t('book_now'), 'callback_data' => 'start_booking']
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
        $data = $this->localization->getZimaData();
        
        $message = "📍 **" . $this->localization->t('contact_info') . " Zima SPA Wellness**\n\n";
        $message .= "🏠 **" . $this->localization->t('location') . ":** " . $data['location'] . "\n";
        $message .= "📞 **" . $this->localization->t('phone') . ":** " . $data['phone'] . "\n";
        $message .= "📧 **" . $this->localization->t('email') . ":** " . $data['email'] . "\n";
        $message .= "🕒 **" . $this->localization->t('working_hours') . ":** " . $data['working_hours'] . "\n\n";
        $message .= "🚗 **" . $this->localization->t('how_to_get') . ":**\n";
        $message .= $this->localization->t('location_description');

        $this->telegramService->sendMessage($chatId, $message, $messageId);
    }

    /**
     * Отправка справки
     */
    private function sendHelpMessage($chatId, $messageId = null)
    {
        $message = "❓ **" . $this->localization->t('help_text') . ":**\n\n";
        $message .= "**" . $this->localization->t('available_commands') . ":**\n";
        $message .= "/start - " . $this->localization->t('start_bot') . "\n";
        $message .= "/menu - " . $this->localization->t('main_menu') . "\n";
        $message .= "/services - " . $this->localization->t('view_services') . "\n";
        $message .= "/booking - " . $this->localization->t('book_now') . "\n";
        $message .= "/prices - " . $this->localization->t('view_prices') . "\n";
        $message .= "/contact - " . $this->localization->t('contact_info') . "\n";
        $message .= "/help - " . $this->localization->t('help_text') . "\n\n";
        $message .= "**" . $this->localization->t('you_can_also') . ":**\n";
        $message .= "• " . $this->localization->t('write_question') . "\n";
        $message .= "• " . $this->localization->t('use_buttons') . "\n";
        $message .= "• " . $this->localization->t('ask_about_preparation') . "\n\n";
        $message .= $this->localization->t('if_questions_ask');

        $this->telegramService->sendMessage($chatId, $message, $messageId);
    }

    /**
     * Начало процесса бронирования
     */
    private function startBookingProcess($chatId, $messageId = null)
    {
        $message = "📅 **" . $this->localization->t('booking') . "**\n\n";
        $message .= $this->localization->t('choose_service') . ":";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💆‍♀️ ' . $this->localization->t('massage'), 'callback_data' => 'book_massage'],
                    ['text' => '🌿 ' . $this->localization->t('treatment'), 'callback_data' => 'book_treatment']
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
        $message = "📅 **" . $this->localization->t('booking') . ": {$service}**\n\n";
        $message .= $this->localization->t('booking_instructions') . ":\n\n";
        $message .= "1. **" . $this->localization->t('date') . "** (" . $this->localization->t('example') . ": " . $this->localization->t('tomorrow') . ", " . $this->localization->t('january_15') . ")\n";
        $message .= "2. **" . $this->localization->t('time') . "** (" . $this->localization->t('example') . ": 19:00)\n";
        $message .= "3. **" . $this->localization->t('guests') . "**\n";
        $message .= "4. **" . $this->localization->t('additional_services') . "** (" . $this->localization->t('if_needed') . ")\n\n";
        $message .= $this->localization->t('write_info_next_message');

        $this->telegramService->sendMessage($chatId, $message);
    }
}

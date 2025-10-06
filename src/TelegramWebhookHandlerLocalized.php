<?php
/**
 * Локализованный обработчик webhook событий от Telegram
 */

require_once 'TelegramService.php';
require_once 'AIServiceLocalized.php';
require_once 'LocalizationService.php';
require_once 'TranscriptionService.php';

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

        // Обработка голосовых сообщений
        if (isset($message['voice'])) {
            $this->handleVoiceMessage($chatId, $message['voice'], $messageId, $from);
            return;
        }

        // Обработка команд
        if (strpos($text, '/') === 0) {
            $this->handleCommand($chatId, $text, $messageId);
            return;
        }

        // Обработка специальных команд для платежей
        if (strpos($text, '/start payment_success') === 0) {
            $this->handlePaymentSuccess($chatId, $messageId);
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
            // Показываем статус "печатает"
            $this->telegramService->sendTypingAction($chatId);
            
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

        // Сначала отвечаем на callback query (быстро!)
        try {
            $this->telegramService->answerCallbackQuery($callbackQueryId);
        } catch (Exception $e) {
            error_log("Callback query answer failed (probably too old): " . $e->getMessage());
            // Продолжаем выполнение, даже если не удалось ответить на callback
        }

        // Инициализируем локализацию для callback query
        $userLanguage = $this->detectUserLanguage($from);
        $this->localization = new LocalizationService($userLanguage);
        $this->aiService = new AIServiceLocalized($userLanguage);
        $this->aiService->initialize();

        error_log("Processing callback query from chat {$chatId} in language {$userLanguage}: {$data}");

        // Удалено: обработка тестовой отметки об оплате

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
            case 'start_booking':
                $this->startBookingProcess($chatId);
                break;
            case 'show_contacts':
                $this->sendContactInfo($chatId);
                break;
            case 'crypto_payment_massage':
                $this->handleCryptoPayment($chatId, 'massage');
                break;
            case 'crypto_payment_treatment':
                $this->handleCryptoPayment($chatId, 'treatment');
                break;
            case 'crypto_payment_spa':
                $this->handleCryptoPayment($chatId, 'spa');
                break;
                case 'crypto_payment_wellness':
                    $this->handleCryptoPayment($chatId, 'wellness');
                    break;
                // Удалено: тестовая оплата
                case 'voice_booking_info':
                    $this->sendVoiceBookingInfo($chatId);
                    break;
                case 'confirm_voice_booking':
                    $this->confirmVoiceBooking($chatId);
                    break;
                case 'edit_voice_booking':
                    $this->editVoiceBooking($chatId);
                    break;
                case 'cancel_voice_booking':
                    $this->cancelVoiceBooking($chatId);
                    break;
                default:
                    if (strpos($data, 'sel_') === 0) {
                        $this->handleSelectService($chatId, $data);
                        break;
                    }
                    $this->telegramService->sendMessage($chatId, $this->localization->t('unknown_action') . ": " . $data);
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
                    ['text' => '🎤 ' . $this->localization->t('voice_booking'), 'callback_data' => 'voice_booking_info']
                ],
                // Удалено: кнопка тестовой оплаты
                [
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
        $data = $this->localization->getZimaData();
        $services = $data['services'] ?? [];

        $message = "🏊‍♀️ " . $this->localization->t('services') . ":\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($services as $index => $service) {
            $name = $service['name_' . $this->localization->getLanguage()] ?? $service['name_ru'];
            $priceThb = $service['price'];
            $callback = 'sel_' . $index . '_' . (int)$priceThb; // Короткий callback_data
            $buttonText = $name . ' — ' . $this->localization->formatPrice($priceThb);
            $keyboard['inline_keyboard'][] = [ ['text' => $buttonText, 'callback_data' => $callback] ];
        }

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

        // Добавляем кнопку для криптоплатежки
        // Определяем тип услуги по переданному названию
        $serviceType = 'massage'; // по умолчанию
        if (strpos($service, $this->localization->t('treatment')) !== false) {
            $serviceType = 'treatment';
        } elseif (strpos($service, $this->localization->t('spa')) !== false) {
            $serviceType = 'spa';
        } elseif (strpos($service, $this->localization->t('wellness')) !== false) {
            $serviceType = 'wellness';
        }
        
        $keyboard = [
            [
                ['text' => '💳 ' . $this->localization->t('pay_with_crypto'), 'callback_data' => 'crypto_payment_' . $serviceType]
            ]
        ];
        
        $this->telegramService->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Обработка успешной оплаты
     */
    private function handlePaymentSuccess($chatId, $messageId = null)
    {
        $message = "🎉 **" . $this->localization->t('payment_success') . "!**\n\n";
        $message .= $this->localization->t('ticket_created') . "! " . $this->localization->t('if_questions_contact');
        
        $this->telegramService->sendMessage($chatId, $message, $messageId);
    }

    /**
     * Обработка выбора услуги: select_service_{name}_{priceThb}
     */
    private function handleSelectService($chatId, $data)
    {
        // Разбираем callback_data (sel_0_750)
        $parts = explode('_', $data);
        $serviceIndex = (int)$parts[1]; // Индекс услуги
        $priceThb = (int)$parts[2]; // Цена

        // Загружаем данные услуг
        $dataFile = 'zima_data.json';
        if (!file_exists($dataFile)) {
            $this->telegramService->sendMessage($chatId, "❌ " . $this->localization->t('data_not_found'));
            return;
        }

        $jsonData = json_decode(file_get_contents($dataFile), true);
        $services = $jsonData['services'] ?? [];

        if (!isset($services[$serviceIndex])) {
            $this->telegramService->sendMessage($chatId, "❌ " . $this->localization->t('service_not_found'));
            return;
        }

        $service = $services[$serviceIndex];
        $serviceName = $service['name_' . $this->localization->getLanguage()] ?? $service['name_ru'];

        require_once 'CurrencyService.php';
        require_once 'PaymentHandler.php';

        // Конвертируем THB -> USD
        $fx = new CurrencyService();
        $amountUsd = $fx->convertThbToUsd($priceThb);

        // Создаем инвойс
        $paymentHandler = new PaymentHandler($this->localization->getLanguage());
        $result = $paymentHandler->createPaymentInvoice($chatId, $serviceName, $amountUsd, 'USDTTRC20');

        if (!empty($result['success'])) {
            $text = "💳 **" . $this->localization->t('crypto_payment') . "**\n\n";
            $text .= "🏊‍♀️ **" . $this->localization->t('service') . ":** {$serviceName}\n";
            $text .= "💰 **" . $this->localization->t('amount') . ":** {$amountUsd} USD (≈ {$priceThb} THB)\n\n";
            $text .= "⏰ " . $this->localization->t('payment_expires_in') . ": 15 минут";

            $kb = [ 'inline_keyboard' => [ [ ['text' => '🌐 ' . $this->localization->t('open_payment'), 'url' => $result['pay_url'] ] ] ] ];
            $this->telegramService->sendMessageWithKeyboard($chatId, $text, $kb);
        } else {
            $this->telegramService->sendMessage($chatId, "❌ " . $this->localization->t('payment_failed') . "\n\n" . ($result['error'] ?? '')); 
        }
    }

    /**
     * Симуляция успешной оплаты (тестовая отметка)
     */
    // Удалено: simulateTestPayment

    /**
     * Обработка голосового сообщения
     */
    private function handleVoiceMessage($chatId, $voice, $messageId, $from)
    {
        $userLanguage = $this->detectUserLanguage($from);
        $this->localization = new LocalizationService($userLanguage);
        
        try {
            // Показываем статус "печатает"
            $this->telegramService->sendTypingAction($chatId);

            // Создаем сервис транскрибации
            $transcriptionService = new TranscriptionService();
            
            // Получаем и транскрибируем аудио
            $audioData = $transcriptionService->getVoiceFile($voice['file_id']);
            $transcription = $transcriptionService->transcribeAudio($audioData, $userLanguage);
            
            if (empty($transcription)) {
                $this->telegramService->sendMessage(
                    $chatId,
                    "❌ " . $this->localization->t('transcription_failed'),
                    $messageId
                );
                return;
            }

            // Анализируем транскрипцию
            $analysis = $transcriptionService->analyzeTranscription($transcription, $userLanguage);

            // Если это бронирование, обрабатываем его
            if ($analysis['is_booking']) {
                $this->handleVoiceBooking($chatId, $analysis, $messageId);
            } else {
                // Иначе обрабатываем как обычное сообщение
                $this->handleAIMessage($chatId, $transcription, $messageId, $from);
            }

        } catch (Exception $e) {
            error_log("Voice message processing error: " . $e->getMessage());
            $this->telegramService->sendMessage(
                $chatId,
                "❌ " . $this->localization->t('voice_processing_error'),
                $messageId
            );
        }
    }

    /**
     * Обработка бронирования по голосовому сообщению
     */
    private function handleVoiceBooking($chatId, $analysis, $messageId)
    {
        // 1) Показать клиенту список всех услуг (кнопки)
        $this->sendServicesInfo($chatId, $messageId);

        // 2) Отправить инструкции как бронировать голосом
        $this->sendVoiceBookingInfo($chatId);

        // 3) Сформировать предварительный результат распознавания (если удалось)
        $formattedResult = (new TranscriptionService())->formatBookingResult($analysis, $this->localization->getLanguage());
        
        if ($formattedResult) {
            // Сохраняем данные бронирования для последующего использования
            $bookingData = [
                'chat_id' => $chatId,
                'service' => $analysis['service'] ?? 'massage',
                'date' => $analysis['date'] ?? null,
                'time' => $analysis['time'] ?? null,
                'guests' => $analysis['guests'] ?? 1,
                'created_at' => time(),
                'language' => $this->localization->getLanguage()
            ];
            
            // Сохраняем в файл
            $bookingFile = 'data/voice_booking_' . $chatId . '.json';
            if (!file_exists('data')) {
                mkdir('data', 0755, true);
            }
            file_put_contents($bookingFile, json_encode($bookingData));
            
            // Создаем кнопки для подтверждения/изменения
            $keyboard = [
                [
                    ['text' => '✅ ' . $this->localization->t('confirm_booking'), 'callback_data' => 'confirm_voice_booking'],
                    ['text' => '✏️ ' . $this->localization->t('edit_booking'), 'callback_data' => 'edit_voice_booking']
                ],
                [
                    ['text' => '❌ ' . $this->localization->t('cancel_booking'), 'callback_data' => 'cancel_voice_booking']
                ]
            ];
            
            $this->telegramService->sendMessageWithKeyboard($chatId, $formattedResult, $keyboard, $messageId);
        }
    }

    /**
     * Отправка информации о голосовом бронировании
     */
    private function sendVoiceBookingInfo($chatId)
    {
        $message = "🎤 **" . $this->localization->t('voice_booking') . "**\n\n";
        $message .= $this->localization->t('speak_booking') . "!\n\n";
        $message .= "💡 **" . $this->localization->t('voice_instructions') . "**\n\n";
        $message .= "🔊 " . $this->localization->t('just_send_voice') . "!";
        
        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Подтверждение голосового бронирования
     */
    private function confirmVoiceBooking($chatId)
    {
        // Загружаем данные бронирования
        $bookingFile = 'data/voice_booking_' . $chatId . '.json';
        if (!file_exists($bookingFile)) {
            $this->telegramService->sendMessage($chatId, "❌ " . $this->localization->t('booking_data_not_found'));
            return;
        }
        
        $bookingData = json_decode(file_get_contents($bookingFile), true);
        $service = $bookingData['service'] ?? 'massage';
        
        // Загружаем данные услуг для получения цены
        $dataFile = 'zima_data.json';
        if (!file_exists($dataFile)) {
            $this->telegramService->sendMessage($chatId, "❌ " . $this->localization->t('data_not_found'));
            return;
        }
        
        $jsonData = json_decode(file_get_contents($dataFile), true);
        $services = $jsonData['services'] ?? [];
        
        // Находим подходящую услугу с улучшенным поиском
        $selectedService = null;
        $serviceLower = strtolower($service);
        
        // Сначала ищем точное совпадение
        foreach ($services as $serviceData) {
            $nameRu = strtolower($serviceData['name_ru']);
            $nameEn = strtolower($serviceData['name_en']);
            
            if (strpos($nameRu, $serviceLower) !== false || 
                strpos($nameEn, $serviceLower) !== false ||
                strpos($serviceLower, $nameRu) !== false ||
                strpos($serviceLower, $nameEn) !== false) {
                $selectedService = $serviceData;
                break;
            }
        }
        
        // Если не найдено, ищем по ключевым словам
        if (!$selectedService) {
            $keywords = [
                'массаж' => 'massage',
                'тайский' => 'thai',
                'арома' => 'aroma',
                'горячие камни' => 'hot stone',
                'антицеллюлитный' => 'anti-cellulite',
                'рефлексология' => 'reflexology',
                'пилинг' => 'scrub',
                'обертывание' => 'wrap',
                'алоэ' => 'aloe',
                'ароматерапия' => 'aromatherapy',
                'парение' => 'steaming',
                'сауна' => 'sauna',
                'steelworker' => 'steelworker',
                'full-body' => 'full-body',
                'back steaming' => 'back steaming',
                'hot oil' => 'hot oil',
                'deep tissue' => 'deep tissue',
                'office syndrome' => 'office syndrome'
            ];
            
            foreach ($services as $serviceData) {
                $nameRu = strtolower($serviceData['name_ru']);
                $nameEn = strtolower($serviceData['name_en']);
                
                foreach ($keywords as $ruKeyword => $enKeyword) {
                    if ((strpos($serviceLower, $ruKeyword) !== false && strpos($nameRu, $ruKeyword) !== false) ||
                        (strpos($serviceLower, $enKeyword) !== false && strpos($nameEn, $enKeyword) !== false)) {
                        $selectedService = $serviceData;
                        break 2;
                    }
                }
            }
        }
        
        if (!$selectedService) {
            // Если не найдена точная услуга, берем первую услугу массажа
            foreach ($services as $serviceData) {
                if ($serviceData['category'] === 'massage') {
                    $selectedService = $serviceData;
                    break;
                }
            }
        }
        
        if (!$selectedService) {
            $this->telegramService->sendMessage($chatId, "❌ " . $this->localization->t('service_not_found'));
            return;
        }
        
        $serviceName = $selectedService['name_' . $this->localization->getLanguage()] ?? $selectedService['name_ru'];
        $priceThb = $selectedService['price'];
        
        // Конвертируем THB в USD
        require_once 'CurrencyService.php';
        require_once 'PaymentHandler.php';
        
        $currencyService = new CurrencyService();
        $paymentHandler = new PaymentHandler($this->localization->getLanguage());
        
        try {
            $usdAmount = $currencyService->convertThbToUsd($priceThb);
            
            if ($usdAmount < 15) {
                $usdAmount = 15; // Минимальная сумма для NOWPayments
            }
            
            // Создаем инвойс
            $result = $paymentHandler->createPaymentInvoice($chatId, $serviceName, $usdAmount, 'USDTTRC20');
            
            if ($result['success']) {
                $message = "✅ **" . $this->localization->t('booking_confirmed') . "!**\n\n";
                $message .= "🏊‍♀️ **" . $this->localization->t('service') . ":** {$serviceName}\n";
                $message .= "💰 **" . $this->localization->t('amount') . ":** {$usdAmount} USDT (≈ {$priceThb} THB)\n";
                $message .= "👥 **" . $this->localization->t('guests') . ":** " . ($bookingData['guests'] ?? 1) . "\n\n";
                $message .= "⏰ " . $this->localization->t('payment_expires_in') . ": 15 минут\n\n";
                $message .= $this->localization->t('if_questions_contact');
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🌐 ' . $this->localization->t('open_payment'), 'url' => $result['pay_url']]
                        ]
                    ]
                ];
                
                $this->telegramService->sendMessageWithKeyboard($chatId, $message, $keyboard);
                
                // Удаляем временный файл бронирования
                unlink($bookingFile);
            } else {
                $this->telegramService->sendMessage($chatId, "❌ " . $this->localization->t('payment_failed') . ": " . ($result['error'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log("Voice booking payment error: " . $e->getMessage());
            $this->telegramService->sendMessage($chatId, "❌ " . $this->localization->t('processing_error') . ": " . $e->getMessage());
        }
    }

    /**
     * Редактирование голосового бронирования
     */
    private function editVoiceBooking($chatId)
    {
        $message = "✏️ **" . $this->localization->t('edit_booking') . "**\n\n";
        $message .= $this->localization->t('send_new_voice') . " " . $this->localization->t('voice_instructions');
        
        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Отмена голосового бронирования
     */
    private function cancelVoiceBooking($chatId)
    {
        $message = "❌ **" . $this->localization->t('booking_cancelled') . "**\n\n";
        $message .= $this->localization->t('can_book_again') . "!";
        
        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обработка криптоплатежки
     */
    private function handleCryptoPayment($chatId, $service, $messageId = null)
    {
        // Показываем статус "печатает"
        $this->telegramService->sendTypingAction($chatId);
        
        require_once 'PaymentHandler.php';
        
        $paymentHandler = new PaymentHandler($this->localization->getLanguage());
        
        // Получаем цену услуги (примерные цены)
        $prices = [
            'massage' => 15,
            'treatment' => 25,
            'spa' => 30,
            'wellness' => 35
        ];
        
        $amount = $prices[$service] ?? 50;
        
            // Создаем инвойс (используем USDTTRC20)
            $result = $paymentHandler->createPaymentInvoice($chatId, $service, $amount, 'USDTTRC20');
        
        if ($result['success']) {
            $message = "💳 **" . $this->localization->t('crypto_payment') . "**\n\n";
            $message .= "🏊‍♀️ **" . $this->localization->t('service') . ":** {$service}\n";
            $message .= "💰 **" . $this->localization->t('amount') . ":** {$amount} USDT\n\n";
            $message .= "⏰ " . $this->localization->t('payment_expires_in') . ": 15 минут";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🌐 ' . $this->localization->t('open_payment'), 'url' => $result['pay_url']]
                    ]
                ]
            ];

            $this->telegramService->sendMessageWithKeyboard($chatId, $message, $keyboard, $messageId);
        } else {
            $message = "❌ **" . $this->localization->t('payment_failed') . "**\n\n";
            $message .= $result['error'];
            
            $this->telegramService->sendMessage($chatId, $message, $messageId);
        }
    }
}

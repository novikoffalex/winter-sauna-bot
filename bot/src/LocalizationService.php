<?php
/**
 * Сервис локализации для бота Zima SPA Wellness
 */

class LocalizationService
{
    private $userLanguage;
    private $translations;
    private $zimaData;

    public function __construct($userLanguage = 'en')
    {
        $this->userLanguage = $this->detectLanguage($userLanguage);
        $this->loadTranslations();
        $this->loadZimaData();
    }

    /**
     * Определение языка пользователя
     */
    private function detectLanguage($userLanguage)
    {
        // Если язык не передан, используем английский по умолчанию
        if (empty($userLanguage)) {
            return 'en';
        }

        // Проверяем поддерживаемые языки
        $supportedLanguages = ['ru', 'en'];
        
        if (in_array($userLanguage, $supportedLanguages)) {
            return $userLanguage;
        }

        // Если язык не поддерживается, возвращаем английский
        return 'en';
    }

    /**
     * Загрузка переводов
     */
    private function loadTranslations()
    {
        $this->translations = [
            'ru' => [
                'welcome' => '🧖‍♀️ Добро пожаловать в Zima SPA Wellness!',
                'welcome_subtitle' => 'Ваш AI-помощник для бронирования и консультаций',
                'services' => 'Наши услуги',
                'booking' => 'Бронирование',
                'prices' => 'Цены',
                'contact' => 'Контакты',
                'help' => 'Помощь',
                'location' => 'Местоположение',
                'working_hours' => 'Время работы',
                'phone' => 'Телефон',
                'email' => 'Email',
                'book_now' => 'Забронировать сейчас',
                'view_services' => 'Посмотреть услуги',
                'view_prices' => 'Посмотреть цены',
                'contact_info' => 'Контактная информация',
                'help_text' => 'Справка',
                'main_menu' => 'Главное меню',
                'choose_service' => 'Выберите услугу',
                'booking_form' => 'Форма бронирования',
                'booking_instructions' => 'Инструкции по бронированию',
                'example' => 'например',
                'tomorrow' => 'завтра',
                'january_15' => '15 января',
                'if_needed' => 'при необходимости',
                'write_info_next_message' => 'Напишите информацию в следующем сообщении',
                'unknown_action' => 'Неизвестное действие',
                'unknown_command' => 'Неизвестная команда',
                'use_help' => 'используйте /help',
                'processing_error' => 'Ошибка обработки',
                'date' => 'Дата',
                'time' => 'Время',
                'guests' => 'Количество гостей',
                'additional_services' => 'Дополнительные услуги',
                'price' => 'Цена',
                'duration' => 'Длительность',
                'category' => 'Категория',
                'description' => 'Описание',
                'thb' => 'бат',
                'hour' => 'час',
                'massage' => 'Массаж',
                'treatment' => 'Лечение',
                'spa' => 'СПА',
                'wellness' => 'Велнес',
                'crypto_payment' => 'Криптоплатеж',
                'pay_with_crypto' => 'Оплатить криптовалютой',
                'select_payment_method' => 'Выберите способ оплаты',
                'ticket_created' => 'Билет создан',
                'ticket_id' => 'ID билета',
                'service' => 'Услуга',
                'amount' => 'Сумма',
                'expires_at' => 'Действует до',
                'how_to_use' => 'Как использовать',
                'show_qr_at_entrance' => 'Покажите QR-код на входе',
                'scan_qr_code' => 'Отсканируйте QR-код',
                'enjoy_service' => 'Наслаждайтесь услугой',
                'ticket_valid_24h' => 'Билет действителен 24 часа',
                'open_payment' => 'Открыть оплату',
                'mark_paid_test' => 'Отметить как оплачено (тест)',
                'open_ticket' => 'Открыть билет',
                'view_ticket_online' => 'Посмотреть билет онлайн',
                'ticket_details' => 'Детали билета',
                'access_code' => 'Код доступа',
                'if_questions_contact' => 'Если есть вопросы, обращайтесь к нам!',
                'payment_success' => 'Оплата прошла успешно!',
                'payment_failed' => 'Ошибка оплаты',
                    'crypto_currencies' => 'Криптовалюты',
                    'usdt' => 'USDT',
                    'btc' => 'Bitcoin',
                    'eth' => 'Ethereum',
                    'processing_voice' => 'Обрабатываю голосовое сообщение',
                    'transcription' => 'Транскрипция',
                    'transcription_failed' => 'Не удалось распознать речь',
                    'voice_processing_error' => 'Ошибка обработки голосового сообщения',
                    'confirm_booking' => 'Подтвердить бронирование',
                    'edit_booking' => 'Изменить бронирование',
                    'cancel_booking' => 'Отменить бронирование',
                    'voice_booking' => 'Голосовое бронирование',
                    'speak_booking' => 'Просто скажите, что хотите забронировать',
                    'voice_instructions' => 'Например: "Хочу записаться на массаж завтра в 14:00 на 2 человек"',
                    'just_send_voice' => 'Просто отправьте голосовое сообщение',
                    'booking_confirmed' => 'Бронирование подтверждено',
                    'booking_details_sent' => 'Детали бронирования отправлены',
                    'booking_cancelled' => 'Бронирование отменено',
                    'can_book_again' => 'Можете забронировать снова',
                    'send_new_voice' => 'Отправьте новое голосовое сообщение с изменениями',
                    'booking_data_not_found' => 'Данные бронирования не найдены',
                    'data_not_found' => 'Данные не найдены',
                    'service_not_found' => 'Услуга не найдена'
            ],
            'en' => [
                'welcome' => '🧖‍♀️ Welcome to Zima SPA Wellness!',
                'welcome_subtitle' => 'Your AI assistant for booking and consultations',
                'services' => 'Our Services',
                'booking' => 'Booking',
                'prices' => 'Prices',
                'contact' => 'Contact',
                'help' => 'Help',
                'location' => 'Location',
                'working_hours' => 'Working Hours',
                'phone' => 'Phone',
                'email' => 'Email',
                'book_now' => 'Book Now',
                'view_services' => 'View Services',
                'view_prices' => 'View Prices',
                'contact_info' => 'Contact Information',
                'help_text' => 'Help',
                'main_menu' => 'Main Menu',
                'choose_service' => 'Choose Service',
                'booking_form' => 'Booking Form',
                'booking_instructions' => 'Booking instructions',
                'example' => 'example',
                'tomorrow' => 'tomorrow',
                'january_15' => 'January 15',
                'if_needed' => 'if needed',
                'write_info_next_message' => 'Write the information in the next message',
                'unknown_action' => 'Unknown action',
                'unknown_command' => 'Unknown command',
                'use_help' => 'use /help',
                'processing_error' => 'Processing error',
                'date' => 'Date',
                'time' => 'Time',
                'guests' => 'Number of Guests',
                'additional_services' => 'Additional Services',
                'price' => 'Price',
                'duration' => 'Duration',
                'category' => 'Category',
                'description' => 'Description',
                'thb' => 'THB',
                'hour' => 'hour',
                'massage' => 'Massage',
                'treatment' => 'Treatment',
                'spa' => 'SPA',
                'wellness' => 'Wellness',
                'crypto_payment' => 'Crypto Payment',
                'pay_with_crypto' => 'Pay with Crypto',
                'select_payment_method' => 'Select Payment Method',
                'ticket_created' => 'Ticket Created',
                'ticket_id' => 'Ticket ID',
                'service' => 'Service',
                'amount' => 'Amount',
                'expires_at' => 'Expires At',
                'how_to_use' => 'How to Use',
                'show_qr_at_entrance' => 'Show QR code at entrance',
                'scan_qr_code' => 'Scan QR code',
                'enjoy_service' => 'Enjoy your service',
                'ticket_valid_24h' => 'Ticket valid for 24 hours',
                'open_payment' => 'Open Payment',
                'mark_paid_test' => 'Mark as Paid (test)',
                'open_ticket' => 'Open Ticket',
                'view_ticket_online' => 'View Ticket Online',
                'ticket_details' => 'Ticket Details',
                'access_code' => 'Access Code',
                'if_questions_contact' => 'If you have questions, contact us!',
                'payment_success' => 'Payment successful!',
                'payment_failed' => 'Payment failed',
                    'crypto_currencies' => 'Cryptocurrencies',
                    'usdt' => 'USDT',
                    'btc' => 'Bitcoin',
                    'eth' => 'Ethereum',
                    'processing_voice' => 'Processing voice message',
                    'transcription' => 'Transcription',
                    'transcription_failed' => 'Failed to recognize speech',
                    'voice_processing_error' => 'Voice message processing error',
                    'confirm_booking' => 'Confirm Booking',
                    'edit_booking' => 'Edit Booking',
                    'cancel_booking' => 'Cancel Booking',
                    'voice_booking' => 'Voice Booking',
                    'speak_booking' => 'Just say what you want to book',
                    'voice_instructions' => 'For example: "I want to book a massage for tomorrow at 2 PM for 2 people"',
                    'just_send_voice' => 'Just send a voice message',
                    'booking_confirmed' => 'Booking confirmed',
                    'booking_details_sent' => 'Booking details sent',
                    'booking_cancelled' => 'Booking cancelled',
                    'can_book_again' => 'You can book again',
                    'send_new_voice' => 'Send a new voice message with changes',
                    'booking_data_not_found' => 'Booking data not found',
                    'data_not_found' => 'Data not found',
                    'service_not_found' => 'Service not found'
            ]
        ];
    }

    /**
     * Загрузка данных Zima
     */
    private function loadZimaData()
    {
        if (file_exists('zima_data.json')) {
            $this->zimaData = json_decode(file_get_contents('zima_data.json'), true);
        } else {
            $this->zimaData = [
                'name' => 'Zima SPA Wellness',
                'short_name' => 'Zima',
                'location' => '83/14 Moo 2, Wiset Rd, Rawai, Phuket 83100, Thailand',
                'phone' => '+66 81 234 5678',
                'email' => 'info@zimaspawellness.com',
                'working_hours' => '10:00-22:00',
                'services' => []
            ];
        }
    }

    /**
     * Получение перевода
     */
    public function t($key, $params = [])
    {
        // Используем единый источник переводов с fallback на EN
        $translation = $this->translations[$this->userLanguage][$key]
            ?? $this->translations['en'][$key]
            ?? $key;

        // Замена параметров в переводе
        foreach ($params as $param => $value) {
            $translation = str_replace('{' . $param . '}', $value, $translation);
        }

        return $translation;
    }

    /**
     * Получение данных Zima на текущем языке
     */
    public function getZimaData()
    {
        $data = $this->zimaData;
        
        // Локализация услуг
        if (isset($data['services'])) {
            foreach ($data['services'] as &$service) {
                $service['name'] = $service['name_' . $this->userLanguage] ?? $service['name_ru'];
                $service['description'] = $service['description_' . $this->userLanguage] ?? $service['description_ru'];
            }
        }
        
        return $data;
    }

    /**
     * Получение AI промпта на текущем языке
     */
    public function getAIPrompt()
    {
        if (file_exists('ai_prompts.json')) {
            $prompts = json_decode(file_get_contents('ai_prompts.json'), true);
            return $prompts[$this->userLanguage] ?? $prompts['ru'];
        }
        
        return "You are an AI assistant for Zima SPA Wellness in Phuket, Thailand.";
    }

    /**
     * Получение языка пользователя
     */
    public function getLanguage()
    {
        return $this->userLanguage;
    }

    /**
     * Установка языка пользователя
     */
    public function setLanguage($language)
    {
        $this->userLanguage = $this->detectLanguage($language);
    }

    /**
     * Получение услуг по категории
     */
    public function getServicesByCategory($category = null)
    {
        $data = $this->getZimaData();
        $services = $data['services'] ?? [];
        
        if ($category) {
            $services = array_filter($services, function($service) use ($category) {
                return $service['category'] === $category;
            });
        }
        
        return $services;
    }

    /**
     * Форматирование цены
     */
    public function formatPrice($price, $currency = 'THB')
    {
        if ($this->userLanguage === 'ru') {
            return $price . ' ' . $this->t('thb');
        } else {
            return $currency . ' ' . $price;
        }
    }

    /**
     * Получение приветственного сообщения
     */
    public function getWelcomeMessage()
    {
        $data = $this->getZimaData();
        
        $message = $this->t('welcome') . "\n\n";
        $message .= $this->t('welcome_subtitle') . "\n\n";
        $message .= "📍 " . $this->t('location') . ": " . $data['location'] . "\n";
        $message .= "🕒 " . $this->t('working_hours') . ": " . $data['working_hours'] . "\n";
        $message .= "📞 " . $this->t('phone') . ": " . $data['phone'] . "\n\n";
        $message .= $this->t('choose_service') . ":";
        
        return $message;
    }

    /**
     * Получение сообщения с услугами
     */
    public function getServicesMessage()
    {
        $services = $this->getServicesByCategory();
        $message = "🏊‍♀️ " . $this->t('services') . ":\n\n";
        
        foreach ($services as $index => $service) {
            $message .= ($index + 1) . ". **{$service['name']}**\n";
            $message .= "   💰 " . $this->t('price') . ": " . $this->formatPrice($service['price']) . "\n";
            $message .= "   ⏱️ " . $this->t('duration') . ": {$service['duration']}\n";
            $message .= "   📝 {$service['description']}\n\n";
        }
        
        return $message;
    }

    /**
     * Получение сообщения с ценами
     */
    public function getPricesMessage()
    {
        $services = $this->getServicesByCategory();
        $message = "💰 " . $this->t('prices') . ":\n\n";
        
        // Группируем по категориям
        $categories = [];
        foreach ($services as $service) {
            $category = $service['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $service;
        }
        
        foreach ($categories as $category => $categoryServices) {
            $message .= "**" . $this->t($category) . ":**\n";
            foreach ($categoryServices as $service) {
                $message .= "• {$service['name']}: " . $this->formatPrice($service['price']) . "\n";
            }
            $message .= "\n";
        }
        
        return $message;
    }
    
    // УДАЛЕНО: дублирующийся метод t() (объединено выше)
}

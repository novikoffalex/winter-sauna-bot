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
                'wellness' => 'Велнес'
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
                'wellness' => 'Wellness'
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
        $translation = $this->translations[$this->userLanguage][$key] ?? $key;
        
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
}

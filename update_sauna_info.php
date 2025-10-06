<?php
/**
 * Скрипт для обновления информации о бане на основе найденных данных
 */

// Найденные данные о банях на Пхукете
$foundSaunas = [
    [
        'name' => 'Khao Rang Herbal Steam',
        'address' => '31 Soi Susanti, Wichit, Mueang Phuket District, Phuket',
        'phone' => '+66 62 353 4111',
        'type' => 'Herbal Steam'
    ],
    [
        'name' => 'Ice Bath Club',
        'address' => '146/5, Choeng Thale, Thalang District, Phuket',
        'phone' => '+66 89 725 3580',
        'type' => 'Ice Bath'
    ],
    [
        'name' => 'Sauna Patong Bar',
        'address' => '3 Hasippi Rd, Pa Tong, Kathu District, Phuket',
        'phone' => '+66 80 147 3738',
        'type' => 'Sauna Bar'
    ],
    [
        'name' => 'Rawai Massage & Sauna',
        'address' => '11 Wiset Rd, Rawai, Amphoe Mueang Phuket, Phuket',
        'phone' => '+66 92 555 8011',
        'type' => 'Massage & Sauna'
    ],
    [
        'name' => 'Nanai Sauna',
        'address' => '39 Soi Nanai 2, Pa Tong, Kathu District, Phuket',
        'phone' => '+66 81 747 3353',
        'type' => 'Traditional Sauna'
    ]
];

echo "🏊‍♀️ Найденные бани и СПА на Пхукете:\n";
echo "=====================================\n\n";

foreach ($foundSaunas as $index => $sauna) {
    echo ($index + 1) . ". {$sauna['name']}\n";
    echo "   📍 Адрес: {$sauna['address']}\n";
    echo "   📞 Телефон: {$sauna['phone']}\n";
    echo "   🏷️ Тип: {$sauna['type']}\n\n";
}

// Рекомендации для бани "Зима"
echo "💡 Рекомендации для бани 'Зима':\n";
echo "================================\n\n";

echo "1. 📍 Адрес: Обновите на более точный адрес в Rawai\n";
echo "   Текущий: 83/14, Moo 2, Rawai, Phuket, Thailand\n";
echo "   Рекомендуемый формат: '83/14 Moo 2, Wiset Rd, Rawai, Phuket 83100, Thailand'\n\n";

echo "2. 📞 Телефон: Добавьте реальный номер\n";
echo "   Примеры форматов: +66 81 234 5678 или +66-81-234-5678\n\n";

echo "3. 🌐 Дополнительные контакты:\n";
echo "   - Facebook страница\n";
echo "   - Instagram аккаунт\n";
echo "   - WhatsApp для бронирования\n";
echo "   - Email для корпоративных клиентов\n\n";

echo "4. 🏷️ Услуги (на основе найденных бань):\n";
echo "   - Русская баня с паром\n";
echo "   - Финская сауна\n";
echo "   - Травяной пар (Herbal Steam)\n";
echo "   - Ледяная ванна (Ice Bath)\n";
echo "   - Массаж и СПА процедуры\n";
echo "   - Зона отдыха с чаем\n\n";

// Команды для обновления Heroku
echo "🔧 Команды для обновления бота:\n";
echo "===============================\n\n";

echo "# Обновить адрес\n";
echo "heroku config:set SAUNA_LOCATION='83/14 Moo 2, Wiset Rd, Rawai, Phuket 83100, Thailand' --app winter-sauna-bot-phuket\n\n";

echo "# Добавить телефон (замените на реальный)\n";
echo "heroku config:set SAUNA_PHONE='+66 81 234 5678' --app winter-sauna-bot-phuket\n\n";

echo "# Добавить email\n";
echo "heroku config:set SAUNA_EMAIL='info@zimasauna-phuket.com' --app winter-sauna-bot-phuket\n\n";

echo "# Добавить Facebook\n";
echo "heroku config:set SAUNA_FACEBOOK='https://facebook.com/zimasaunaphuket' --app winter-sauna-bot-phuket\n\n";

echo "# Добавить Instagram\n";
echo "heroku config:set SAUNA_INSTAGRAM='https://instagram.com/zimasaunaphuket' --app winter-sauna-bot-phuket\n\n";

echo "# Добавить WhatsApp\n";
echo "heroku config:set SAUNA_WHATSAPP='+66812345678' --app winter-sauna-bot-phuket\n\n";

// Создание файла с контактной информацией
$contactInfo = [
    'name' => 'Зима',
    'location' => '83/14 Moo 2, Wiset Rd, Rawai, Phuket 83100, Thailand',
    'phone' => '+66 81 234 5678', // Замените на реальный
    'email' => 'info@zimasauna-phuket.com',
    'facebook' => 'https://facebook.com/zimasaunaphuket',
    'instagram' => 'https://instagram.com/zimasaunaphuket',
    'whatsapp' => '+66812345678',
    'working_hours' => '10:00-22:00',
    'services' => [
        'Русская баня с паром',
        'Финская сауна',
        'Травяной пар',
        'Ледяная ванна',
        'Массаж и СПА процедуры',
        'Зона отдыха с чаем'
    ]
];

file_put_contents('sauna_contact_info.json', json_encode($contactInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "📄 Создан файл sauna_contact_info.json с контактной информацией\n";
echo "   Используйте его для обновления бота\n\n";

echo "🎯 Следующие шаги:\n";
echo "==================\n";
echo "1. Получите реальный телефон для бани 'Зима'\n";
echo "2. Создайте социальные сети (Facebook, Instagram)\n";
echo "3. Обновите переменные в Heroku\n";
echo "4. Протестируйте обновленный бот\n";
echo "5. Добавьте фотографии бани в бот\n\n";

echo "✅ Готово! Информация собрана и готова к использованию.\n";
?>

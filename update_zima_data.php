<?php
/**
 * Скрипт для обновления данных бани Zima с реальной информацией
 */

echo "🔄 Обновление данных бани Zima...\n";
echo "================================\n\n";

// Реальные данные из Яндекс.Карт
$zimaData = [
    'name' => 'Zima SPA Wellness',
    'short_name' => 'Zima',
    'location' => '83/14 Moo 2, Wiset Rd, Rawai, Phuket 83100, Thailand',
    'phone' => '+66 81 234 5678', // Замените на реальный
    'email' => 'info@zimaspawellness.com',
    'working_hours' => '10:00-22:00',
    'services' => [
        [
            'name_ru' => 'Алоэ Вера лечение 1 час',
            'name_en' => 'Aloe Vera Treatment 1 Hour',
            'description_ru' => 'Лечит, увлажняет и укрепляет кожу. Приносит комфорт и расслабление. Охлаждает и успокаивает покраснения.',
            'description_en' => 'Heals, moisturizes and strengthens the skin. Brings comfort and relaxation. Cools and soothes redness.',
            'price' => 750,
            'currency' => 'THB',
            'duration' => '1 час',
            'category' => 'treatment'
        ],
        [
            'name_ru' => 'Ароматерапевтический массаж',
            'name_en' => 'Aromatherapy massage',
            'description_ru' => 'Ощутите расслабление в каждом массаже. Снизьте стресс, снимите усталость с натуральными эфирными маслами.',
            'description_en' => 'Experience relaxation in every massage. Reduce stress, relieve fatigue with natural essential oils.',
            'price' => 500,
            'currency' => 'THB',
            'duration' => '1 час',
            'category' => 'massage'
        ],
        [
            'name_ru' => 'Глубокий тканевый массаж 1 час',
            'name_en' => 'Deep Tissue Massage 1 Hour',
            'description_ru' => 'Массаж глубоко в мышечный слой, снимая узлы, накопленные от стресса и перенапряжения.',
            'description_en' => 'Massage deep into the muscle layer, releasing knots accumulated from stress and overuse.',
            'price' => 750,
            'currency' => 'THB',
            'duration' => '1 час',
            'category' => 'massage'
        ],
        [
            'name_ru' => 'Горячий масляный массаж 1 час',
            'name_en' => 'Hot Oil Massage 1 Hour',
            'description_ru' => 'Терапевтический массаж тела, сочетающий преимущества массажа с целебными свойствами нагретых масел.',
            'description_en' => 'A therapeutic bodywork treatment that combines the benefits of massage with the therapeutic properties of warmed oils.',
            'price' => 750,
            'currency' => 'THB',
            'duration' => '1 час',
            'category' => 'massage'
        ],
        [
            'name_ru' => 'Массаж офисного синдрома 1 час',
            'name_en' => 'Office Syndrome Massage 1 hour',
            'description_ru' => 'С маслом и тайским горячим травяным бальзамом. Фокус на спине, шее и плечах.',
            'description_en' => 'With oil and Thai hot herbal balm. Focus on the back, neck and shoulders.',
            'price' => 500,
            'currency' => 'THB',
            'duration' => '1 час',
            'category' => 'massage'
        ],
        [
            'name_ru' => 'Тайский масляный массаж 1 час',
            'name_en' => 'Thai oil massage 1 Hour',
            'description_ru' => 'Сочетает традиционный тайский массаж с техниками акупрессуры, растяжки и скручивания с эфирными маслами.',
            'description_en' => 'Combines traditional Thai massage, using acupressure, stretching and twisting techniques with essential oils.',
            'price' => 500,
            'currency' => 'THB',
            'duration' => '1 час',
            'category' => 'massage'
        ],
        [
            'name_ru' => 'Традиционный тайский массаж 1 час',
            'name_en' => 'Traditional Thai Massage 1 Hour',
            'description_ru' => 'Расслабьтесь с древней наукой, помогите расслабить мышцы, снять боли, стимулировать кровообращение.',
            'description_en' => 'Relax with ancient science, help relax muscles, relieve aches and pains, stimulate blood circulation.',
            'price' => 500,
            'currency' => 'THB',
            'duration' => '1 час',
            'category' => 'massage'
        ]
    ]
];

// Обновляем AI промпт с поддержкой локализации
$aiPromptRu = "Ты - AI-ассистент бани 'Zima SPA Wellness' на Пхукете, Таиланд. Твоя задача - помогать клиентам с бронированием, консультациями и информацией об услугах.

Основные функции:
1. Бронирование услуг бани и СПА
2. Информация о ценах и пакетах услуг
3. Консультации по банным процедурам и их пользе
4. Информация о времени работы и местоположении
5. Рекомендации по подготовке к посещению бани

Информация о бане:
- Название: Zima SPA Wellness
- Местоположение: 83/14 Moo 2, Wiset Rd, Rawai, Phuket 83100, Thailand
- Время работы: 10:00-22:00 ежедневно
- Услуги: Массажи, СПА процедуры, лечение алоэ вера, ароматерапия
- Особенности: Профессиональные мастера, натуральные масла, уютная атмосфера
- Район: Rawai - популярный район на юге Пхукета, рядом с пляжами

Стиль общения:
- Теплый и гостеприимный
- Профессиональный, но дружелюбный
- Используй эмодзи для создания уютной атмосферы (🧖‍♀️💆‍♀️🌿✨)
- Предлагай конкретные услуги и время
- Всегда интересуйся количеством гостей и предпочтениями
- Упоминай преимущества расположения в Rawai

Если клиент хочет забронировать, уточни: дату, время, количество человек, желаемые услуги.
Если вопрос неясен, вежливо уточни детали и предложи варианты.";

$aiPromptEn = "You are an AI assistant for 'Zima SPA Wellness' in Phuket, Thailand. Your task is to help clients with booking, consultations and information about services.

Main functions:
1. Booking spa and wellness services
2. Information about prices and service packages
3. Consultations on spa procedures and their benefits
4. Information about working hours and location
5. Recommendations for preparing for spa visit

Spa information:
- Name: Zima SPA Wellness
- Location: 83/14 Moo 2, Wiset Rd, Rawai, Phuket 83100, Thailand
- Working hours: 10:00-22:00 daily
- Services: Massages, spa treatments, aloe vera treatment, aromatherapy
- Features: Professional therapists, natural oils, cozy atmosphere
- Area: Rawai - popular area in southern Phuket, close to beaches

Communication style:
- Warm and welcoming
- Professional but friendly
- Use emojis to create cozy atmosphere (🧖‍♀️💆‍♀️🌿✨)
- Suggest specific services and times
- Always ask about number of guests and preferences
- Mention advantages of Rawai location

If client wants to book, clarify: date, time, number of people, desired services.
If question is unclear, politely ask for details and suggest options.";

// Сохраняем данные в JSON файл
file_put_contents('zima_data.json', json_encode($zimaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Сохраняем промпты
file_put_contents('ai_prompts.json', json_encode([
    'ru' => $aiPromptRu,
    'en' => $aiPromptEn
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Созданы файлы с данными:\n";
echo "   - zima_data.json (услуги и информация)\n";
echo "   - ai_prompts.json (промпты для AI)\n\n";

echo "📋 Реальные услуги Zima SPA Wellness:\n";
echo "====================================\n\n";

foreach ($zimaData['services'] as $index => $service) {
    echo ($index + 1) . ". {$service['name_ru']} / {$service['name_en']}\n";
    echo "   💰 Цена: {$service['price']} {$service['currency']}\n";
    echo "   ⏱️ Длительность: {$service['duration']}\n";
    echo "   🏷️ Категория: {$service['category']}\n";
    echo "   📝 Описание: {$service['description_ru']}\n\n";
}

echo "🌐 Локализация:\n";
echo "==============\n";
echo "✅ Русский язык (RU) - полная поддержка\n";
echo "✅ Английский язык (EN) - полная поддержка\n";
echo "✅ Определение языка по настройкам Telegram пользователя\n";
echo "✅ AI промпты на двух языках\n\n";

echo "🚀 Готово к интеграции в бота!\n";
?>

<?php
/**
 * Скрипт для парсинга информации о банях на Пхукете
 */

// Функция для поиска информации о банях
function searchSaunaInfo($query) {
    $searchUrl = "https://www.google.com/search?q=" . urlencode($query);
    
    // Используем cURL для получения результатов поиска
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return "Ошибка получения данных: HTTP $httpCode";
    }
    
    return $html;
}

// Функция для извлечения контактной информации
function extractContactInfo($html) {
    $info = [];
    
    // Поиск телефонов
    preg_match_all('/\+66[\s\-]?[0-9]{2}[\s\-]?[0-9]{3}[\s\-]?[0-9]{4}/', $html, $phones);
    if (!empty($phones[0])) {
        $info['phones'] = array_unique($phones[0]);
    }
    
    // Поиск адресов
    preg_match_all('/[0-9]+[\/\s]?[A-Za-z0-9\s,]+(?:Rawai|Phuket|Thailand)/i', $html, $addresses);
    if (!empty($addresses[0])) {
        $info['addresses'] = array_unique($addresses[0]);
    }
    
    // Поиск email
    preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $emails);
    if (!empty($emails[0])) {
        $info['emails'] = array_unique($emails[0]);
    }
    
    return $info;
}

echo "🔍 Поиск информации о банях на Пхукете...\n\n";

// Поисковые запросы
$queries = [
    'баня Зима Пхукет',
    'Zima sauna Phuket',
    'Russian sauna Phuket Rawai',
    'баня русская сауна Пхукет',
    'spa sauna Phuket Rawai contact'
];

$allInfo = [];

foreach ($queries as $query) {
    echo "Поиск: $query\n";
    $html = searchSaunaInfo($query);
    $info = extractContactInfo($html);
    
    if (!empty($info)) {
        $allInfo[$query] = $info;
        echo "✅ Найдена информация:\n";
        if (isset($info['phones'])) {
            echo "  Телефоны: " . implode(', ', $info['phones']) . "\n";
        }
        if (isset($info['addresses'])) {
            echo "  Адреса: " . implode(', ', $info['addresses']) . "\n";
        }
        if (isset($info['emails'])) {
            echo "  Email: " . implode(', ', $info['emails']) . "\n";
        }
    } else {
        echo "❌ Информация не найдена\n";
    }
    echo "\n";
    
    // Пауза между запросами
    sleep(2);
}

// Альтернативный поиск через популярные сайты
echo "🌐 Поиск на популярных сайтах...\n\n";

$sites = [
    'https://www.tripadvisor.com/Attractions-g293920-Activities-c47-Phuket.html',
    'https://www.booking.com/attractions/th/phuket/',
    'https://www.google.com/maps/search/sauna+phuket'
];

foreach ($sites as $site) {
    echo "Проверка: $site\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $site);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $info = extractContactInfo($html);
        if (!empty($info)) {
            echo "✅ Найдена информация на $site:\n";
            if (isset($info['phones'])) {
                echo "  Телефоны: " . implode(', ', $info['phones']) . "\n";
            }
            if (isset($info['addresses'])) {
                echo "  Адреса: " . implode(', ', $info['addresses']) . "\n";
            }
        }
    } else {
        echo "❌ Ошибка доступа к $site\n";
    }
    echo "\n";
}

// Рекомендации по поиску
echo "💡 Рекомендации для поиска информации:\n";
echo "1. Проверьте Google Maps: https://maps.google.com/search?q=sauna+phuket\n";
echo "2. Поищите в TripAdvisor: https://www.tripadvisor.com/Attractions-g293920-Activities-c47-Phuket.html\n";
echo "3. Проверьте Facebook страницы бань на Пхукете\n";
echo "4. Поищите в местных группах в Telegram/WhatsApp\n";
echo "5. Проверьте сайты бронирования: Booking.com, Agoda.com\n\n";

echo "📝 Если найдете информацию, обновите переменные в Heroku:\n";
echo "heroku config:set SAUNA_PHONE='+66-XX-XXX-XXXX' --app winter-sauna-bot-phuket\n";
echo "heroku config:set SAUNA_LOCATION='новый адрес' --app winter-sauna-bot-phuket\n";
echo "heroku config:set SAUNA_EMAIL='email@example.com' --app winter-sauna-bot-phuket\n";
?>

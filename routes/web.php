<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return 'bot ok';
});

// Прямой обработчик для Telegram webhook
Route::any('/bot/webhook.php', function (Request $request) {
    $botPath = base_path('bot');
    
    // Устанавливаем рабочий каталог
    chdir($botPath);
    
    // Загружаем конфигурацию
    require_once $botPath . '/config/config.php';
    require_once $botPath . '/src/TelegramWebhookHandlerLocalized.php';
    
    // Получаем POST данные
    $input = $request->getContent();
    if (empty($input)) {
        $input = json_encode($request->all());
    }
    
    // Сохраняем в глобальную переменную для доступа в webhook
    $GLOBALS['HTTP_RAW_POST_DATA'] = $input;
    
    // Создаем обработчик и обрабатываем webhook
    $handler = new TelegramWebhookHandlerLocalized();
    $handler->handleWebhook();
    
    // Очищаем глобальные переменные
    unset($GLOBALS['HTTP_RAW_POST_DATA']);
    
    return response('OK', 200);
});

// Прямой обработчик для NOWPayments webhook
Route::any('/bot/crypto-webhook.php', function (Request $request) {
    $botPath = base_path('bot');
    
    // Устанавливаем рабочий каталог
    chdir($botPath);
    
    // Загружаем конфигурацию
    require_once $botPath . '/config/config.php';
    
    // Получаем POST данные
    $input = $request->getContent();
    if (empty($input)) {
        $input = json_encode($request->all());
    }
    
    // Сохраняем в глобальную переменную
    $GLOBALS['HTTP_RAW_POST_DATA'] = $input;
    
    // Выполняем файл crypto-webhook.php
    ob_start();
    require $botPath . '/crypto-webhook.php';
    $output = ob_get_clean();
    
    // Очищаем глобальные переменные
    unset($GLOBALS['HTTP_RAW_POST_DATA']);
    
    return response($output, 200);
});

// Проксирование других запросов к боту
Route::any('/bot/{path}', function (Request $request, $path = '') {
    $botPath = base_path('bot');
    $requestPath = $path ?: 'index.php';
    
    // Если это PHP файл, выполняем его
    if (str_ends_with($requestPath, '.php')) {
        $filePath = $botPath . '/' . $requestPath;
        
        if (file_exists($filePath)) {
            // Устанавливаем рабочий каталог
            chdir($botPath);
            
            // Захватываем вывод
            ob_start();
            
            // Выполняем PHP файл
            $_SERVER['REQUEST_METHOD'] = $request->method();
            $_GET = $request->query->all();
            $_POST = $request->request->all();
            
            // Для POST/PUT/PATCH запросов сохраняем содержимое
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
                $input = $request->getContent();
                // Если контент пустой, пробуем получить из json()
                if (empty($input)) {
                    $input = json_encode($request->all());
                }
                // Сохраняем в глобальную переменную для доступа в webhook
                $GLOBALS['HTTP_RAW_POST_DATA'] = $input;
                // Также в переменную окружения
                putenv('HTTP_RAW_POST_DATA=' . $input);
                // Устанавливаем $_POST для совместимости
                if (!empty($input)) {
                    $decoded = json_decode($input, true);
                    if ($decoded) {
                        $_POST = array_merge($_POST, $decoded);
                    }
                }
            }
            
            require $filePath;
            $output = ob_get_clean();
            
            // Очищаем глобальные переменные
            unset($GLOBALS['HTTP_RAW_POST_DATA']);
            
            return response($output);
        }
    }
    
    // Для других файлов возвращаем 404
    abort(404);
})->where('path', '.*');

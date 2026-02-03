<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return 'bot ok';
});

// Прямой обработчик для Telegram webhook
Route::any('/bot/webhook.php', function (Request $request) {
    try {
        $botPath = base_path('bot');
        
        // Проверяем существование директории
        if (!is_dir($botPath)) {
            \Log::error("Bot directory not found: $botPath");
            return response('Bot directory not found', 500);
        }
        
        // Устанавливаем рабочий каталог
        chdir($botPath);
        
        // Загружаем конфигурацию
        $configPath = $botPath . '/config/config.php';
        if (!file_exists($configPath)) {
            \Log::error("Config file not found: $configPath");
            return response('Config file not found', 500);
        }
        require_once $configPath;
        
        $handlerPath = $botPath . '/src/TelegramWebhookHandlerLocalized.php';
        if (!file_exists($handlerPath)) {
            \Log::error("Handler file not found: $handlerPath");
            return response('Handler file not found', 500);
        }
        require_once $handlerPath;
        
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
    } catch (\Exception $e) {
        \Log::error('Webhook error: ' . $e->getMessage());
        return response('Error: ' . $e->getMessage(), 500);
    }
});

// Прямой обработчик для NOWPayments webhook
Route::any('/bot/crypto-webhook.php', function (Request $request) {
    try {
        $botPath = base_path('bot');
        
        // Проверяем существование директории
        if (!is_dir($botPath)) {
            \Log::error("Bot directory not found: $botPath");
            return response('Bot directory not found', 500);
        }
        
        // Устанавливаем рабочий каталог
        chdir($botPath);
        
        // Загружаем конфигурацию
        $configPath = $botPath . '/config/config.php';
        if (!file_exists($configPath)) {
            \Log::error("Config file not found: $configPath");
            return response('Config file not found', 500);
        }
        require_once $configPath;
        
        // Получаем POST данные
        $input = $request->getContent();
        if (empty($input)) {
            $input = json_encode($request->all());
        }
        
        // Сохраняем в глобальную переменную
        $GLOBALS['HTTP_RAW_POST_DATA'] = $input;
        
        // Выполняем файл crypto-webhook.php
        $cryptoWebhookPath = $botPath . '/crypto-webhook.php';
        if (!file_exists($cryptoWebhookPath)) {
            \Log::error("Crypto webhook file not found: $cryptoWebhookPath");
            return response('Crypto webhook file not found', 500);
        }
        
        ob_start();
        require $cryptoWebhookPath;
        $output = ob_get_clean();
        
        // Очищаем глобальные переменные
        unset($GLOBALS['HTTP_RAW_POST_DATA']);
        
        return response($output, 200);
    } catch (\Exception $e) {
        \Log::error('Crypto webhook error: ' . $e->getMessage());
        return response('Error: ' . $e->getMessage(), 500);
    }
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

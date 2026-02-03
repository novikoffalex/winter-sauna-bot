<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return 'bot ok';
});

// Проксирование запросов к боту
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
                // Сохраняем в глобальную переменную для доступа в webhook
                $GLOBALS['HTTP_RAW_POST_DATA'] = $input;
                // Также в переменную окружения
                putenv('HTTP_RAW_POST_DATA=' . $input);
            }
            
            // Эмулируем php://input через stream wrapper
            if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
                // Создаем временный файл для эмуляции php://input
                $tempFile = tmpfile();
                fwrite($tempFile, $GLOBALS['HTTP_RAW_POST_DATA']);
                rewind($tempFile);
                // Переопределяем php://input через stream context
                stream_context_set_default([
                    'http' => [
                        'method' => $request->method(),
                        'content' => $GLOBALS['HTTP_RAW_POST_DATA']
                    ]
                ]);
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

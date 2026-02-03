#!/bin/bash
# Скрипт для запуска сервера бота

PORT=${1:-8000}

# Проверяем, не занят ли порт
if lsof -i :$PORT > /dev/null 2>&1; then
    echo "⚠️  Порт $PORT уже занят!"
    echo ""
    echo "Запущенные процессы на порту $PORT:"
    lsof -i :$PORT
    echo ""
    read -p "Остановить существующий процесс и запустить новый? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Останавливаю процесс на порту $PORT..."
        lsof -ti :$PORT | xargs kill -9 2>/dev/null
        sleep 1
    else
        echo "Используйте другой порт: ./start-server.sh 8001"
        exit 1
    fi
fi

echo "🚀 Запуск бота на http://localhost:$PORT"
echo "📡 Webhook URL: http://localhost:$PORT/webhook.php"
echo "❤️  Health check: http://localhost:$PORT/health.php"
echo ""
echo "🛑 Для остановки нажмите Ctrl+C"
echo ""

# Запускаем сервер
php -S localhost:$PORT

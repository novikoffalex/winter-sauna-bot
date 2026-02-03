#!/bin/bash
# Скрипт для настройки webhook через LocalTunnel

PORT=${1:-8000}
BOT_TOKEN="8352783894:AAH4o0yi18izMoF3iTCpUP5LN8YwxAyFd04"

echo "🚀 Запуск LocalTunnel на порту $PORT..."
echo ""
echo "В отдельном терминале запустите:"
echo "  lt --port $PORT"
echo ""
echo "После получения URL выполните:"
echo "  curl -X POST \"https://api.telegram.org/bot$BOT_TOKEN/setWebhook\" -d \"url=https://ВАШ-URL.loca.lt/webhook.php\""
echo ""
echo "Или используйте этот скрипт после запуска LocalTunnel:"
echo "  ./setup-webhook-localtunnel.sh https://ваш-url.loca.lt"

if [ -n "$2" ]; then
    TUNNEL_URL="$2"
    echo ""
    echo "Настраиваю webhook на $TUNNEL_URL/webhook.php..."
    
    RESPONSE=$(curl -s -X POST "https://api.telegram.org/bot$BOT_TOKEN/setWebhook" \
        -d "url=$TUNNEL_URL/webhook.php")
    
    echo "Ответ от Telegram API:"
    echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
    
    echo ""
    echo "Проверка webhook:"
    curl -s "https://api.telegram.org/bot$BOT_TOKEN/getWebhookInfo" | python3 -m json.tool 2>/dev/null || \
    curl -s "https://api.telegram.org/bot$BOT_TOKEN/getWebhookInfo"
fi

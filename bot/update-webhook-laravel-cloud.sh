#!/bin/bash
# Скрипт для обновления webhook на Laravel Cloud URL

echo "🔄 Обновление webhook на Laravel Cloud..."
echo ""

# Загружаем переменные из .env
if [ ! -f .env ]; then
    echo "❌ Файл .env не найден!"
    exit 1
fi

# Читаем токен и URL из .env
TELEGRAM_BOT_TOKEN=$(grep "^TELEGRAM_BOT_TOKEN=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
LARAVEL_CLOUD_URL="https://winter-sauna-bot-main-xkszpa.laravel.cloud"
WEBHOOK_URL="${LARAVEL_CLOUD_URL}/bot/webhook.php"

if [ -z "$TELEGRAM_BOT_TOKEN" ] || [ "$TELEGRAM_BOT_TOKEN" = "your_telegram_bot_token_here" ]; then
    echo "❌ TELEGRAM_BOT_TOKEN не установлен в .env!"
    exit 1
fi

echo "📋 Настройки:"
echo "   Bot Token: ${TELEGRAM_BOT_TOKEN:0:10}..."
echo "   Webhook URL: $WEBHOOK_URL"
echo ""

# Устанавливаем webhook
echo "⚙️  Установка webhook..."
response=$(curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/setWebhook" \
    -H "Content-Type: application/json" \
    -d "{\"url\":\"${WEBHOOK_URL}\"}")

# Проверяем ответ
if echo "$response" | grep -q '"ok":true'; then
    echo "✅ Webhook успешно обновлен!"
    echo "   URL: $WEBHOOK_URL"
    echo ""
    
    # Проверяем информацию о webhook
    echo "🔍 Проверка webhook..."
    webhook_info=$(curl -s "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/getWebhookInfo")
    echo "$webhook_info" | python3 -m json.tool 2>/dev/null || echo "$webhook_info"
else
    echo "❌ Ошибка обновления webhook:"
    echo "$response" | python3 -m json.tool 2>/dev/null || echo "$response"
    exit 1
fi

echo ""
echo "💡 Теперь обновите TELEGRAM_WEBHOOK_URL в Laravel Cloud:"
echo "   Settings → Environment Variables → TELEGRAM_WEBHOOK_URL"
echo "   Значение: $WEBHOOK_URL"

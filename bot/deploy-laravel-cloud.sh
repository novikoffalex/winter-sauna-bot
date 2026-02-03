#!/bin/bash
# Скрипт для ручного деплоя через deploy hook Laravel Cloud

DEPLOY_HOOK_URL="https://cloud.laravel.com/deploy/a0fda4a9-b705-4d2a-a07b-e3d3c2663b76/H84hTdplnalzegyu"

echo "🚀 Запуск деплоя через deploy hook..."
echo ""

# Проверяем curl
if ! command -v curl &> /dev/null; then
    echo "❌ curl не установлен"
    exit 1
fi

# Отправляем POST запрос к deploy hook
echo "📤 Отправка запроса на деплой..."
echo "   URL: $DEPLOY_HOOK_URL"
echo ""

# Используем -L для следования редиректам и -f для обработки ошибок
response=$(curl -s -w "\n%{http_code}" -L -X POST "$DEPLOY_HOOK_URL" \
    -H "Content-Type: application/json" \
    -H "User-Agent: Laravel-Cloud-Deploy/1.0")

# Разделяем ответ и HTTP код
http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

echo "   HTTP код: $http_code"
echo ""

# Проверяем различные успешные коды
if [ "$http_code" -eq 200 ] || [ "$http_code" -eq 201 ] || [ "$http_code" -eq 202 ] || [ "$http_code" -eq 302 ]; then
    if [ "$http_code" -eq 302 ]; then
        echo "⚠️  Получен редирект (302) - возможно требуется авторизация"
        echo "   Попробуйте запустить деплой через Laravel Cloud Dashboard"
    else
        echo "✅ Деплой запущен успешно!"
    fi
    echo ""
    echo "📋 Следующие шаги:"
    echo "   1. Проверьте статус в Laravel Cloud Dashboard → Deployments"
    echo "   2. Дождитесь завершения деплоя (обычно 1-3 минуты)"
    echo "   3. Проверьте логи на наличие ошибок"
    echo ""
    echo "🌐 Dashboard: https://cloud.laravel.com/alex-novikov/winter-sauna-bot-2/main/deployments"
    echo ""
    echo "💡 Если deploy hook не работает, используйте:"
    echo "   - Ручной деплой через Dashboard → Deployments → Deploy Latest"
    echo "   - Или настройте автодеплой через webhook в GitLab"
else
    echo "❌ Ошибка запуска деплоя"
    echo "   HTTP код: $http_code"
    echo ""
    echo "💡 Попробуйте:"
    echo "   1. Ручной деплой через Dashboard → Deployments → Deploy Latest"
    echo "   2. Проверьте настройки автодеплоя в Settings → Deployments"
    exit 1
fi

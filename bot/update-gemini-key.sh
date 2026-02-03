#!/bin/bash
# Скрипт для обновления GEMINI_API_KEY в .env файле

if [ -z "$1" ]; then
    echo "Использование: ./update-gemini-key.sh YOUR_API_KEY"
    echo ""
    echo "Пример:"
    echo "  ./update-gemini-key.sh AIzaSyAbCdEfGhIjKlMnOpQrStUvWxYz1234567"
    exit 1
fi

API_KEY="$1"
ENV_FILE=".env"

if [ ! -f "$ENV_FILE" ]; then
    echo "Ошибка: файл $ENV_FILE не найден"
    exit 1
fi

# Заменяем GEMINI_API_KEY
if grep -q "GEMINI_API_KEY=" "$ENV_FILE"; then
    # Заменяем существующую строку
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        sed -i '' "s|GEMINI_API_KEY=.*|GEMINI_API_KEY=$API_KEY|" "$ENV_FILE"
    else
        # Linux
        sed -i "s|GEMINI_API_KEY=.*|GEMINI_API_KEY=$API_KEY|" "$ENV_FILE"
    fi
    echo "✅ GEMINI_API_KEY обновлен в $ENV_FILE"
else
    echo "❌ Строка GEMINI_API_KEY не найдена в $ENV_FILE"
    exit 1
fi

# Проверяем результат
echo ""
echo "Проверка:"
grep "GEMINI_API_KEY=" "$ENV_FILE" | head -1

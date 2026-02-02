# Инструкция по тестированию бота

## Подготовка к тестированию

### 1. Получение API ключей

#### Google Gemini API Key
1. Перейдите на [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Войдите в аккаунт Google
3. Создайте новый API ключ
4. Скопируйте ключ (он будет выглядеть как `AIza...`)

#### Telegram Bot Token
1. Найдите бота [@BotFather](https://t.me/BotFather) в Telegram
2. Отправьте команду `/newbot` или используйте существующего бота
3. Скопируйте токен бота

### 2. Настройка переменных окружения

Создайте файл `bot/.env` на основе `bot/env.example`:

```bash
cd bot
cp env.example .env
```

Отредактируйте `bot/.env` и укажите:

```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=ваш_токен_бота
TELEGRAM_WEBHOOK_URL=https://your-domain.com/webhook.php

# Google Gemini Configuration
GEMINI_API_KEY=ваш_gemini_api_ключ
GEMINI_MODEL=gemini-pro

# Опционально: OpenAI для транскрипции голосовых
OPENAI_API_KEY=ваш_openai_ключ (опционально)
```

### 3. Установка зависимостей

```bash
cd bot
composer install
```

## Локальное тестирование

### Вариант 1: Через встроенный PHP сервер

```bash
cd bot
php -S localhost:8000
```

Бот будет доступен на `http://localhost:8000`

### Вариант 2: Через скрипт запуска

```bash
# Из корня проекта
./scripts/run-bot.sh
```

### 3. Настройка webhook для Telegram

Для локального тестирования используйте ngrok:

```bash
# В отдельном терминале
ngrok http 8000
```

Скопируйте HTTPS URL от ngrok (например: `https://abc123.ngrok.io`)

Затем настройте webhook:

```bash
curl -X POST "https://api.telegram.org/bot<ВАШ_ТОКЕН>/setWebhook" \
  -d "url=https://abc123.ngrok.io/webhook.php"
```

Или используйте скрипт настройки:

```bash
cd bot
php setup-webhook.php
```

## Тестирование функциональности

### 1. Базовый тест

Отправьте боту в Telegram:
- `/start` - должно прийти приветственное сообщение
- `Привет` или `Hello` - бот должен ответить через Gemini

### 2. Тест AI ответов

Попробуйте разные вопросы:
- "Какие услуги у вас есть?"
- "Сколько стоит массаж?"
- "Как забронировать сауну?"
- "Где вы находитесь?"

### 3. Тест команд

- `/services` - список услуг
- `/prices` - цены
- `/booking` - бронирование
- `/contact` - контакты
- `/help` - помощь

### 4. Тест бронирования

1. Отправьте `/booking` или нажмите кнопку "Забронировать"
2. Выберите услугу
3. Следуйте инструкциям бота

### 5. Тест транскрипции (если настроен OpenAI)

Отправьте голосовое сообщение боту - оно должно быть распознано и обработано.

## Проверка логов

Логи PHP можно посмотреть:

```bash
# В реальном времени
tail -f /var/log/php_errors.log

# Или в файле логов бота (если настроено)
tail -f bot/storage/logs/bot.log
```

## Тестирование через curl

### Проверка health endpoint

```bash
curl http://localhost:8000/health.php
```

### Симуляция webhook от Telegram

```bash
curl -X POST http://localhost:8000/webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "message_id": 1,
      "from": {
        "id": 123456,
        "first_name": "Test",
        "language_code": "ru"
      },
      "chat": {
        "id": 123456
      },
      "date": 1234567890,
      "text": "Привет"
    }
  }'
```

## Устранение проблем

### Бот не отвечает

1. Проверьте, что webhook настроен:
   ```bash
   curl "https://api.telegram.org/bot<ТОКЕН>/getWebhookInfo"
   ```

2. Проверьте логи на ошибки

3. Убедитесь, что `GEMINI_API_KEY` установлен правильно

4. Проверьте, что сервер запущен и доступен

### Ошибки Gemini API

- **401 Unauthorized**: Проверьте правильность API ключа
- **429 Too Many Requests**: Превышен лимит запросов, подождите
- **400 Bad Request**: Проверьте формат запроса

### Проблемы с webhook

- Убедитесь, что URL доступен из интернета (используйте ngrok)
- Проверьте, что URL заканчивается на `/webhook.php`
- Убедитесь, что сервер принимает POST запросы

## Тестирование в продакшене

После деплоя в Laravel Cloud:

1. Обновите `TELEGRAM_WEBHOOK_URL` в переменных окружения
2. Настройте webhook на продакшн URL
3. Протестируйте все функции бота

## Полезные команды

```bash
# Проверка конфигурации
cd bot
php test-telegram-bot.php

# Настройка webhook
php setup-webhook.php

# Быстрый тест
php quick-test.php
```

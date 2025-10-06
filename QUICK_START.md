# 🧖‍♀️ Winter Sauna Bot - Быстрый старт

## 📋 Что сделано

✅ Проект полностью адаптирован под тематику бани "Зима" на Пхукете  
✅ Интеграция с Telegram Bot API вместо Lark  
✅ AI-ассистент с системными промптами для банных услуг  
✅ Готовые команды и inline-кнопки для удобства пользователей  
✅ Красивый веб-интерфейс для мониторинга  

## 🚀 Для запуска бота выполните:

### 1. Настройка переменных окружения
Отредактируйте файл `.env`:
```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
TELEGRAM_WEBHOOK_URL=http://localhost:8000/webhook.php

# OpenAI Configuration  
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4

# Sauna Business Info
SAUNA_NAME=Зима
SAUNA_LOCATION=Пхукет, Таиланд
SAUNA_WORKING_HOURS=10:00-22:00
SAUNA_PHONE=+66-XX-XXX-XXXX
```

### 2. Создание Telegram бота
1. Найдите [@BotFather](https://t.me/botfather) в Telegram
2. Отправьте `/newbot`
3. Следуйте инструкциям
4. Скопируйте токен в `.env` файл

### 3. Запуск локального сервера
```bash
# Запуск ngrok для публичного URL
ngrok http 8000

# Обновите TELEGRAM_WEBHOOK_URL в .env файле на ваш ngrok URL
# Например: https://abc123.ngrok.io/webhook.php

# Запуск PHP сервера
php -S 0.0.0.0:8000
```

### 4. Настройка webhook
```bash
# Автоматическая настройка
php setup-webhook.php

# Или вручную
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
     -d "url=https://your-ngrok-url.ngrok.io/webhook.php"
```

### 5. Тестирование
```bash
# Проверка конфигурации
php test-telegram-bot.php

# Откройте браузер: http://localhost:8000
```

## 🤖 Возможности бота

### Команды:
- `/start` - Приветствие и главное меню
- `/services` - Список услуг бани
- `/booking` - Бронирование времени
- `/prices` - Информация о ценах
- `/contact` - Контакты и местоположение
- `/help` - Справка по использованию

### AI-функции:
- Умные ответы на вопросы о бане
- Помощь с бронированием
- Консультации по банным процедурам
- Рекомендации по подготовке

### Inline-кнопки:
- Быстрый доступ к услугам
- Прямое бронирование
- Просмотр цен
- Контактная информация

## 📁 Структура проекта

```
winter-sauna-bot/
├── src/
│   ├── TelegramService.php      # Работа с Telegram API
│   ├── AIService.php           # Интеграция с OpenAI
│   └── TelegramWebhookHandler.php # Обработка сообщений
├── config/
│   └── config.php              # Конфигурация
├── index.php                   # Главная страница
├── webhook.php                 # Webhook endpoint
├── test-telegram-bot.php       # Тестирование
├── setup-webhook.php           # Настройка webhook
├── .env                        # Переменные окружения
└── README.md                   # Подробная документация
```

## 🎯 Следующие шаги

1. **Настройте реальные данные** в `.env` файле
2. **Создайте Telegram бота** через @BotFather
3. **Запустите сервер** и настройте webhook
4. **Протестируйте бота** в Telegram
5. **Добавьте свои услуги** и цены в код
6. **Настройте деплой** на сервер (Heroku, Railway, etc.)

## 💡 Дополнительные возможности

- Добавление базы данных для бронирований
- Интеграция с платежными системами
- Уведомления о бронированиях
- Аналитика использования бота
- Многоязычная поддержка

---

**Готово к использованию! 🎉**

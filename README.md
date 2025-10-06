# Winter Sauna Bot 🧖‍♀️ (PHP версия)

Интеллектуальный Telegram бот для бани "Зима" на Пхукете с интеграцией ИИ для помощи клиентам с бронированием и консультациями.

## 🚀 Возможности

- **Умные ответы**: Интеграция с OpenAI GPT-4 для естественного общения
- **Бронирование**: Помощь в бронировании услуг бани
- **Консультации**: Информация о банных процедурах и услугах
- **Расписание**: Информация о времени работы и доступности
- **Telegram интеграция**: Полная поддержка Telegram Bot API

## 📋 Требования

- PHP 7.4+ 
- cURL расширение
- JSON расширение
- Аккаунт в Lark Open Platform
- OpenAI API ключ
- ngrok для локального тестирования

## 🛠 Установка

1. **Клонируйте репозиторий**
```bash
git clone https://gitlab.com/a.novikov6/staff-helper.git
cd staff-helper
```

2. **Настройте переменные окружения**
```bash
cp env.php .env
```

3. **Отредактируйте .env файл** с вашими API ключами

## 🚀 Быстрый запуск

```bash
# Запустите локальный сервер
php start-server.php
```

## 🔧 Настройка переменных окружения

Отредактируйте файл `.env`:

```env
# Lark Bot Configuration
LARK_APP_ID=your_app_id_here
LARK_APP_SECRET=your_app_secret_here
LARK_WEBHOOK_URL=http://localhost:8000/webhook.php

# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4

# Security
WEBHOOK_VERIFICATION_TOKEN=your_verification_token_here
```

## 🔧 Настройка Lark Bot

### 1. Создание приложения в Lark

1. Перейдите в [Lark Open Platform](https://open.feishu.cn/)
2. Создайте новое приложение
3. В разделе "Credentials" скопируйте `App ID` и `App Secret`

### 2. Настройка Event Subscriptions

1. В разделе "Event Subscriptions" включите подписку на события
2. Добавьте URL вашего webhook: `https://your-ngrok-url.ngrok.io/webhook.php`
3. Подпишитесь на события:
   - `im.message.receive_v1` - получение сообщений
   - `im.message.message_read_v1` - прочтение сообщений

### 3. Настройка Permissions

В разделе "Permissions" добавьте необходимые права:
- `im:message` - отправка сообщений
- `im:message:read` - чтение сообщений
- `im:chat` - доступ к чатам
- `contact:user.id:read` - чтение информации о пользователях

### 4. Публикация приложения

1. В разделе "Version Management" создайте версию
2. Запросите публикацию приложения
3. После одобрения добавьте бота в нужные чаты

## 📁 Структура проекта

```
├── index.php                # Главный файл
├── webhook.php              # Webhook endpoint
├── health.php               # Health check
├── start-server.php         # Скрипт запуска сервера
├── test-webhook.php         # Тестирование webhook
├── config/
│   └── config.php           # Конфигурация
├── src/
│   ├── WebhookHandler.php   # Обработка webhook событий
│   ├── LarkService.php      # Интеграция с Lark API
│   └── AIService.php        # Интеграция с OpenAI
└── env.php                  # Шаблон переменных окружения
```

## 🔌 API Endpoints

### `POST /webhook.php`
Основной endpoint для получения событий от Lark.

**Поддерживаемые события:**
- `url_verification` - проверка URL
- `im.message.receive_v1` - получение сообщений

### `GET /health.php`
Проверка состояния сервиса.

## 🧪 Тестирование

```bash
# Тестирование webhook
php test-webhook.php

# Проверка health check
curl http://localhost:8000/health.php
```

## 🤖 Использование бота

### В приватном чате
Просто напишите боту любое сообщение.

### В групповом чате
Упомяните бота (@бот) или используйте ключевые слова:
- "помощь", "помоги"
- "задача", "план"
- "напомни"

### Примеры команд

```
👤 Пользователь: Помоги спланировать день
🤖 Бот: 📋 Отлично! Давайте составим план на день. 
        Расскажите, какие у вас основные задачи и приоритеты?

👤 Пользователь: Напомни мне про встречу в 15:00
🤖 Бот: ⏰ Конечно! Я напомню вам про встречу в 15:00. 
        Что это за встреча и с кем?

👤 Пользователь: Создай список покупок
🤖 Бот: 📋 Создаю список покупок! Напишите, что нужно купить, 
        и я структурирую список для удобства.
```

## 🚨 Устранение неполадок

### Бот не отвечает
1. Проверьте логи PHP: `tail -f /var/log/php_errors.log`
2. Убедитесь, что webhook URL доступен через ngrok
3. Проверьте настройки приложения в Lark

### Ошибки API
1. Проверьте правильность App ID и Secret в .env
2. Убедитесь, что у приложения есть необходимые права
3. Проверьте лимиты OpenAI API

### Проблемы с ngrok
1. Убедитесь, что ngrok запущен: `ngrok http 8000`
2. Проверьте, что URL в Lark соответствует ngrok URL
3. Убедитесь, что webhook URL заканчивается на `/webhook.php`

---

**Удачного использования! 🚀**
# 🚀 Ручной деплой на Heroku

## 📋 **Шаги для деплоя:**

### 1. **Создание приложения в Heroku Dashboard:**
1. Перейдите на https://dashboard.heroku.com
2. Нажмите "New" → "Create new app"
3. **App name:** `stuffhelper-lark-bot` (или любое уникальное имя)
4. **Region:** United States
5. Нажмите "Create app"

### 2. **Подключение Git репозитория:**
```bash
# Добавьте Heroku remote
heroku git:remote -a stuffhelper-lark-bot

# Деплой
git push heroku main
```

### 3. **Настройка Environment Variables:**
В Heroku Dashboard → Settings → Config Vars добавьте:

```
LARK_APP_ID=cli_a764e7a267789028
LARK_APP_SECRET=PnrsxYG5dNQ2hrBJOE5veht5GxJFSJPa
LARK_WEBHOOK_URL=https://stuffhelper-lark-bot.herokuapp.com/webhook.php
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4
WEBHOOK_VERIFICATION_TOKEN=your_verification_token_here
NODE_ENV=production
```

### 4. **Тестирование:**
```bash
# Health check
curl https://stuffhelper-lark-bot.herokuapp.com/health.php

# Webhook test
curl -X POST https://stuffhelper-lark-bot.herokuapp.com/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"type":"url_verification","challenge":"test123"}'
```

### 5. **Обновление Webhook URL в Lark:**
1. Перейдите в Lark Developer Console
2. **Events & Callbacks** → **Request URL:** `https://stuffhelper-lark-bot.herokuapp.com/webhook.php`
3. Сохраните изменения

### 6. **Тестирование бота:**
1. В Lark найдите бота "Тест"
2. Добавьте в группу
3. Напишите: `@Тест привет`

## 🔧 **Альтернативный способ через CLI:**

```bash
# Логин (откроется браузер)
heroku login

# Создание приложения
heroku create stuffhelper-lark-bot

# Настройка переменных
heroku config:set LARK_APP_ID="cli_a764e7a267789028" --app stuffhelper-lark-bot
heroku config:set LARK_APP_SECRET="PnrsxYG5dNQ2hrBJOE5veht5GxJFSJPa" --app stuffhelper-lark-bot
heroku config:set OPENAI_API_KEY="your_openai_api_key_here
heroku config:set LARK_WEBHOOK_URL="https://stuffhelper-lark-bot.herokuapp.com/webhook.php" --app stuffhelper-lark-bot
heroku config:set OPENAI_MODEL="gpt-4" --app stuffhelper-lark-bot
heroku config:set WEBHOOK_VERIFICATION_TOKEN="your_verification_token_here" --app stuffhelper-lark-bot
heroku config:set NODE_ENV="production" --app stuffhelper-lark-bot

# Деплой
git push heroku main
```

## 🎉 **Готово!**

После деплоя ваш бот будет доступен по адресу:
**https://stuffhelper-lark-bot.herokuapp.com**

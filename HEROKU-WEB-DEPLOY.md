# 🚀 Деплой через Heroku Dashboard

## 📋 **Переменные окружения уже настроены!**

✅ **LARK_APP_ID:** `cli_a764e7a267789028`  
✅ **LARK_APP_SECRET:** `PnrsxYG5dNQ2hrBJOE5veht5GxJFSJPa`  
✅ **OPENAI_API_KEY:** `your_openai_api_key_here
✅ **LARK_WEBHOOK_URL:** `https://staff-helper.herokuapp.com/webhook.php`  
✅ **OPENAI_MODEL:** `gpt-4`  
✅ **WEBHOOK_VERIFICATION_TOKEN:** `your_verification_token_here`  
✅ **NODE_ENV:** `production`  

## 🚀 **Деплой через GitHub:**

### 1. **Подключите GitHub репозиторий:**
1. Перейдите на https://dashboard.heroku.com/apps/staff-helper
2. **Deploy** → **Deployment method** → **GitHub**
3. Подключите ваш репозиторий: `a.novikov6/staff-helper`
4. Выберите ветку: `main`

### 2. **Автоматический деплой:**
1. Включите **"Wait for CI to pass before deploy"** (если нужно)
2. Нажмите **"Deploy Branch"**

### 3. **Или ручной деплой через Git:**
```bash
# Если у вас есть доступ к Heroku CLI
git push heroku main
```

## 🧪 **Тестирование после деплоя:**

### 1. **Health Check:**
```bash
curl https://staff-helper.herokuapp.com/health.php
```

### 2. **Webhook Test:**
```bash
curl -X POST https://staff-helper.herokuapp.com/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"type":"url_verification","challenge":"test123"}'
```

## 🔄 **Обновление Webhook URL в Lark:**

1. Перейдите в Lark Developer Console
2. **Events & Callbacks** → **Request URL:** `https://staff-helper.herokuapp.com/webhook.php`
3. Сохраните изменения

## 📱 **Тестирование бота:**

1. В Lark найдите бота "Тест"
2. Добавьте в группу
3. Напишите: `@Тест привет`

## 🛠️ **Логи:**

Просмотр логов в Heroku Dashboard:
- **More** → **View logs**

## 🎉 **Готово!**

Ваш бот будет доступен по адресу:
**https://staff-helper.herokuapp.com**

### 📝 **Важные файлы:**
- `composer.json` - зависимости PHP
- `Procfile` - команда запуска
- `index.php` - главная страница
- `webhook.php` - webhook endpoint
- `health.php` - health check

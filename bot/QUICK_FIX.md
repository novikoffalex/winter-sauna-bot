# Быстрое исправление проблемы с ботом

## Проблема
Бот не работает, потому что отсутствует `GEMINI_API_KEY` в файле `.env`.

## Решение

### 1. Получите API ключ Gemini

1. Перейдите на [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Войдите в аккаунт Google
3. Нажмите "Create API Key"
4. Скопируйте ключ (начинается с `AIza...`)

### 2. Добавьте ключ в .env

Откройте файл `bot/.env` и добавьте (или замените `your_gemini_api_key_here`):

```env
GEMINI_API_KEY=AIza...ваш_ключ_здесь
GEMINI_MODEL=gemini-pro
```

### 3. Проверьте конфигурацию

```bash
cd bot
php -r "require_once 'config/config.php'; echo 'GEMINI_API_KEY: ' . (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) ? 'OK' : 'НЕ УСТАНОВЛЕН') . PHP_EOL;"
```

Должно вывести: `GEMINI_API_KEY: OK`

### 4. Запустите бота

```bash
php -S localhost:8000
```

### 5. Настройте webhook (если еще не настроен)

Для локального тестирования используйте ngrok:

```bash
# В отдельном терминале
ngrok http 8000
```

Затем настройте webhook:

```bash
curl -X POST "https://api.telegram.org/bot<ВАШ_ТОКЕН>/setWebhook" \
  -d "url=https://ваш-ngrok-url.ngrok.io/webhook.php"
```

## Другие возможные проблемы

### Бот не отвечает на сообщения

1. Проверьте, что webhook настроен:
   ```bash
   curl "https://api.telegram.org/bot<ТОКЕН>/getWebhookInfo"
   ```

2. Проверьте логи:
   ```bash
   tail -f /var/log/php_errors.log
   ```

### Ошибки Gemini API

- **401 Unauthorized**: Проверьте правильность API ключа
- **429 Too Many Requests**: Превышен лимит, подождите
- **400 Bad Request**: Проверьте формат запроса

### Сервер не запускается

Убедитесь, что порт 8000 свободен:
```bash
lsof -i :8000
```

Если занят, используйте другой порт:
```bash
php -S localhost:8001
```

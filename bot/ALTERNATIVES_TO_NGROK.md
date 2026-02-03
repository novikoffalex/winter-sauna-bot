# Альтернативы ngrok для локального тестирования

## Проблема
Ваша версия ngrok (3.18.2) устарела. Нужна версия 3.19.0 или новее.

## Решения

### Вариант 1: Обновить ngrok

```bash
# Обновить через команду (если доступно)
ngrok update

# Или скачать новую версию
# macOS:
brew upgrade ngrok/ngrok/ngrok

# Или вручную:
# 1. Скачайте с https://ngrok.com/download
# 2. Замените старый бинарник
```

### Вариант 2: Использовать локальный туннель (LocalTunnel)

```bash
# Установка
npm install -g localtunnel

# Запуск
lt --port 8000
```

Вы получите URL вида: `https://random-name.loca.lt`

### Вариант 3: Использовать Cloudflare Tunnel (cloudflared)

```bash
# Установка
brew install cloudflare/cloudflare/cloudflared

# Запуск
cloudflared tunnel --url http://localhost:8000
```

### Вариант 4: Использовать serveo.net (без установки)

```bash
ssh -R 80:localhost:8000 serveo.net
```

### Вариант 5: Использовать localhost.run

```bash
ssh -R 80:localhost:8000 ssh.localhost.run
```

## Рекомендация

Для быстрого тестирования используйте **LocalTunnel**:

```bash
# 1. Установите
npm install -g localtunnel

# 2. Запустите (в отдельном терминале)
lt --port 8000

# 3. Скопируйте URL (например: https://random-name.loca.lt)

# 4. Настройте webhook
curl -X POST "https://api.telegram.org/bot8352783894:AAH4o0yi18izMoF3iTCpUP5LN8YwxAyFd04/setWebhook" \
  -d "url=https://random-name.loca.lt/webhook.php"
```

## Проверка webhook

```bash
curl "https://api.telegram.org/bot8352783894:AAH4o0yi18izMoF3iTCpUP5LN8YwxAyFd04/getWebhookInfo"
```

# Быстрая настройка webhook для тестирования

## Текущая ситуация
- ✅ Сервер работает на `http://localhost:8000`
- ❌ ngrok устарел (нужна версия 3.19.0+)

## Решение 1: Обновить ngrok (требует sudo)

```bash
sudo ngrok update
```

Или скачайте новую версию вручную:
```bash
# Скачайте с https://ngrok.com/download
# Замените /usr/local/bin/ngrok
sudo mv ~/Downloads/ngrok /usr/local/bin/ngrok
sudo chmod +x /usr/local/bin/ngrok
```

## Решение 2: Использовать LocalTunnel (рекомендуется)

### Установка:
```bash
npm install -g localtunnel
```

### Запуск:
```bash
# В отдельном терминале
lt --port 8000
```

Вы получите URL вида: `https://random-name.loca.lt`

### Настройка webhook:
```bash
curl -X POST "https://api.telegram.org/bot8352783894:AAH4o0yi18izMoF3iTCpUP5LN8YwxAyFd04/setWebhook" \
  -d "url=https://ваш-localtunnel-url.loca.lt/webhook.php"
```

## Решение 3: Использовать Cloudflare Tunnel

### Установка:
```bash
brew install cloudflare/cloudflare/cloudflared
```

### Запуск:
```bash
cloudflared tunnel --url http://localhost:8000
```

## Решение 4: Использовать serveo.net (без установки)

```bash
ssh -R 80:localhost:8000 serveo.net
```

## Проверка webhook

После настройки проверьте:

```bash
curl "https://api.telegram.org/bot8352783894:AAH4o0yi18izMoF3iTCpUP5LN8YwxAyFd04/getWebhookInfo"
```

Должно показать ваш webhook URL.

## Тестирование

Отправьте боту в Telegram:
- `/start` - приветствие
- `Привет` - ответ через Gemini

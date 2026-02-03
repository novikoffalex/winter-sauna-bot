# Как добавить GEMINI_API_KEY в .env файл

## Проблема
В файле `.env` все еще стоит заглушка `your_gemini_api_key_here` вместо реального ключа.

## Решение

### Вариант 1: Через скрипт (рекомендуется)

```bash
cd winter-sauna-bot/bot
./update-gemini-key.sh AIza...ваш_ключ_здесь
```

### Вариант 2: Вручную через редактор

1. Откройте файл `bot/.env` в любом текстовом редакторе
2. Найдите строку:
   ```
   GEMINI_API_KEY=your_gemini_api_key_here
   ```
3. Замените `your_gemini_api_key_here` на ваш реальный ключ:
   ```
   GEMINI_API_KEY=AIzaSyAbCdEfGhIjKlMnOpQrStUvWxYz1234567
   ```
4. **ВАЖНО**: 
   - Не добавляйте пробелы вокруг знака `=`
   - Не используйте кавычки
   - Не добавляйте комментарии в той же строке
5. Сохраните файл

### Вариант 3: Через команду sed

```bash
cd winter-sauna-bot/bot
sed -i '' 's|GEMINI_API_KEY=your_gemini_api_key_here|GEMINI_API_KEY=ВАШ_РЕАЛЬНЫЙ_КЛЮЧ|' .env
```

## Проверка

После добавления ключа проверьте:

```bash
cd winter-sauna-bot/bot
php check-bot.php
```

Должно вывести: `✅ GEMINI_API_KEY: установлен`

## Где получить ключ

1. Перейдите на https://makersuite.google.com/app/apikey
2. Войдите в аккаунт Google
3. Нажмите "Create API Key"
4. Скопируйте ключ (начинается с `AIza...`)

## Пример правильного формата

```env
# Правильно ✅
GEMINI_API_KEY=AIzaSyAbCdEfGhIjKlMnOpQrStUvWxYz1234567

# Неправильно ❌
GEMINI_API_KEY = AIzaSyAbCdEfGhIjKlMnOpQrStUvWxYz1234567  # пробелы
GEMINI_API_KEY="AIzaSyAbCdEfGhIjKlMnOpQrStUvWxYz1234567"  # кавычки
GEMINI_API_KEY=your_gemini_api_key_here  # заглушка
```

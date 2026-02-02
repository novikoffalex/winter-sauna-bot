# Миграция с OpenAI на Google Gemini

## Что было изменено

1. ✅ Создан новый класс `GeminiService.php` для работы с Google Gemini API
2. ✅ Обновлен `TelegramWebhookHandlerLocalized.php` - теперь использует Gemini вместо OpenAI
3. ✅ Обновлена конфигурация - добавлены переменные `GEMINI_API_KEY` и `GEMINI_MODEL`
4. ✅ Обновлен `env.example` с новыми переменными
5. ✅ OpenAI оставлен только для транскрипции голосовых сообщений (опционально)

## Быстрый старт

### 1. Получите API ключ Gemini

Перейдите на [Google AI Studio](https://makersuite.google.com/app/apikey) и создайте API ключ.

### 2. Обновите .env файл

В файле `bot/.env` добавьте:

```env
GEMINI_API_KEY=ваш_ключ_здесь
GEMINI_MODEL=gemini-pro
```

### 3. Запустите бота

```bash
./scripts/run-bot.sh
```

### 4. Протестируйте

Отправьте боту сообщение в Telegram - он должен ответить через Gemini.

## Отличия от OpenAI

- **Нет Assistant API** - Gemini использует обычный Chat API
- **Другой формат запросов** - используется формат `contents` с `role` и `parts`
- **История разговора** - сохраняется локально через `ConversationStore`
- **Системный промпт** - передается как первое сообщение в истории

## Что осталось от OpenAI

- `TranscriptionService.php` - все еще использует OpenAI Whisper для транскрипции голосовых сообщений
- Если хотите полностью отказаться от OpenAI, можно использовать Google Speech-to-Text API

## Проверка работоспособности

См. файл `TESTING.md` для подробных инструкций по тестированию.

# Чеклист проверки функциональности бота

## ✅ Что работает

### 1. Базовая функциональность
- [x] Telegram Bot подключен
- [x] Gemini API работает (платный тариф)
- [x] Webhook настроен и работает
- [x] Локализация (RU/EN) работает
- [x] Команды бота (`/start`, `/help`, `/services`, `/prices`, `/booking`)

### 2. QR-генерация
- [x] Генерация QR-кодов работает
- [x] Билеты сохраняются в `data/tickets.json`
- [x] QR-коды генерируются в SVG формате
- [x] Валидация QR-кодов реализована
- [ ] Тест валидации (нужно сохранить билет перед валидацией)

### 3. Криптоплатежи (NOWPayments)
- [ ] API ключи не настроены в `.env`
- [ ] Webhook URL не настроен в NOWPayments dashboard
- [ ] Тестирование создания инвойса
- [ ] Тестирование обработки платежа
- [ ] Тестирование отправки QR-билета после оплаты

## 🔧 Что нужно настроить

### NOWPayments

1. **Получить API ключи**:
   - Зарегистрируйтесь на https://nowpayments.io/
   - Получите API Key, Public Key, IPN Secret

2. **Добавить в `.env`**:
   ```env
   NOWPAYMENTS_API_KEY=ваш_ключ
   NOWPAYMENTS_PUBLIC_KEY=ваш_публичный_ключ
   NOWPAYMENTS_WEBHOOK_SECRET=ваш_ipn_secret
   ```

3. **Настроить Webhook**:
   - В NOWPayments dashboard → IPN Settings
   - URL: `https://ваш-домен/crypto-webhook.php`
   - Для локального тестирования: используйте ngrok/localtunnel

### Webhook URL для продакшена

Текущий webhook URL в коде указывает на Heroku:
```
https://winter-sauna-bot-phuket-f79605d5d044.herokuapp.com/crypto-webhook.php
```

Если используете другой домен, обновите в:
- `bot/src/CryptoPaymentService.php` (строка 42)
- `bot/src/PaymentHandler.php` (если есть)
- `bot/src/TicketService.php` (строка 217)

## 🧪 Команды для тестирования

### Тест QR-генерации
```bash
cd winter-sauna-bot/bot
php test-qr-generation.php
```

### Тест NOWPayments
```bash
php test-nowpayments.php
```

### Полный тест функциональности
```bash
php test-full-functionality.php
```

### Проверка конфигурации
```bash
php check-bot.php
```

## 📋 Полный поток работы

1. **Пользователь отправляет `/booking`**
   - Бот показывает услуги
   - Пользователь выбирает услугу

2. **Создание инвойса**
   - `PaymentHandler->createPaymentInvoice()`
   - `CryptoPaymentService->createInvoice()`
   - Сохранение заказа в `data/orders.json`

3. **Оплата**
   - Пользователь переходит по ссылке NOWPayments
   - Оплачивает криптовалютой
   - NOWPayments отправляет webhook на `crypto-webhook.php`

4. **Обработка оплаты**
   - `crypto-webhook.php` получает уведомление
   - `PaymentHandler->handleSuccessfulPayment()`
   - Создание билета через `TicketService->createTicket()`
   - Генерация QR через `TicketService->generateTicketQR()`
   - Отправка билета пользователю

5. **Использование билета**
   - Пользователь показывает QR-код на входе
   - Сканирование и валидация через `TicketService->validateTicket()`
   - Помечание билета как использованного

## ⚠️ Известные проблемы

1. **Deprecated warnings** в QR-генерации
   - Не критично, библиотека работает
   - Можно обновить `simplesoftwareio/simple-qrcode` позже

2. **Webhook URL указывает на Heroku**
   - Нужно обновить для продакшена
   - Или использовать переменную окружения

3. **NOWPayments не настроен**
   - Требуется регистрация и настройка API ключей

## 📝 Следующие шаги

1. ✅ Настроить NOWPayments API ключи
2. ✅ Обновить webhook URL для продакшена
3. ✅ Протестировать полный цикл: бронирование → оплата → QR-билет
4. ✅ Настроить мониторинг и логирование

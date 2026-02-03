# Настройка NOWPayments для криптоплатежей

## Текущий статус
❌ NOWPayments API ключи не настроены в `.env`

## Шаг 1: Регистрация в NOWPayments

1. Перейдите на https://nowpayments.io/
2. Зарегистрируйтесь или войдите в аккаунт
3. Перейдите в **Dashboard** → **API Settings**

## Шаг 2: Получение API ключей

1. В разделе **API Settings** найдите:
   - **API Key** (секретный ключ)
   - **Public Key** (публичный ключ, опционально)
   - **IPN Secret** (секрет для webhook)

2. Скопируйте ключи

## Шаг 3: Настройка .env файла

Откройте `bot/.env` и добавьте:

```env
# Crypto Payment Configuration (NOWPayments)
NOWPAYMENTS_API_KEY=ваш_api_ключ_здесь
NOWPAYMENTS_PUBLIC_KEY=ваш_публичный_ключ_здесь
NOWPAYMENTS_WEBHOOK_SECRET=ваш_ipn_secret_здесь
```

## Шаг 4: Настройка Webhook в NOWPayments

1. Перейдите в **Dashboard** → **IPN Settings**
2. Добавьте webhook URL:
   - **Для локального тестирования**: используйте ngrok или localtunnel
     ```
     https://ваш-ngrok-url.ngrok.io/crypto-webhook.php
     ```
   - **Для продакшена**: ваш домен
     ```
     https://ваш-домен.com/crypto-webhook.php
     ```
3. Сохраните настройки

## Шаг 5: Тестирование подключения

```bash
cd winter-sauna-bot/bot
php test-nowpayments.php
```

Должно показать:
- ✅ Доступные валюты получены
- ✅ Инвойс создан
- ✅ Курсы валют получены

## Шаг 6: Тестирование полного функционала

```bash
php test-full-functionality.php
```

## Поддерживаемые валюты

NOWPayments поддерживает множество криптовалют:
- **USDTTRC20** (Tether на TRON) - используется по умолчанию
- BTC, ETH, LTC и другие

## Важные настройки

### Минимальная сумма платежа
- NOWPayments имеет минимальные суммы для разных валют
- Для USDTTRC20: обычно $5-10 USD
- В коде установлен минимум $15 USD

### Webhook URL
Убедитесь, что webhook URL:
- Доступен из интернета (не localhost)
- Использует HTTPS
- Указывает на правильный файл: `/crypto-webhook.php`

## Проверка работы

После настройки протестируйте:

1. **Создание инвойса**:
   - Отправьте боту `/booking`
   - Выберите услугу
   - Нажмите "Оплатить криптовалютой"

2. **Оплата**:
   - Перейдите по ссылке оплаты
   - Оплатите через NOWPayments
   - Дождитесь подтверждения

3. **Получение QR-билета**:
   - После оплаты бот автоматически отправит QR-билет
   - Проверьте, что QR-код валиден

## Устранение проблем

### Ошибка "API key invalid"
- Проверьте правильность ключа в `.env`
- Убедитесь, что нет лишних пробелов
- Проверьте, что ключ активен в NOWPayments dashboard

### Webhook не приходит
- Проверьте, что URL доступен из интернета
- Убедитесь, что используется HTTPS
- Проверьте логи в NOWPayments dashboard

### Ошибка создания инвойса
- Проверьте минимальную сумму (должна быть >= $15)
- Убедитесь, что валюта поддерживается
- Проверьте баланс в NOWPayments аккаунте

## Полезные ссылки

- NOWPayments Dashboard: https://nowpayments.io/dashboard
- API Documentation: https://documenter.getpostman.com/view/7907941/T1LJjU52
- Support: https://nowpayments.io/help

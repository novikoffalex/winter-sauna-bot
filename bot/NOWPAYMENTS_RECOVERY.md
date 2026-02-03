# Восстановление доступа к NOWPayments

## Быстрые шаги

### 1. Попробуйте восстановить пароль

1. Перейдите на https://nowpayments.io/login
2. Нажмите "Forgot Password" или "Reset Password"
3. Введите email, который вы использовали при регистрации
4. Проверьте почту и следуйте инструкциям

### 2. Проверьте вашу почту

Поищите в почте письма от:
- `nowpayments.io`
- `noreply@nowpayments.io`
- `support@nowpayments.io`

Обычно там есть:
- Подтверждение регистрации
- Приветственное письмо
- Уведомления о платежах

### 3. Проверьте сохраненные пароли в браузере

- Chrome: Settings → Passwords → поиск "nowpayments"
- Safari: Preferences → Passwords → поиск "nowpayments"
- Firefox: Settings → Privacy → Saved Logins

## Если не помните email

### Вариант 1: Вспомните, какой email вы использовали
- Рабочий email
- Личный email
- Email для бизнеса

### Вариант 2: Проверьте все почтовые ящики
Поищите письма от NOWPayments во всех ваших email аккаунтах

### Вариант 3: Свяжитесь с поддержкой

**Email поддержки:**
```
support@nowpayments.io
```

**Тема письма:**
```
Account Recovery - Need API Keys Access
```

**Текст письма (на английском):**

```
Dear NOWPayments Support,

I need help recovering access to my NOWPayments account. 
I cannot remember my login credentials (email/password) but need 
to access my API settings to retrieve my API keys for a Telegram 
bot integration project.

Could you please:
1. Help me identify which email address is associated with my account
2. Send password reset instructions to that email
3. Or provide alternative verification methods

I can provide any information needed to verify my identity, such as:
- Business name or project details
- Approximate registration date
- Any transaction history (if available)
- Payment methods used

Thank you for your assistance.

Best regards,
[Your Name]
[Your Contact Email]
[Your Phone Number - optional]
```

## Альтернативные способы связи

1. **Чат поддержки** (если доступен на сайте)
2. **Форма обратной связи**: https://nowpayments.io/help
3. **Telegram канал** (если есть): проверьте их соцсети

## Если аккаунт не найден

Возможно, вы еще не регистрировались. В этом случае:

1. Зарегистрируйтесь на https://nowpayments.io/signup
2. Подтвердите email
3. Получите API ключи в Dashboard → API Settings

## После восстановления доступа

1. Войдите в Dashboard
2. Перейдите в **API Settings**
3. Скопируйте:
   - API Key
   - Public Key (если есть)
   - IPN Secret
4. Добавьте в `bot/.env`:
   ```env
   NOWPAYMENTS_API_KEY=ваш_ключ
   NOWPAYMENTS_PUBLIC_KEY=ваш_публичный_ключ
   NOWPAYMENTS_WEBHOOK_SECRET=ваш_ipn_secret
   ```

## Полезные ссылки

- NOWPayments Login: https://nowpayments.io/login
- Password Reset: https://nowpayments.io/forgot-password (если доступно)
- Support: support@nowpayments.io
- Help Center: https://nowpayments.io/help

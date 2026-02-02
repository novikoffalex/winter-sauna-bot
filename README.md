<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Deploy to Laravel Cloud

This project is configured for deployment to Laravel Cloud. The main Laravel application serves the web routes, while the Telegram bot runs as a separate process.

### Deployment Steps

1. **Connect your repository** to Laravel Cloud
2. **Configure environment variables** in Laravel Cloud dashboard:
   - All bot-related environment variables from `bot/.env` should be added to Laravel Cloud environment
3. **Deploy the application** - Laravel Cloud will automatically:
   - Install Composer dependencies
   - Run migrations (if needed)
   - Start the Laravel web server

### Running the Bot Process

The bot runs as a separate process. To start it, use the provided script:

```bash
./scripts/run-bot.sh
```

Or manually:

```bash
cd bot
php -S 0.0.0.0:${PORT:-8000} -t .
```

In Laravel Cloud, you can configure a separate worker/process to run the bot using the `run-bot.sh` script.

## Run bot locally

To run the bot locally for development:

1. **Start the Laravel application**:
   ```bash
   php artisan serve
   ```
   This will start the Laravel web server (usually on http://localhost:8000)

2. **Start the bot in a separate terminal**:
   ```bash
   ./scripts/run-bot.sh
   ```
   Or manually:
   ```bash
   cd bot
   php -S 0.0.0.0:8001 -t .
   ```

3. **Configure webhook** (if needed):
   - Use ngrok or similar tool to expose your local bot server
   - Update webhook URL in Telegram Bot settings

### Bot Configuration

The bot configuration is located in the `bot/` directory. Make sure to:
- Copy `bot/env.example` to `bot/.env` (if exists)
- Configure all required environment variables
- Install bot dependencies: `cd bot && composer install`

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

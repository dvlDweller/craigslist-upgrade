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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Frontend Authentication Integration

The backend uses Laravel Sanctum cookie-based SPA authentication.

Local development origins:
- Frontend: http://localhost:3000
- Backend: http://localhost:8000

Authentication flow:
1. Request GET /sanctum/csrf-cookie.
2. Send browser requests with credentials enabled.
3. Send Accept: application/json.
4. Include the decoded XSRF-TOKEN cookie value in the X-XSRF-TOKEN header when the browser client does not handle it automatically.
5. Submit registration or login.
6. Reuse the session cookie for subsequent requests.
7. Call POST /api/v1/auth/logout to invalidate the session.

Production frontend/API domains, HTTPS, CORS origins, and the session cookie domain must be finalized during deployment.

## Seller Listing Management API

Authenticated seller endpoints (require `auth:sanctum` cookie session):

- `GET /api/v1/seller/listings` — paginated listings owned by the authenticated user (all statuses), newest first.
- `POST /api/v1/seller/listings` — create a draft listing owned by the authenticated user.
- `GET /api/v1/seller/listings/{listing}` — view an owned listing (draft/active/sold).
- `PATCH /api/v1/seller/listings/{listing}` — update permitted listing fields.
- `DELETE /api/v1/seller/listings/{listing}` — soft-delete an owned listing (204).

Protected fields (`user_id`, `status`, `published_at`, `currency`, `province`) cannot be changed by clients.

## Listing Lifecycle API

Authenticated seller endpoints (require `auth:sanctum` cookie session):

- `POST /api/v1/seller/listings/{listing}/publish` — validate and publish a draft listing (status becomes `active`, `published_at` set).
- `POST /api/v1/seller/listings/{listing}/mark-sold` — mark an active listing as sold.

Both endpoints return `409 Conflict` when the listing is not in the required source status.

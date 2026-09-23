# Log Report Backend

Laravel 13 JSON API for the Log Report application. It uses SQLite for local development and Laravel Sanctum for API authentication.

## Requirements

- PHP 8.3 or newer
- Composer

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

The API is then available at `http://localhost:8000/api`.

## API endpoints

| Method | Endpoint | Authentication | Description |
| --- | --- | --- | --- |
| `GET` | `/api/v1/health` | Public | Check whether the API is running |
| `GET` | `/api/v1/user` | Sanctum | Return the authenticated user |

## Frontend connection

Set `FRONTEND_URL` in `.env` to the frontend origin. The default is `http://localhost:5173`. CORS credentials are enabled for Sanctum's cookie-based SPA authentication.

The React frontend reads its API base URL from `VITE_API_URL`.

## Verification

```bash
php artisan test
vendor/bin/pint --dirty
composer audit
```

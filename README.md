# IT Support Log System: Backend

Laravel JSON API for the IT Support Log System. It uses Laravel Sanctum cookie (SPA) authentication and permission-based role access control (RBAC).

The matching React frontend lives in a separate repository/folder (see its README).

## Requirements

- PHP 8.3 or newer
- Composer
- A database: SQLite (easiest for local use) or PostgreSQL
- PHP extensions: `pdo_sqlite` (or `pdo_pgsql`), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`

Check with `php -v` and `composer -V`.

## Quick start

```bash
# 1. Install dependencies
composer install

# 2. Create your environment file
cp .env.example .env
php artisan key:generate

# 3. Configure .env (see "Environment" below)

# 4. Create the database tables
php artisan migrate

# 5. Load roles, permissions, users and lookup data
php artisan db:seed

# 6. Start the API
php artisan serve
```

The API is now available at `http://127.0.0.1:8000/api`.

### Using SQLite (simplest)

```bash
touch database/database.sqlite
```

In `.env`:

```env
DB_CONNECTION=sqlite
# Remove or comment out DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

### Using PostgreSQL

Create an empty database first, then set in `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=support_log
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

## Environment

These values must be set in `.env`. The cookie-auth ones are the most common source of problems.

```env
APP_URL=http://127.0.0.1:8000

# Frontend origin(s), comma-separated, with scheme
FRONTEND_URL=http://localhost:5173

# Frontend host:port WITHOUT scheme. Must match how you open the frontend.
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173

SESSION_DRIVER=database
SESSION_DOMAIN=null
```

Use `localhost` or `127.0.0.1` consistently. Opening the frontend on one and the API on the other breaks cookies.

If your seeders read initial passwords from the environment, set them before seeding:

```env
INITIAL_ADMIN_PASSWORD=choose-a-private-password
INITIAL_RESOURCE_PASSWORD=choose-another-password
```

Check `database/seeders/UserSeeder.php` for the exact variable names and the seeded email addresses. After seeding, those are the accounts you log in with.

## Roles and permissions

Access is controlled by **permissions** assigned to **roles**. Admins can change the assignments in the app under **Roles & Permissions** (`/admin/roles`).

Seeded roles: `admin`, `network_administrator`, `software_developer`.

| Permission | Allows |
|---|---|
| `manage_departments` | Create, edit and delete departments |
| `manage_item_types` | Manage item types |
| `manage_issue_types` | Manage issue types |
| `manage_users` | Manage user accounts |
| `manage_roles` | Edit which permissions each role has |
| `view_reports` | Organisation-wide dashboard and all logs |
| `create_support_logs` | Create support logs |
| `update_own_support_logs` | Edit logs the user created or is assigned to |
| `user_performance` | See the user performance section on the dashboard |

By default `admin` gets every permission. The other roles get `create_support_logs` and `update_own_support_logs`.

After changing a role's permissions, affected users must log out and back in.

## Authentication (cookie-based Sanctum)

1. `GET /sanctum/csrf-cookie` sets the `XSRF-TOKEN` and session cookies.
2. `POST /api/auth/login` with `{ "email": "...", "password": "..." }`.
3. Send the cookies and an `X-XSRF-TOKEN` header on every later request.
4. `GET /api/auth/me` returns the current user with `role.permissions`.
5. `POST /api/auth/logout` ends the session.

Always send `Accept: application/json`, otherwise Laravel redirects instead of returning JSON errors.

## Main endpoints

All routes are under `/api`.

| Area | Endpoints |
|---|---|
| Auth | `POST auth/login`, `GET auth/me`, `POST auth/logout` |
| Support logs | `GET/POST support-logs`, `GET/PUT/DELETE support-logs/{id}` |
| Departments | `GET departments`; `POST/PUT/DELETE` need `manage_departments` |
| Item types | `GET items`; `POST/PUT/DELETE` need `manage_item_types` |
| Issue types | `GET issues`; `POST/PUT/DELETE` need `manage_issue_types` |
| Users | `GET/POST users`, `GET/PUT/DELETE users/{id}` |
| Roles | `GET roles` |
| Permissions | `GET permissions`, `GET/PUT roles/{role_id}/permissions` |
| Dashboard | `GET dashboard/user-performance` (needs `user_performance`) |

Validation failures return `422` with `message` and field-level `errors`.

## Testing with Postman

1. Create an environment variable `base = http://127.0.0.1:8000`.
2. Add headers `Accept: application/json` and `Origin: http://localhost:5173` (it must match a `SANCTUM_STATEFUL_DOMAINS` entry).
3. Add a collection pre-request script that copies the CSRF cookie into the header:

   ```js
   const token = pm.cookies.get('XSRF-TOKEN');
   if (token) {
     pm.request.headers.upsert({ key: 'X-XSRF-TOKEN', value: decodeURIComponent(token) });
   }
   ```
4. Run `GET {{base}}/sanctum/csrf-cookie`, then `POST {{base}}/api/auth/login`.

## Common problems

| Symptom | Likely cause |
|---|---|
| `419 CSRF token mismatch` | Skipped `/sanctum/csrf-cookie`, or the frontend host does not match `SANCTUM_STATEFUL_DOMAINS` |
| `401` right after login | `localhost` vs `127.0.0.1` mismatch, or `SESSION_DOMAIN` is wrong |
| `403` on an endpoint | The user's role lacks the permission; fix it in Roles & Permissions, then log in again |
| HTML or a redirect instead of JSON | Missing `Accept: application/json` header |
| CORS error in the browser | `FRONTEND_URL` does not include the exact frontend origin |
| `could not find driver` | Missing `pdo_sqlite` / `pdo_pgsql` PHP extension |
| Changes to `.env` ignored | Run `php artisan config:clear` |

## Useful commands

```bash
php artisan migrate               # apply new migrations
php artisan db:seed               # run all seeders
php artisan db:seed --class=PermissionSeeder
php artisan route:list            # list all routes
php artisan config:clear          # clear cached config
php artisan tinker                # interactive console
php artisan test                  # run tests
```

> **Warning:** `php artisan migrate:fresh` deletes all data. Never run it against a database with real records.

## Support log statuses

`indoor_repairing`, `outdoor_repairing`, `solved`

`resolved_at` is set when a log becomes `solved`, and cleared if it is reopened. The dashboard's average resolution time is calculated from `created_at` to `resolved_at`.
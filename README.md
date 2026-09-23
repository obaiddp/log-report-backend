# Log Report Backend

Laravel 13 JSON API for the Asset Inspection Form application. It uses SQLite, Sanctum SPA cookie authentication, typed PHP enums, API resources, Form Requests, role middleware, factories, and deterministic seed data.

## Requirements

- PHP 8.3 or newer (the project is tested on PHP 8.5)
- Composer

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

The API base URL is `http://localhost:8000/api/v1`.

Seeded administrator credentials:

- Email: `admin@logreport.test`
- Password: `password`

The seeder also creates four departments, the technical personnel named Afaq, Waseem, Obaid, Zulfiqar, and Mudassar, sample users, assets, and inspections. Seeding is idempotent.

## Sanctum SPA authentication

The frontend must send credentialed requests and the CSRF header:

1. `GET /sanctum/csrf-cookie` with credentials enabled.
2. Send the URL-decoded `XSRF-TOKEN` cookie as `X-XSRF-TOKEN` on state-changing requests.
3. `POST /api/v1/auth/login`, then use the authenticated session cookie for API requests.
4. Send `Accept: application/json`, `Origin`, or `Referer` headers as appropriate.

`FRONTEND_URL` defaults to the local Vite origins on `localhost` and `127.0.0.1`. `SANCTUM_STATEFUL_DOMAINS` defaults to the local frontend/backend hosts. For production, set both values to the actual frontend origin(s). `FRONTEND_URL` may contain a comma-separated list of allowed origins.

## Response contract

- Singular resources are returned as JSON objects.
- Paginated lists use Laravel's standard `{data, links, meta}` envelope.
- Dates are ISO 8601 strings; date-only business fields use `YYYY-MM-DD`.
- Validation failures return `422` with `message` and field-level `errors`.
- Missing authentication returns `401`; insufficient role access returns `403`.
- User passwords, remember tokens, and Sanctum tokens are never exposed.

## Authorization

| Capability | Admin | Technician | User |
| --- | :---: | :---: | :---: |
| View assets, inspections, users, departments, and personnel | Yes | Yes | Yes |
| Manage users, departments, personnel, and assets | Yes | No | No |
| Create, update, and delete inspections | Yes | Yes | No |
| View reports and export CSV | Yes | Yes | No |

Inactive users cannot log in, and any existing authenticated session receives `403` on protected routes.

## Endpoints

All routes below are relative to `/api/v1`.

### Public and authentication

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/health` | Service readiness |
| `POST` | `/auth/login` | Authenticate with `email`, `password`, and optional `remember` |
| `GET` | `/auth/me` | Current user |
| `GET` | `/user` | Backwards-compatible alias of `/auth/me` |
| `POST` | `/auth/logout` | Invalidate the current session |

### Users

| Method | Endpoint | Minimum role | Description |
| --- | --- | --- | --- |
| `GET` | `/users` | Authenticated | Search/filter/paginate users |
| `POST` | `/users` | Admin | Create a user |
| `GET` | `/users/{user}` | Authenticated | View a user |
| `PUT/PATCH` | `/users/{user}` | Admin | Update a user |
| `DELETE` | `/users/{user}` | Admin | Delete an unlinked user |

### Departments

| Method | Endpoint | Minimum role | Description |
| --- | --- | --- | --- |
| `GET` | `/departments` | Authenticated | Search/filter/paginate departments |
| `POST` | `/departments` | Admin | Create a department |
| `GET` | `/departments/{department}` | Authenticated | View a department |
| `PUT/PATCH` | `/departments/{department}` | Admin | Update a department |
| `DELETE` | `/departments/{department}` | Admin | Delete an unlinked department |

### Technical personnel

| Method | Endpoint | Minimum role | Description |
| --- | --- | --- | --- |
| `GET` | `/technical-personnel` | Authenticated | Search/filter/paginate personnel |
| `POST` | `/technical-personnel` | Admin | Create personnel |
| `GET` | `/technical-personnel/{technical_personnel}` | Authenticated | View personnel |
| `PUT/PATCH` | `/technical-personnel/{technical_personnel}` | Admin | Update personnel |
| `DELETE` | `/technical-personnel/{technical_personnel}` | Admin | Delete unassigned personnel |

### Assets

| Method | Endpoint | Minimum role | Description |
| --- | --- | --- | --- |
| `GET` | `/assets` | Authenticated | Search/filter/paginate assets |
| `POST` | `/assets` | Admin | Create an asset |
| `GET` | `/assets/{asset}` | Authenticated | View an asset and its latest inspection |
| `PUT/PATCH` | `/assets/{asset}` | Admin | Update an asset |
| `DELETE` | `/assets/{asset}` | Admin | Delete an asset without inspection history |

For assets, `status` filters the mapped user's status. `department_id` also filters the mapped user's department. `date_from` and `date_to` filter `acquired_at`.

### Inspections

| Method | Endpoint | Minimum role | Description |
| --- | --- | --- | --- |
| `GET` | `/inspections` | Authenticated | Search/filter/paginate inspections |
| `POST` | `/inspections` | Admin/technician | Create an inspection |
| `GET` | `/inspections/{inspection}` | Authenticated | View an inspection |
| `PUT/PATCH` | `/inspections/{inspection}` | Admin/technician | Update an inspection |
| `DELETE` | `/inspections/{inspection}` | Admin/technician | Delete an inspection |

`created_by` is always taken from the authenticated user and cannot be spoofed. Repair inspections require `sub_category=in_house|out_house`; new-purchase inspections must leave it null. `service_mode` is accepted as a compatibility alias for `sub_category`.

### Reports

| Method | Endpoint | Minimum role | Description |
| --- | --- | --- | --- |
| `GET` | `/reports/summary` | Admin/technician | JSON report metrics and chart arrays |
| `GET` | `/reports/export` | Admin/technician | Stream filtered inspection rows as CSV |

Report periods can be selected in one of these ways:

- `range=daily&date=YYYY-MM-DD`
- `range=weekly` for the current Monday-Sunday week
- `date_from=YYYY-MM-DD&date_to=YYYY-MM-DD` for a custom daily period
- No period parameters defaults to today

Named periods cannot be combined with `date_from`/`date_to`. Reports also accept `search`, `type`, `department_id`, `user_id`, `status` (inspection status), `category`, and `technical_personnel_id`.

The summary response has these top-level arrays/objects:

- `period`
- `metrics`
- `status_breakdown`
- `asset_distribution`
- `ram_usage_by_department`
- `purchase_vs_repair`
- `inspection_trend`
- `technician_workload`

Asset and user metrics describe the current filtered register. Inspection, purchase/repair, trend, and workload metrics use the selected period.

## List filters, sorting, and pagination

All list endpoints accept `page`, `per_page` (maximum 100), `sort_by`, and `sort_direction` (`asc` or `desc`). Sort fields are allow-listed per endpoint and always have a stable ID tie-breaker.

| Endpoint | Additional parameters |
| --- | --- |
| `/users` | `search`, `department_id`, `status`, `role` |
| `/departments` | `search`, `status` |
| `/technical-personnel` | `search`, `department_id`, `status` |
| `/assets` | `search`, `type`, `department_id`, `user_id`, `status`, `date_from`, `date_to` |
| `/inspections` | `search`, `status`, `type`, `department_id`, `user_id`, `category`, `technical_personnel_id`, `date_from`, `date_to` |

Allowed sort fields:

| Resource | `sort_by` values |
| --- | --- |
| Users | `name`, `email`, `designation`, `territory`, `status`, `role`, `created_at`, `updated_at` |
| Departments | `name`, `code`, `status`, `created_at`, `updated_at` |
| Technical personnel | `name`, `email`, `phone`, `designation`, `specialization`, `status`, `created_at`, `updated_at` |
| Assets | `asset_tag`, `type`, `brand`, `model`, `serial_number`, `ram_gb`, `acquired_at`, `created_at`, `updated_at` |
| Inspections | `problem_id`, `status`, `category`, `sub_category`, `inspection_date`, `created_at`, `updated_at` |

## Domain values

- User roles: `admin`, `technician`, `user`
- Status values: `active`, `inactive`
- Asset types: `laptop`, `printer`, `projector`, `computer`, `it_support_equipment`
- Inspection statuses: `sold`, `in_progress`, `indoor_repair`, `outdoor_repair`
- Inspection categories: `new_purchase`, `repair`
- Inspection sub-categories: `in_house`, `out_house`

## Safe deletion

Departments, users, technical personnel, and assets are not hard-deleted while dependent records exist. The API returns a field-level `422` validation error in those cases. Inspection deletion is allowed for administrators and technicians.

## Verification

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
php artisan test --compact
composer validate
composer audit
```

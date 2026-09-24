# Log Report Backend

Laravel 13 JSON API for the Asset Inspection Form application. It uses SQLite, typed PHP enums, API resources, Form Requests, factories, and deterministic seed data.

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

The seeder creates four departments, the technical personnel named Afaq, Waseem, Obaid, Zulfiqar, and Mudassar, directory users, assets, and inspections. Seeding is idempotent.

## Public API

Authentication and login screens are intentionally not part of this application. All versioned API endpoints are public for the current internal-network deployment model. The `role`, `status`, and `territory` fields on users remain organizational metadata and can be used by a future authorization layer.

Set `FRONTEND_URL` in `.env` to a comma-separated list of allowed frontend origins. Local defaults include `http://localhost:5173` and `http://127.0.0.1:5173`.

> This open API is appropriate only for a trusted local/internal network. Add authentication and authorization before exposing write endpoints publicly.

## Response contract

- Singular resources are returned as JSON objects.
- Paginated lists use Laravel's standard `{data, links, meta}` envelope.
- Dates are ISO 8601 strings; date-only business fields use `YYYY-MM-DD`.
- Validation failures return `422` with `message` and field-level `errors`.
- User passwords and other sensitive fields are never exposed.

## Endpoints

All routes below are relative to `/api/v1`.

### Health

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/health` | Service readiness |

### Users

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/users` | Search, filter, and paginate users |
| `POST` | `/users` | Create a directory user |
| `GET` | `/users/{user}` | View a user |
| `PUT/PATCH` | `/users/{user}` | Update a user |
| `DELETE` | `/users/{user}` | Delete an unlinked user |

### Departments

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/departments` | Search, filter, and paginate departments |
| `POST` | `/departments` | Create a department |
| `GET` | `/departments/{department}` | View a department |
| `PUT/PATCH` | `/departments/{department}` | Update a department |
| `DELETE` | `/departments/{department}` | Delete an unlinked department |

### Technical personnel

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/technical-personnel` | Search, filter, and paginate personnel |
| `POST` | `/technical-personnel` | Create personnel |
| `GET` | `/technical-personnel/{technical_personnel}` | View personnel |
| `PUT/PATCH` | `/technical-personnel/{technical_personnel}` | Update personnel |
| `DELETE` | `/technical-personnel/{technical_personnel}` | Delete unassigned personnel |

### Assets

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/assets` | Search, filter, and paginate assets |
| `POST` | `/assets` | Create an asset |
| `GET` | `/assets/{asset}` | View an asset and its latest inspection |
| `PUT/PATCH` | `/assets/{asset}` | Update an asset |
| `DELETE` | `/assets/{asset}` | Delete an asset without inspection history |

For assets, `status` filters the mapped user's status. `department_id` filters the mapped user's department. `date_from` and `date_to` filter `acquired_at`.

### Inspections

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/inspections` | Search, filter, and paginate inspections |
| `POST` | `/inspections` | Create an inspection |
| `GET` | `/inspections/{inspection}` | View an inspection |
| `PUT/PATCH` | `/inspections/{inspection}` | Update an inspection |
| `DELETE` | `/inspections/{inspection}` | Delete an inspection |

Repair inspections require `sub_category=in_house|out_house`; new-purchase inspections must leave it null. `service_mode` is accepted as a compatibility input alias for `sub_category`.

### Reports

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/reports/summary` | JSON report metrics and chart arrays |
| `GET` | `/reports/export` | Stream filtered inspection rows as CSV |

Report periods can be selected in one of these ways:

- `range=daily&date=YYYY-MM-DD`
- `range=weekly` for the current Monday-Sunday week
- `date_from=YYYY-MM-DD&date_to=YYYY-MM-DD` for a custom daily period
- No period parameters defaults to today

Named periods cannot be combined with `date_from`/`date_to`. Reports also accept `search`, `type`, `department_id`, `user_id`, `status`, `category`, and `technical_personnel_id`.

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

## Domain values

- User roles: `admin`, `technician`, `user`
- Status values: `active`, `inactive`
- Asset types: `laptop`, `printer`, `projector`, `computer`, `it_support_equipment`
- Inspection statuses: `sold`, `in_progress`, `indoor_repair`, `outdoor_repair`
- Inspection categories: `new_purchase`, `repair`
- Inspection sub-categories: `in_house`, `out_house`

## Safe deletion

Departments, users, technical personnel, and assets are not hard-deleted while dependent records exist. The API returns a field-level `422` validation error in those cases. Inspection deletion removes the inspection record.

## Verification

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
php artisan test --compact
composer validate
composer audit
```

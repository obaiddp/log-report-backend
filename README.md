# IT Support Log System Backend

Laravel 13 JSON API for the IT Support Log System. The application uses PostgreSQL-compatible migrations, Sanctum SPA cookie/session authentication, API resources, Form Requests, policies, factories, and idempotent seed data. SQLite is supported for the automated test suite.

## Requirements

- PHP 8.3 or newer (the project is tested on PHP 8.5)
- Composer
- PostgreSQL for production-like deployments

## Setup

1. Copy `.env.example` to `.env` and set `APP_KEY`, the PostgreSQL connection values, `FRONTEND_URL`, and `SANCTUM_STATEFUL_DOMAINS`.
2. Set `INITIAL_ADMIN_PASSWORD` to a private value (12+ characters is recommended). Set `INITIAL_RESOURCE_PASSWORD` as well before creating the initial technical-resource accounts. The seeder never uses a known default password and stops with a setup message if a required initial password is missing.
3. Run additive migrations with `php artisan migrate`. Do not use `migrate:fresh` against a database containing real data.
4. Run `php artisan db:seed` when the initial configuration and accounts are required. Seeding is idempotent and does not remove legacy assets or inspections.
5. Start the API with `php artisan serve`.

The API base URL is `http://localhost:8000/api/v1`.

## Authentication and browser security

The versioned API uses Laravel Sanctum first-party SPA authentication:

- `GET /sanctum/csrf-cookie` starts the web session and sets the CSRF cookie.
- The frontend sends the session cookie and `X-XSRF-TOKEN` header on subsequent requests.
- `POST /api/v1/auth/login` accepts `{email, password, remember?}` and is protected by a five-attempt-per-minute email/IP throttle.
- `POST /api/v1/auth/logout` logs out the current session.
- `GET /api/v1/auth/me` returns the current user.
- CORS allows the comma-separated `FRONTEND_URL` origins with credentials for both API routes and `/sanctum/csrf-cookie`. `SANCTUM_STATEFUL_DOMAINS` must contain the frontend host/port without a scheme.

Login requires an **active and email-verified** account. Inactive and unverified accounts receive `403`; invalid credentials receive the normal `422` validation response. The additive role migration marks pre-existing active legacy users as verified because the earlier application had no verification workflow; newly created unverified users remain blocked. There is no public registration endpoint. Passwords are hashed and are never returned.

## Response contract

- Singular resources are JSON objects.
- Paginated lists use Laravel's standard `{data, links, meta}` envelope.
- User objects consistently expose `id`, `name`, `email`, `role`, `department_id`, `department`, and `status` (plus non-sensitive directory metadata where appropriate).
- Support-log resources expose the fields required by the frontend: `id`, `ticket_number`, `issue_date`, `initiated_by`, `department_id`, `department`, `issue_types`, `item_type_id`, `item_type`, `description`, `status`, `priority`, `assigned_to`, `assigned_resource`, `created_by`, `creator`, `resolution_notes`, `internal_remarks`, `resolved_at`, `closed_at`, `created_at`, `updated_at`, and `assignments`.
- `issue_types` is an array of `{id, name, status}` config objects; `item_type`, `department`, `assigned_resource`, and `creator` are nested objects. `assignments` is an ordered history array containing assignment IDs, resource/assigner IDs and nested users, `assigned_at`, and nullable `unassigned_at`.
- Date-only business fields use `YYYY-MM-DD`; timestamps use ISO 8601.
- Validation failures return `422` with `message` and field-level `errors`.
- CSV cells beginning with spreadsheet formula characters are prefixed with an apostrophe.

### Canonical roles

New API writes and responses use only:

- `admin`
- `technical_resource`

Legacy `technician` and `user` values are read and treated as `technical_resource`; the additive role migration converts them where the database permits. Technical resources cannot change roles or promote users.

## Authorization

- **Admin**: manage all support logs, assign/reassign resources, archive logs, export reports, manage users/departments/legacy personnel, and manage issue/item configuration.
- **Technical Resource**: create support logs, view the shared support-log queue, and update status, resolution notes, and internal remarks only on logs assigned to them. Technical resources can self-assign a log they create when no assignee is supplied. Internal remarks are returned only to an admin or the assigned technical resource.
- **Support-log deletion** is admin-only and is implemented as a soft archive. Assignment history is never discarded during reassignment.
- Legacy asset/inspection routes remain available for historical compatibility, but are authenticated, deprecated, and must not be used as the new support-log workflow. Their tables and data are preserved.

## Support-log workflow

Statuses are code-defined in v1 and are intentionally not configurable:

`open`, `in_progress`, `indoor_repair`, `outdoor_repair`, `resolved`, `closed`, `cancelled`

Priorities are: `low`, `medium`, `high`, `critical`.

The server generates a human-readable, unique ticket number in the form `ITL-YYYYMMDD-XXXXXXXXXX`. Creation timestamps are server-owned. `issue_date` cannot be in the future. A resolution note is required when entering `resolved` or `closed`. `resolved_at` is set on resolution and `closed_at` on closure; reopening an active workflow clears terminal timestamps.

The initial transition policy is:

- `open` may move to `in_progress`, `indoor_repair`, `outdoor_repair`, `resolved`, or `cancelled`.
- `in_progress` may move to either repair state, `resolved`, or `cancelled`.
- Repair states may move to another repair state, `in_progress`, `resolved`, or `cancelled`.
- `resolved` may be closed, reopened to an active repair state, or cancelled.
- `closed` and `cancelled` are terminal; an administrator must archive/recreate a log rather than silently rewriting terminal history.

Status values are kept in `App\Enums\SupportLogStatus`; changing the code-defined workflow is a code change, not a configuration CRUD operation.

## Endpoints

All routes below are relative to `/api/v1` and, except for health and login, require Sanctum authentication.

### Health and authentication

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/health` | Public service readiness |
| `GET` | `/sanctum/csrf-cookie` | Sanctum SPA CSRF/session bootstrap (outside the `/api/v1` prefix) |
| `POST` | `/auth/login` | Start a session |
| `POST` | `/auth/logout` | End the session |
| `GET` | `/auth/me` | Current user |

### Support logs

| Method | Endpoint | Authorization |
| --- | --- | --- |
| `GET` | `/support-log-options` | Authenticated safe lookup for form options; admins also receive inactive historical options |
| `GET` | `/support-logs` | Authenticated shared queue |
| `POST` | `/support-logs` | Admin or technical resource |
| `GET` | `/support-logs/{id}` | Authenticated shared queue |
| `PUT/PATCH` | `/support-logs/{id}` | Admin, or assigned technical resource for permitted fields |
| `DELETE` | `/support-logs/{id}` | Admin; soft archive |

List parameters: `search`, `date_from`, `date_to`, `department_id`, `issue_type_id`, `item_type_id`, `status`, `priority`, `assigned_to`, `initiated_by`, `ticket_number`, `page`, `per_page` (maximum 100), `sort_by`, and `sort_direction`. Sort fields are allow-listed.

### Configuration and directory

| Method | Endpoint | Authorization |
| --- | --- | --- |
| `GET` | `/issue-types` | Authenticated lookup |
| `POST` | `/issue-types` | Admin |
| `GET/PUT/PATCH/DELETE` | `/issue-types/{id}` | Authenticated read; admin mutation |
| `GET` | `/item-types` | Authenticated lookup |
| `POST` | `/item-types` | Admin |
| `GET/PUT/PATCH/DELETE` | `/item-types/{id}` | Authenticated read; admin mutation |
| `GET` | `/departments` | Authenticated lookup |
| `POST` | `/departments` | Admin |
| `GET/PUT/PATCH/DELETE` | `/departments/{id}` | Authenticated read; admin mutation |
| `GET` | `/users` | Authenticated directory lookup |
| `POST` | `/users` | Admin |
| `GET/PUT/PATCH/DELETE` | `/users/{id}` | Authenticated safe read; admin mutation; passwords are write-only |
| `GET` | `/technical-personnel` | Authenticated lookup (deprecated legacy directory) |
| `POST` | `/technical-personnel` | Admin (deprecated legacy directory) |
| `GET/PUT/PATCH/DELETE` | `/technical-personnel/{id}` | Authenticated safe read; admin mutation (deprecated legacy directory) |

Issue types and item types have `active`/`inactive` status fields. Config records referenced by support-log history cannot be hard-deleted.

### Dashboard and reports

These endpoints are authenticated and admin-oriented. They accept `date_from`, `date_to`, and the common support-log filters where meaningful. Date filters use `issue_date`; dashboard `created_*` metrics use `created_at`.

| Method | Endpoint | Response shape |
| --- | --- | --- |
| `GET` | `/dashboard/summary` | `{filters, period, metrics, overdue_rule, resolution_time_basis, generated_at}` |
| `GET` | `/reports/by-department` | `{data, filters, meta}`; each row has department and status counts |
| `GET` | `/reports/by-resource` | `{data, filters, meta}`; includes an unassigned row |
| `GET` | `/reports/by-issue-type` | `{data, filters, meta}`; counts the relational pivot without duplicate log rows |
| `GET` | `/reports/by-item` | `{data, filters, meta}`; item-category totals and status counts |
| `GET` | `/reports/by-status` | `{data, filters, meta}`; includes every status, including zero counts |
| `GET` | `/reports/export` | Streamed support-log CSV |

Dashboard metrics include `total`, `open`, `in_progress`, `indoor_repair`, `outdoor_repair`, `resolved`, `closed`, `cancelled`, `unresolved`, `unassigned`, `critical`, `created_today`, `created_this_week`, `created_this_month`, `overdue`, and `average_resolution_time_hours`. Overdue means an active log older than seven days from `created_at`. Average resolution time is calculated only when both `created_at` and `resolved_at` are present. The summary also includes status, priority, department, resource, issue-type, and item breakdowns for the dashboard charts. Reports aggregate in the database and do not serialize all matching rows into JSON.

## Migrations and legacy data

The new tables are additive:

- `issue_types`
- `item_types`
- `support_logs`
- `support_log_issue_types`
- `support_log_assignments`

The role normalization and Sanctum personal-access-token migrations are also additive. Existing `assets`, `inspections`, `technical_personnel`, and their rows are not dropped. Foreign keys use restrictive deletion for historical actors/configuration and soft deletion is used for support logs.

## Seed data

`DatabaseSeeder` idempotently creates the baseline departments, initial issue/item options, four technical-resource users (`Afaq`, `Haris`, `Obaid`, and `Zulfiqar`), and `Mudassir` as the IT Department Head administrator who may also be assigned work. It never creates a known default password.

## Current limitations and decisions

- Attachments/file uploads are not included in v1 because no secure storage and malware-validation workflow has been defined.
- Statuses and priorities are code-defined enums; only departments, issue types, and item types are administrator-configurable.
- Support-log deletion is a soft archive. Historical rows, assignment history, and legacy asset/inspection data are retained.
- User passwords are administrator-managed; there is no public registration or self-service password-reset flow in v1.
- PostgreSQL is the deployment target. The automated suite uses isolated SQLite because a local PostgreSQL role/password was not available during verification.

## Verification

The test suite uses an in-memory SQLite database configured by `phpunit.xml`:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
composer validate
```

Use a disposable database under `/tmp/opencode` for migration smoke tests. Never run `migrate:fresh`, `migrate:reset`, 
or another destructive command against `database/database.sqlite` or a real PostgreSQL database.




============================

---- Current Permissions ----

 id |          name           |     created_at      |     updated_at      
----+-------------------------+---------------------+---------------------
  1 | manage_departments      
  2 | manage_item_types       
  3 | manage_issue_types

  4 | manage_users

  5 | view_reports            
  6 | create_support_logs     
  7 | update_own_support_logs 
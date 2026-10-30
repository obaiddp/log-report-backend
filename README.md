# CEF365: Backend

## log-report and  asset inspection form
Laravel JSON APIs for the 
- IT Support Log System (only for internal ICT department usage).
- Asset/Item Inspection form (form for ICT department and then pdf for admin department)
- It uses Laravel Sanctum cookie (SPA) authentication
- permission-based role access control (RBAC).
- long polling used for asset inspection

## Database overview

Three areas: access control (roles and permissions), IT support logs, and asset inspections.

```mermaid
erDiagram
    ROLES ||--o{ USERS : assigns
    ROLES ||--o{ ROLE_PERMISSIONS : grants
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : contains

    DEPARTMENTS o|--o{ USERS : employs
    DEPARTMENTS ||--o{ SUPPORT_LOGS : categorizes
    DEPARTMENTS ||--o{ ASSET_INSPECTIONS : categorizes

    ITEM_TYPES ||--o{ SUPPORT_LOGS : concerns
    ITEM_TYPES ||--o{ ASSET_INSPECTIONS : identifies

    USERS ||--o{ SUPPORT_LOGS : creates
    USERS o|--o{ SUPPORT_LOGS : currently_assigned_to

    SUPPORT_LOGS ||--o{ SUPPORT_LOG_ASSIGNMENTS : assignment_history
    USERS ||--o{ SUPPORT_LOG_ASSIGNMENTS : assigned_to
    USERS ||--o{ SUPPORT_LOG_ASSIGNMENTS : assigned_by

    SUPPORT_LOGS ||--o{ SUPPORT_LOG_ISSUE_TYPES : classified_by
    ISSUE_TYPES ||--o{ SUPPORT_LOG_ISSUE_TYPES : categorizes

    USERS ||--o{ ASSET_INSPECTIONS : prepares
    USERS ||--o{ ASSET_INSPECTIONS : approves

    ROLES {
        bigint id PK
        varchar name UK
    }

    PERMISSIONS {
        bigint id PK
        varchar name UK
    }

    ROLE_PERMISSIONS {
        bigint id PK
        bigint role_id FK
        bigint permission_id FK
    }

    USERS {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        bigint role_id FK
        bigint department_id FK
        varchar designation
        timestamp email_verified_at
    }

    DEPARTMENTS {
        bigint id PK
        varchar name
        varchar code UK
    }

    ITEM_TYPES {
        bigint id PK
        varchar name
        varchar has_specification
    }

    ISSUE_TYPES {
        bigint id PK
        varchar name
    }

    SUPPORT_LOGS {
        bigint id PK
        varchar ticket_number UK
        date issue_date
        varchar initiated_by
        bigint department_id FK
        bigint item_type_id FK
        varchar status
        text issue_details
        bigint assigned_to FK
        bigint created_by FK
        timestamp resolved_at
        timestamp closed_at
    }

    SUPPORT_LOG_ASSIGNMENTS {
        bigint id PK
        bigint support_log_id FK
        bigint assigned_to FK
        bigint assigned_by FK
        timestamp assigned_at
        timestamp unassigned_at
    }

    SUPPORT_LOG_ISSUE_TYPES {
        bigint id PK
        bigint support_log_id FK
        bigint issue_type_id FK
    }

    ASSET_INSPECTIONS {
        bigint id PK
        varchar username
        varchar designation
        varchar email
        varchar cell
        bigint department_id FK
        varchar territory
        bigint item_type_id FK
        varchar model
        varchar brand
        varchar serial_number
        varchar item_tag
        varchar ram
        varchar generation
        varchar hard_drive
        varchar status
        text item_problem
        text it_remarks
        text admin_remarks
        varchar approval_status
        bigint prepared_by FK
        bigint admin_user FK
        boolean prepared_by_digital_signature
        boolean user_digital_signature
        boolean admin_digital_signature
        timestamp prepared_signed_at
        timestamp user_signed_at
        timestamp admin_signed_at
        text return_reason
    }

    NOTIFICATIONS {
        uuid id PK
        varchar type
        varchar notifiable_type
        bigint notifiable_id
        text data
        timestamp read_at
    }

    PUSH_SUBSCRIPTIONS {
        bigint id PK
        varchar subscribable_type
        bigint subscribable_id
        varchar endpoint UK
        varchar public_key
        varchar auth_token
        varchar content_encoding
    }
```

## -----------------------------------

## -----------------------------------

--- Current `Item_TYPES`

| Category | ~Tickets | Category | ~Tickets |
|---|---|---|---|
| Laptop | ~385 | AV & Cabling | ~11 |
| Email | ~65 | Internet (→Network) | ~11 |
| Desktop | ~70 | Procurement | ~14 |
| Display & Projection | ~38 | Scanner | ~10 |
| Meeting | ~50 | CCTV | ~9 |
| Network | ~23 | Support | ~9 |
| Data & Backup | ~31 | Servers | ~8 |
| Printer | ~34 | Accessories / Components / Mobile / Accounts / Applications | ~25 |

--- Current `STATUS`
closed
in_progress
indoor_repairing
outdoor_repairing
resolved

--- Current `Issue Type`
Hardware
Software
Hardware & Software


## ============================= Current Roles and permission assigning
```
 Illuminate\Support\Collection {#7731
    all: [
      [
        "super_admin",
        [
          "manage_dashboard",
          "dashboard_support_logs",
          "dashboard_asset_inspections",
          "user_performance",
          "manage_support_logs",
          "manage_asset_inspections",
          "create_asset_inspections",
          "manage_departments",
          "manage_item_types",
          "manage_issue_types",
          "manage_users",
          "manage_role_permissions",
        ],
      ],
      [
        "ict_manager",
        [
          "manage_dashboard",
          "dashboard_support_logs",
          "dashboard_asset_inspections",
          "user_performance",
          "manage_support_logs",
          "manage_asset_inspections",
          "create_asset_inspections",
          "manage_item_types",
          "manage_issue_types",
        ],
      ],
      [
        "ict_staff",
        [
          "manage_dashboard",
          "dashboard_support_logs",
          "dashboard_asset_inspections",
          "manage_support_logs",
          "manage_asset_inspections",
          "create_asset_inspections",
        ],
      ],
      [
        "admin_approver",
        [
          "manage_dashboard",
          "dashboard_asset_inspections",
          "manage_asset_inspections",
        ],
      ],
    ],
  }
```
## ======================================== Dashboard

What each returns
Endpoint	Who	Data
/dashboard/support-logs	super_admin, ict_manager (all logs), ict_staff (own)	total, by_status, last_7_days
/dashboard/asset-inspections	everyone with the permission	total, by_approval_status, needs_my_signature, returned_to_me
/dashboard/user-performance	super_admin, ict_manager	unchanged
 
## ==== Dashboard permissions

// for dashboard tab in frontend
'manage_dashboard',

'dashboard_support_logs',
'dashboard_asset_inspections',
'user_performance',
'dashboard_department_view',
'dashboard_all_view',

## ==== Dashboard

The design

Instead of hard-coding “role X sees dashboard Y”, every dashboard endpoint takes a scope query parameter (?scope=mine, department or all):

Scope	What it counts	Permission needed
mine	Records you are involved in	none (everyone)
department	Records involving anyone in your department	dashboard_department_view
all	Everything	dashboard_all_view

Two new permissions give you these tabs per role:

Role	Tabs	Content
super_admin	Mine, Department, All	support logs and inspections
ict_manager	Mine, Department, All	support logs and inspections
ict_staff	Mine	support logs and inspections (own only)
admin_approver	Mine, Department	inspections only (no support-log permission)

====


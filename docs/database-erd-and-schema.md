# PICKLED Database ERD and Schema Documentation

Generated from the application source, especially `database/schema.sql`, `database/pickled.sql`, repository classes in `app/repositories`, and CRUD/admin flows in `app/services` and `admin`.

Important source note: `database/schema.sql` is the most complete schema because it includes `events`, `notifications`, and `admin_logs`, all of which are used by repository/service code. `database/pickled.sql` appears to be an older phpMyAdmin dump and contains only `users`, `password_resets`, `carts`, `cart_items`, `courts`, `booking_variants`, `sessions`, `bookings`, and `booking_items`.

## A. Entity Relationship Diagram

```mermaid
erDiagram
    USERS ||--o{ PASSWORD_RESETS : requests
    USERS ||--o| CARTS : owns
    USERS ||--o{ BOOKINGS : creates
    USERS ||--o{ EVENTS : creates
    USERS ||--o{ NOTIFICATIONS : receives
    USERS ||--o{ ADMIN_LOGS : performs

    COURTS ||--o{ BOOKING_VARIANTS : offers
    BOOKING_VARIANTS ||--o{ SESSIONS : schedules
    SESSIONS ||--o{ CART_ITEMS : reserved_in
    SESSIONS ||--o{ BOOKING_ITEMS : booked_as
    CARTS ||--o{ CART_ITEMS : contains
    BOOKINGS ||--o{ BOOKING_ITEMS : contains

    USERS {
        int id PK
        string name
        string email UK
        string password_hash
        string role
        datetime created_at
    }

    PASSWORD_RESETS {
        int id PK
        int user_id FK
        string token_hash UK
        datetime expires_at
        datetime used_at
        datetime created_at
    }

    CARTS {
        int id PK
        int user_id FK_UK
        datetime started_at
        datetime expires_at
        datetime created_at
        datetime updated_at
    }

    CART_ITEMS {
        int id PK
        int cart_id FK
        int session_id FK
        int quantity
        decimal unit_price
        datetime created_at
    }

    COURTS {
        int id PK
        string name
        string slug UK
    }

    BOOKING_VARIANTS {
        int id PK
        int court_id FK
        string slug UK
        string name
        string category
        string duration_label
        decimal price
        int participants_limit
        int capacity
        string image
        boolean active
    }

    SESSIONS {
        int id PK
        int variant_id FK
        string session_date
        string session_time
        int capacity
        int booked_count
    }

    BOOKINGS {
        int id PK
        int user_id FK
        string reference UK
        string status
        decimal subtotal
        decimal payment_fee
        decimal total
        string payment_method
        string payment_status
        text notes
        string cancellation_label
        datetime created_at
    }

    BOOKING_ITEMS {
        int id PK
        int booking_id FK
        int session_id FK
        string variant_id
        string name
        string court
        string category
        string duration_label
        string booking_date
        string booking_time
        int quantity
        decimal unit_price
        string image
    }

    EVENTS {
        int id PK
        string title
        text description
        string event_date
        string event_time
        string location
        int max_participants
        int current_participants
        string status
        int created_by FK
        datetime created_at
        datetime updated_at
    }

    NOTIFICATIONS {
        int id PK
        int user_id FK
        string title
        text message
        string type
        boolean is_read
        string link
        datetime created_at
    }

    ADMIN_LOGS {
        int id PK
        int admin_id FK
        string action
        string entity_type
        int entity_id
        json details
        datetime created_at
    }
```

## B. Database Schema Documentation

### Table: users

Purpose: Stores player, coach, and admin accounts used for authentication, authorization, booking ownership, notifications, and admin audit activity.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | User identifier. |
| name | VARCHAR(120) |  | Display/full name. |
| email | VARCHAR(160) | UNIQUE | Login email; repository lowercases email before storage/search. |
| password_hash | VARCHAR(255) |  | Hashed password generated with PHP `password_hash`. |
| role | VARCHAR(40) |  | Application role: code uses `player`, `coach`, and `admin`. |
| created_at | DATETIME |  | Account creation timestamp. |

### Table: password_resets

Purpose: Stores one active password reset token per user; repository deletes prior resets before creating a new one.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Password reset row identifier. |
| user_id | INT | FK | References `users.id`. |
| token_hash | CHAR(64) | UNIQUE | SHA-256-sized reset token hash. |
| expires_at | DATETIME |  | Expiration timestamp. |
| used_at | DATETIME NULL |  | Timestamp when reset was consumed. |
| created_at | DATETIME |  | Reset request creation timestamp. |

### Table: carts

Purpose: Stores a user's temporary checkout cart and hold timer. The `user_id` unique key enforces at most one cart per user.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Cart identifier. |
| user_id | INT | FK, UNIQUE | References `users.id`; one cart per user. |
| started_at | DATETIME NULL |  | Cart hold start time. |
| expires_at | DATETIME NULL |  | Cart hold expiration time. |
| created_at | DATETIME |  | Cart creation timestamp. |
| updated_at | DATETIME |  | Last update timestamp. |

### Table: cart_items

Purpose: Stores sessions temporarily reserved in a user's cart before checkout.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Cart item identifier. |
| cart_id | INT | FK | References `carts.id`. |
| session_id | INT | FK | References `sessions.id`. |
| quantity | INT |  | Participant/reservation quantity. |
| unit_price | DECIMAL(10,2) |  | Price captured for the cart item. |
| created_at | DATETIME |  | Cart item creation timestamp. |

Unique constraint: `(cart_id, session_id)` prevents duplicate session rows in the same cart.

### Table: courts

Purpose: Master list of physical courts.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Court identifier. |
| name | VARCHAR(80) |  | Court display name. |
| slug | VARCHAR(80) | UNIQUE | Stable URL/code value such as `green` or `pink`. |

### Table: booking_variants

Purpose: Catalog of bookable products/services offered by a court, such as court rentals, lessons, private coaching, training, social play, and tournaments.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Variant identifier. |
| court_id | INT | FK | References `courts.id`. |
| slug | VARCHAR(120) | UNIQUE | Stable variant code used by UI/API, e.g. `green-lessons`. |
| name | VARCHAR(160) |  | Variant display name. |
| category | VARCHAR(120) |  | Business category, e.g. Coaching or Social Play. |
| duration_label | VARCHAR(80) |  | Human-readable duration. |
| price | DECIMAL(10,2) |  | Base price. |
| participants_limit | INT |  | Maximum quantity/participants per cart add. |
| capacity | INT |  | Default capacity copied into generated sessions. |
| image | VARCHAR(255) NULL |  | Image path used in cart/booking UI. |
| active | TINYINT(1) |  | Active flag used by catalog queries. |

### Table: sessions

Purpose: Concrete dated/time-slotted availability for a booking variant. Sessions are created or updated by `AvailabilityService` and `CatalogRepository`.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Session identifier. |
| variant_id | INT | FK | References `booking_variants.id`. |
| session_date | VARCHAR(80) | UNIQUE PART | Human-readable date label, e.g. `Monday, May 18, 2026`. |
| session_time | VARCHAR(80) | UNIQUE PART | Human-readable time range. |
| capacity | INT |  | Session capacity. |
| booked_count | INT |  | Confirmed quantity already booked. |

Unique constraint: `(variant_id, session_date, session_time)` prevents duplicate slots for the same variant/date/time.

### Table: bookings

Purpose: Checkout order header for confirmed or pending reservations.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Booking identifier. |
| user_id | INT | FK | References `users.id`. |
| reference | VARCHAR(40) | UNIQUE | Public booking reference such as `PKL-4D3F917B`. |
| status | VARCHAR(40) |  | Booking lifecycle status. |
| subtotal | DECIMAL(10,2) |  | Sum of booking item prices before payment fee. |
| payment_fee | DECIMAL(10,2) |  | Fee computed from payment method. |
| total | DECIMAL(10,2) |  | Subtotal plus payment fee. |
| payment_method | VARCHAR(80) |  | Human-readable method label. |
| payment_status | VARCHAR(80) |  | Payment state used by admin filters. |
| notes | TEXT NULL |  | Customer/admin notes captured during checkout. |
| cancellation_label | VARCHAR(120) |  | Human-readable cancellation policy label captured at booking time. |
| created_at | DATETIME |  | Booking creation timestamp. |

### Table: booking_items

Purpose: Booking line items created from cart/session selections. This table intentionally snapshots display and pricing details at checkout time.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Booking item identifier. |
| booking_id | INT | FK | References `bookings.id`. |
| session_id | INT | FK | References `sessions.id`. |
| variant_id | VARCHAR(120) |  | Variant slug captured at checkout; not a declared FK. |
| name | VARCHAR(160) |  | Variant name snapshot. |
| court | VARCHAR(120) |  | Court name snapshot. |
| category | VARCHAR(120) |  | Category snapshot. |
| duration_label | VARCHAR(80) |  | Duration snapshot. |
| booking_date | VARCHAR(80) |  | Session date snapshot. |
| booking_time | VARCHAR(80) |  | Session time snapshot. |
| quantity | INT |  | Quantity booked. |
| unit_price | DECIMAL(10,2) |  | Price captured at booking time. |
| image | VARCHAR(255) NULL |  | Image snapshot/path. |

### Table: events

Purpose: Admin-managed event listings with capacity counters. Used by `EventRepository` and admin event management screens.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Event identifier. |
| title | VARCHAR(160) |  | Event title. |
| description | TEXT NULL |  | Event details. |
| event_date | VARCHAR(80) |  | Event date. Code form uses an HTML date input, but schema stores string. |
| event_time | VARCHAR(80) NULL |  | Event time. Code form uses an HTML time input, but schema stores string. |
| location | VARCHAR(160) NULL |  | Event location. |
| max_participants | INT NULL |  | Event capacity. |
| current_participants | INT |  | Current participant count. |
| status | VARCHAR(40) |  | Event status; admin UI uses `upcoming`, `past`, `cancelled`. |
| created_by | INT | FK | References `users.id`; admin user who created event. |
| created_at | DATETIME |  | Event creation timestamp. |
| updated_at | DATETIME NULL |  | Last update timestamp. |

### Table: notifications

Purpose: User notifications created by admin actions and broadcast notification flows.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Notification identifier. |
| user_id | INT NULL | FK | References `users.id`; nullable in schema, though repository create flows pass a user id. |
| title | VARCHAR(160) |  | Notification title. |
| message | TEXT |  | Notification body. |
| type | VARCHAR(40) |  | Type such as `info`, `success`, or `error`. |
| is_read | TINYINT(1) |  | Read/unread flag. |
| link | VARCHAR(255) NULL |  | Optional destination URL. |
| created_at | DATETIME |  | Notification creation timestamp. |

### Table: admin_logs

Purpose: Audit log for admin actions, including user management, booking payment decisions, event changes, and notification sending.

| Column | Data Type | Key | Description |
| --- | --- | --- | --- |
| id | INT AUTO_INCREMENT | PK | Log row identifier. |
| admin_id | INT | FK | References `users.id`; should point to a user with role `admin`. |
| action | VARCHAR(120) |  | Action code such as `payment_approved` or `event_created`. |
| entity_type | VARCHAR(40) NULL |  | Logical entity type such as `user`, `booking`, `event`, `notification`. |
| entity_id | INT NULL |  | Logical entity id; no declared FK because it is polymorphic. |
| details | JSON NULL |  | Optional structured action metadata. |
| created_at | DATETIME |  | Log creation timestamp. |

## C. Relationship Analysis

### One-to-One

- `users` to `carts`: declared as `users.id` -> `carts.user_id` with a unique key on `carts.user_id`. The application calls `INSERT ... ON DUPLICATE KEY UPDATE`, confirming one active cart/timer row per user.

### One-to-Many

- `users` to `password_resets`: each user can request many password resets over time. The repository deletes previous reset rows for a user before inserting a new reset, but the schema still supports many historical rows.
- `users` to `bookings`: each booking belongs to one user; resident booking pages and admin booking details fetch bookings by `user_id`.
- `users` to `events`: each event has a `created_by` user, populated from the admin session id.
- `users` to `notifications`: notifications are queried by `user_id`; admin payment approval/rejection creates notifications for the booking owner.
- `users` to `admin_logs`: each audit log has an `admin_id`; admin log queries left join `admin_logs.admin_id = users.id`.
- `courts` to `booking_variants`: catalog queries join variants to courts on `booking_variants.court_id = courts.id`.
- `booking_variants` to `sessions`: availability creates sessions from variants and uses the unique variant/date/time slot.
- `sessions` to `cart_items`: a cart item reserves a concrete session temporarily.
- `sessions` to `booking_items`: a booking item confirms a concrete session.
- `carts` to `cart_items`: cart rows contain zero or many items; deleting the cart cascades to items.
- `bookings` to `booking_items`: booking rows contain one or many line items; deleting the booking cascades to items.

### Many-to-Many

- `carts` to `sessions` through `cart_items`: a cart can hold many sessions, and a session can appear in many users' carts. The code prevents duplicates within the same cart only.
- `bookings` to `sessions` through `booking_items`: a booking can contain many sessions, and a session can appear in many booking items until capacity is full.
- `users` to `sessions` through `bookings`/`booking_items`: users book many sessions, and sessions can be booked by many users subject to `sessions.capacity`.

## Referential Integrity Requirements and Cascade Recommendations

- Keep `ON DELETE CASCADE` for `password_resets.user_id`, `carts.user_id`, `cart_items.cart_id`, `booking_items.booking_id`, and `notifications.user_id`. These are dependent rows that should not outlive the parent.
- Consider `ON DELETE RESTRICT` for `bookings.user_id`, `events.created_by`, and `admin_logs.admin_id` to preserve financial, event, and audit history. If user deletion must be allowed, prefer soft-deleting/anonymizing users rather than hard deletes.
- Consider `ON DELETE RESTRICT` for `booking_variants.court_id`, `sessions.variant_id`, `cart_items.session_id`, and `booking_items.session_id`. Existing bookings and capacity accounting depend on these catalog/session rows.
- Add `ON UPDATE CASCADE` to foreign keys if primary key values are ever migrated or reseeded. In normal auto-increment usage, primary keys should not change.
- `admin_logs.entity_type` and `admin_logs.entity_id` are polymorphic and cannot be enforced by a single standard FK. If stricter integrity is required, split logs by entity type or add application-level validation.

## D. Missing Database Improvements

### Missing or Weak Foreign Keys

- `booking_items.variant_id` stores a variant slug but is not a declared FK to `booking_variants.slug`. This appears intentional as a checkout snapshot, but the name is misleading. Prefer `variant_slug` for the snapshot, or add a separate nullable `variant_id INT FK`.
- `admin_logs.entity_id` is not constrained. This is expected for polymorphic audit logs, but it means database-level integrity is not guaranteed.
- `notifications.user_id` is nullable while repository flows use targeted user ids. Make it `NOT NULL` unless broadcast/global notifications without recipients are required.

### Redundant or Denormalized Data

- `booking_items` duplicates `name`, `court`, `category`, `duration_label`, `booking_date`, `booking_time`, `unit_price`, and `image` from catalog/session data. This is acceptable for immutable booking receipts, but should be documented as a snapshot.
- `sessions.capacity` duplicates `booking_variants.capacity`. This is useful because a specific session capacity may differ from the default variant capacity.
- `bookings.payment_method` stores a label from config rather than a stable method code. If labels change, historical reports may become inconsistent.
- `bookings.status` and `bookings.payment_status` are free-form strings. Code uses several values with inconsistent casing/content, including `Pending Payment`, `Confirmed`, `pay on site`, `paid demo checkout`, `Pending`, `Completed`, and `Rejected`.

### Data Type Improvements

- Convert `sessions.session_date`, `events.event_date` to `DATE`.
- Convert `sessions.session_time` to structured `start_time TIME` and `end_time TIME`, or use `starts_at DATETIME` and `ends_at DATETIME`.
- Convert `events.event_time` to `TIME` if a single time is enough.
- Consider `ENUM` or lookup tables for `users.role`, `bookings.status`, `bookings.payment_status`, `events.status`, and `notifications.type`.

### Suggested Indexes

- `bookings(user_id, created_at)` for resident booking history.
- `bookings(payment_status, created_at)` for admin payment filters.
- `bookings(status, created_at)` for admin booking status filters.
- `booking_items(booking_id)` already exists in the dump; keep it.
- `booking_items(session_id)` already exists in the dump; keep it.
- `notifications(user_id, is_read, created_at)` for unread notification counts and listing.
- `admin_logs(admin_id, created_at)` for admin activity views.
- `events(status, event_date)` for admin event filters.
- `sessions(variant_id, session_date)` for monthly availability lookups. The existing unique key starts with `variant_id`, but string date `LIKE` filtering limits index usefulness.

### Security Concerns

- Database credentials default to root with no password in `includes/database.php`. Use environment variables in production and a least-privileged DB user.
- Passwords are properly hashed in application code, and queries are generally prepared statements. Continue avoiding string-concatenated SQL.
- Session security and CSRF helpers are present. Ensure production cookies use `Secure`, `HttpOnly`, and `SameSite` settings over HTTPS.
- Do not expose `database/pickled.sql` or reset-token data from a public web root in production.
- Audit logs store JSON details. Avoid storing secrets, raw tokens, or payment credentials in `admin_logs.details`.

## E. Normalization Review

### 1NF

Mostly satisfied because tables use scalar columns and primary keys. The main concern is human-readable date/time ranges stored as strings in `sessions` and `events`; these are scalar, but not ideal atomic temporal values for querying, sorting, or constraint checks.

### 2NF

Satisfied for tables with single-column surrogate primary keys. There are no composite primary keys with partial dependencies. Composite unique constraints are used appropriately for `cart_items(cart_id, session_id)` and `sessions(variant_id, session_date, session_time)`.

### 3NF

Mostly satisfied for core account, catalog, session, cart, and booking header tables. Intentional denormalization exists in `booking_items` for historical receipt snapshots. Potential 3NF improvements:

- Normalize roles/statuses/payment methods into lookup tables or enforce them with `CHECK` constraints/ENUMs.
- Rename snapshot columns in `booking_items` to clarify they are historical copies.
- Store stable payment method code and optional display label separately.
- Model event participants in a join table if users can register for events; `current_participants` alone is only a counter.

## Production Readiness Recommendations

1. Use `database/schema.sql` as the canonical migration baseline and update `database/pickled.sql` or remove it to avoid schema drift.
2. Add migrations instead of relying on manual SQL dumps.
3. Replace string dates/times with `DATE`, `TIME`, or `DATETIME` columns before the dataset grows.
4. Add indexes for booking filters, notification unread counts, event filtering, and availability queries.
5. Standardize status and payment values with lookup tables, ENUMs, or CHECK constraints.
6. Use soft deletes for `users`, `bookings`, `sessions`, and catalog tables to preserve financial/audit history.
7. Make user deletion behavior explicit. Current cascades remove carts, resets, and notifications, but bookings/admin logs/events are restricted by FKs.
8. Add database constraints for positive money and counts, such as `quantity > 0`, `capacity >= 0`, `booked_count >= 0`, and `booked_count <= capacity`.
9. Protect the database and dump files outside the public document root in production.
10. Add transaction coverage around checkout cart clearing and booking creation if cart-to-booking conversion is extended. Booking creation already uses a transaction and atomic capacity update.


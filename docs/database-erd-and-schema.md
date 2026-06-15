# PICKLED Final Database ERD and Schema Documentation

This document defines the finalized database design for the PICKLED system. It reflects the current defended scope of the application: user authentication, player and coach account management, court and booking catalog management, scheduling, carts, bookings, payments, feedback, notifications, admin audit logs, and private session inquiries.

The finalized scope intentionally excludes unsupported or out-of-scope entities such as generic events, content pages, media assets, waitlists, session attendance, session notes, and private events. Public content and images are handled as static/customizable assets, not database-managed records.

## A. Final Entity Relationship Diagram

```mermaid
erDiagram
    USERS ||--o| USER_PROFILES : optionally_has
    USERS ||--o| COACH_PROFILES : optionally_has
    USERS ||--o{ PASSWORD_RESETS : requests
    USERS ||--o| CARTS : owns
    USERS ||--o{ BOOKINGS : creates
    USERS ||--o{ NOTIFICATIONS : receives
    USERS ||--o{ ADMIN_LOGS : admin_id
    COACH_PROFILES ||--o{ COACH_AVAILABILITY : sets
    COACH_AVAILABILITY ||--o| SESSIONS : schedules
    USERS ||--o{ PRIVATE_INQUIRIES : submits

    COURTS ||--o{ BOOKING_VARIANTS : offers
    BOOKING_VARIANTS ||--o{ SESSIONS : schedules
    USERS ||--o{ SESSIONS : coaches

    CARTS ||--o{ CART_ITEMS : contains
    SESSIONS ||--o{ CART_ITEMS : reserved_in

    BOOKINGS ||--o{ BOOKING_ITEMS : contains
    SESSIONS ||--o{ BOOKING_ITEMS : booked_as
    BOOKINGS ||--o{ PAYMENTS : paid_by
    BOOKINGS ||--o{ FEEDBACK : receives
    USERS ||--o{ FEEDBACK : gives

    PRIVATE_PACKAGES ||--o{ PRIVATE_INQUIRIES : requested_for

    USERS {
        int id PK
        string user_code UK
        string name
        string email UK
        string password_hash
        string role
        boolean is_verified
        datetime created_at
        datetime updated_at
    }

    USER_PROFILES {
        int id PK
        int user_id FK_UK
        string phone
        string city
        string province
        string avatar
        datetime created_at
        datetime updated_at
    }

    COACH_PROFILES {
        int id PK
        int user_id FK_UK
        string specialization
        text bio
        string experience
        string status
        datetime created_at
        datetime updated_at
    }

    PASSWORD_RESETS {
        int id PK
        int user_id FK
        string token_hash UK
        datetime expires_at
        datetime used_at
        datetime created_at
    }

    COURTS {
        int id PK
        string name
        string slug UK
        string status
        datetime created_at
        datetime updated_at
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
        datetime created_at
        datetime updated_at
    }

    SESSIONS {
        int id PK
        string session_code UK
        int variant_id FK
        int coach_user_id FK
        int availability_id FK
        date session_date
        time start_time
        time end_time
        int capacity
        int booked_count
        string status
        datetime created_at
        datetime updated_at
    }

    COACH_AVAILABILITY {
        int id PK
        int coach_profile_id FK
        int day_of_week
        time start_time
        time end_time
        string status
        datetime created_at
        datetime updated_at
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
        datetime updated_at
    }

    BOOKING_ITEMS {
        int id PK
        int booking_id FK
        int session_id FK
        string variant_slug
        string name
        string court
        string category
        string duration_label
        date booking_date
        time start_time
        time end_time
        int quantity
        decimal unit_price
        string image
        datetime created_at
    }

    PAYMENTS {
        int id PK
        string payment_code UK
        int booking_id FK
        string receipt
        string status
        int reviewed_by_admin_id FK
        datetime reviewed_at
        datetime created_at
    }

    FEEDBACK {
        int id PK
        int user_id FK
        int booking_id FK
        int rating
        text comment
        datetime created_at
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

    PRIVATE_PACKAGES {
        int id PK
        string name
        text description
        string guest_range
        decimal starting_price
        boolean active
        datetime created_at
        datetime updated_at
    }

    PRIVATE_INQUIRIES {
        int id PK
        string inquiry_code UK
        int user_id FK
        int private_package_id FK
        string name
        string email
        string phone
        date event_date
        text message
        string status
        datetime created_at
        datetime updated_at
    }
```

## B. Final Database Schema

### Table: users

Purpose: Stores all system accounts for players, coaches, and admins. This table is the authentication and authorization root for the system.

Primary key: `id`

Foreign keys: none

Important fields:

| Column | Description |
| --- | --- |
| `user_code` | Unique human-readable user code used for easier tracking and record management. |
| `name` | Full/display name used across the public, coach, and admin interfaces. |
| `email` | Unique login email. |
| `password_hash` | Hashed password generated by PHP password hashing. |
| `role` | Account role: `player`, `coach`, or `admin`. |
| `is_verified` | Email verification flag used by the OTP verification process. |
| `created_at`, `updated_at` | Account lifecycle timestamps. |

### Table: user_profiles

Purpose: Stores optional player-facing profile details that are separate from login credentials. Admin and coach accounts may not need player profile details.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `user_id` | `users.id` | One user has zero or one user profile. |

Important fields:

| Column | Description |
| --- | --- |
| `phone` | Contact number shown in player/admin profile views. |
| `city` | Player city. |
| `province` | Player province. |
| `avatar` | Optional profile photo path or identifier. |
| `created_at`, `updated_at` | Profile timestamps. |

### Table: coach_profiles

Purpose: Stores optional coach-specific profile information used by coach management and coach-facing pages. Only coach accounts need coach profile details.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `user_id` | `users.id` | One coach user has zero or one coach profile. |

Important fields:

| Column | Description |
| --- | --- |
| `specialization` | Coaching focus such as private coaching, youth coaching, group coaching, or social play. |
| `bio` | Coach description. |
| `experience` | Human-readable coaching experience summary. |
| `status` | Coach state such as active, inactive, or leave. |
| `created_at`, `updated_at` | Profile timestamps. |

### Table: password_reset

Purpose: Stores password reset requests for user accounts.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `user_id` | `users.id` | One user can have many reset requests over time. |

Important fields:

| Column | Description |
| --- | --- |
| `token_hash` | Unique hash of the reset token. |
| `expires_at` | Expiration time. |
| `used_at` | Timestamp once the reset token is consumed. |
| `created_at` | Request timestamp. |

### Table: courts

Purpose: Stores physical courts available in the PICKLED facility.

Primary key: `id`

Foreign keys: none

Important fields:

| Column | Description |
| --- | --- |
| `name` | Court display name, such as Court Green or Court Pink. |
| `slug` | Unique stable code, such as `green` or `pink`. |
| `status` | Court availability state, such as active or inactive. |
| `created_at`, `updated_at` | Court record timestamps. |

### Table: booking_variants

Purpose: Stores bookable services and products offered on each court.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `court_id` | `courts.id` | One court offers many booking variants. |

Important fields:

| Column | Description |
| --- | --- |
| `slug` | Unique code used by the booking UI/API. |
| `name` | Service name, such as Court Rentals, Lessons, Training, Private Coaching, Open Match Play, or Weekly Tournament. |
| `category` | Service category, such as Court Rental, Coaching, Training, Private Coaching, or Social Play. |
| `duration_label` | Human-readable duration. |
| `price` | Base service price. |
| `participants_limit` | Maximum quantity allowed per cart add. |
| `capacity` | Default capacity used when creating sessions. |
| `image` | Optional display image path. |
| `active` | Whether the service can be booked. |

### Table: sessions

Purpose: Stores concrete bookable schedule slots generated from booking variants.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `variant_id` | `booking_variants.id` | One booking variant schedules many sessions. |
| `coach_user_id` | `users.id` | Optional coach assigned to a coaching/private coaching session. |
| `availability_id` | `coach_availability.id` | Availability slot used to generate the session. |

Important fields:

| Column | Description |
| --- | --- |
| `session_code` | Unique human-readable session code used for scheduling and tracking. |
| `session_date` | Date of the session. |
| `start_time` | Session start time. |
| `end_time` | Session end time. |
| `capacity` | Slot capacity. |
| `booked_count` | Number of confirmed booked participants. |
| `status` | Session status, such as open, full, cancelled, or completed. |

Recommended unique constraint: `(variant_id, session_date, start_time, end_time)`.

### Table: coach_availability

Purpose: Stores recurring coach availability used by coach management and scheduling. Availability records define the recurring time windows during which coaches are available to facilitate coaching-related sessions.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `coach_profile_id` | `coach_profiles.id` | One coach profile can define many availability windows. |

Important fields:

| Column | Description |
| --- | --- |
| `day_of_week` | Numeric day value representing recurring availability. |
| `start_time` | Availability start time. |
| `end_time` | Availability end time. |
| `status` | Availability state such as available, unavailable, or leave. |
| `created_at` | Availability creation timestamp. |
| `updated_at` | Availability update timestamp. |

### Table: carts

Purpose: Stores one active cart and booking hold timer per user.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `user_id` | `users.id` | One user owns zero or one active cart. |

Important fields:

| Column | Description |
| --- | --- |
| `started_at` | Cart hold start time. |
| `expires_at` | Cart hold expiration time. |
| `created_at`, `updated_at` | Cart timestamps. |

Unique constraint: `user_id`.

### Table: cart_items

Purpose: Stores temporary session reservations before checkout.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `cart_id` | `carts.id` | One cart contains many cart items. |
| `session_id` | `sessions.id` | One session can be reserved in many carts until capacity is reached. |

Important fields:

| Column | Description |
| --- | --- |
| `quantity` | Number of participants or slots selected. |
| `unit_price` | Price captured at the time the item was added. |
| `created_at` | Cart item timestamp. |

Recommended unique constraint: `(cart_id, session_id)`.

### Table: bookings

Purpose: Stores checkout/order headers for confirmed or pending reservations.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `user_id` | `users.id` | One user can create many bookings. |

Important fields:

| Column | Description |
| --- | --- |
| `reference` | Unique public booking reference, such as `PKL-4D3F917B`. |
| `status` | Booking lifecycle status, such as pending, confirmed, completed, or cancelled. |
| `subtotal` | Total before payment fee. |
| `payment_fee` | Additional payment method fee. |
| `total` | Final total amount. |
| `payment_method` | Selected payment method label or code. |
| `payment_status` | Payment state, such as pending, paid, rejected, or pay on site. |
| `notes` | Customer notes from checkout. |
| `cancellation_label` | Cancellation policy snapshot. |
| `created_at`, `updated_at` | Booking timestamps. |

### Table: booking_items

Purpose: Stores booking line items and historical snapshots of service, schedule, court, and price details.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `booking_id` | `bookings.id` | One booking contains many booking items. |
| `session_id` | `sessions.id` | One session can appear in many booking items until capacity is full. |

Important fields:

| Column | Description |
| --- | --- |
| `variant_slug` | Booking variant slug captured at checkout. |
| `name` | Service name snapshot. |
| `court` | Court name snapshot. |
| `category` | Service category snapshot. |
| `duration_label` | Duration snapshot. |
| `booking_date` | Booked date snapshot. |
| `start_time`, `end_time` | Booked time range snapshot. |
| `quantity` | Quantity booked. |
| `unit_price` | Price captured at checkout. |
| `image` | Optional image snapshot. |

### Table: payments

Purpose: Stores payment review and receipt information for booking management.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `booking_id` | `bookings.id` | One booking can have one or more payment records. |
| `reviewed_by_admin_id` | `users.id` | Admin user who reviewed the payment. |

Important fields:

| Column | Description |
| --- | --- |
| `payment_code` | Unique human-readable payment code used for payment tracking and verification. |
| `receipt` | Optional receipt/proof reference. |
| `status` | Payment status, such as pending, approved, rejected, or paid on site. |
| `reviewed_at` | Timestamp of admin review. |
| `created_at` | Payment record timestamp. |

### Table: feedback

Purpose: Stores booking feedback submitted by players after a booking flow.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `user_id` | `users.id` | One user can submit many feedback entries. |
| `booking_id` | `bookings.id` | One booking can receive feedback. |

Important fields:

| Column | Description |
| --- | --- |
| `rating` | Numeric rating. |
| `comment` | Optional written feedback. |
| `created_at` | Feedback timestamp. |

### Table: notifications

Purpose: Stores user notifications created by admin actions and booking/payment updates.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `user_id` | `users.id` | One user can receive many notifications. |

Important fields:

| Column | Description |
| --- | --- |
| `title` | Notification title. |
| `message` | Notification body. |
| `type` | Notification type, such as info, success, warning, or error. |
| `is_read` | Read/unread flag. |
| `link` | Optional destination link. |
| `created_at` | Notification timestamp. |

### Table: admin_logs

Purpose: Stores an audit trail of admin actions for user management, booking management, payment review, notifications, and private session management.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `admin_id` | `users.id` | One admin user can perform many logged actions. The referenced user should have `role = 'admin'`. |

Important fields:

| Column | Description |
| --- | --- |
| `action` | Action code, such as `payment_approved`, `booking_status_changed`, or `notification_sent`. |
| `entity_type` | Logical entity affected by the action. |
| `entity_id` | Identifier of affected entity. Polymorphic and not enforced by one FK. |
| `details` | Optional JSON metadata. |
| `created_at` | Log timestamp. |

### Table: private_packages

Purpose: Stores private session package options promoted by the private sessions/admin flow.

Primary key: `id`

Foreign keys: none

Important fields:

| Column | Description |
| --- | --- |
| `name` | Package name, such as Corporate Team Building, Birthday Celebration, or Exclusive Venue Rental. |
| `description` | Package description. |
| `guest_range` | Expected guest count range. |
| `starting_price` | Starting package price. |
| `active` | Whether the package is available for inquiries. |
| `created_at`, `updated_at` | Package timestamps. |

### Table: private_inquiries

Purpose: Stores private package inquiries submitted by customers or entered by admins.

Primary key: `id`

Foreign keys:

| Column | References | Relationship |
| --- | --- | --- |
| `user_id` | `users.id` | Optional logged-in user who submitted the inquiry. |
| `private_package_id` | `private_packages.id` | Package requested by the inquiry. |

Important fields:

| Column | Description |
| --- | --- |
| `inquiry_code` | Unique human-readable inquiry code used for monitoring and reference. |
| `name` | Inquiry contact name. |
| `email` | Inquiry contact email. |
| `phone` | Inquiry contact phone. |
| `event_date` | Requested event date. |
| `message` | Inquiry message. |
| `status` | Inquiry status, such as new, contacted, confirmed, or closed. |
| `created_at`, `updated_at` | Inquiry timestamps. |

## C. Relationship and Cardinality Analysis

### Authentication and User Relationships

- `users` to `user_profiles` is optional one-to-one. Login data stays in `users`; player details stay in `user_profiles`. Admin and coach users may have no `user_profiles` row.
- `users` to `coach_profiles` is optional one-to-one. Only users with role `coach` should have coach profile rows.
- `users` to `password_reset` is one-to-many. A user can request multiple resets over time.

### Court and Scheduling Relationships

- `courts` to `booking_variants` is one-to-many. Court Green and Court Pink each offer multiple services.
- `booking_variants` to `sessions` is one-to-many. A service such as Court Rentals, Lessons, Training, Open Match Play, or Private Coaching can generate many dated sessions.
- `users` to `sessions` is one-to-many for coach assignments. This applies to coach-led sessions such as Lessons, Training, and Private Coaching.
- `coach_profiles` to `coach_availability` is one-to-many. A coach profile may define multiple recurring availability windows.
- `coach_availability` to `sessions` is one-to-zero-or-one. An availability slot may be used to create a scheduled coaching session.

### Booking System Relationships

- `users` to `carts` is one-to-zero-or-one. Each user has at most one active cart.
- `carts` to `cart_items` is one-to-many. A cart can hold several pending session selections.
- `sessions` to `cart_items` is one-to-many. A session can appear in multiple carts before checkout, subject to cart and capacity rules.
- `users` to `bookings` is one-to-many. A player can create many bookings.
- `bookings` to `booking_items` is one-to-many. A booking can contain one or more booked services/sessions.
- `sessions` to `booking_items` is one-to-many. A session can receive multiple booking items until capacity is full.
- `bookings` to `payments` is one-to-many. This supports payment review history and receipt tracking.
- `bookings` to `feedback` is one-to-many, though the application can enforce one feedback entry per user per booking if desired.

### Administration Relationships

- `users` to `notifications` is one-to-many. Notifications are targeted to users.
- `users` to `admin_logs` is one-to-many through `admin_logs.admin_id`. Only users with role `admin` should perform logged admin actions.
- `admin_logs.entity_type` and `admin_logs.entity_id` are polymorphic references. They are intentionally not modeled as one database foreign key.

### Private Sessions Relationships

- `private_packages` to `private_inquiries` is one-to-many. A package can receive multiple customer inquiries.
- `users` to `private_inquiries` is one-to-many and optional. Guests can inquire without an account; logged-in users can be linked.

## D. Scope Alignment

This finalized design supports the defended PICKLED scope:

- Court Green and Court Pink through `courts`.
- Court Rentals, Lessons, Training, Private Coaching, Open Match Play, and Weekly Tournament through `booking_variants`.
- Date/time booking availability through `sessions`.
- Coach management through `users`, `coach_profiles`, `coach_availability`, and optional `sessions.coach_user_id`.
- Player management and email verification through `users`, `user_profiles`, `bookings`, and `feedback`.
- Booking management through `bookings`, `booking_items`, `payments`, and `admin_logs`.
- Payments through `payments` and booking payment status fields.
- Feedback through `feedback`.
- Notifications through `notifications`.
- Private Packages and inquiries through `private_packages` and `private_inquiries`.

## E. Removed Out-of-Scope Entities

The following entities are intentionally not part of the finalized ERD:

| Removed entity | Reason |
| --- | --- |
| `events` | Generic event CRUD is not part of the finalized defended flow. Social play and tournaments are represented as booking variants and sessions. |
| `content_pages` | Content is customizable/static and not part of transactional database scope. |
| `media_assets` | Images are managed as static project assets, not database records. |
| `waitlist_entries` | Waitlist is not part of the finalized project scope. |
| `session_attendance` | Attendance tracking is not part of the finalized database scope. |
| `session_notes` | Coach notes are not part of the finalized database scope. |
| `private_events` | Private session demand is represented by packages and inquiries only. |

## F. Referential Integrity Recommendations

- Use `ON DELETE CASCADE` for dependent rows that should not outlive their parent: `password_reset`, `cart_items`, and non-audit temporary records.
- Use `ON DELETE RESTRICT` or soft deletes for `users`, `bookings`, `booking_items`, `payments`, `sessions`, `courts`, and `booking_variants` to preserve booking, payment, and audit history.
- Keep `admin_logs.entity_type` and `admin_logs.entity_id` polymorphic, with integrity enforced at the application level.
- Use unique constraints for `users.email`, `courts.slug`, `booking_variants.slug`, `carts.user_id`, and session slot uniqueness.
- `COACH_AVAILABILITY.coach_profile_id` references `COACH_PROFILES.id`.
- `SESSIONS.availability_id` references `COACH_AVAILABILITY.id`.

## G. Data Type and Constraint Recommendations

- Store session dates and times using `DATE` and `TIME` fields rather than display strings.
- Keep snapshot fields in `booking_items` so historical booking receipts remain accurate even if service names, prices, or court labels change later.
- Enforce positive values for `quantity`, `price`, `capacity`, `booked_count`, `subtotal`, `payment_fee`, `total`, and `starting_price`.
- Add a constraint or application validation so `sessions.booked_count <= sessions.capacity`.
- Standardize status values for bookings, payments, sessions, notifications, coach availability, and private inquiries.

## H. Suggested Indexes

| Table | Suggested index | Purpose |
| --- | --- | --- |
| `users` | `email` unique | Login lookup. |
| `users` | `user_code` unique | Human-readable user lookup. |
| `users` | `role` | Admin player/coach filtering. |
| `bookings` | `(user_id, created_at)` | Player booking history. |
| `bookings` | `(status, created_at)` | Admin booking filters. |
| `bookings` | `(payment_status, created_at)` | Admin payment filters. |
| `booking_items` | `booking_id` | Booking detail lookup. |
| `booking_items` | `session_id` | Session booking lookup. |
| `sessions` | `session_code` unique | Human-readable session lookup. |
| `sessions` | `(variant_id, session_date, start_time, end_time)` unique | Availability and duplicate prevention. |
| `cart_items` | `(cart_id, session_id)` unique | Prevent duplicate session rows per cart. |
| `notifications` | `(user_id, is_read, created_at)` | Notification listing and unread counts. |
| `admin_logs` | `(admin_id, created_at)` | Admin activity history. |
| `coach_availability` | `(coach_profile_id, day_of_week, start_time)` | Coach schedule lookup. |
| `payments` | `payment_code` unique | Human-readable payment lookup. |
| `private_inquiries` | `inquiry_code` unique | Human-readable private inquiry lookup. |
| `private_inquiries` | `(status, created_at)` | Admin private inquiry management. |

## I. Defense Notes

This ERD is intentionally scoped to the operational database needs of PICKLED. It does not model every visual customization on the website because static page content, images, and purely decorative assets do not require database persistence. The final model prioritizes the core system workflows: account access, player and coach management, court/service scheduling, cart checkout, booking records, payment review, feedback, notifications, audit logging, and private package inquiries.

Roles are handled by `users.role`. The finalized design does not add separate `admins`, `coaches`, or `players` tables; role-specific details are represented only where needed through optional profile tables.

# PICKLED: A Web-Based Court and Events Booking Management System

## Data Dictionary

This data dictionary documents the database structure of the PICKLED: A Web-Based Court and Events Booking Management System. The database consists of eighteen interrelated tables designed to support user account management, coach profile management, court booking options, session scheduling, cart-based reservations, finalized booking transactions, payment verification, feedback collection, user notifications, administrative logging, private service packages, and private booking inquiries.

Each field is described using its recommended MySQL data type, field length, key designation, nullability, default value, and functional description to support database consistency, integrity, and maintainability.

### Table 1.1 Data Dictionary for USERS

Stores account information and access credentials for all registered users of the system.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| user_id | Unique identifier assigned to each user account. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| user_code | Stores a unique human-readable identification code assigned to each user for easier tracking and record management. | VARCHAR | 20 | UNIQUE, NOT NULL |
| name | Stores the complete name of the user. | VARCHAR | 100 | NOT NULL |
| email | Stores the user's email address used for authentication and communication. | VARCHAR | 255 | NOT NULL, UNIQUE |
| password_hash | Stores the hashed password used for secure user authentication. | VARCHAR | 255 | NOT NULL |
| role | Defines the authorization level of the user within the system. | ENUM('player','coach','admin') | - | NOT NULL, DEFAULT 'player' |
| is_verified | Indicates whether the user's email address has been successfully verified through the OTP verification process. A value of 0 means the account is not verified, while 1 means the account is verified. | TINYINT | 1 | NOT NULL, DEFAULT 0 |
| created_at | Stores the date and time when the user account was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the user account was last modified. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.2 Data Dictionary for USER_PROFILES

Stores additional profile information for registered users.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| profile_id | Unique identifier assigned to each user profile record. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| user_id | References the user who owns the profile record. This field is unique to ensure that each user can only have one profile. | INT | 11 | FK, UNIQUE, NOT NULL |
| phone | Stores the contact number of the user. | VARCHAR | 20 | NULL |
| city | Stores the city of residence of the user. | VARCHAR | 100 | NULL |
| province | Stores the province of residence of the user. | VARCHAR | 100 | NULL |
| avatar | Stores the file path or URL of the user's profile image. | VARCHAR | 255 | NULL |
| created_at | Stores the date and time when the profile record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the profile record was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.3 Data Dictionary for COACH_PROFILES

Stores professional and coaching-related information for users assigned as coaches.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| coach_profile_id | Unique identifier assigned to each coach profile record. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| user_id | References the user account associated with the coach profile; unique to ensure one coach profile per user. | INT | 11 | FK, UNIQUE, NOT NULL |
| specialization | Stores the coach's specialization or field of expertise. | VARCHAR | 150 | NULL |
| bio | Stores the coach biography, qualifications, and background information. | TEXT | - | NULL |
| experience | Stores the coach's experience details, such as years of practice or coaching background. | VARCHAR | 100 | NULL |
| status | Indicates the coach profile status within the system. | ENUM('active','inactive') | - | NOT NULL, DEFAULT 'active' |
| created_at | Stores the date and time when the coach profile was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the coach profile was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.4 Data Dictionary for PASSWORD_RESETS

Stores password reset requests and account recovery tokens.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| reset_id | Unique identifier assigned to each password reset request. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| user_id | References the user requesting a password reset. | INT | 11 | FK, NOT NULL |
| token_hash | Stores the hashed reset token used for account recovery. | VARCHAR | 255 | UNIQUE, NOT NULL |
| expires_at | Stores the expiration date and time of the reset token. | DATETIME | - | NOT NULL |
| used_at | Stores the date and time when the reset token was used; null means unused. | DATETIME | - | NULL |
| created_at | Stores the date and time when the password reset request was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### Table 1.5 Data Dictionary for COURTS

Stores court information and operational status for court-based booking services.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| court_id | Unique identifier assigned to each court. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| name | Stores the official name of the court. | VARCHAR | 100 | NOT NULL |
| slug | Stores a unique URL-friendly identifier for the court. | VARCHAR | 120 | UNIQUE, NOT NULL |
| status | Indicates the operational status of the court, such as active, inactive, or maintenance. | ENUM('active','inactive','maintenance') | - | NOT NULL, DEFAULT 'active' |
| created_at | Stores the date and time when the court record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the court record was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.6 Data Dictionary for BOOKING_VARIANTS

Stores booking options and services offered for each court.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| variant_id | Unique identifier assigned to each booking variant. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| court_id | References the court where the booking variant is offered. | INT | 11 | FK, NOT NULL |
| slug | Stores a unique URL-friendly identifier for the booking variant. | VARCHAR | 150 | UNIQUE, NOT NULL |
| name | Stores the display name of the booking option. | VARCHAR | 150 | NOT NULL |
| category | Classifies the booking type, such as Court Rental, Private Coaching, Social Play, Tournament, or Training Session. | ENUM('court_rental','private_coaching','social_play','tournament','training_session') | - | NOT NULL |
| duration_label | Stores the human-readable duration of the booking option. | VARCHAR | 50 | NOT NULL |
| price | Stores the price of the booking variant in Philippine Peso. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| participants_limit | Stores the maximum number of participants allowed, when applicable. | INT | 11 | NULL |
| capacity | Stores the default capacity for sessions created under this booking variant. | INT | 11 | NOT NULL, DEFAULT 1 |
| image | Stores the file path or URL of the booking variant image. | VARCHAR | 255 | NULL |
| active | Indicates whether the booking variant is active. 1 means active; 0 means inactive. | BOOLEAN | - | NOT NULL, DEFAULT TRUE |
| created_at | Stores the date and time when the booking variant was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the booking variant was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.7 Data Dictionary for COACH_AVAILABILITY

Stores recurring coach availability schedules for coaching-related sessions.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| availability_id | Unique identifier assigned to each coach availability record. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| coach_profile_id | References the coach profile whose availability is recorded. | INT | 11 | FK, NOT NULL |
| day_of_week | Stores the recurring day of the week when the coach is available. | TINYINT | 1 | NOT NULL |
| start_time | Stores the start time of the coach availability period. | TIME | - | NOT NULL |
| end_time | Stores the end time of the coach availability period. | TIME | - | NOT NULL |
| status | Indicates the availability status, such as available, unavailable, or leave. | ENUM('available','unavailable','leave') | - | NOT NULL, DEFAULT 'available' |
| created_at | Stores the date and time when the availability record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the availability record was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.8 Data Dictionary for SESSIONS

Stores scheduled sessions, participant capacity, coach assignments, and availability information.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| session_id | Unique identifier assigned to each scheduled session. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| session_code | Stores a unique human-readable identification code assigned to each session for scheduling, tracking, and reference purposes. | VARCHAR | 20 | UNIQUE, NOT NULL |
| variant_id | References the booking variant associated with the session. | INT | 11 | FK, NOT NULL |
| coach_user_id | References the assigned coach responsible for the session; null for non-coaching sessions. | INT | 11 | FK, NULL |
| availability_id | References the coach availability slot used to create the session. | INT | 11 | FK, NULL |
| session_date | Stores the scheduled date of the session. | DATE | - | NOT NULL |
| start_time | Stores the starting time of the session. | TIME | - | NOT NULL |
| end_time | Stores the ending time of the session. | TIME | - | NOT NULL |
| capacity | Stores the maximum number of participants allowed for the session. | INT | 11 | NOT NULL, DEFAULT 1 |
| booked_count | Stores the current number of confirmed bookings; must not exceed capacity. | INT | 11 | NOT NULL, DEFAULT 0 |
| status | Indicates the session status, such as available, full, cancelled, or completed. | ENUM('open','full','cancelled','completed') | - | NOT NULL, DEFAULT 'open' |
| created_at | Stores the date and time when the session record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the session record was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.9 Data Dictionary for CARTS

Stores active booking carts created by users before checkout.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| cart_id | Unique identifier assigned to each cart. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| user_id | References the user who owns the cart. This may be unique to maintain one active cart per user. | INT | 11 | FK, UNIQUE, NOT NULL |
| expires_at | Stores the expiration date and time of the temporary cart hold. | DATETIME | - | NOT NULL |
| created_at | Stores the date and time when the cart record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the cart record was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.10 Data Dictionary for CART_ITEMS

Stores individual session items placed in a user cart before checkout.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| cart_item_id | Unique identifier assigned to each cart item. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| cart_id | References the cart containing the item. | INT | 11 | FK, NOT NULL |
| session_id | References the session added to the cart. | INT | 11 | FK, NOT NULL |
| quantity | Stores the number of slots or units selected for the session. | INT | 11 | NOT NULL, DEFAULT 1, CHECK(quantity > 0) |
| unit_price | Stores the price per selected session slot at the time it was added to the cart. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| created_at | Stores the date and time when the cart item was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### Table 1.11 Data Dictionary for BOOKINGS

Stores finalized booking transactions created after checkout.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| booking_id | Unique identifier assigned to each booking transaction. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| user_id | References the user who created the booking. | INT | 11 | FK, NOT NULL |
| reference | Stores the unique public booking reference used for tracking. | VARCHAR | 50 | UNIQUE, NOT NULL |
| status | Indicates the booking status, such as pending, confirmed, completed, cancelled, or rejected. | ENUM('pending','confirmed','completed','cancelled','rejected') | - | NOT NULL, DEFAULT 'pending' |
| subtotal | Stores the booking amount before additional payment fees. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| payment_fee | Stores the additional fee based on the selected payment method, when applicable. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| payment_method | Stores the selected payment method for the booking. | VARCHAR | 50 | NULL |
| total | Stores the final total amount of the booking transaction. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| notes | Stores optional notes or remarks provided during checkout. | TEXT | - | NULL |
| cancellation_note | Stores the reason for cancellation or rejection, when applicable. | VARCHAR | 255 | NULL |
| created_at | Stores the date and time when the booking record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the booking record was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.12 Data Dictionary for BOOKING_ITEMS

Stores booking line items and historical snapshots of booked services.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| booking_item_id | Unique identifier assigned to each booking item. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| booking_id | References the booking transaction containing the item. | INT | 11 | FK, NOT NULL |
| session_id | References the session added to the cart. | INT | 11 | FK, NOT NULL |
| name | Stores the booked service name at the time of checkout. | VARCHAR | 150 | NOT NULL |
| court | Stores the court name snapshot at the time of checkout. | VARCHAR | 100 | NULL |
| category | Stores the service category snapshot at the time of checkout. | VARCHAR | 50 | NOT NULL |
| variant_slug | Stores the booking variant slug captured at checkout. | VARCHAR | 150 | NOT NULL |
| booking_date | Stores the booked date snapshot. | DATE | - | NOT NULL |
| start_time | Stores the booked start time snapshot. | TIME | - | NOT NULL |
| end_time | Stores the booked end time snapshot. | TIME | - | NOT NULL |
| duration_label | Stores the human-readable duration snapshot. | VARCHAR | 50 | NOT NULL |
| quantity | Stores the number of slots or units reserved. | INT | 11 | NOT NULL, DEFAULT 1 |
| unit_price | Stores the unit price at the time of booking. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| created_at | Stores the date and time when the booking item record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### Table 1.13 Data Dictionary for PAYMENTS

Stores payment review and receipt information for booking transactions.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| payment_id | Unique identifier assigned to each payment record. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| payment_code | Stores a unique human-readable identification code assigned to each payment transaction for tracking and verification purposes. | VARCHAR | 20 | UNIQUE, NOT NULL |
| booking_id | References the booking associated with the payment; unique to enforce one payment record per booking. | INT | 11 | FK, UNIQUE, NOT NULL |
| amount | Stores how much was paid or submitted. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| proof_of_payment | Stores the file path or reference of the uploaded proof of payment. | VARCHAR | 255 | NULL |
| status | Indicates the payment status, such as pending, approved, or rejected. | ENUM('pending','approved','rejected') | - | NOT NULL, DEFAULT 'pending' |
| reviewed_by_admin_id | References the administrator who reviewed the payment. | INT | 11 | FK, NULL |
| reviewed_at | Stores the date and time when the payment was reviewed. | DATETIME | - | NULL |
| created_at | Stores the date and time when the payment record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### Table 1.14 Data Dictionary for FEEDBACK

Stores user ratings and comments related to completed bookings.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| feedback_id | Unique identifier assigned to each feedback record. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| booking_id | References the booking being reviewed; unique to enforce one feedback record per booking. | INT | 11 | FK, UNIQUE, NOT NULL |
| user_id | References the user who submitted the feedback. | INT | 11 | FK, NOT NULL |
| rating | Stores the user rating using a scale of 1 to 5. | TINYINT | 1 | NOT NULL, CHECK(rating BETWEEN 1 AND 5) |
| comment | Stores optional written feedback or remarks from the user. | TEXT | - | NULL |
| created_at | Stores the date and time when the feedback was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### Table 1.15 Data Dictionary for NOTIFICATIONS

Stores system-generated notifications and alerts sent to users.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| notification_id | Unique identifier assigned to each notification record. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| user_id | References the notification recipient. | INT | 11 | FK, NOT NULL |
| message | Stores the notification content. | TEXT | - | NOT NULL |
| type | Classifies the notification type, such as booking, payment, session, or general. | VARCHAR | 50 | NOT NULL, DEFAULT 'general' |
| link | Stores an optional link related to the notification. | VARCHAR | 255 | NULL |
| is_read | Indicates whether the notification has been read. 0 means unread; 1 means read. | BOOLEAN | - | NOT NULL, DEFAULT FALSE |
| created_at | Stores the date and time when the notification was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### Table 1.16 Data Dictionary for ADMIN_LOGS

Stores audit trail records of administrative actions performed within the system.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| log_id | Unique identifier assigned to each administrative log entry. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| admin_id | References the administrator who performed the action. | INT | 11 | FK, NOT NULL |
| entity_type | Stores the type of entity affected by the administrative action. | VARCHAR | 100 | NOT NULL |
| entity_id | Stores the identifier of the affected entity; handled as an application-level reference. | INT | 11 | NULL |
| action | Stores the type or name of the administrative action performed. | VARCHAR | 100 | NOT NULL |
| details | Stores additional details about the administrative action. | TEXT | - | NULL |
| created_at | Stores the date and time when the administrative action was recorded. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### Table 1.17 Data Dictionary for PRIVATE_PACKAGES

Stores private coaching and customized service packages offered by the system.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| package_id | Unique identifier assigned to each private package. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| name | Stores the name of the private package. | VARCHAR | 150 | NOT NULL |
| description | Stores package details, inclusions, and service information. | TEXT | - | NULL |
| guest_price | Stores the per-guest rate for the package in Philippine Peso. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| starting_price | Stores the base starting price of the package in Philippine Peso. | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 |
| active | Indicates whether the package is available for booking. 1 means active; 0 means inactive. | BOOLEAN | - | NOT NULL, DEFAULT TRUE |
| created_at | Stores the date and time when the package record was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the package record was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table 1.18 Data Dictionary for PRIVATE_INQUIRIES

Stores user inquiries and booking requests for private packages.

| Field Name | Description | Data Type | Field Size | Constraints |
|---|---|---|---|---|
| inquiry_id | Unique identifier assigned to each private inquiry. | INT | 11 | PK, NOT NULL, AUTO_INCREMENT |
| inquiry_code | Stores a unique human-readable identification code assigned to each private inquiry for monitoring and reference purposes. | VARCHAR | 20 | UNIQUE, NOT NULL |
| user_id | References the user who submitted the inquiry. | INT | 11 | FK, NOT NULL |
| private_package_id | References the private package being requested. | INT | 11 | FK, NOT NULL |
| name | Stores the name of the inquiry contact person. | VARCHAR | 150 | NOT NULL |
| email | Stores the email address of the inquiry contact person. | VARCHAR | 100 | NOT NULL |
| phone | Stores the contact number of the inquiry contact person. | VARCHAR | 20 | NOT NULL |
| event_date | Stores the requested date of the private event or session. | DATE | - | NOT NULL |
| message | Stores additional inquiry details, requests, or special instructions. | TEXT | - | NULL |
| status | Indicates the inquiry status, such as pending, contacted, approved, rejected, or completed. | ENUM('pending','contacted','confirmed','rejected','completed') | - | NOT NULL, DEFAULT 'pending' |
| created_at | Stores the date and time when the inquiry was created. | TIMESTAMP | - | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | Stores the date and time when the inquiry was last modified. | TIMESTAMP | - | NULL, CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

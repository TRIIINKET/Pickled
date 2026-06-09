# PICKLED Architecture

PICKLED keeps the current visible booking experience, but the folders are organized for a student-friendly role-based demo.

## Frontend

- `index.php` is the landing page.
- `resident/courts.php` is the primary court booking surface.
- `resident/social-play.php` handles community and open play.
- `resident/private.php` handles coaching and private lessons.
- `resident/cart.php` renders cart review and checkout UI.
- `auth/login.php`, `auth/forgot-password.php`, and `auth/reset-password.php` expose auth pages.
- `includes/header.php`, `includes/navbar.php`, and `includes/footer.php` store shared resident layouts.
- `includes/admin-header.php`, `includes/admin-navbar.php`, and `includes/admin-footer.php` store shared admin layouts.
- `includes/config.php`, `includes/database.php`, and `includes/paths.php` store app, database, and path configuration.
- `includes/booking-system.php` and `includes/security.php` store small reusable helper functions.
- `includes/payment-methods.php` stores the reusable payment method UI.

## PHP Code

- `auth/` contains the public auth pages and shared auth handlers, including the admin login handler.
- `app/controllers/` contains UI-facing controller helpers such as payment calculations.
- `app/api/` exposes small JSON endpoints.
- `app/services/` contains application logic:
  - `AuthService`
  - `CartService`
  - `CheckoutService`
- `app/repositories/` contains persistence files:
  - `UserRepository`
  - `CartRepository`
  - `BookingRepository`
  - `PasswordResetRepository`
- `database/Database.php` provides the shared PDO connection.
- `database/schema.sql` defines the MySQL schema.
- `app/api/availability.php` exposes month-by-month database availability for the booking calendars.
- `includes/booking-system.php` contains small booking-domain helpers; catalog data comes from database repositories.

## State and persistence

- Authentication uses hardened PHP sessions.
- Users are stored in MySQL.
- Password reset tokens are stored hashed in MySQL and expire after 30 minutes.
- Booking variants, courts, and user-selected sessions are stored in normalized MySQL tables.
- Carts are stored as `carts` + `cart_items`, linked to normalized `sessions`.
- Completed bookings and booking items are stored in MySQL.
- `sessions.capacity` and `sessions.booked_count` are the server-side source of truth for availability.
- Checkout increments `booked_count` transactionally and rejects sessions that became full before payment.
- Cart timers still remain visible in the session for the current browser, but are restored from the database when the user returns.

## Local setup

1. Create/import the schema in XAMPP MySQL using `database/schema.sql`.
2. By default the app expects:
   - database: `pickled`
   - host: `127.0.0.1`
   - port: `3306`
   - user: `root`
   - password: empty
3. To override those values, set:
   - `PICKLED_DB_DSN`
   - `PICKLED_DB_USER`
   - `PICKLED_DB_PASS`
4. `database/schema.sql` seeds demo users, including `admin@example.com`.

## Next architectural steps

- Move page-level hard-coded visual copy into database-backed view models where it is genuinely shared.
- Add real mail delivery for password reset instead of showing a demo link on-screen.
- Add admin tools for blocking dates, changing capacity, and managing exceptional schedules.
- Add integration tests around auth, cart restore, and checkout persistence.

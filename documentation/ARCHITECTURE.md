# PICKLED Architecture

PICKLED keeps the current visible booking experience, but the backend is now split into clearer layers.

## Frontend

- `frontend/index.php` is the landing page.
- `frontend/pages/courts.php` is the primary court booking surface.
- `frontend/pages/social-play.php` handles community and open play.
- `frontend/pages/private.php` handles coaching and private lessons.
- `frontend/pages/cart.php` renders cart review and checkout UI.
- `frontend/login.php`, `frontend/forgot-password.php`, and `frontend/reset-password.php` expose auth pages.
- `frontend/components/` stores reusable UI pieces.
- `frontend/includes/` stores shared layout and URL helpers.

## Backend layers

- `backend/auth/` contains auth page controllers.
- `backend/controllers/` contains UI-facing controller helpers such as payment calculations.
- `backend/services/` contains application logic:
  - `AuthService`
  - `CartService`
  - `CheckoutService`
- `backend/repositories/` contains persistence code:
  - `UserRepository`
  - `CartRepository`
  - `BookingRepository`
  - `PasswordResetRepository`
- `backend/database/Database.php` provides the shared PDO connection.
- `backend/database/schema.sql` defines the MySQL schema.
- `backend/api/availability.php` exposes month-by-month database availability for the booking calendars.
- `backend/includes/booking_system.php` now contains only small booking-domain helpers; catalog data comes from database repositories.

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

1. Create/import the schema in XAMPP MySQL using `backend/database/schema.sql`.
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

## Next architectural steps

- Move page-level hard-coded visual copy into database-backed view models where it is genuinely shared.
- Add real mail delivery for password reset instead of showing a demo link on-screen.
- Add admin tools for blocking dates, changing capacity, and managing exceptional schedules.
- Add integration tests around auth, cart restore, and checkout persistence.

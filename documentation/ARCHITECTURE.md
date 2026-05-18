# PICKLED Architecture

PICKLED keeps the visible booking experience centered on `COURTS`.

## Frontend

- `frontend/index.php` is the landing page.
- `frontend/pages/courts.php` is the primary court booking surface.
- `frontend/pages/social-play.php` handles community and open play.
- `frontend/pages/private.php` handles coaching and private lessons.
- `frontend/pages/cart.php` handles cart review, checkout, payment selection, timer warnings, and confirmation.
- `frontend/components/` stores reusable UI pieces such as payment methods.
- `frontend/includes/` stores shared header, navbar, and footer.

## Backend

- `backend/auth/` handles login/logout and the 1-minute auth cookie.
- `backend/includes/booking_system.php` contains session cart, waitlist, variant, and timer helpers.
- `backend/controllers/CheckoutController.php` contains checkout/payment calculations.
- `backend/models/BookingCatalog.php` documents the scalable court variant model.
- `backend/api/cart.php` exposes cart summary JSON for future asynchronous UI.
- `backend/database/schema.sql` contains the proposed production database schema.

## State Rules

- Authentication uses cookies only.
- Cart, booking timer, checkout notes, payment method, and waitlist data use PHP sessions.
- Cart data is never stored in cookies.

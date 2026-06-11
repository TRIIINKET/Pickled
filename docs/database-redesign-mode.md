# Database Redesign Mode

The current MySQL implementation is temporarily disabled while the database schema is being redesigned.

## Active Switch

`includes/config.php`

```php
'database' => [
    'enabled' => false,
    'redesign_mode' => true,
],
```

When `enabled` is `false`, `database/Database.php` blocks real PDO connections. This prevents schema-related errors from being shown to users while the old database is offline.

## Temporary Data Layer

`app/support/DatabaseRedesign.php` provides temporary demo data and session-backed records for:

- Demo users for player, coach, and admin login.
- Booking catalog variants for Court Green, Court Pink, and Social Play.
- Session-based carts and checkout booking snapshots.
- Empty or placeholder admin stats, logs, courts, and reports.

Demo login accounts:

- `player@example.com` / `password`
- `coach@example.com` / `password`
- `admin@example.com` / `password`

## Reconnection Checklist

After the new schema is finalized:

- Set `database.enabled` to `true`.
- Replace `DatabaseRedesign` fallbacks in repositories/services with new schema queries.
- Reconnect admin dashboard/report queries to the new aggregate tables or views.
- Reconnect booking availability, cart persistence, checkout, and booking history.
- Reconnect auth registration, password resets, profile updates, and notification logs.
- Remove or retire temporary `TODO(database-redesign)` comments once each area is migrated.


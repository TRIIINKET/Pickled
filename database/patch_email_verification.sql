USE pickled;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER role;

UPDATE users
SET is_verified = 1
WHERE role IN ('admin', 'coach')
   OR email IN ('admin@example.com', 'coach@example.com', 'player@example.com');

-- Creates profile rows for every existing user that does not have one.
-- Existing user_profiles rows are intentionally not modified.

START TRANSACTION;

INSERT INTO user_profiles (user_id, phone, city, province, avatar)
SELECT u.id, NULL, NULL, NULL, NULL
FROM users u
LEFT JOIN user_profiles up ON up.user_id = u.id
WHERE up.user_id IS NULL;

COMMIT;


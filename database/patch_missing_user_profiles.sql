-- Creates profile rows for every existing user that does not have one.
-- Replaces NULL profile fields with application-safe defaults.
-- Tightens defaults so future inserts cannot create NULL profile fields.

START TRANSACTION;

UPDATE user_profiles
SET
  phone = COALESCE(phone, ''),
  city = COALESCE(city, ''),
  province = COALESCE(province, ''),
  avatar = COALESCE(avatar, 'avatars/default.png')
WHERE phone IS NULL
   OR city IS NULL
   OR province IS NULL
   OR avatar IS NULL;

INSERT INTO user_profiles (user_id, phone, city, province, avatar)
SELECT u.id, '', '', '', 'avatars/default.png'
FROM users u
LEFT JOIN user_profiles up ON up.user_id = u.id
WHERE up.user_id IS NULL;

COMMIT;

ALTER TABLE user_profiles
  MODIFY phone VARCHAR(40) NOT NULL DEFAULT '',
  MODIFY city VARCHAR(120) NOT NULL DEFAULT '',
  MODIFY province VARCHAR(120) NOT NULL DEFAULT '',
  MODIFY avatar VARCHAR(255) NOT NULL DEFAULT 'avatars/default.png';

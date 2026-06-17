CREATE DATABASE IF NOT EXISTS pickled
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pickled;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'player',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  CONSTRAINT chk_users_role CHECK (role IN ('player', 'coach', 'admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_profiles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  city VARCHAR(120) NOT NULL DEFAULT '',
  province VARCHAR(120) NOT NULL DEFAULT '',
  avatar VARCHAR(255) NOT NULL DEFAULT 'avatars/default.png',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_profiles_user_id (user_id),
  CONSTRAINT fk_user_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coach_profiles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  specialization VARCHAR(160) NULL,
  bio TEXT NULL,
  experience VARCHAR(160) NULL,
  status VARCHAR(40) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coach_profiles_user_id (user_id),
  KEY idx_coach_profiles_status (status),
  CONSTRAINT fk_coach_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NULL,
  email VARCHAR(160) NOT NULL,
  token VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_reset_token (token),
  KEY idx_password_reset_email (email),
  KEY idx_password_reset_user_id (user_id),
  KEY idx_password_reset_expires_at (expires_at),
  KEY idx_password_reset_used (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password_hash, role)
VALUES
  ('Admin Demo', 'pickled.shopph@gmail.com', '$2y$12$fEHcjIeDNUe/k9zgcxG4MuQ.yKbnuW5EQnNYRzJeuBtnQ4sx.25nO', 'admin'),
  ('Coach Demo', 'coach@example.com', '$2y$12$ibR8MUreHNnonxJI.OPxNeSj6FXqyrcSDCHJs94MFMUxDXD5JgXZO', 'coach'),
  ('Player Demo', 'player@example.com', '$2y$12$ibR8MUreHNnonxJI.OPxNeSj6FXqyrcSDCHJs94MFMUxDXD5JgXZO', 'player')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password_hash = VALUES(password_hash),
  role = VALUES(role);

INSERT INTO user_profiles (user_id, phone, city, province, avatar)
SELECT u.id, '', '', '', 'avatars/default.png'
FROM users u
LEFT JOIN user_profiles up ON up.user_id = u.id
WHERE up.user_id IS NULL;

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

ALTER TABLE user_profiles
  MODIFY phone VARCHAR(40) NOT NULL DEFAULT '',
  MODIFY city VARCHAR(120) NOT NULL DEFAULT '',
  MODIFY province VARCHAR(120) NOT NULL DEFAULT '',
  MODIFY avatar VARCHAR(255) NOT NULL DEFAULT 'avatars/default.png';

INSERT INTO coach_profiles (user_id, specialization, bio, experience, status)
SELECT id, 'Private Coaching', 'Certified pickleball coach for beginner and intermediate players.', '3 years coaching experience', 'active'
FROM users
WHERE email = 'coach@example.com'
ON DUPLICATE KEY UPDATE
  specialization = VALUES(specialization),
  bio = VALUES(bio),
  experience = VALUES(experience),
  status = VALUES(status);

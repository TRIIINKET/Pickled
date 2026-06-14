-- FINAL_SEED.sql
-- Consolidated schema and seed file for PICKLED development databases.
-- Safe to run on a fresh database and safe to re-run for canonical seed rows.

CREATE DATABASE IF NOT EXISTS pickled
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pickled;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- AUTH / USERS
-- Source files: auth_user_management.sql, content-notes.txt
-- Dependency root: users
-- ============================================================

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
  phone VARCHAR(40) NULL,
  city VARCHAR(120) NULL,
  province VARCHAR(120) NULL,
  avatar VARCHAR(255) NULL,
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
  profile_image VARCHAR(255) NULL,
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

ALTER TABLE coach_profiles
  ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) NULL AFTER status;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_resets_token_hash (token_hash),
  KEY idx_password_resets_user_id (user_id),
  KEY idx_password_resets_expires_at (expires_at),
  CONSTRAINT fk_password_resets_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canonical login users. Passwords are stored as bcrypt hashes only.
INSERT INTO users (name, email, password_hash, role)
VALUES
  ('Admin Demo', 'admin@example.com', '$2y$12$ibR8MUreHNnonxJI.OPxNeSj6FXqyrcSDCHJs94MFMUxDXD5JgXZO', 'admin'),
  ('Player Demo', 'player@example.com', '$2y$12$ibR8MUreHNnonxJI.OPxNeSj6FXqyrcSDCHJs94MFMUxDXD5JgXZO', 'player'),
  ('Coach Anton', 'anton.coach@pickled.ph', '$2y$12$UUAm5M1ti1REXd03LwxYb.AMTUqcg6q2eBHdSakk.kcSwrI7Kb3Qq', 'coach'),
  ('Coach David', 'david.coach@pickled.ph', '$2y$12$XmTC0GuiTh0jORWuM3y68ORyw7r8vf86bzRKOJAmpOXlVyQSljmHu', 'coach'),
  ('Coach Kenji', 'kenji.coach@pickled.ph', '$2y$12$6nTX/XMV6ULy5t9kJQl3k.YDgqZO.KTVsEMtsocT9u/rcDxXYG.Gi', 'coach'),
  ('Coach Martina', 'martina.coach@pickled.ph', '$2y$12$cxAnlVf57pG4.VWj39Ee3uMAbHWJjJkqzM7Izk0dzK6OkbVO.ZMvC', 'coach'),
  ('Coach Sophia', 'sophia.coach@pickled.ph', '$2y$12$RWN6pWCkLmjnQ/95/DQDseKHiMSTOgurpgg1zsk6A2h9bLZzN6.ku', 'coach')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password_hash = VALUES(password_hash),
  role = VALUES(role);

-- Remove the legacy single demo coach account from older seeds.
DELETE FROM users
WHERE email = 'coach@example.com'
  AND role = 'coach';

INSERT INTO user_profiles (user_id, phone, city, province, avatar)
SELECT id, '09171234567', 'Makati', 'Metro Manila', 'avatars/player-demo.png'
FROM users
WHERE email = 'player@example.com'
ON DUPLICATE KEY UPDATE
  phone = VALUES(phone),
  city = VALUES(city),
  province = VALUES(province),
  avatar = VALUES(avatar);

INSERT INTO coach_profiles (user_id, specialization, bio, experience, status, profile_image)
SELECT u.id, seed.specialization, seed.bio, seed.experience, seed.status, seed.profile_image
FROM users u
JOIN (
  SELECT 'anton.coach@pickled.ph' AS email,
         'Defensive Play & Dinking Mastery' AS specialization,
         'Soft-game specialist who teaches patience, reset defense, dinking patterns, and consistency under pressure.' AS bio,
         '5+ years of Asian Pickleball tournament experience.' AS experience,
         'active' AS status,
         'anton.jpg' AS profile_image
  UNION ALL SELECT 'david.coach@pickled.ph',
         'Competitive Singles & Strategy',
         'High-energy tactical coach for intermediate players who want sharper court coverage, transition play, and kitchen control.',
         'Former collegiate tennis player turned pickleball pro.',
         'active',
         'david.jpg'
  UNION ALL SELECT 'kenji.coach@pickled.ph',
         'Power Hitting & Offensive Doubles',
         'Results-driven doubles coach focused on controlled aggression, power placement, poaching, and third-shot execution.',
         'Expert in modern offensive strategies and third shot drop mastery.',
         'active',
         'kenji.jpg'
  UNION ALL SELECT 'martina.coach@pickled.ph',
         'Technical Fundamentals & Youth Development',
         'Patient and detail-oriented coach who helps beginners build strong fundamentals with safe movement and confident technique.',
         'Certified IPTPA Level 1 Coach focused on biomechanics and injury prevention.',
         'active',
         'martina.jpg'
  UNION ALL SELECT 'sophia.coach@pickled.ph',
         'Social Play & Women''s Clinics',
         'Supportive group coach who builds inclusive, upbeat sessions for social players and women-focused clinics.',
         'Specializes in group dynamics and community-building through sport.',
         'active',
         'sophia.jpg'
) AS seed ON seed.email = u.email
WHERE u.role = 'coach'
ON DUPLICATE KEY UPDATE
  specialization = VALUES(specialization),
  bio = VALUES(bio),
  experience = VALUES(experience),
  status = VALUES(status),
  profile_image = VALUES(profile_image);

DELETE cp
FROM coach_profiles cp
LEFT JOIN users u ON u.id = cp.user_id
WHERE u.id IS NULL OR u.role <> 'coach';

-- ============================================================
-- COURTS / SERVICES
-- Source file: court_service_catalog.sql
-- Depends on: users only indirectly through later sessions
-- ============================================================

CREATE TABLE IF NOT EXISTS courts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  status VARCHAR(40) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_courts_slug (slug),
  KEY idx_courts_status (status),
  CONSTRAINT chk_courts_status CHECK (status IN ('active', 'inactive', 'maintenance'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_variants (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  court_id INT UNSIGNED NOT NULL,
  slug VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  category VARCHAR(120) NOT NULL,
  duration_label VARCHAR(80) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  participants_limit INT UNSIGNED NOT NULL,
  capacity INT UNSIGNED NOT NULL,
  image VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_booking_variants_slug (slug),
  KEY idx_booking_variants_court_id (court_id),
  KEY idx_booking_variants_court_active (court_id, active),
  KEY idx_booking_variants_category (category),
  KEY idx_booking_variants_active (active),
  CONSTRAINT fk_booking_variants_court
    FOREIGN KEY (court_id) REFERENCES courts(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT chk_booking_variants_price CHECK (price >= 0),
  CONSTRAINT chk_booking_variants_participants CHECK (participants_limit > 0),
  CONSTRAINT chk_booking_variants_capacity CHECK (capacity > 0),
  CONSTRAINT chk_booking_variants_active CHECK (active IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO courts (name, slug, status)
VALUES
  ('Court Green', 'green', 'active'),
  ('Court Pink', 'pink', 'active')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  status = VALUES(status);

INSERT INTO booking_variants
  (court_id, slug, name, category, duration_label, price, participants_limit, capacity, image, active)
SELECT c.id, seed.slug, seed.name, seed.category, seed.duration_label, seed.price,
       seed.participants_limit, seed.capacity, seed.image, seed.active
FROM courts c
JOIN (
  SELECT 'green' AS court_slug, 'green-court-rentals' AS slug, 'Court Rentals' AS name, 'Court Reservation' AS category, '1 hour' AS duration_label, 600.00 AS price, 6 AS participants_limit, 12 AS capacity, '../assets/img/court/court green-1.png' AS image, 1 AS active
  UNION ALL SELECT 'green', 'green-lessons', 'Lessons', 'Coaching', '1 hour', 500.00, 6, 12, '../assets/img/court/court green-1.png', 1
  UNION ALL SELECT 'green', 'green-private-coaching', 'Private Coaching', 'Coaching', '1 hour', 1200.00, 1, 4, '../assets/img/court/court green-1.png', 1
  UNION ALL SELECT 'green', 'green-training', 'Training', 'Training', '1 hour', 800.00, 6, 12, '../assets/img/court/court green-1.png', 1
  UNION ALL SELECT 'green', 'green-open-match-play', 'Open Match-Play', 'Social Play', '2 hours', 350.00, 8, 16, '../assets/img/court/social play-1.png', 1
  UNION ALL SELECT 'green', 'green-weekly-tournament', 'Weekly Tournament', 'Social Play', 'This week', 900.00, 1, 16, '../assets/img/court/social play-1.png', 1
  UNION ALL SELECT 'pink', 'pink-base-rate', 'Court Rental', 'Court Reservation', '1 hour', 400.00, 6, 10, '../assets/img/court/court pink-1.webp', 1
  UNION ALL SELECT 'pink', 'pink-kids-pickleball-class-ages-6-10', 'Kids Pickleball Class (Ages 6-10)', 'Academy', '1 hour', 350.00, 8, 10, '../assets/img/court/court pink-1.webp', 1
  UNION ALL SELECT 'pink', 'pink-youth-development-class-ages-11-17', 'Youth Development Class (Ages 11-17)', 'Academy', '1 hour', 350.00, 8, 10, '../assets/img/court/court pink-1.webp', 1
  UNION ALL SELECT 'pink', 'pink-parent-child-session', 'Parent & Child Session', 'Academy', '1 hour', 500.00, 2, 10, '../assets/img/court/court pink-1.webp', 1
  UNION ALL SELECT 'pink', 'pink-foundational-ages-6-10', 'Foundational Ages 6-10', 'Academy', '4 sessions', 1200.00, 8, 10, '../assets/img/court/academy.png', 1
  UNION ALL SELECT 'pink', 'pink-youth-development-ages-11-17', 'Youth Development Ages 11-17', 'Academy', '4 sessions', 1200.00, 8, 10, '../assets/img/court/academy.png', 1
  UNION ALL SELECT 'pink', 'pink-adult-beginner-bootcamp', 'Adult Beginner Bootcamp', 'Academy', '4 sessions', 1800.00, 8, 10, '../assets/img/court/academy.png', 1
  UNION ALL SELECT 'pink', 'pink-introductory-trial-class', 'Introductory Trial Class', 'Academy', '1 hour', 250.00, 8, 10, '../assets/img/court/academy.png', 1
  UNION ALL SELECT 'pink', 'pink-parent-child-trial', 'Parent & Child Trial', 'Academy', '1 hour', 500.00, 2, 10, '../assets/img/court/academy.png', 1
) AS seed ON seed.court_slug = c.slug
ON DUPLICATE KEY UPDATE
  court_id = VALUES(court_id),
  name = VALUES(name),
  category = VALUES(category),
  duration_label = VALUES(duration_label),
  price = VALUES(price),
  participants_limit = VALUES(participants_limit),
  capacity = VALUES(capacity),
  image = VALUES(image),
  active = VALUES(active);

-- ============================================================
-- COACH SESSIONS
-- Source file: sessions_coach_availability.sql
-- Depends on: users, booking_variants
-- ============================================================

CREATE TABLE IF NOT EXISTS sessions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  variant_id INT UNSIGNED NOT NULL,
  coach_user_id INT UNSIGNED NULL,
  session_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  capacity INT UNSIGNED NOT NULL,
  booked_count INT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(40) NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sessions_variant_slot (variant_id, session_date, start_time, end_time),
  KEY idx_sessions_variant_date (variant_id, session_date),
  KEY idx_sessions_coach_date (coach_user_id, session_date),
  KEY idx_sessions_status_date (status, session_date),
  CONSTRAINT fk_sessions_variant
    FOREIGN KEY (variant_id) REFERENCES booking_variants(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_sessions_coach_user
    FOREIGN KEY (coach_user_id) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT chk_sessions_time_range CHECK (start_time < end_time),
  CONSTRAINT chk_sessions_capacity CHECK (capacity > 0),
  CONSTRAINT chk_sessions_booked_count CHECK (booked_count <= capacity),
  CONSTRAINT chk_sessions_status CHECK (status IN ('open', 'full', 'cancelled', 'completed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coach_availability (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  coach_user_id INT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'available',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coach_availability_exact (coach_user_id, day_of_week, start_time, end_time),
  KEY idx_coach_availability_lookup (coach_user_id, day_of_week, start_time),
  KEY idx_coach_availability_status (status),
  CONSTRAINT fk_coach_availability_coach_user
    FOREIGN KEY (coach_user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT chk_coach_availability_day CHECK (day_of_week BETWEEN 0 AND 6),
  CONSTRAINT chk_coach_availability_time_range CHECK (start_time < end_time),
  CONSTRAINT chk_coach_availability_status CHECK (status IN ('available', 'unavailable', 'leave'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS trg_coach_availability_no_overlap_insert;
DROP TRIGGER IF EXISTS trg_coach_availability_no_overlap_update;

DELIMITER //

CREATE TRIGGER trg_coach_availability_no_overlap_insert
BEFORE INSERT ON coach_availability
FOR EACH ROW
BEGIN
  IF EXISTS (
    SELECT 1
    FROM coach_availability ca
    WHERE ca.coach_user_id = NEW.coach_user_id
      AND ca.day_of_week = NEW.day_of_week
      AND NEW.start_time < ca.end_time
      AND NEW.end_time > ca.start_time
    LIMIT 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Overlapping coach availability is not allowed.';
  END IF;
END//

CREATE TRIGGER trg_coach_availability_no_overlap_update
BEFORE UPDATE ON coach_availability
FOR EACH ROW
BEGIN
  IF EXISTS (
    SELECT 1
    FROM coach_availability ca
    WHERE ca.id <> NEW.id
      AND ca.coach_user_id = NEW.coach_user_id
      AND ca.day_of_week = NEW.day_of_week
      AND NEW.start_time < ca.end_time
      AND NEW.end_time > ca.start_time
    LIMIT 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Overlapping coach availability is not allowed.';
  END IF;
END//

DELIMITER ;

DELETE ca
FROM coach_availability ca
JOIN users u ON u.id = ca.coach_user_id
WHERE u.email IN (
  'anton.coach@pickled.ph',
  'david.coach@pickled.ph',
  'kenji.coach@pickled.ph',
  'martina.coach@pickled.ph',
  'sophia.coach@pickled.ph'
);

INSERT INTO coach_availability (coach_user_id, day_of_week, start_time, end_time, status)
SELECT u.id, schedule.day_of_week, schedule.start_time, schedule.end_time, schedule.status
FROM users u
JOIN (
  SELECT 1 AS day_of_week, '09:00:00' AS start_time, '12:00:00' AS end_time, 'available' AS status
  UNION ALL SELECT 3, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 5, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 2, '17:00:00', '20:00:00', 'available'
  UNION ALL SELECT 4, '17:00:00', '20:00:00', 'available'
) AS schedule
WHERE u.role = 'coach'
  AND u.email IN (
    'anton.coach@pickled.ph',
    'david.coach@pickled.ph',
    'kenji.coach@pickled.ph',
    'martina.coach@pickled.ph',
    'sophia.coach@pickled.ph'
  );

INSERT INTO sessions (variant_id, coach_user_id, session_date, start_time, end_time, capacity, booked_count, status)
SELECT v.id,
       coach.id,
       seed.session_date,
       seed.start_time,
       seed.end_time,
       seed.capacity,
       0,
       'open'
FROM booking_variants v
JOIN (
  SELECT 'green-private-coaching' AS slug, 'martina.coach@pickled.ph' AS coach_email, DATE('2026-06-15') AS session_date, '09:00:00' AS start_time, '10:00:00' AS end_time, 1 AS capacity
  UNION ALL SELECT 'green-court-rentals', NULL, DATE('2026-06-14'), '07:00:00', '08:00:00', 8
  UNION ALL SELECT 'pink-base-rate', NULL, DATE('2026-06-14'), '08:00:00', '09:00:00', 8
  UNION ALL SELECT 'green-lessons', 'david.coach@pickled.ph', DATE('2026-06-16'), '17:00:00', '18:00:00', 8
  UNION ALL SELECT 'pink-kids-pickleball-class-ages-6-10', 'sophia.coach@pickled.ph', DATE('2026-06-18'), '17:00:00', '18:00:00', 10
  UNION ALL SELECT 'green-open-match-play', NULL, DATE('2026-06-19'), '18:00:00', '20:00:00', 16
  UNION ALL SELECT 'green-weekly-tournament', NULL, DATE('2026-06-21'), '09:00:00', '12:00:00', 24
) AS seed ON seed.slug = v.slug
LEFT JOIN users coach ON coach.email = seed.coach_email AND coach.role = 'coach'
ON DUPLICATE KEY UPDATE
  coach_user_id = VALUES(coach_user_id),
  capacity = VALUES(capacity),
  status = VALUES(status),
  updated_at = CURRENT_TIMESTAMP;

-- ============================================================
-- PRIVATE PACKAGES
-- Source files: private_packages.sql, private_inquiries.sql
-- Depends on: users, coach_profiles
-- ============================================================

CREATE TABLE IF NOT EXISTS private_packages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(160) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  duration VARCHAR(80) NOT NULL,
  coach_profile_id INT UNSIGNED NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_private_packages_coach_profile (coach_profile_id),
  KEY idx_private_packages_status_created (status, created_at),
  CONSTRAINT fk_private_packages_coach_profile
    FOREIGN KEY (coach_profile_id) REFERENCES coach_profiles(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT chk_private_packages_price CHECK (price >= 0),
  CONSTRAINT chk_private_packages_status CHECK (status IN ('active', 'inactive', 'archived'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE private_packages pp
JOIN coach_profiles old_cp ON old_cp.id = pp.coach_profile_id
JOIN users old_u ON old_u.id = old_cp.user_id AND old_u.email = 'coach@example.com'
JOIN users martina_u ON martina_u.email = 'martina.coach@pickled.ph'
JOIN coach_profiles martina_cp ON martina_cp.user_id = martina_u.id
SET pp.coach_profile_id = martina_cp.id;

DELETE FROM users
WHERE email = 'coach@example.com'
  AND role = 'coach';

UPDATE private_packages pp
JOIN coach_profiles cp ON cp.id = pp.coach_profile_id
JOIN users u ON u.id = cp.user_id
SET pp.title = 'Private Coaching',
    pp.description = 'One-on-one or small-group coaching tailored to the player''s goals, timing, and skill level.',
    pp.price = 1200.00,
    pp.duration = '1 hour',
    pp.status = 'active'
WHERE pp.title = 'Private Coaching'
  AND u.email = 'martina.coach@pickled.ph';

INSERT INTO private_packages (title, description, price, duration, coach_profile_id, status)
SELECT 'Private Coaching',
       'One-on-one or small-group coaching tailored to the player''s goals, timing, and skill level.',
       1200.00,
       '1 hour',
       cp.id,
       'active'
FROM coach_profiles cp
JOIN users u ON u.id = cp.user_id
WHERE u.email = 'martina.coach@pickled.ph'
  AND NOT EXISTS (
    SELECT 1
    FROM private_packages pp
    WHERE pp.title = 'Private Coaching'
      AND pp.coach_profile_id = cp.id
  );

CREATE TABLE IF NOT EXISTS private_inquiries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  private_package_id INT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'new',
  admin_response TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_private_inquiries_user_created (user_id, created_at),
  KEY idx_private_inquiries_package_created (private_package_id, created_at),
  KEY idx_private_inquiries_status_created (status, created_at),
  CONSTRAINT fk_private_inquiries_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_private_inquiries_package
    FOREIGN KEY (private_package_id) REFERENCES private_packages(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT chk_private_inquiries_status CHECK (status IN ('new', 'in_review', 'responded', 'closed', 'cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BOOKINGS / CART
-- Source files: booking_management.sql, cart_system.sql
-- Depends on: users, sessions
-- ============================================================

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  reference VARCHAR(40) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(80) NOT NULL,
  payment_status VARCHAR(80) NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  cancellation_label VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bookings_reference (reference),
  KEY idx_bookings_user_created (user_id, created_at),
  KEY idx_bookings_status_created (status, created_at),
  KEY idx_bookings_payment_status_created (payment_status, created_at),
  CONSTRAINT fk_bookings_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT chk_bookings_status CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled')),
  CONSTRAINT chk_bookings_subtotal CHECK (subtotal >= 0),
  CONSTRAINT chk_bookings_payment_fee CHECK (payment_fee >= 0),
  CONSTRAINT chk_bookings_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  session_id INT UNSIGNED NOT NULL,
  variant_slug VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  court VARCHAR(80) NOT NULL,
  category VARCHAR(120) NOT NULL,
  duration_label VARCHAR(80) NOT NULL,
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_booking_items_booking_id (booking_id),
  KEY idx_booking_items_session_id (session_id),
  KEY idx_booking_items_booking_date (booking_date),
  KEY idx_booking_items_service_date (name, booking_date),
  CONSTRAINT fk_booking_items_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_booking_items_session
    FOREIGN KEY (session_id) REFERENCES sessions(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT chk_booking_items_time_range CHECK (start_time < end_time),
  CONSTRAINT chk_booking_items_quantity CHECK (quantity > 0),
  CONSTRAINT chk_booking_items_unit_price CHECK (unit_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  started_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carts_user_id (user_id),
  KEY idx_carts_expires_at (expires_at),
  CONSTRAINT fk_carts_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id INT UNSIGNED NOT NULL,
  session_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cart_items_cart_session (cart_id, session_id),
  KEY idx_cart_items_cart_id (cart_id),
  KEY idx_cart_items_session_id (session_id),
  CONSTRAINT fk_cart_items_cart
    FOREIGN KEY (cart_id) REFERENCES carts(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_cart_items_session
    FOREIGN KEY (session_id) REFERENCES sessions(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT chk_cart_items_quantity CHECK (quantity > 0),
  CONSTRAINT chk_cart_items_unit_price CHECK (unit_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No default booking/cart rows are inserted. These tables are runtime-owned.

-- ============================================================
-- PAYMENTS
-- Source file: payments.sql
-- Depends on: bookings, users
-- ============================================================

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  proof_image VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(80) NOT NULL,
  reference_number VARCHAR(120) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  remarks TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payments_booking_created (booking_id, created_at),
  KEY idx_payments_status_created (status, created_at),
  KEY idx_payments_reference_number (reference_number),
  KEY idx_payments_reviewed_by (reviewed_by),
  CONSTRAINT fk_payments_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_payments_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT chk_payments_amount CHECK (amount >= 0),
  CONSTRAINT chk_payments_status CHECK (status IN ('pending', 'approved', 'rejected'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTIFICATIONS
-- Source file: notifications.sql
-- Depends on: users
-- ============================================================

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  link VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user_created (user_id, created_at),
  KEY idx_notifications_user_unread (user_id, is_read, created_at),
  KEY idx_notifications_type_created (type, created_at),
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT chk_notifications_type CHECK (
    type IN (
      'info',
      'success',
      'warning',
      'error',
      'booking_created',
      'booking_confirmed',
      'booking_cancelled',
      'booking_expired',
      'payment_uploaded',
      'payment_approved',
      'payment_rejected',
      'session_updated'
    )
  ),
  CONSTRAINT chk_notifications_is_read CHECK (is_read IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FEEDBACK / LOGS
-- Source files: feedback.sql, admin_logs.sql
-- Depends on: users, bookings, booking_items, sessions
-- ============================================================

CREATE TABLE IF NOT EXISTS feedback (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  booking_item_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NOT NULL,
  coach_user_id INT UNSIGNED NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_feedback_booking (booking_id),
  KEY idx_feedback_user_created (user_id, created_at),
  KEY idx_feedback_coach_created (coach_user_id, created_at),
  KEY idx_feedback_rating_created (rating, created_at),
  KEY idx_feedback_booking_item (booking_item_id),
  CONSTRAINT fk_feedback_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_feedback_booking_item
    FOREIGN KEY (booking_item_id) REFERENCES booking_items(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT fk_feedback_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_feedback_coach_user
    FOREIGN KEY (coach_user_id) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS trg_feedback_before_insert;
DROP TRIGGER IF EXISTS trg_feedback_before_update;

DELIMITER //

CREATE TRIGGER trg_feedback_before_insert
BEFORE INSERT ON feedback
FOR EACH ROW
BEGIN
  DECLARE item_coach_user_id INT UNSIGNED DEFAULT NULL;

  IF NOT EXISTS (
    SELECT 1
    FROM bookings b
    WHERE b.id = NEW.booking_id
      AND b.status = 'completed'
    LIMIT 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback requires a completed booking.';
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM bookings b
    WHERE b.id = NEW.booking_id
      AND b.user_id = NEW.user_id
    LIMIT 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback user must own the booking.';
  END IF;

  IF NEW.booking_item_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1
      FROM booking_items bi
      WHERE bi.id = NEW.booking_item_id
        AND bi.booking_id = NEW.booking_id
      LIMIT 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback booking item must belong to the booking.';
    END IF;

    SELECT s.coach_user_id
    INTO item_coach_user_id
    FROM booking_items bi
    LEFT JOIN sessions s ON s.id = bi.session_id
    WHERE bi.id = NEW.booking_item_id
    LIMIT 1;

    IF NEW.coach_user_id IS NULL THEN
      SET NEW.coach_user_id = item_coach_user_id;
    END IF;
  END IF;

  IF NEW.coach_user_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.id = NEW.coach_user_id
        AND u.role = 'coach'
      LIMIT 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback coach must be a coach user.';
    END IF;

    IF NOT EXISTS (
      SELECT 1
      FROM booking_items bi
      JOIN sessions s ON s.id = bi.session_id
      WHERE bi.booking_id = NEW.booking_id
        AND s.coach_user_id = NEW.coach_user_id
      LIMIT 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback coach must be assigned to the booked session.';
    END IF;
  END IF;
END//

CREATE TRIGGER trg_feedback_before_update
BEFORE UPDATE ON feedback
FOR EACH ROW
BEGIN
  DECLARE item_coach_user_id INT UNSIGNED DEFAULT NULL;

  IF NOT EXISTS (
    SELECT 1
    FROM bookings b
    WHERE b.id = NEW.booking_id
      AND b.status = 'completed'
    LIMIT 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback requires a completed booking.';
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM bookings b
    WHERE b.id = NEW.booking_id
      AND b.user_id = NEW.user_id
    LIMIT 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback user must own the booking.';
  END IF;

  IF NEW.booking_item_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1
      FROM booking_items bi
      WHERE bi.id = NEW.booking_item_id
        AND bi.booking_id = NEW.booking_id
      LIMIT 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback booking item must belong to the booking.';
    END IF;

    SELECT s.coach_user_id
    INTO item_coach_user_id
    FROM booking_items bi
    LEFT JOIN sessions s ON s.id = bi.session_id
    WHERE bi.id = NEW.booking_item_id
    LIMIT 1;

    IF NEW.coach_user_id IS NULL THEN
      SET NEW.coach_user_id = item_coach_user_id;
    END IF;
  END IF;

  IF NEW.coach_user_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1
      FROM users u
      WHERE u.id = NEW.coach_user_id
        AND u.role = 'coach'
      LIMIT 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback coach must be a coach user.';
    END IF;

    IF NOT EXISTS (
      SELECT 1
      FROM booking_items bi
      JOIN sessions s ON s.id = bi.session_id
      WHERE bi.booking_id = NEW.booking_id
        AND s.coach_user_id = NEW.coach_user_id
      LIMIT 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback coach must be assigned to the booked session.';
    END IF;
  END IF;
END//

DELIMITER ;

CREATE TABLE IF NOT EXISTS admin_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NOT NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id INT UNSIGNED NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_admin_logs_admin_id (admin_id),
  KEY idx_admin_logs_created_at (created_at),
  KEY idx_admin_logs_entity_type (entity_type),
  CONSTRAINT fk_admin_logs_admin
    FOREIGN KEY (admin_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- End of FINAL_SEED.sql

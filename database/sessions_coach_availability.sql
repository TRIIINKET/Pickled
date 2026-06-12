CREATE DATABASE IF NOT EXISTS pickled
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pickled;

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

INSERT INTO coach_availability (coach_user_id, day_of_week, start_time, end_time, status)
SELECT u.id, schedule.day_of_week, schedule.start_time, schedule.end_time, schedule.status
FROM users u
JOIN (
  SELECT 1 AS day_of_week, '09:00:00' AS start_time, '12:00:00' AS end_time, 'available' AS status
  UNION ALL SELECT 3, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 5, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 2, '17:00:00', '20:00:00', 'available'
  UNION ALL SELECT 4, '17:00:00', '20:00:00', 'available'
) schedule
WHERE u.role = 'coach'
  AND u.email = 'coach@example.com'
ON DUPLICATE KEY UPDATE
  status = VALUES(status),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO sessions (variant_id, coach_user_id, session_date, start_time, end_time, capacity, booked_count, status)
SELECT v.id,
       CASE WHEN v.category IN ('Coaching', 'Private Coaching', 'Academy') THEN coach.id ELSE NULL END,
       seed.session_date, seed.start_time, seed.end_time, seed.capacity, 0, 'open'
FROM booking_variants v
JOIN (
  SELECT 'green-private-coaching' AS slug, DATE('2026-06-15') AS session_date, '09:00:00' AS start_time, '10:00:00' AS end_time, 1 AS capacity
  UNION ALL SELECT 'green-lessons', DATE('2026-06-16'), '17:00:00', '18:00:00', 8
  UNION ALL SELECT 'pink-kids-pickleball-class-ages-6-10', DATE('2026-06-18'), '17:00:00', '18:00:00', 10
  UNION ALL SELECT 'green-open-match-play', DATE('2026-06-19'), '18:00:00', '20:00:00', 16
  UNION ALL SELECT 'green-weekly-tournament', DATE('2026-06-21'), '09:00:00', '12:00:00', 24
) seed ON seed.slug = v.slug
LEFT JOIN users coach ON coach.email = 'coach@example.com' AND coach.role = 'coach'
ON DUPLICATE KEY UPDATE
  coach_user_id = VALUES(coach_user_id),
  capacity = VALUES(capacity),
  status = VALUES(status),
  updated_at = CURRENT_TIMESTAMP;

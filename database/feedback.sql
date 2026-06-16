CREATE DATABASE IF NOT EXISTS pickled CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pickled;

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
      AND LOWER(b.status) NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
      AND EXISTS (
        SELECT 1
        FROM booking_items bi
        WHERE bi.booking_id = b.id
      )
    LIMIT 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback is not available for this booking.';
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

    SELECT COALESCE(bi.coach_user_id, s.coach_user_id)
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
      LEFT JOIN sessions s ON s.id = bi.session_id
      WHERE bi.booking_id = NEW.booking_id
        AND COALESCE(bi.coach_user_id, s.coach_user_id) = NEW.coach_user_id
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
      AND LOWER(b.status) NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
      AND EXISTS (
        SELECT 1
        FROM booking_items bi
        WHERE bi.booking_id = b.id
      )
    LIMIT 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback is not available for this booking.';
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

    SELECT COALESCE(bi.coach_user_id, s.coach_user_id)
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
      LEFT JOIN sessions s ON s.id = bi.session_id
      WHERE bi.booking_id = NEW.booking_id
        AND COALESCE(bi.coach_user_id, s.coach_user_id) = NEW.coach_user_id
      LIMIT 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Feedback coach must be assigned to the booked session.';
    END IF;
  END IF;
END//

DELIMITER ;

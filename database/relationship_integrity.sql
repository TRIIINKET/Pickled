CREATE DATABASE IF NOT EXISTS pickled
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pickled;

DROP PROCEDURE IF EXISTS migrate_relationship_integrity;

DELIMITER //

CREATE PROCEDURE migrate_relationship_integrity()
BEGIN
  UPDATE password_reset pr
  LEFT JOIN users existing_user ON existing_user.id = pr.user_id
  JOIN users email_user ON LOWER(email_user.email) = LOWER(pr.email)
  SET pr.user_id = email_user.id
  WHERE pr.user_id IS NULL
     OR pr.user_id <= 0
     OR existing_user.id IS NULL;

  UPDATE password_reset pr
  LEFT JOIN users u ON u.id = pr.user_id
  SET pr.user_id = NULL
  WHERE pr.user_id IS NOT NULL
    AND u.id IS NULL;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'password_reset'
      AND COLUMN_NAME = 'user_id'
      AND REFERENCED_TABLE_NAME = 'users'
  ) THEN
    ALTER TABLE password_reset MODIFY user_id INT UNSIGNED NULL;
    ALTER TABLE password_reset
      ADD CONSTRAINT fk_password_reset_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE SET NULL
      ON UPDATE CASCADE;
  END IF;

  UPDATE booking_items bi
  JOIN sessions s ON s.id = bi.session_id
  SET bi.coach_user_id = s.coach_user_id
  WHERE bi.coach_user_id IS NULL
    AND s.coach_user_id IS NOT NULL;

  UPDATE cart_items ci
  JOIN sessions s ON s.id = ci.session_id
  SET ci.coach_user_id = s.coach_user_id
  WHERE ci.coach_user_id IS NULL
    AND s.coach_user_id IS NOT NULL;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'booking_items'
      AND COLUMN_NAME = 'coach_user_id'
      AND REFERENCED_TABLE_NAME = 'users'
  ) THEN
    ALTER TABLE booking_items
      ADD CONSTRAINT fk_booking_items_coach_user
      FOREIGN KEY (coach_user_id) REFERENCES users(id)
      ON DELETE SET NULL
      ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cart_items'
      AND COLUMN_NAME = 'variant_id'
      AND REFERENCED_TABLE_NAME = 'booking_variants'
  ) THEN
    ALTER TABLE cart_items
      ADD CONSTRAINT fk_cart_items_variant
      FOREIGN KEY (variant_id) REFERENCES booking_variants(id)
      ON DELETE RESTRICT
      ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cart_items'
      AND COLUMN_NAME = 'coach_user_id'
      AND REFERENCED_TABLE_NAME = 'users'
  ) THEN
    ALTER TABLE cart_items
      ADD CONSTRAINT fk_cart_items_coach_user
      FOREIGN KEY (coach_user_id) REFERENCES users(id)
      ON DELETE SET NULL
      ON UPDATE CASCADE;
  END IF;
END//

CALL migrate_relationship_integrity()//
DROP PROCEDURE IF EXISTS migrate_relationship_integrity//

DROP TRIGGER IF EXISTS trg_password_reset_user_before_insert//
CREATE TRIGGER trg_password_reset_user_before_insert
BEFORE INSERT ON password_reset
FOR EACH ROW
BEGIN
  IF (NEW.user_id IS NULL OR NEW.user_id = 0)
     AND NEW.email IS NOT NULL
     AND TRIM(NEW.email) <> '' THEN
    SET NEW.user_id = (
      SELECT u.id
      FROM users u
      WHERE LOWER(u.email) = LOWER(NEW.email)
      LIMIT 1
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_password_reset_user_before_update//
CREATE TRIGGER trg_password_reset_user_before_update
BEFORE UPDATE ON password_reset
FOR EACH ROW
BEGIN
  IF (NEW.user_id IS NULL OR NEW.user_id = 0)
     AND NEW.email IS NOT NULL
     AND TRIM(NEW.email) <> '' THEN
    SET NEW.user_id = (
      SELECT u.id
      FROM users u
      WHERE LOWER(u.email) = LOWER(NEW.email)
      LIMIT 1
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_booking_items_coach_before_insert//
CREATE TRIGGER trg_booking_items_coach_before_insert
BEFORE INSERT ON booking_items
FOR EACH ROW
BEGIN
  IF NEW.coach_user_id IS NULL
     AND NEW.session_id IS NOT NULL THEN
    SET NEW.coach_user_id = (
      SELECT s.coach_user_id
      FROM sessions s
      WHERE s.id = NEW.session_id
      LIMIT 1
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_booking_items_coach_before_update//
CREATE TRIGGER trg_booking_items_coach_before_update
BEFORE UPDATE ON booking_items
FOR EACH ROW
BEGIN
  IF NEW.coach_user_id IS NULL
     AND NEW.session_id IS NOT NULL THEN
    SET NEW.coach_user_id = (
      SELECT s.coach_user_id
      FROM sessions s
      WHERE s.id = NEW.session_id
      LIMIT 1
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_cart_items_coach_before_insert//
CREATE TRIGGER trg_cart_items_coach_before_insert
BEFORE INSERT ON cart_items
FOR EACH ROW
BEGIN
  IF NEW.coach_user_id IS NULL
     AND NEW.session_id IS NOT NULL THEN
    SET NEW.coach_user_id = (
      SELECT s.coach_user_id
      FROM sessions s
      WHERE s.id = NEW.session_id
      LIMIT 1
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_cart_items_coach_before_update//
CREATE TRIGGER trg_cart_items_coach_before_update
BEFORE UPDATE ON cart_items
FOR EACH ROW
BEGIN
  IF NEW.coach_user_id IS NULL
     AND NEW.session_id IS NOT NULL THEN
    SET NEW.coach_user_id = (
      SELECT s.coach_user_id
      FROM sessions s
      WHERE s.id = NEW.session_id
      LIMIT 1
    );
  END IF;
END//

DELIMITER ;

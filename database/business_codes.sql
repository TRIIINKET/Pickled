CREATE DATABASE IF NOT EXISTS pickled CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pickled;

-- Auxiliary/support table, not a core ERD entity.
-- Purpose: stores sequence counters used to generate business/reference
-- codes such as booking references, payment references, user codes,
-- session codes, and inquiry codes. This supports reference number
-- generation but is not counted as one of the 18 core ERD entities.
CREATE TABLE IF NOT EXISTS business_code_sequences (
  entity VARCHAR(40) NOT NULL,
  last_value INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS migrate_users_business_codes;
DROP PROCEDURE IF EXISTS migrate_sessions_business_codes;
DROP PROCEDURE IF EXISTS migrate_payments_business_codes;
DROP PROCEDURE IF EXISTS migrate_private_inquiries_business_codes;

DELIMITER //

CREATE PROCEDURE migrate_users_business_codes()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'user_code'
  ) THEN
    ALTER TABLE users ADD COLUMN user_code VARCHAR(50) NULL AFTER id;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'is_verified'
  ) THEN
    ALTER TABLE users ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER user_code;
  END IF;

  UPDATE users
  SET user_code = CONCAT('USR-', LPAD(id, 6, '0'))
  WHERE user_code IS NULL
     OR TRIM(user_code) = ''
     OR user_code NOT REGEXP '^USR-[0-9]{6}$';

  ALTER TABLE users
    MODIFY user_code VARCHAR(50) NOT NULL,
    MODIFY is_verified TINYINT(1) NOT NULL DEFAULT 0;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'user_code'
      AND NON_UNIQUE = 0
  ) THEN
    ALTER TABLE users ADD UNIQUE KEY uq_users_user_code (user_code);
  END IF;

  INSERT INTO business_code_sequences (entity, last_value)
  SELECT 'users', COALESCE(MAX(CAST(SUBSTRING(user_code, 5) AS UNSIGNED)), 0)
  FROM users
  WHERE user_code REGEXP '^USR-[0-9]{6}$'
  ON DUPLICATE KEY UPDATE
    last_value = GREATEST(last_value, VALUES(last_value));
END//

CREATE PROCEDURE migrate_sessions_business_codes()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sessions'
      AND COLUMN_NAME = 'session_code'
  ) THEN
    ALTER TABLE sessions ADD COLUMN session_code VARCHAR(50) NULL AFTER id;
  END IF;

  UPDATE sessions
  SET session_code = CONCAT('SES-', LPAD(id, 6, '0'))
  WHERE session_code IS NULL
     OR TRIM(session_code) = ''
     OR session_code NOT REGEXP '^SES-[0-9]{6}$';

  ALTER TABLE sessions
    MODIFY session_code VARCHAR(50) NOT NULL;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sessions'
      AND COLUMN_NAME = 'session_code'
      AND NON_UNIQUE = 0
  ) THEN
    ALTER TABLE sessions ADD UNIQUE KEY uq_sessions_session_code (session_code);
  END IF;

  INSERT INTO business_code_sequences (entity, last_value)
  SELECT 'sessions', COALESCE(MAX(CAST(SUBSTRING(session_code, 5) AS UNSIGNED)), 0)
  FROM sessions
  WHERE session_code REGEXP '^SES-[0-9]{6}$'
  ON DUPLICATE KEY UPDATE
    last_value = GREATEST(last_value, VALUES(last_value));
END//

CREATE PROCEDURE migrate_payments_business_codes()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payments'
      AND COLUMN_NAME = 'payment_code'
  ) THEN
    ALTER TABLE payments ADD COLUMN payment_code VARCHAR(50) NULL AFTER id;
  END IF;

  UPDATE payments
  SET payment_code = CONCAT('PAY-', LPAD(id, 6, '0'))
  WHERE payment_code IS NULL
     OR TRIM(payment_code) = ''
     OR payment_code NOT REGEXP '^PAY-[0-9]{6}$';

  ALTER TABLE payments
    MODIFY payment_code VARCHAR(50) NOT NULL;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payments'
      AND COLUMN_NAME = 'payment_code'
      AND NON_UNIQUE = 0
  ) THEN
    ALTER TABLE payments ADD UNIQUE KEY uq_payments_payment_code (payment_code);
  END IF;

  INSERT INTO business_code_sequences (entity, last_value)
  SELECT 'payments', COALESCE(MAX(CAST(SUBSTRING(payment_code, 5) AS UNSIGNED)), 0)
  FROM payments
  WHERE payment_code REGEXP '^PAY-[0-9]{6}$'
  ON DUPLICATE KEY UPDATE
    last_value = GREATEST(last_value, VALUES(last_value));
END//

CREATE PROCEDURE migrate_private_inquiries_business_codes()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'private_inquiries'
      AND COLUMN_NAME = 'inquiry_code'
  ) THEN
    ALTER TABLE private_inquiries ADD COLUMN inquiry_code VARCHAR(50) NULL AFTER id;
  END IF;

  UPDATE private_inquiries
  SET inquiry_code = CONCAT('INQ-', LPAD(id, 6, '0'))
  WHERE inquiry_code IS NULL
     OR TRIM(inquiry_code) = ''
     OR inquiry_code NOT REGEXP '^INQ-[0-9]{6}$';

  ALTER TABLE private_inquiries
    MODIFY inquiry_code VARCHAR(50) NOT NULL;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'private_inquiries'
      AND COLUMN_NAME = 'inquiry_code'
      AND NON_UNIQUE = 0
  ) THEN
    ALTER TABLE private_inquiries ADD UNIQUE KEY uq_private_inquiries_inquiry_code (inquiry_code);
  END IF;

  INSERT INTO business_code_sequences (entity, last_value)
  SELECT 'private_inquiries', COALESCE(MAX(CAST(SUBSTRING(inquiry_code, 5) AS UNSIGNED)), 0)
  FROM private_inquiries
  WHERE inquiry_code REGEXP '^INQ-[0-9]{6}$'
  ON DUPLICATE KEY UPDATE
    last_value = GREATEST(last_value, VALUES(last_value));
END//

CALL migrate_users_business_codes()//
CALL migrate_sessions_business_codes()//
CALL migrate_payments_business_codes()//
CALL migrate_private_inquiries_business_codes()//

DROP PROCEDURE IF EXISTS migrate_users_business_codes//
DROP PROCEDURE IF EXISTS migrate_sessions_business_codes//
DROP PROCEDURE IF EXISTS migrate_payments_business_codes//
DROP PROCEDURE IF EXISTS migrate_private_inquiries_business_codes//

DROP TRIGGER IF EXISTS trg_users_business_code_before_insert//
CREATE TRIGGER trg_users_business_code_before_insert
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
  DECLARE next_code_number INT UNSIGNED DEFAULT 0;

  INSERT INTO business_code_sequences (entity, last_value)
  VALUES ('users', 0)
  ON DUPLICATE KEY UPDATE last_value = last_value;

  IF NEW.user_code IS NULL OR TRIM(NEW.user_code) = '' THEN
    UPDATE business_code_sequences
    SET last_value = last_value + 1
    WHERE entity = 'users';

    SELECT last_value
    INTO next_code_number
    FROM business_code_sequences
    WHERE entity = 'users';

    SET NEW.user_code = CONCAT('USR-', LPAD(next_code_number, 6, '0'));
  ELSEIF NEW.user_code REGEXP '^USR-[0-9]{6}$' THEN
    UPDATE business_code_sequences
    SET last_value = GREATEST(last_value, CAST(SUBSTRING(NEW.user_code, 5) AS UNSIGNED))
    WHERE entity = 'users';
  END IF;

  IF NEW.is_verified IS NULL THEN
    SET NEW.is_verified = 0;
  END IF;
END//

DROP TRIGGER IF EXISTS trg_sessions_business_code_before_insert//
CREATE TRIGGER trg_sessions_business_code_before_insert
BEFORE INSERT ON sessions
FOR EACH ROW
BEGIN
  DECLARE next_code_number INT UNSIGNED DEFAULT 0;

  INSERT INTO business_code_sequences (entity, last_value)
  VALUES ('sessions', 0)
  ON DUPLICATE KEY UPDATE last_value = last_value;

  IF NEW.session_code IS NULL OR TRIM(NEW.session_code) = '' THEN
    UPDATE business_code_sequences
    SET last_value = last_value + 1
    WHERE entity = 'sessions';

    SELECT last_value
    INTO next_code_number
    FROM business_code_sequences
    WHERE entity = 'sessions';

    SET NEW.session_code = CONCAT('SES-', LPAD(next_code_number, 6, '0'));
  ELSEIF NEW.session_code REGEXP '^SES-[0-9]{6}$' THEN
    UPDATE business_code_sequences
    SET last_value = GREATEST(last_value, CAST(SUBSTRING(NEW.session_code, 5) AS UNSIGNED))
    WHERE entity = 'sessions';
  END IF;
END//

DROP TRIGGER IF EXISTS trg_payments_business_code_before_insert//
CREATE TRIGGER trg_payments_business_code_before_insert
BEFORE INSERT ON payments
FOR EACH ROW
BEGIN
  DECLARE next_code_number INT UNSIGNED DEFAULT 0;

  INSERT INTO business_code_sequences (entity, last_value)
  VALUES ('payments', 0)
  ON DUPLICATE KEY UPDATE last_value = last_value;

  IF NEW.payment_code IS NULL OR TRIM(NEW.payment_code) = '' THEN
    UPDATE business_code_sequences
    SET last_value = last_value + 1
    WHERE entity = 'payments';

    SELECT last_value
    INTO next_code_number
    FROM business_code_sequences
    WHERE entity = 'payments';

    SET NEW.payment_code = CONCAT('PAY-', LPAD(next_code_number, 6, '0'));
  ELSEIF NEW.payment_code REGEXP '^PAY-[0-9]{6}$' THEN
    UPDATE business_code_sequences
    SET last_value = GREATEST(last_value, CAST(SUBSTRING(NEW.payment_code, 5) AS UNSIGNED))
    WHERE entity = 'payments';
  END IF;
END//

DROP TRIGGER IF EXISTS trg_private_inquiries_business_code_before_insert//
CREATE TRIGGER trg_private_inquiries_business_code_before_insert
BEFORE INSERT ON private_inquiries
FOR EACH ROW
BEGIN
  DECLARE next_code_number INT UNSIGNED DEFAULT 0;

  INSERT INTO business_code_sequences (entity, last_value)
  VALUES ('private_inquiries', 0)
  ON DUPLICATE KEY UPDATE last_value = last_value;

  IF NEW.inquiry_code IS NULL OR TRIM(NEW.inquiry_code) = '' THEN
    UPDATE business_code_sequences
    SET last_value = last_value + 1
    WHERE entity = 'private_inquiries';

    SELECT last_value
    INTO next_code_number
    FROM business_code_sequences
    WHERE entity = 'private_inquiries';

    SET NEW.inquiry_code = CONCAT('INQ-', LPAD(next_code_number, 6, '0'));
  ELSEIF NEW.inquiry_code REGEXP '^INQ-[0-9]{6}$' THEN
    UPDATE business_code_sequences
    SET last_value = GREATEST(last_value, CAST(SUBSTRING(NEW.inquiry_code, 5) AS UNSIGNED))
    WHERE entity = 'private_inquiries';
  END IF;
END//

DELIMITER ;

UPDATE bookings
SET reference = CONCAT('PKL-', LPAD(id, 6, '0'))
WHERE reference IS NULL
   OR TRIM(reference) = '';

DELIMITER $$

DROP PROCEDURE IF EXISTS patch_password_management $$
CREATE PROCEDURE patch_password_management()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'password_reset'
  ) THEN
    CREATE TABLE password_reset (
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
  ELSE
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND column_name = 'user_id'
    ) THEN
      ALTER TABLE password_reset ADD COLUMN user_id INT NULL AFTER id;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND column_name = 'email'
    ) THEN
      ALTER TABLE password_reset ADD COLUMN email VARCHAR(160) NULL AFTER user_id;
      UPDATE password_reset SET email = '' WHERE email IS NULL;
      ALTER TABLE password_reset MODIFY email VARCHAR(160) NOT NULL;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND column_name = 'token'
    ) THEN
      ALTER TABLE password_reset ADD COLUMN token VARCHAR(128) NULL AFTER email;
      UPDATE password_reset
      SET token = LOWER(SHA2(CONCAT(UUID(), '-', id), 256))
      WHERE token IS NULL OR token = '';
      ALTER TABLE password_reset MODIFY token VARCHAR(128) NOT NULL;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND column_name = 'expires_at'
    ) THEN
      ALTER TABLE password_reset ADD COLUMN expires_at DATETIME NULL AFTER token;
      UPDATE password_reset
      SET expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
      WHERE expires_at IS NULL;
      ALTER TABLE password_reset MODIFY expires_at DATETIME NOT NULL;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND column_name = 'used'
    ) THEN
      ALTER TABLE password_reset ADD COLUMN used TINYINT(1) NOT NULL DEFAULT 0 AFTER expires_at;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND column_name = 'used_at'
    ) THEN
      ALTER TABLE password_reset ADD COLUMN used_at DATETIME NULL AFTER used;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND column_name = 'created_at'
    ) THEN
      ALTER TABLE password_reset ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER used_at;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND index_name = 'uq_password_reset_token'
    ) THEN
      ALTER TABLE password_reset ADD UNIQUE KEY uq_password_reset_token (token);
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND index_name = 'idx_password_reset_email'
    ) THEN
      ALTER TABLE password_reset ADD KEY idx_password_reset_email (email);
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND index_name = 'idx_password_reset_user_id'
    ) THEN
      ALTER TABLE password_reset ADD KEY idx_password_reset_user_id (user_id);
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND index_name = 'idx_password_reset_expires_at'
    ) THEN
      ALTER TABLE password_reset ADD KEY idx_password_reset_expires_at (expires_at);
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'password_reset'
        AND index_name = 'idx_password_reset_used'
    ) THEN
      ALTER TABLE password_reset ADD KEY idx_password_reset_used (used);
    END IF;
  END IF;

  IF EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'password_resets'
  ) THEN
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'password_resets'
        AND column_name = 'email'
    ) THEN
      ALTER TABLE password_resets ADD COLUMN email VARCHAR(160) NULL AFTER user_id;
    END IF;

    UPDATE password_resets pr
    LEFT JOIN users u ON u.id = pr.user_id
    SET pr.email = COALESCE(NULLIF(pr.email, ''), u.email, CONCAT('legacy-reset-', pr.id, '@local.invalid'))
    WHERE pr.email IS NULL OR pr.email = '';

    INSERT IGNORE INTO password_reset (user_id, email, token, expires_at, used, used_at, created_at)
    SELECT
      pr.user_id,
      pr.email,
      pr.token_hash,
      pr.expires_at,
      CASE WHEN pr.used_at IS NULL THEN 0 ELSE 1 END,
      pr.used_at,
      pr.created_at
    FROM password_resets pr
    WHERE pr.token_hash IS NOT NULL
      AND pr.token_hash <> '';

    DROP TABLE password_resets;
  END IF;
END $$

CALL patch_password_management() $$
DROP PROCEDURE IF EXISTS patch_password_management $$

DELIMITER ;

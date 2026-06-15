CREATE DATABASE IF NOT EXISTS pickled CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pickled;

CREATE TABLE IF NOT EXISTS private_packages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(160) NOT NULL,
  category VARCHAR(120) NOT NULL DEFAULT 'Private Coaching',
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  duration VARCHAR(80) NOT NULL,
  capacity INT UNSIGNED NULL,
  coach_profile_id INT UNSIGNED NULL,
  required_coach TINYINT(1) NOT NULL DEFAULT 1,
  slug VARCHAR(190) NULL,
  sort_order INT NOT NULL DEFAULT 0,
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

CREATE DATABASE IF NOT EXISTS pickled CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pickled;

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

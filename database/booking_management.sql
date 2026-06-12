CREATE DATABASE IF NOT EXISTS pickled CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pickled;

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

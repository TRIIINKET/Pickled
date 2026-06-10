CREATE DATABASE IF NOT EXISTS pickled CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pickled;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'player',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  started_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS courts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS booking_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  court_id INT NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  category VARCHAR(120) NOT NULL,
  duration_label VARCHAR(80) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  participants_limit INT NOT NULL DEFAULT 1,
  capacity INT NOT NULL DEFAULT 1,
  image VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (court_id) REFERENCES courts(id)
);

CREATE TABLE IF NOT EXISTS sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  variant_id INT NOT NULL,
  session_date VARCHAR(80) NOT NULL,
  session_time VARCHAR(80) NOT NULL,
  capacity INT NOT NULL,
  booked_count INT NOT NULL DEFAULT 0,
  UNIQUE KEY unique_variant_slot (variant_id, session_date, session_time),
  FOREIGN KEY (variant_id) REFERENCES booking_variants(id)
);

CREATE TABLE IF NOT EXISTS cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  session_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_cart_session (cart_id, session_id),
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (session_id) REFERENCES sessions(id)
);

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reference VARCHAR(40) NOT NULL UNIQUE,
  status VARCHAR(40) NOT NULL DEFAULT 'Pending Payment',
  subtotal DECIMAL(10,2) NOT NULL,
  payment_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(80) NOT NULL,
  payment_status VARCHAR(80) NOT NULL,
  notes TEXT NULL,
  cancellation_label VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS booking_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  session_id INT NOT NULL,
  variant_id VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  court VARCHAR(120) NOT NULL,
  category VARCHAR(120) NOT NULL,
  duration_label VARCHAR(80) NOT NULL,
  booking_date VARCHAR(80) NOT NULL,
  booking_time VARCHAR(80) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (session_id) REFERENCES sessions(id)
);

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  description TEXT,
  event_date VARCHAR(80) NOT NULL,
  event_time VARCHAR(80),
  location VARCHAR(160),
  max_participants INT,
  current_participants INT DEFAULT 0,
  status VARCHAR(40) DEFAULT 'upcoming',
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(40) DEFAULT 'info',
  is_read TINYINT(1) DEFAULT 0,
  link VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admin_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(40),
  entity_id INT,
  details JSON,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES users(id)
);

INSERT INTO users (name, email, password_hash, role)
VALUES
  ('Admin', 'admin@example.com', '$2y$12$KNh/CplDSuT71nQMLS7/iOKrsTDtlWIYdMM2XzKcZmojpCznjiUg.', 'admin'),
  ('Player', 'player@example.com', '$2y$12$KNh/CplDSuT71nQMLS7/iOKrsTDtlWIYdMM2XzKcZmojpCznjiUg.', 'player'),
  ('Coach', 'coach@example.com', '$2y$12$OtmXd8ca7eatk3JguuO4HuuyBabiXEVcJPZ8/xZ95AfxZPI7wwvZS', 'coach')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO courts (name, slug)
VALUES ('Court Green', 'green'), ('Court Pink', 'pink')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO booking_variants (court_id, slug, name, category, duration_label, price, participants_limit, capacity, image)
SELECT c.id, seed.slug, seed.name, seed.category, seed.duration_label, seed.price, seed.participants_limit, seed.capacity, '../assets/img/Hero.jpg'
FROM courts c
JOIN (
  SELECT 'green' court_slug, 'green-court-rentals' slug, 'Court Rentals' name, 'Court Reservation' category, '1 hour' duration_label, 600.00 price, 6 participants_limit, 12 capacity
  UNION ALL SELECT 'green', 'green-lessons', 'Lessons', 'Coaching', '1 hour', 500.00, 6, 12
  UNION ALL SELECT 'green', 'green-private-coaching', 'Private Coaching', 'Coaching', '1 hour', 1200.00, 1, 4
  UNION ALL SELECT 'green', 'green-training', 'Training', 'Training', '1 hour', 800.00, 6, 12
  UNION ALL SELECT 'green', 'green-open-match-play', 'Open Match-Play', 'Social Play', '2 hours', 350.00, 8, 16
  UNION ALL SELECT 'green', 'green-weekly-tournament', 'Weekly Tournament', 'Social Play', 'This week', 900.00, 1, 16
  UNION ALL SELECT 'pink', 'pink-base-rate', 'Court Pink Base Rate', 'Court Reservation', '1 hour', 400.00, 6, 10
  UNION ALL SELECT 'pink', 'pink-foundational-ages-6-10', 'Foundational Ages 6-10', 'Academy', '4 sessions', 1200.00, 8, 10
  UNION ALL SELECT 'pink', 'pink-youth-development-ages-11-17', 'Youth Development Ages 11-17', 'Academy', '4 sessions', 1200.00, 8, 10
  UNION ALL SELECT 'pink', 'pink-adult-beginner-bootcamp', 'Adult Beginner Bootcamp', 'Academy', '4 sessions', 1800.00, 8, 10
  UNION ALL SELECT 'pink', 'pink-introductory-trial-class', 'Introductory Trial Class', 'Academy', '1 hour', 250.00, 8, 10
  UNION ALL SELECT 'pink', 'pink-parent-child-trial', 'Parent & Child Trial', 'Academy', '1 hour', 500.00, 2, 10
) seed ON seed.court_slug = c.slug
ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price), capacity = VALUES(capacity);

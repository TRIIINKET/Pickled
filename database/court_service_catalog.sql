CREATE DATABASE IF NOT EXISTS pickled
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pickled;

CREATE TABLE IF NOT EXISTS courts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  status VARCHAR(40) NOT NULL,
  description TEXT NULL,
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  capacity INT UNSIGNED NOT NULL DEFAULT 1,
  operating_hours VARCHAR(100) NULL,
  court_type VARCHAR(100) NULL,
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
  description TEXT NULL,
  category VARCHAR(120) NOT NULL,
  duration_label VARCHAR(80) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  pricing_type VARCHAR(40) NOT NULL DEFAULT 'per_session',
  participants_limit INT UNSIGNED NOT NULL,
  coach_required VARCHAR(20) NOT NULL DEFAULT 'no',
  capacity INT UNSIGNED NOT NULL,
  image VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
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

CREATE TABLE IF NOT EXISTS court_media (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  court_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  image_type VARCHAR(50) NOT NULL DEFAULT 'gallery',
  is_hero TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_court_media_court (court_id, status, sort_order),
  CONSTRAINT fk_court_media_court
    FOREIGN KEY (court_id) REFERENCES courts(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO courts (name, slug, status, description, base_price, capacity, operating_hours, court_type)
VALUES
  ('Court Green', 'green', 'active', 'Main standard indoor court', 350.00, 16, '8AM - 10PM', 'Indoor'),
  ('Court Pink', 'pink', 'active', 'Youth-friendly indoor court', 400.00, 10, '8AM - 10PM', 'Indoor')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  status = VALUES(status),
  description = VALUES(description),
  base_price = VALUES(base_price),
  capacity = VALUES(capacity),
  operating_hours = VALUES(operating_hours),
  court_type = VALUES(court_type);

INSERT INTO booking_variants
  (court_id, slug, name, category, duration_label, price, participants_limit, capacity, image, active, sort_order)
SELECT c.id, seed.slug, seed.name, seed.category, seed.duration_label, seed.price, seed.participants_limit, seed.capacity, seed.image, seed.active, seed.sort_order
FROM courts c
JOIN (
  SELECT 'green' AS court_slug, 'green-court-rentals' AS slug, 'Court Rentals' AS name, 'Court Reservation' AS category, '1 hour' AS duration_label, 600.00 AS price, 6 AS participants_limit, 12 AS capacity, '../assets/img/court/court green-1.png' AS image, 1 AS active, 60 AS sort_order
  UNION ALL SELECT 'green', 'green-lessons', 'Lessons', 'Coaching', '1 hour', 500.00, 6, 12, '../assets/img/court/court green-1.png', 1, 50
  UNION ALL SELECT 'green', 'green-private-coaching', 'Private Coaching', 'Coaching', '1 hour', 1200.00, 1, 4, '../assets/img/court/court green-1.png', 1, 40
  UNION ALL SELECT 'green', 'green-training', 'Training', 'Training', '1 hour', 800.00, 6, 12, '../assets/img/court/court green-1.png', 1, 30
  UNION ALL SELECT 'green', 'green-open-match-play', 'Open Match-Play', 'Social Play', '2 hours', 350.00, 8, 16, '../assets/img/court/social play-1.png', 1, 20
  UNION ALL SELECT 'green', 'green-weekly-tournament', 'Weekly Tournament', 'Social Play', 'This week', 900.00, 1, 16, '../assets/img/court/social play-1.png', 1, 10
  UNION ALL SELECT 'pink', 'pink-base-rate', 'Court Rental', 'Court Reservation', '1 hour', 400.00, 6, 10, '../assets/img/court/court pink-1.webp', 1, 10
  UNION ALL SELECT 'pink', 'pink-kids-pickleball-class-ages-6-10', 'Kids Pickleball Class (Ages 6-10)', 'Class', '1 hour', 350.00, 8, 10, '../assets/img/court/court pink-1.webp', 1, 20
  UNION ALL SELECT 'pink', 'pink-youth-development-class-ages-11-17', 'Youth Development Class (Ages 11-17)', 'Class', '1 hour', 350.00, 8, 10, '../assets/img/court/court pink-1.webp', 1, 30
  UNION ALL SELECT 'pink', 'pink-parent-child-session', 'Parent & Child Session', 'Family Session', '1 hour', 500.00, 2, 10, '../assets/img/court/court pink-1.webp', 1, 40
  UNION ALL SELECT 'pink', 'pink-foundational-ages-6-10', 'Foundational Ages 6-10', 'Academy', '4 sessions', 1200.00, 8, 10, '../assets/img/court/academy.png', 0, 50
  UNION ALL SELECT 'pink', 'pink-youth-development-ages-11-17', 'Youth Development Ages 11-17', 'Academy', '4 sessions', 1200.00, 8, 10, '../assets/img/court/academy.png', 0, 60
  UNION ALL SELECT 'pink', 'pink-adult-beginner-bootcamp', 'Adult Beginner Bootcamp', 'Academy', '4 sessions', 1800.00, 8, 10, '../assets/img/court/academy.png', 0, 70
  UNION ALL SELECT 'pink', 'pink-introductory-trial-class', 'Introductory Trial Class', 'Academy', '1 hour', 250.00, 8, 10, '../assets/img/court/academy.png', 0, 80
  UNION ALL SELECT 'pink', 'pink-parent-child-trial', 'Parent & Child Trial', 'Academy', '1 hour', 500.00, 2, 10, '../assets/img/court/academy.png', 0, 90
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
  active = VALUES(active),
  sort_order = VALUES(sort_order);

INSERT INTO court_media (court_id, image_path, image_type, is_hero, sort_order, status)
SELECT c.id, seed.image_path, seed.image_type, seed.is_hero, seed.sort_order, 'active'
FROM courts c
JOIN (
  SELECT 'green' AS court_slug, 'img/court/court green-1.png' AS image_path, 'hero' AS image_type, 1 AS is_hero, 0 AS sort_order
  UNION ALL SELECT 'green', 'img/court/court green-2.png', 'gallery', 0, 10
  UNION ALL SELECT 'green', 'img/court/court green-3.png', 'gallery', 0, 20
  UNION ALL SELECT 'green', 'img/court/social play-1.png', 'gallery', 0, 30
  UNION ALL SELECT 'pink', 'img/court/court pink-1.webp', 'hero', 1, 0
  UNION ALL SELECT 'pink', 'img/court/court pink-2.png', 'gallery', 0, 10
  UNION ALL SELECT 'pink', 'img/court/court pink-3.png', 'gallery', 0, 20
  UNION ALL SELECT 'pink', 'img/court/academy.png', 'gallery', 0, 30
) AS seed ON seed.court_slug = c.slug
WHERE NOT EXISTS (
  SELECT 1
  FROM court_media cm
  WHERE cm.court_id = c.id
    AND cm.image_path = seed.image_path
);

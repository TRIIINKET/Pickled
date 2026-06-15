-- FINAL_SEED.sql
-- Data-only idempotent seed for the live PICKLED database schema.
-- No schema changes are included in this file.

USE pickled;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- Users and login credentials
-- ---------------------------------------------------------------------------

-- Ensure the canonical admin exists. Existing admin credentials are preserved.
INSERT INTO users (name, email, password_hash, role)
SELECT
  'Admin Demo',
  'admin@example.com',
  '$2y$12$ibR8MUreHNnonxJI.OPxNeSj6FXqyrcSDCHJs94MFMUxDXD5JgXZO',
  'admin'
WHERE NOT EXISTS (
  SELECT 1 FROM users WHERE email = 'admin@example.com'
);

-- Set known credentials for all existing seeded coaches.
UPDATE users u
JOIN (
  SELECT 'anton.coach@pickled.ph' AS email, 'Coach Anton' AS name
  UNION ALL SELECT 'david.coach@pickled.ph', 'Coach David'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 'Coach Kenji'
  UNION ALL SELECT 'martina.coach@pickled.ph', 'Coach Martina'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 'Coach Sophia'
) seed ON seed.email = u.email
SET
  u.name = seed.name,
  u.password_hash = '$2y$10$li/IiWJj4VUh00l451Sf9uc9s8pdSDAbqljTOSeM.7CpJ3H3d1l7O',
  u.role = 'coach';

-- Add five realistic player accounts.
INSERT INTO users (name, email, password_hash, role)
VALUES
  ('Maya Santos', 'maya.santos@pickled.ph', '$2y$10$HCXjMUGwXEMp5vW.QcNCcu/QA60DKzu5W3zs/B4K/hFrNQb/K7pAu', 'player'),
  ('Jose Reyes', 'jose.reyes@pickled.ph', '$2y$10$HCXjMUGwXEMp5vW.QcNCcu/QA60DKzu5W3zs/B4K/hFrNQb/K7pAu', 'player'),
  ('Bianca Cruz', 'bianca.cruz@pickled.ph', '$2y$10$HCXjMUGwXEMp5vW.QcNCcu/QA60DKzu5W3zs/B4K/hFrNQb/K7pAu', 'player'),
  ('Nico Lim', 'nico.lim@pickled.ph', '$2y$10$HCXjMUGwXEMp5vW.QcNCcu/QA60DKzu5W3zs/B4K/hFrNQb/K7pAu', 'player'),
  ('Ella Garcia', 'ella.garcia@pickled.ph', '$2y$10$HCXjMUGwXEMp5vW.QcNCcu/QA60DKzu5W3zs/B4K/hFrNQb/K7pAu', 'player')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password_hash = VALUES(password_hash),
  role = VALUES(role);

-- ---------------------------------------------------------------------------
-- User profiles
-- ---------------------------------------------------------------------------

INSERT INTO user_profiles (user_id, phone, city, province, avatar)
SELECT u.id, seed.phone, seed.city, seed.province, seed.avatar
FROM users u
JOIN (
  SELECT 'admin@example.com' AS email, '09170000001' AS phone, 'Makati' AS city, 'Metro Manila' AS province, 'assets/img/avatars/admin-demo.png' AS avatar
  UNION ALL SELECT 'anton.coach@pickled.ph', '09170000047', 'Taguig', 'Metro Manila', 'assets/img/avatars/coach-anton.png'
  UNION ALL SELECT 'david.coach@pickled.ph', '09170000048', 'Pasig', 'Metro Manila', 'assets/img/avatars/coach-david.png'
  UNION ALL SELECT 'kenji.coach@pickled.ph', '09170000049', 'Quezon City', 'Metro Manila', 'assets/img/avatars/coach-kenji.png'
  UNION ALL SELECT 'martina.coach@pickled.ph', '09170000050', 'Makati', 'Metro Manila', 'assets/img/avatars/coach-martina.png'
  UNION ALL SELECT 'sophia.coach@pickled.ph', '09170000051', 'San Juan', 'Metro Manila', 'assets/img/avatars/coach-sophia.png'
  UNION ALL SELECT 'maya.santos@pickled.ph', '09175550101', 'Makati', 'Metro Manila', 'assets/img/avatars/player-maya-santos.png'
  UNION ALL SELECT 'jose.reyes@pickled.ph', '09175550102', 'Pasig', 'Metro Manila', 'assets/img/avatars/player-jose-reyes.png'
  UNION ALL SELECT 'bianca.cruz@pickled.ph', '09175550103', 'Taguig', 'Metro Manila', 'assets/img/avatars/player-bianca-cruz.png'
  UNION ALL SELECT 'nico.lim@pickled.ph', '09175550104', 'Quezon City', 'Metro Manila', 'assets/img/avatars/player-nico-lim.png'
  UNION ALL SELECT 'ella.garcia@pickled.ph', '09175550105', 'Mandaluyong', 'Metro Manila', 'assets/img/avatars/player-ella-garcia.png'
) seed ON seed.email = u.email
ON DUPLICATE KEY UPDATE
  phone = VALUES(phone),
  city = VALUES(city),
  province = VALUES(province),
  avatar = VALUES(avatar);

-- ---------------------------------------------------------------------------
-- Coach profiles
-- ---------------------------------------------------------------------------

INSERT INTO coach_profiles (user_id, specialization, bio, experience, status, profile_image)
SELECT u.id, seed.specialization, seed.bio, seed.experience, 'active', seed.profile_image
FROM users u
JOIN (
  SELECT
    'anton.coach@pickled.ph' AS email,
    'Defensive Play & Dinking Mastery' AS specialization,
    'Soft-game specialist who helps players build patient rallies, reliable resets, and confident kitchen decision-making.' AS bio,
    '5+ years coaching club and tournament players across Metro Manila.' AS experience,
    'assets/img/avatars/coach-anton.png' AS profile_image
  UNION ALL SELECT
    'david.coach@pickled.ph',
    'Competitive Singles & Court Strategy',
    'Energetic strategy coach focused on court coverage, transition decisions, serve pressure, and singles point construction.',
    'Former collegiate racket-sport athlete with 6 years of pickleball coaching experience.',
    'assets/img/avatars/coach-david.png'
  UNION ALL SELECT
    'kenji.coach@pickled.ph',
    'Power Hitting & Offensive Doubles',
    'Doubles specialist who teaches controlled aggression, third-shot execution, poaching, and partner communication.',
    '7 years of competitive doubles training and tournament preparation.',
    'assets/img/avatars/coach-kenji.png'
  UNION ALL SELECT
    'martina.coach@pickled.ph',
    'Technical Fundamentals & Youth Development',
    'Patient fundamentals coach who builds safe movement patterns, clean mechanics, and confidence for new and younger players.',
    'Certified beginner and youth development coach with 8 years of instruction experience.',
    'assets/img/avatars/coach-martina.png'
  UNION ALL SELECT
    'sophia.coach@pickled.ph',
    'Social Play & Group Clinics',
    'Supportive group coach who designs upbeat clinics for social players, families, and women-focused sessions.',
    '5 years leading community clinics, group lessons, and social-play programs.',
    'assets/img/avatars/coach-sophia.png'
) seed ON seed.email = u.email
WHERE u.role = 'coach'
ON DUPLICATE KEY UPDATE
  specialization = VALUES(specialization),
  bio = VALUES(bio),
  experience = VALUES(experience),
  status = VALUES(status),
  profile_image = VALUES(profile_image);

-- ---------------------------------------------------------------------------
-- Coach availability
-- ---------------------------------------------------------------------------

UPDATE coach_availability ca
JOIN users u ON u.id = ca.coach_user_id
JOIN (
  SELECT 'anton.coach@pickled.ph' AS email, 1 AS day_of_week, '09:00:00' AS start_time, '12:00:00' AS end_time, 'available' AS status
  UNION ALL SELECT 'anton.coach@pickled.ph', 2, '17:00:00', '20:00:00', 'available'
  UNION ALL SELECT 'anton.coach@pickled.ph', 3, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'anton.coach@pickled.ph', 5, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'anton.coach@pickled.ph', 6, '09:00:00', '11:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 1, '14:00:00', '17:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 2, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 4, '17:00:00', '20:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 5, '14:00:00', '17:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 6, '12:00:00', '15:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 2, '13:00:00', '16:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 3, '17:00:00', '20:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 4, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 5, '10:00:00', '13:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 6, '15:00:00', '18:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 0, '10:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 1, '08:00:00', '11:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 3, '13:00:00', '16:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 4, '14:00:00', '17:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 6, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 0, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 2, '16:00:00', '19:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 3, '08:00:00', '11:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 5, '16:00:00', '19:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 6, '13:00:00', '16:00:00', 'available'
) seed ON seed.email = u.email
  AND seed.day_of_week = ca.day_of_week
  AND seed.start_time = ca.start_time
  AND seed.end_time = ca.end_time
SET ca.status = seed.status;

INSERT INTO coach_availability (coach_user_id, day_of_week, start_time, end_time, status)
SELECT u.id, seed.day_of_week, seed.start_time, seed.end_time, seed.status
FROM users u
JOIN (
  SELECT 'anton.coach@pickled.ph' AS email, 1 AS day_of_week, '09:00:00' AS start_time, '12:00:00' AS end_time, 'available' AS status
  UNION ALL SELECT 'anton.coach@pickled.ph', 2, '17:00:00', '20:00:00', 'available'
  UNION ALL SELECT 'anton.coach@pickled.ph', 3, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'anton.coach@pickled.ph', 5, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'anton.coach@pickled.ph', 6, '09:00:00', '11:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 1, '14:00:00', '17:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 2, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 4, '17:00:00', '20:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 5, '14:00:00', '17:00:00', 'available'
  UNION ALL SELECT 'david.coach@pickled.ph', 6, '12:00:00', '15:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 2, '13:00:00', '16:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 3, '17:00:00', '20:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 4, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 5, '10:00:00', '13:00:00', 'available'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 6, '15:00:00', '18:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 0, '10:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 1, '08:00:00', '11:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 3, '13:00:00', '16:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 4, '14:00:00', '17:00:00', 'available'
  UNION ALL SELECT 'martina.coach@pickled.ph', 6, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 0, '09:00:00', '12:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 2, '16:00:00', '19:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 3, '08:00:00', '11:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 5, '16:00:00', '19:00:00', 'available'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 6, '13:00:00', '16:00:00', 'available'
) seed ON seed.email = u.email
WHERE u.role = 'coach'
  AND NOT EXISTS (
    SELECT 1
    FROM coach_availability existing
    WHERE existing.coach_user_id = u.id
      AND existing.day_of_week = seed.day_of_week
      AND seed.start_time < existing.end_time
      AND seed.end_time > existing.start_time
  );

-- ---------------------------------------------------------------------------
-- Coach-assigned sessions
-- ---------------------------------------------------------------------------

INSERT INTO sessions (variant_id, coach_user_id, session_date, start_time, end_time, capacity, booked_count, status)
SELECT
  v.id,
  coach.id,
  seed.session_date,
  seed.start_time,
  seed.end_time,
  seed.capacity,
  seed.booked_count,
  seed.status
FROM booking_variants v
JOIN (
  SELECT 'green-private-coaching' AS slug, 'martina.coach@pickled.ph' AS coach_email, DATE('2026-06-15') AS session_date, '09:00:00' AS start_time, '10:00:00' AS end_time, 1 AS capacity, 0 AS booked_count, 'open' AS status
  UNION ALL SELECT 'green-lessons', 'david.coach@pickled.ph', DATE('2026-06-16'), '17:00:00', '18:00:00', 8, 0, 'open'
  UNION ALL SELECT 'pink-kids-pickleball-class-ages-6-10', 'sophia.coach@pickled.ph', DATE('2026-06-18'), '17:00:00', '18:00:00', 10, 0, 'open'
  UNION ALL SELECT 'green-lessons', 'kenji.coach@pickled.ph', DATE('2026-06-10'), '17:00:00', '18:00:00', 8, 1, 'completed'
  UNION ALL SELECT 'green-private-coaching', 'anton.coach@pickled.ph', DATE('2026-06-11'), '09:00:00', '10:00:00', 1, 1, 'completed'
  UNION ALL SELECT 'green-private-coaching', 'martina.coach@pickled.ph', DATE('2026-06-22'), '09:00:00', '10:00:00', 1, 0, 'open'
  UNION ALL SELECT 'green-lessons', 'anton.coach@pickled.ph', DATE('2026-06-22'), '10:00:00', '11:00:00', 8, 0, 'open'
  UNION ALL SELECT 'green-training', 'david.coach@pickled.ph', DATE('2026-06-23'), '17:00:00', '18:00:00', 12, 1, 'open'
  UNION ALL SELECT 'pink-kids-pickleball-class-ages-6-10', 'sophia.coach@pickled.ph', DATE('2026-06-24'), '17:00:00', '18:00:00', 10, 1, 'open'
  UNION ALL SELECT 'pink-youth-development-ages-11-17', 'kenji.coach@pickled.ph', DATE('2026-06-25'), '17:00:00', '18:00:00', 10, 0, 'open'
  UNION ALL SELECT 'pink-adult-beginner-bootcamp', 'martina.coach@pickled.ph', DATE('2026-06-26'), '18:00:00', '19:00:00', 10, 0, 'open'
  UNION ALL SELECT 'green-lessons', 'kenji.coach@pickled.ph', DATE('2026-06-30'), '17:00:00', '18:00:00', 8, 0, 'open'
  UNION ALL SELECT 'pink-parent-child-session', 'sophia.coach@pickled.ph', DATE('2026-07-01'), '16:00:00', '17:00:00', 10, 2, 'open'
) seed ON seed.slug = v.slug
JOIN users coach ON coach.email = seed.coach_email AND coach.role = 'coach'
ON DUPLICATE KEY UPDATE
  coach_user_id = VALUES(coach_user_id),
  capacity = VALUES(capacity),
  booked_count = GREATEST(sessions.booked_count, VALUES(booked_count)),
  status = VALUES(status),
  updated_at = CURRENT_TIMESTAMP;

-- ---------------------------------------------------------------------------
-- Bookings and booking items
-- ---------------------------------------------------------------------------

INSERT INTO bookings (user_id, reference, status, subtotal, payment_fee, total, payment_method, payment_status, notes, cancellation_label, created_at)
SELECT u.id, seed.reference, seed.status, seed.subtotal, seed.payment_fee, seed.total, seed.payment_method, seed.payment_status, seed.notes, '', seed.created_at
FROM users u
JOIN (
  SELECT 'maya.santos@pickled.ph' AS email, 'PKL-SEED-0001' AS reference, 'completed' AS status, 1200.00 AS subtotal, 0.00 AS payment_fee, 1200.00 AS total, 'Manual Online Payment' AS payment_method, 'approved' AS payment_status, 'Completed private coaching seed booking.' AS notes, '2026-06-11 08:15:00' AS created_at
  UNION ALL SELECT 'jose.reyes@pickled.ph', 'PKL-SEED-0002', 'completed', 500.00, 0.00, 500.00, 'Manual Online Payment', 'approved', 'Completed group lesson seed booking.', '2026-06-10 08:45:00'
  UNION ALL SELECT 'bianca.cruz@pickled.ph', 'PKL-SEED-0003', 'confirmed', 800.00, 0.00, 800.00, 'Manual Online Payment', 'approved', 'Confirmed training session seed booking.', '2026-06-18 10:20:00'
  UNION ALL SELECT 'nico.lim@pickled.ph', 'PKL-SEED-0004', 'confirmed', 350.00, 0.00, 350.00, 'Manual Online Payment', 'pending', 'Pending payment review for youth class.', '2026-06-18 11:10:00'
  UNION ALL SELECT 'ella.garcia@pickled.ph', 'PKL-SEED-0005', 'pending', 1000.00, 0.00, 1000.00, 'Manual Online Payment', 'pending', 'Parent and child booking awaiting payment review.', '2026-06-18 13:30:00'
) seed ON seed.email = u.email
ON DUPLICATE KEY UPDATE
  user_id = VALUES(user_id),
  status = VALUES(status),
  subtotal = VALUES(subtotal),
  payment_fee = VALUES(payment_fee),
  total = VALUES(total),
  payment_method = VALUES(payment_method),
  payment_status = VALUES(payment_status),
  notes = VALUES(notes),
  cancellation_label = VALUES(cancellation_label),
  updated_at = CURRENT_TIMESTAMP;

UPDATE booking_items bi
JOIN bookings b ON b.id = bi.booking_id
JOIN (
  SELECT 'PKL-SEED-0001' AS reference, 'green-private-coaching' AS slug, DATE('2026-06-11') AS session_date, '09:00:00' AS start_time, '10:00:00' AS end_time, 1 AS quantity
  UNION ALL SELECT 'PKL-SEED-0002', 'green-lessons', DATE('2026-06-10'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'PKL-SEED-0003', 'green-training', DATE('2026-06-23'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'PKL-SEED-0004', 'pink-kids-pickleball-class-ages-6-10', DATE('2026-06-24'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'PKL-SEED-0005', 'pink-parent-child-session', DATE('2026-07-01'), '16:00:00', '17:00:00', 2
) seed ON seed.reference = b.reference
JOIN booking_variants v ON v.slug = seed.slug
JOIN courts c ON c.id = v.court_id
JOIN sessions s ON s.variant_id = v.id
  AND s.session_date = seed.session_date
  AND s.start_time = seed.start_time
  AND s.end_time = seed.end_time
  AND s.id = bi.session_id
SET
  bi.variant_slug = v.slug,
  bi.name = v.name,
  bi.court = c.name,
  bi.category = v.category,
  bi.duration_label = v.duration_label,
  bi.booking_date = s.session_date,
  bi.start_time = s.start_time,
  bi.end_time = s.end_time,
  bi.quantity = seed.quantity,
  bi.unit_price = v.price,
  bi.image = v.image;

INSERT INTO booking_items (booking_id, session_id, variant_slug, name, court, category, duration_label, booking_date, start_time, end_time, quantity, unit_price, image)
SELECT b.id, s.id, v.slug, v.name, c.name, v.category, v.duration_label, s.session_date, s.start_time, s.end_time, seed.quantity, v.price, v.image
FROM bookings b
JOIN (
  SELECT 'PKL-SEED-0001' AS reference, 'green-private-coaching' AS slug, DATE('2026-06-11') AS session_date, '09:00:00' AS start_time, '10:00:00' AS end_time, 1 AS quantity
  UNION ALL SELECT 'PKL-SEED-0002', 'green-lessons', DATE('2026-06-10'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'PKL-SEED-0003', 'green-training', DATE('2026-06-23'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'PKL-SEED-0004', 'pink-kids-pickleball-class-ages-6-10', DATE('2026-06-24'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'PKL-SEED-0005', 'pink-parent-child-session', DATE('2026-07-01'), '16:00:00', '17:00:00', 2
) seed ON seed.reference = b.reference
JOIN booking_variants v ON v.slug = seed.slug
JOIN courts c ON c.id = v.court_id
JOIN sessions s ON s.variant_id = v.id
  AND s.session_date = seed.session_date
  AND s.start_time = seed.start_time
  AND s.end_time = seed.end_time
WHERE NOT EXISTS (
  SELECT 1
  FROM booking_items existing
  WHERE existing.booking_id = b.id
    AND existing.session_id = s.id
);

UPDATE sessions s
JOIN booking_variants v ON v.id = s.variant_id
JOIN (
  SELECT 'green-private-coaching' AS slug, DATE('2026-06-11') AS session_date, '09:00:00' AS start_time, '10:00:00' AS end_time, 1 AS quantity
  UNION ALL SELECT 'green-lessons', DATE('2026-06-10'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'green-training', DATE('2026-06-23'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'pink-kids-pickleball-class-ages-6-10', DATE('2026-06-24'), '17:00:00', '18:00:00', 1
  UNION ALL SELECT 'pink-parent-child-session', DATE('2026-07-01'), '16:00:00', '17:00:00', 2
) seed ON seed.slug = v.slug
  AND seed.session_date = s.session_date
  AND seed.start_time = s.start_time
  AND seed.end_time = s.end_time
SET s.booked_count = GREATEST(s.booked_count, seed.quantity);

-- ---------------------------------------------------------------------------
-- Active carts
-- ---------------------------------------------------------------------------

INSERT INTO carts (user_id, started_at, expires_at)
SELECT u.id, NOW(), DATE_ADD(NOW(), INTERVAL seed.hold_hours HOUR)
FROM users u
JOIN (
  SELECT 'maya.santos@pickled.ph' AS email, 2 AS hold_hours
  UNION ALL SELECT 'jose.reyes@pickled.ph', 3
) seed ON seed.email = u.email
ON DUPLICATE KEY UPDATE
  started_at = VALUES(started_at),
  expires_at = VALUES(expires_at),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO cart_items (cart_id, session_id, quantity, unit_price)
SELECT cart.id, s.id, seed.quantity, v.price
FROM carts cart
JOIN users u ON u.id = cart.user_id
JOIN (
  SELECT 'maya.santos@pickled.ph' AS email, 'pink-youth-development-ages-11-17' AS slug, DATE('2026-06-25') AS session_date, '17:00:00' AS start_time, '18:00:00' AS end_time, 1 AS quantity
  UNION ALL SELECT 'jose.reyes@pickled.ph', 'pink-adult-beginner-bootcamp', DATE('2026-06-26'), '18:00:00', '19:00:00', 1
) seed ON seed.email = u.email
JOIN booking_variants v ON v.slug = seed.slug
JOIN sessions s ON s.variant_id = v.id
  AND s.session_date = seed.session_date
  AND s.start_time = seed.start_time
  AND s.end_time = seed.end_time
ON DUPLICATE KEY UPDATE
  quantity = VALUES(quantity),
  unit_price = VALUES(unit_price);

-- ---------------------------------------------------------------------------
-- Payments
-- ---------------------------------------------------------------------------

UPDATE payments p
JOIN (
  SELECT 'PKL-SEED-0001' AS booking_reference, 'PAY-SEED-0001' AS reference_number, 1200.00 AS amount, 'Manual Online Payment' AS payment_method, 'assets/uploads/payments/seed-pkl-seed-0001.jpg' AS proof_image, 'approved' AS status, 'Seed payment approved for completed private coaching booking.' AS remarks, '2026-06-11 08:30:00' AS created_at, '2026-06-11 09:00:00' AS reviewed_at
  UNION ALL SELECT 'PKL-SEED-0002', 'PAY-SEED-0002', 500.00, 'Manual Online Payment', 'assets/uploads/payments/seed-pkl-seed-0002.jpg', 'approved', 'Seed payment approved for completed group lesson booking.', '2026-06-10 09:00:00', '2026-06-10 09:30:00'
  UNION ALL SELECT 'PKL-SEED-0003', 'PAY-SEED-0003', 800.00, 'Manual Online Payment', 'assets/uploads/payments/seed-pkl-seed-0003.jpg', 'approved', 'Seed payment approved for training session.', '2026-06-18 10:45:00', '2026-06-18 11:30:00'
  UNION ALL SELECT 'PKL-SEED-0004', 'PAY-SEED-0004', 350.00, 'Manual Online Payment', 'assets/uploads/payments/seed-pkl-seed-0004.jpg', 'pending', 'Awaiting admin payment review.', '2026-06-18 11:25:00', NULL
  UNION ALL SELECT 'PKL-SEED-0005', 'PAY-SEED-0005', 1000.00, 'Manual Online Payment', 'assets/uploads/payments/seed-pkl-seed-0005.jpg', 'pending', 'Payment submitted for parent and child booking.', '2026-06-18 13:45:00', NULL
) seed ON seed.reference_number = p.reference_number
LEFT JOIN users admin_user ON admin_user.email = 'admin@example.com' AND admin_user.role = 'admin'
SET
  p.proof_image = seed.proof_image,
  p.amount = seed.amount,
  p.payment_method = seed.payment_method,
  p.status = seed.status,
  p.reviewed_by = CASE WHEN seed.status = 'approved' THEN admin_user.id ELSE NULL END,
  p.reviewed_at = seed.reviewed_at,
  p.remarks = seed.remarks;

INSERT INTO payments (booking_id, proof_image, amount, payment_method, reference_number, status, reviewed_by, reviewed_at, remarks, created_at)
SELECT
  b.id,
  seed.proof_image,
  seed.amount,
  seed.payment_method,
  seed.reference_number,
  seed.status,
  CASE WHEN seed.status = 'approved' THEN admin_user.id ELSE NULL END,
  seed.reviewed_at,
  seed.remarks,
  seed.created_at
FROM bookings b
JOIN (
  SELECT 'PKL-SEED-0001' AS booking_reference, 'PAY-SEED-0001' AS reference_number, 1200.00 AS amount, 'Manual Online Payment' AS payment_method, 'assets/uploads/payments/seed-pkl-seed-0001.jpg' AS proof_image, 'approved' AS status, 'Seed payment approved for completed private coaching booking.' AS remarks, '2026-06-11 08:30:00' AS created_at, '2026-06-11 09:00:00' AS reviewed_at
  UNION ALL SELECT 'PKL-SEED-0002', 'PAY-SEED-0002', 500.00, 'Manual Online Payment', 'assets/uploads/payments/seed-pkl-seed-0002.jpg', 'approved', 'Seed payment approved for completed group lesson booking.', '2026-06-10 09:00:00', '2026-06-10 09:30:00'
  UNION ALL SELECT 'PKL-SEED-0003', 'PAY-SEED-0003', 800.00, 'Manual Online Payment', 'assets/uploads/payments/seed-pkl-seed-0003.jpg', 'approved', 'Seed payment approved for training session.', '2026-06-18 10:45:00', '2026-06-18 11:30:00'
  UNION ALL SELECT 'PKL-SEED-0004', 'PAY-SEED-0004', 350.00, 'Manual Online Payment', 'assets/uploads/payments/seed-pkl-seed-0004.jpg', 'pending', 'Awaiting admin payment review.', '2026-06-18 11:25:00', NULL
  UNION ALL SELECT 'PKL-SEED-0005', 'PAY-SEED-0005', 1000.00, 'Manual Online Payment', 'assets/uploads/payments/seed-pkl-seed-0005.jpg', 'pending', 'Payment submitted for parent and child booking.', '2026-06-18 13:45:00', NULL
) seed ON seed.booking_reference = b.reference
LEFT JOIN users admin_user ON admin_user.email = 'admin@example.com' AND admin_user.role = 'admin'
WHERE NOT EXISTS (
  SELECT 1
  FROM payments existing
  WHERE existing.reference_number = seed.reference_number
);

-- ---------------------------------------------------------------------------
-- Feedback
-- ---------------------------------------------------------------------------

INSERT INTO feedback (booking_id, booking_item_id, user_id, coach_user_id, rating, comment, created_at)
SELECT b.id, bi.id, b.user_id, s.coach_user_id, seed.rating, seed.comment, seed.created_at
FROM bookings b
JOIN booking_items bi ON bi.booking_id = b.id
JOIN sessions s ON s.id = bi.session_id
JOIN (
  SELECT 'PKL-SEED-0001' AS reference, 5 AS rating, 'Coach Anton made the private lesson practical and confidence-building.' AS comment, '2026-06-12 10:00:00' AS created_at
  UNION ALL SELECT 'PKL-SEED-0002', 5, 'Coach Kenji broke down doubles positioning in a way that was easy to apply.', '2026-06-11 10:00:00'
) seed ON seed.reference = b.reference
WHERE b.status = 'completed'
ON DUPLICATE KEY UPDATE
  booking_item_id = VALUES(booking_item_id),
  user_id = VALUES(user_id),
  coach_user_id = VALUES(coach_user_id),
  rating = VALUES(rating),
  comment = VALUES(comment),
  updated_at = CURRENT_TIMESTAMP;

-- ---------------------------------------------------------------------------
-- Private packages
-- ---------------------------------------------------------------------------

UPDATE private_packages pp
JOIN coach_profiles cp ON cp.id = pp.coach_profile_id
JOIN users coach ON coach.id = cp.user_id
JOIN (
  SELECT 'anton.coach@pickled.ph' AS coach_email, 'Beginner Foundation Private Session' AS title, 'A focused private session for new players covering grip, ready position, serve mechanics, dinks, and safe court movement.' AS description, 950.00 AS price, '1 hour' AS duration, 'active' AS status
  UNION ALL SELECT 'david.coach@pickled.ph', 'Doubles Strategy Intensive', 'A private strategy session for partners who want clearer rotations, transition decisions, shot selection, and point patterns.', 1500.00, '90 minutes', 'active'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 'Tournament Prep Lab', 'A high-intensity private package for competitive players covering pressure drills, third shots, counters, and game-plan review.', 1800.00, '2 hours', 'active'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 'Youth Skills Private Block', 'A four-session youth-focused package with fundamentals, agility games, rally skills, and confidence-building match play.', 2800.00, '4 sessions', 'active'
) seed ON seed.coach_email = coach.email AND seed.title = pp.title
SET
  pp.description = seed.description,
  pp.price = seed.price,
  pp.duration = seed.duration,
  pp.status = seed.status;

INSERT INTO private_packages (title, description, price, duration, coach_profile_id, status)
SELECT seed.title, seed.description, seed.price, seed.duration, cp.id, seed.status
FROM coach_profiles cp
JOIN users coach ON coach.id = cp.user_id
JOIN (
  SELECT 'anton.coach@pickled.ph' AS coach_email, 'Beginner Foundation Private Session' AS title, 'A focused private session for new players covering grip, ready position, serve mechanics, dinks, and safe court movement.' AS description, 950.00 AS price, '1 hour' AS duration, 'active' AS status
  UNION ALL SELECT 'david.coach@pickled.ph', 'Doubles Strategy Intensive', 'A private strategy session for partners who want clearer rotations, transition decisions, shot selection, and point patterns.', 1500.00, '90 minutes', 'active'
  UNION ALL SELECT 'kenji.coach@pickled.ph', 'Tournament Prep Lab', 'A high-intensity private package for competitive players covering pressure drills, third shots, counters, and game-plan review.', 1800.00, '2 hours', 'active'
  UNION ALL SELECT 'sophia.coach@pickled.ph', 'Youth Skills Private Block', 'A four-session youth-focused package with fundamentals, agility games, rally skills, and confidence-building match play.', 2800.00, '4 sessions', 'active'
) seed ON seed.coach_email = coach.email
WHERE NOT EXISTS (
  SELECT 1
  FROM private_packages existing
  WHERE existing.title = seed.title
    AND existing.coach_profile_id = cp.id
);

-- ---------------------------------------------------------------------------
-- Private inquiries
-- ---------------------------------------------------------------------------

INSERT INTO private_inquiries (user_id, private_package_id, message, status, admin_response, created_at)
SELECT u.id, pp.id, seed.message, seed.status, seed.admin_response, seed.created_at
FROM users u
JOIN (
  SELECT 'bianca.cruz@pickled.ph' AS player_email, 'Youth Skills Private Block' AS package_title, 'Interested in a weekend youth skills block for two beginners. Preferred date: 2026-07-05. Phone: 09175550103.' AS message, 'new' AS status, NULL AS admin_response, '2026-06-18 15:10:00' AS created_at
  UNION ALL SELECT 'nico.lim@pickled.ph', 'Doubles Strategy Intensive', 'Looking for a doubles strategy session before a company tournament. Preferred date: 2026-07-06. Phone: 09175550104.', 'in_review', NULL, '2026-06-18 15:35:00'
  UNION ALL SELECT 'ella.garcia@pickled.ph', 'Beginner Foundation Private Session', 'Requesting a beginner private session for a parent and child pair. Preferred date: 2026-07-07. Phone: 09175550105.', 'responded', 'Thanks for reaching out. We can offer a morning slot and will confirm by email.', '2026-06-18 16:05:00'
) seed ON seed.player_email = u.email
JOIN private_packages pp ON pp.title = seed.package_title
WHERE NOT EXISTS (
  SELECT 1
  FROM private_inquiries existing
  WHERE existing.user_id = u.id
    AND existing.private_package_id = pp.id
    AND existing.message = seed.message
);

COMMIT;

-- ---------------------------------------------------------------------------
-- Admin Credentials
-- ---------------------------------------------------------------------------
-- admin@example.com / password

-- ---------------------------------------------------------------------------
-- Coach Credentials
-- ---------------------------------------------------------------------------
-- anton.coach@pickled.ph / coach123
-- david.coach@pickled.ph / coach123
-- kenji.coach@pickled.ph / coach123
-- martina.coach@pickled.ph / coach123
-- sophia.coach@pickled.ph / coach123

-- ---------------------------------------------------------------------------
-- New Player Credentials
-- ---------------------------------------------------------------------------
-- maya.santos@pickled.ph / player123
-- jose.reyes@pickled.ph / player123
-- bianca.cruz@pickled.ph / player123
-- nico.lim@pickled.ph / player123
-- ella.garcia@pickled.ph / player123

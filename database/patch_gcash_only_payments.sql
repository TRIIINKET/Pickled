DELIMITER $$

DROP PROCEDURE IF EXISTS patch_gcash_only_payments $$
CREATE PROCEDURE patch_gcash_only_payments()
BEGIN
  IF EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
  ) THEN
    UPDATE bookings
    SET payment_method = 'GCash'
    WHERE payment_method IS NULL
       OR TRIM(payment_method) = ''
       OR LOWER(TRIM(payment_method)) <> 'gcash';

    ALTER TABLE bookings
      MODIFY payment_method VARCHAR(80) NOT NULL DEFAULT 'GCash';

    IF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'bookings'
        AND column_name = 'payment_status'
    ) THEN
      UPDATE bookings
      SET payment_status = 'pending'
      WHERE payment_status IS NULL
         OR LOWER(TRIM(payment_status)) IN ('pay on site', 'pay onsite', 'cash on site', 'cash');
    END IF;
  END IF;

  IF EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'payments'
  ) THEN
    UPDATE payments
    SET payment_method = 'GCash'
    WHERE payment_method IS NULL
       OR TRIM(payment_method) = ''
       OR LOWER(TRIM(payment_method)) <> 'gcash';

    ALTER TABLE payments
      MODIFY payment_method VARCHAR(80) NOT NULL DEFAULT 'GCash';
  END IF;
END $$

CALL patch_gcash_only_payments() $$
DROP PROCEDURE IF EXISTS patch_gcash_only_payments $$

DELIMITER ;

<?php
ob_start();
$pageTitle = 'All Bookings';
$activePage = 'bookings';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../app/services/AdminService.php';
require_once __DIR__ . '/../app/services/AdminLogService.php';
require_once __DIR__ . '/../app/services/BookingExpiryService.php';
require_once __DIR__ . '/../app/services/NotificationService.php';
require_once __DIR__ . '/../app/services/EmailService.php';
require_once __DIR__ . '/../includes/upload-helper.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../database/Database.php';

pickled_init_csrf();

// Booking queries use the approved booking_items snapshots and compute display labels from DATE/TIME columns.
$pdo = Database::enabled() ? Database::connection() : null;
$adminService = new AdminService();
$notificationService = new NotificationService();
(new BookingExpiryService())->processExpiredPendingBookings();
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todaySql = $today->format('Y-m-d');
$todayLabel = $today->format('M j, Y (D)');
$todayBookingLabel = $today->format('l, F j, Y');
$view = ($_GET['view'] ?? 'table') === 'calendar' ? 'calendar' : 'table';
$query = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$courtFilter = trim((string) ($_GET['court'] ?? 'all'));
$programFilter = trim((string) ($_GET['program'] ?? 'all'));
$dateFilter = trim((string) ($_GET['date'] ?? ''));
$weekStartFilter = trim((string) ($_GET['week_start'] ?? ''));
$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$successMsg = '';
$errorMsg = '';

function booking_week_start(DateTimeImmutable $today, string $weekStart, string $dateFilter): DateTimeImmutable {
    $timezone = $today->getTimezone();
    $source = $dateFilter !== '' ? $dateFilter : $weekStart;
    if ($source !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $source, $timezone);
        if ($date && $date->format('Y-m-d') === $source) {
            return $date->modify('monday this week');
        }
    }

    return $today->modify('monday this week');
}

function booking_query_url(array $params): string {
    $params = array_filter($params, static fn($value): bool => $value !== '' && $value !== null);
    return pickled_admin_url('manage-bookings.php?' . http_build_query($params));
}

if (($_GET['created'] ?? '') === 'walkin') {
    $successMsg = 'Walk-in booking created successfully.';
}

function booking_rows(?PDO $pdo, string $sql, array $params = []): array {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Booking page query failed: ' . $e->getMessage());
        return [];
    }
}

function booking_scalar(?PDO $pdo, string $sql, array $params = [], float|int $fallback = 0): float|int {
    if (!$pdo) {
        return $fallback;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return is_numeric($value) ? (float) $value : $fallback;
    } catch (Throwable $e) {
        error_log('Booking page query failed: ' . $e->getMessage());
        return $fallback;
    }
}

function booking_status_key(string $status): string {
    $status = strtolower(trim($status));
    if (str_contains($status, 'cancel') || str_contains($status, 'reject')) return 'danger';
    if (str_contains($status, 'pending')) return 'warning';
    if (str_contains($status, 'complete')) return 'neutral';
    if (str_contains($status, 'confirm') || str_contains($status, 'paid')) return 'success';
    return 'neutral';
}

function booking_payment_key(string $status): string {
    $status = strtolower(trim($status));
    if (str_contains($status, 'reject') || str_contains($status, 'refund') || str_contains($status, 'expire')) return 'danger';
    if (str_contains($status, 'pending')) return 'warning';
    if (str_contains($status, 'site') || str_contains($status, 'bank')) return 'purple';
    if (str_contains($status, 'complete') || str_contains($status, 'paid') || str_contains($status, 'approved') || str_contains($status, 'verified')) return 'success';
    return 'neutral';
}

function booking_admin_label(string $status): string {
    $status = strtolower(trim($status));
    return match ($status) {
        'confirmed' => 'Confirmed',
        'paid' => 'Paid',
        default => ucwords(str_replace('_', ' ', $status ?: 'pending')),
    };
}

function booking_program_filter_options(): array {
    return [
        'Court Rental',
        'Lessons',
        'Private Coaching',
        'Training',
        'Kids Pickleball Class',
        'Youth Development Class',
        'Parent & Child Session',
        'Open Match-Play',
        'Weekly Tournament',
    ];
}

function booking_program_aliases(string $program): array {
    $program = trim($program);
    return match ($program) {
        'Court Rental' => ['Court Rental', 'Court Rentals'],
        'Kids Pickleball Class' => ['Kids Pickleball Class', 'Kids Pickleball Class (Ages 6-10)'],
        'Youth Development Class' => ['Youth Development Class', 'Youth Development Class (Ages 11-17)'],
        default => [$program],
    };
}

function booking_status_filter_options(array $allowedStatuses): array {
    $preferred = ['pending', 'confirmed', 'completed', 'cancelled', 'refund_pending', 'rejected', 'expired', 'refunded'];
    $allowed = array_flip(array_map('strtolower', $allowedStatuses));
    $options = [];

    foreach ($preferred as $status) {
        if (isset($allowed[$status]) || in_array($status, ['expired', 'refunded', 'refund_pending'], true)) {
            $options[$status] = booking_admin_label($status);
        }
    }

    return $options;
}

function booking_is_rejected_action(array $booking): bool {
    if (strtolower((string) ($booking['status'] ?? '')) === 'rejected') {
        return true;
    }

    $label = strtolower((string) ($booking['cancellation_label'] ?? ''));
    $notes = strtolower((string) ($booking['notes'] ?? ''));
    return strtolower((string) ($booking['status'] ?? '')) === 'cancelled'
        && (str_contains($label, 'reject') || str_contains($notes, 'rejection note'));
}

function booking_display_status_label(array $booking): string {
    if (booking_is_rejected_action($booking)) {
        return 'Rejected';
    }

    return booking_admin_label((string) ($booking['status'] ?? 'pending'));
}

function booking_payment_label(string $status): string {
    $status = strtolower(trim($status));
    return match ($status) {
        'approved', 'paid', 'verified' => 'Verified',
        'refund_pending' => 'Refund Review',
        default => ucwords(str_replace('_', ' ', $status ?: 'pending')),
    };
}

function booking_has_cancellation_refund_request(array $booking): bool {
    $paymentStatus = strtolower((string) ($booking['payment_status'] ?? ''));
    if (in_array($paymentStatus, ['refunded', 'refund_rejected'], true)) {
        return false;
    }
    $label = strtolower((string) ($booking['cancellation_label'] ?? ''));
    $notes = strtolower((string) ($booking['notes'] ?? ''));
    return $paymentStatus === 'refund_pending'
        || str_contains($label, 'refund review')
        || str_contains($label, 'cancellation/refund')
        || str_contains($notes, 'admin refund review')
        || str_contains($notes, 'refund review may be required');
}

function booking_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function booking_public_url(string $path): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/manage-bookings.php');
    $position = strpos($script, '/admin/');
    $base = $position === false ? rtrim(dirname($script), '/') . '/' : substr($script, 0, $position + 1);
    return htmlspecialchars($base . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
}

function booking_proof_is_image(string $path): bool {
    return (bool) preg_match('/\.(jpe?g|png|webp)$/i', $path);
}

function booking_payment_proof_column(PDO $pdo): string {
    static $column = null;
    if ($column !== null) {
        return $column;
    }

    foreach (['proof_of_payment', 'proof_image'] as $candidate) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([
            'table_name' => 'payments',
            'column_name' => $candidate,
        ]);
        if ((int) $stmt->fetchColumn() > 0) {
            return $column = $candidate;
        }
    }

    return $column = 'proof_image';
}

function booking_payment_empty_proof(PDO $pdo): ?string {
    return booking_payment_proof_column($pdo) === 'proof_of_payment' ? null : '';
}

function booking_payment_proof_path(array $payment): string {
    return trim((string) ($payment['proof_of_payment'] ?? $payment['proof_image'] ?? ''));
}

function booking_upload_has_file(array $file): bool {
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    return $error !== UPLOAD_ERR_NO_FILE;
}

function booking_store_payment_proof(int $bookingId, array $file): string {
    try {
        validateUploadedFile($file, 'receipt');
        return pickled_upload_file(
            $file,
            'uploads/payments',
            [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'webp' => ['image/webp'],
                'pdf' => ['application/pdf', 'application/x-pdf'],
            ],
            5242880,
            'payment_' . $bookingId
        );
    } catch (RuntimeException $e) {
        if (str_contains($e->getMessage(), 'too large') || str_contains($e->getMessage(), '5MB')) {
            throw new RuntimeException('Receipt file must be 5MB or smaller.');
        }
        if (str_contains($e->getMessage(), 'type') || str_contains($e->getMessage(), 'JPG')) {
            throw new RuntimeException('Receipt must be a JPG, JPEG, PNG, WEBP, or PDF file.');
        }
        if (str_contains($e->getMessage(), 'choose')) {
            throw new RuntimeException('Please choose a receipt file before submitting.');
        }
        throw new RuntimeException('Receipt upload failed. Please try again.');
    }
}

function booking_admin_payment_db_status(string $status): string {
    $status = strtolower(trim($status));
    if ($status === 'verified' || $status === 'paid') {
        return 'approved';
    }
    if ($status === 'refunded') {
        return 'rejected';
    }
    return in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending';
}

function booking_payment_allowed_statuses(PDO $pdo): array {
    static $allowed = null;
    if ($allowed !== null) {
        return $allowed;
    }

    $allowed = ['pending', 'approved', 'rejected'];
    try {
        $stmt = $pdo->prepare(
            "SELECT cc.CHECK_CLAUSE
             FROM information_schema.CHECK_CONSTRAINTS cc
             JOIN information_schema.TABLE_CONSTRAINTS tc
               ON tc.CONSTRAINT_SCHEMA = cc.CONSTRAINT_SCHEMA
              AND tc.CONSTRAINT_NAME = cc.CONSTRAINT_NAME
             WHERE tc.TABLE_SCHEMA = DATABASE()
               AND tc.TABLE_NAME = 'payments'
               AND cc.CONSTRAINT_NAME = 'chk_payments_status'
             LIMIT 1"
        );
        $stmt->execute();
        $clause = (string) ($stmt->fetchColumn() ?: '');
        if (preg_match_all("/'([^']+)'/", $clause, $matches) && !empty($matches[1])) {
            $allowed = array_values(array_unique(array_map('strtolower', $matches[1])));
        }
    } catch (Throwable $e) {
        error_log('Unable to read payment status constraint: ' . $e->getMessage());
    }

    return $allowed;
}

function booking_payment_status_value(PDO $pdo, string $requested): string {
    $requested = strtolower(trim($requested));
    $allowed = booking_payment_allowed_statuses($pdo);
    $mapped = match ($requested) {
        'paid', 'verified', 'approve', 'approved' => in_array('verified', $allowed, true) ? 'verified' : (in_array('approved', $allowed, true) ? 'approved' : 'paid'),
        'rejected', 'reject' => 'rejected',
        default => 'pending',
    };

    if (!in_array($mapped, $allowed, true)) {
        throw new RuntimeException('Payment status "' . $requested . '" is not allowed by the current database constraint.');
    }

    return $mapped;
}

function booking_allowed_statuses(PDO $pdo): array {
    static $allowed = null;
    if ($allowed !== null) {
        return $allowed;
    }

    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
    try {
        $stmt = $pdo->prepare(
            "SELECT cc.CHECK_CLAUSE
             FROM information_schema.CHECK_CONSTRAINTS cc
             JOIN information_schema.TABLE_CONSTRAINTS tc
               ON tc.CONSTRAINT_SCHEMA = cc.CONSTRAINT_SCHEMA
              AND tc.CONSTRAINT_NAME = cc.CONSTRAINT_NAME
             WHERE tc.TABLE_SCHEMA = DATABASE()
               AND tc.TABLE_NAME = 'bookings'
               AND cc.CONSTRAINT_NAME = 'chk_bookings_status'
             LIMIT 1"
        );
        $stmt->execute();
        $clause = (string) ($stmt->fetchColumn() ?: '');
        if (preg_match_all("/'([^']+)'/", $clause, $matches) && !empty($matches[1])) {
            $allowed = array_values(array_unique(array_map('strtolower', $matches[1])));
        }
    } catch (Throwable $e) {
        error_log('Unable to read booking status constraint: ' . $e->getMessage());
    }

    return $allowed;
}

function booking_admin_status_value(PDO $pdo, string $requested): string {
    $requested = strtolower(trim($requested));
    $allowed = booking_allowed_statuses($pdo);
    $mapped = match ($requested) {
        'approved', 'approve', 'confirmed' => in_array('confirmed', $allowed, true) ? 'confirmed' : 'approved',
        'rejected', 'reject' => in_array('rejected', $allowed, true) ? 'rejected' : 'cancelled',
        'cancelled', 'cancel' => 'cancelled',
        'completed', 'complete' => 'completed',
        'pending' => 'pending',
        default => $requested,
    };

    if (!in_array($mapped, $allowed, true)) {
        throw new RuntimeException('Booking status "' . $requested . '" is not allowed by the current database constraint.');
    }

    return $mapped;
}

function booking_admin_update_booking(PDO $pdo, AdminService $adminService, int $bookingId, string $status, int $adminId, string $note = ''): bool {
    $requestedStatus = strtolower(trim($status));
    $status = booking_admin_status_value($pdo, $requestedStatus);
    $isRejectedAction = in_array($requestedStatus, ['rejected', 'reject'], true);

    $ok = $adminService->updateBookingStatus($bookingId, $isRejectedAction ? 'rejected' : $status, $adminId);
    if (!$ok) {
        return false;
    }

    if ($note !== '' || $isRejectedAction || $status === 'cancelled') {
        $label = $note !== '' ? $note : ($isRejectedAction ? 'Rejected by admin' : ucfirst($status) . ' by admin');
        if ($isRejectedAction && !str_starts_with(strtolower($label), 'rejected')) {
            $label = 'Rejected: ' . $label;
        }
        $stmt = $pdo->prepare(
            "UPDATE bookings
             SET cancellation_label = CASE WHEN :status_label = 'cancelled' THEN :label ELSE cancellation_label END,
                 notes = CASE
                    WHEN :note = '' THEN notes
                    WHEN notes IS NULL OR notes = '' THEN :note_first
                    ELSE CONCAT(notes, CHAR(10), :note_append)
                 END
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $bookingId,
            'status_label' => $status,
            'label' => $label,
            'note' => $note,
            'note_first' => ($isRejectedAction ? 'Rejection note: ' : 'Admin note: ') . $note,
            'note_append' => ($isRejectedAction ? 'Rejection note: ' : 'Admin note: ') . $note,
        ]);
    }

    if ($isRejectedAction) {
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => $bookingId];
        (new AdminLogService())->recordBookingRejected($booking, $adminId, $note);
    }

    return true;
}

function booking_filter_parts(string $query, string $statusFilter, string $courtFilter, string $programFilter, string $dateFilter): array {
    $where = [];
    $params = [];
    if ($query !== '') {
        $where[] = "(b.reference LIKE :q OR u.name LIKE :q OR u.email LIKE :q OR up.phone LIKE :q)";
        $params['q'] = '%' . $query . '%';
    }
    if ($statusFilter === 'expired') {
        $where[] = "(LOWER(b.status) = 'expired' OR (LOWER(b.status) = 'cancelled' AND LOWER(COALESCE(b.cancellation_label, '')) LIKE '%expired%') OR LOWER(b.payment_status) = 'expired')";
    } elseif ($statusFilter === 'refunded') {
        $where[] = "(LOWER(b.status) = 'refunded' OR LOWER(b.payment_status) = 'refunded')";
    } elseif ($statusFilter === 'refund_pending') {
        $where[] = "LOWER(COALESCE(b.payment_status, '')) NOT IN ('refunded', 'refund_rejected') AND (LOWER(b.payment_status) = 'refund_pending' OR LOWER(COALESCE(b.cancellation_label, '')) LIKE '%refund review%' OR LOWER(COALESCE(b.notes, '')) LIKE '%admin refund review%')";
    } elseif ($statusFilter !== 'all') {
        $normalizedFilter = match (strtolower($statusFilter)) {
            'approved' => 'confirmed',
            default => strtolower($statusFilter),
        };
        $where[] = 'LOWER(b.status) = :status';
        $params['status'] = $normalizedFilter;
    }
    if ($courtFilter === 'social-play') {
        $where[] = "(LOWER(bi.category) = 'social play' OR bi.name IN ('Open Match-Play', 'Weekly Tournament') OR LOWER(bi.court) LIKE '%social%')";
    } elseif ($courtFilter !== 'all') {
        $where[] = "bi.court = :court";
        $params['court'] = $courtFilter;
    }
    if ($programFilter !== 'all') {
        $programAliases = booking_program_aliases($programFilter);
        $programPlaceholders = [];
        foreach ($programAliases as $index => $programAlias) {
            $key = 'program_' . $index;
            $programPlaceholders[] = ':' . $key;
            $params[$key] = $programAlias;
        }
        $where[] = 'bi.name IN (' . implode(', ', $programPlaceholders) . ')';
    }
    if ($dateFilter !== '') {
        $where[] = "(COALESCE(bi.booking_date, s.session_date) = :date_filter_exact OR DATE(b.created_at) = :date_created_exact)";
        $params['date_filter_exact'] = $dateFilter;
        $params['date_created_exact'] = $dateFilter;
    }

    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

function booking_add_week_filter(string $whereSql, array $params, string $weekStart, string $weekEnd): array {
    $rangeClause = 'COALESCE(bi.booking_date, s.session_date) BETWEEN :week_start AND :week_end';
    $whereSql = $whereSql !== '' ? $whereSql . ' AND ' . $rangeClause : 'WHERE ' . $rangeClause;
    $params['week_start'] = $weekStart;
    $params['week_end'] = $weekEnd;
    return [$whereSql, $params];
}

function booking_filtered_rows(?PDO $pdo, string $whereSql, array $params, ?int $limit = 10): array {
    if (!$pdo) {
        return [];
    }

    $limitSql = $limit !== null ? 'LIMIT ' . max(1, $limit) : '';
    $proofColumn = booking_payment_proof_column($pdo);
    return booking_rows($pdo, "
        SELECT b.*, u.name AS user_name, u.email AS user_email, COALESCE(up.phone, '') AS user_phone,
               GROUP_CONCAT(DISTINCT bi.name ORDER BY bi.id SEPARATOR ', ') AS program_names,
               GROUP_CONCAT(DISTINCT bi.court ORDER BY bi.id SEPARATOR ', ') AS courts,
               SUM(bi.quantity) AS players,
               MIN(bi.booking_date) AS booking_date_raw,
               MIN(bi.end_time) AS booking_end_time_raw,
               MIN(DATE_FORMAT(bi.booking_date, '%W, %M %e, %Y')) AS booking_date,
               MIN(CONCAT(TIME_FORMAT(bi.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(bi.end_time, '%h:%i %p'))) AS booking_time,
               lp.id AS latest_payment_id,
               lp.status AS latest_payment_status,
               lp.reference_number AS latest_payment_reference,
               lp.$proofColumn AS latest_payment_proof,
               lp.reviewed_by AS latest_payment_reviewed_by,
               lp.reviewed_at AS latest_payment_reviewed_at
        FROM bookings b
        LEFT JOIN users u ON u.id = b.user_id
        LEFT JOIN user_profiles up ON up.user_id = u.id
        LEFT JOIN booking_items bi ON bi.booking_id = b.id
        LEFT JOIN sessions s ON s.id = bi.session_id
        LEFT JOIN payments lp ON lp.id = (
            SELECT p2.id FROM payments p2 WHERE p2.booking_id = b.id ORDER BY p2.created_at DESC, p2.id DESC LIMIT 1
        )
        $whereSql
        GROUP BY b.id
        ORDER BY b.created_at DESC
        $limitSql
    ", $params);
}

function booking_calendar_items(?PDO $pdo, string $whereSql, array $params, string $weekStart, string $weekEnd): array {
    if (!$pdo) {
        return [];
    }

    $rangeClause = "COALESCE(bi.booking_date, s.session_date) BETWEEN :week_start AND :week_end";
    if ($whereSql !== '') {
        $whereSql .= ' AND ' . $rangeClause;
    } else {
        $whereSql = 'WHERE ' . $rangeClause;
    }
    $params['week_start'] = $weekStart;
    $params['week_end'] = $weekEnd;

    return booking_rows($pdo, "
        SELECT bi.*,
               COALESCE(bi.booking_date, s.session_date) AS schedule_date,
               DATE_FORMAT(COALESCE(bi.booking_date, s.session_date), '%W, %M %e, %Y') AS schedule_date_label,
               COALESCE(bi.start_time, s.start_time) AS schedule_start_time,
               COALESCE(bi.end_time, s.end_time) AS schedule_end_time,
               CONCAT(TIME_FORMAT(COALESCE(bi.start_time, s.start_time), '%h:%i %p'), ' - ', TIME_FORMAT(COALESCE(bi.end_time, s.end_time), '%h:%i %p')) AS schedule_time,
               b.id AS booking_id,
               b.reference,
               b.status,
               b.payment_status,
               b.cancellation_label,
               b.notes,
               b.total,
               u.name AS user_name,
               u.email AS user_email,
               COALESCE(up.phone, '') AS user_phone
        FROM booking_items bi
        JOIN bookings b ON b.id = bi.booking_id
        LEFT JOIN sessions s ON s.id = bi.session_id
        LEFT JOIN users u ON u.id = b.user_id
        LEFT JOIN user_profiles up ON up.user_id = u.id
        $whereSql
        ORDER BY schedule_date ASC, schedule_start_time ASC, bi.id ASC
    ", $params);
}

function booking_export_csv(PDO $pdo, string $query, string $statusFilter, string $courtFilter, string $programFilter, string $dateFilter): void {
    [$whereSql, $params] = booking_filter_parts($query, $statusFilter, $courtFilter, $programFilter, $dateFilter);
    $rows = booking_filtered_rows($pdo, $whereSql, $params, null);

    if (ob_get_length() !== false) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pickled-bookings-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Booking Reference',
        'Player Name',
        'Email',
        'Program / Service',
        'Court',
        'Date',
        'Time',
        'Players',
        'Booking Status',
        'Payment Status',
        'Payment Method',
        'Total Amount',
        'Created At',
    ], ',', '"', '');

    foreach ($rows as $row) {
        $paymentStatus = (string) (($row['latest_payment_status'] ?? '') ?: ($row['payment_status'] ?? 'pending'));
        fputcsv($out, [
            (string) ($row['reference'] ?? ''),
            (string) ($row['user_name'] ?? 'Guest'),
            (string) ($row['user_email'] ?? ''),
            (string) ($row['program_names'] ?: 'Booking'),
            (string) ($row['courts'] ?: 'Any Court'),
            (string) ($row['booking_date'] ?: ''),
            (string) ($row['booking_time'] ?: ''),
            (int) ($row['players'] ?? 1),
            booking_display_status_label($row),
            booking_payment_label($paymentStatus),
            (string) ($row['payment_method'] ?? ''),
            number_format((float) ($row['total'] ?? 0), 2, '.', ''),
            (string) ($row['created_at'] ?? ''),
        ], ',', '"', '');
    }

    fclose($out);
    exit;
}

function booking_generate_reference(PDO $pdo): string {
    do {
        $reference = 'PKL-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT 1 FROM bookings WHERE reference = :reference LIMIT 1');
        $stmt->execute(['reference' => $reference]);
    } while ($stmt->fetchColumn());

    return $reference;
}

function booking_column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $stmt->execute(['table_name' => $table, 'column_name' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function booking_table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name'
    );
    $stmt->execute(['table_name' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function booking_next_business_code(PDO $pdo, string $table, string $column, string $prefix): string {
    $pattern = $prefix . '-[0-9]{6}';
    $stmt = $pdo->prepare(
        "SELECT COALESCE(MAX(CAST(SUBSTRING($column, :offset) AS UNSIGNED)), 0)
         FROM $table
         WHERE $column REGEXP :pattern"
    );
    $stmt->execute([
        'offset' => strlen($prefix) + 2,
        'pattern' => '^' . $pattern . '$',
    ]);
    return $prefix . '-' . str_pad((string) ((int) $stmt->fetchColumn() + 1), 6, '0', STR_PAD_LEFT);
}

function booking_find_or_create_walkin_user(PDO $pdo, string $name, string $email, string $phone, string $reference): int {
    if ($email !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        $existingId = (int) ($stmt->fetchColumn() ?: 0);
        if ($existingId > 0) {
            return $existingId;
        }
    } else {
        $email = strtolower('walkin-' . $reference . '@pickled.local');
    }

    $columns = ['name', 'email', 'password_hash', 'role'];
    $placeholders = [':name', ':email', ':password_hash', ':role'];
    $params = [
        'name' => $name,
        'email' => strtolower($email),
        'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        'role' => 'player',
    ];

    if (booking_column_exists($pdo, 'users', 'user_code')) {
        array_unshift($columns, 'user_code');
        array_unshift($placeholders, ':user_code');
        $params['user_code'] = booking_next_business_code($pdo, 'users', 'user_code', 'USR');
    }

    if (booking_column_exists($pdo, 'users', 'is_verified')) {
        $columns[] = 'is_verified';
        $placeholders[] = ':is_verified';
        $params['is_verified'] = 1;
    }

    $stmt = $pdo->prepare('INSERT INTO users (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
    $stmt->execute($params);
    $userId = (int) $pdo->lastInsertId();

    $profileStmt = $pdo->prepare(
        'INSERT INTO user_profiles (user_id, phone, city, province, avatar)
         VALUES (:user_id, :phone, :city, :province, :avatar)
         ON DUPLICATE KEY UPDATE phone = VALUES(phone)'
    );
    $profileStmt->execute([
        'user_id' => $userId,
        'phone' => $phone,
        'city' => '',
        'province' => '',
        'avatar' => 'avatars/default.png',
    ]);

    return $userId;
}

function booking_available_coach(PDO $pdo, string $bookingDate, string $startTime, string $endTime): ?int {
    $dayOfWeek = (int) (new DateTimeImmutable($bookingDate))->format('w');
    $stmt = $pdo->prepare(
        "SELECT u.id
         FROM users u
         JOIN coach_availability ca ON ca.coach_user_id = u.id
         WHERE u.role = 'coach'
           AND ca.status = 'available'
           AND ca.day_of_week = :day_of_week
           AND ca.start_time <= :start_time
           AND ca.end_time >= :end_time
           AND NOT EXISTS (
               SELECT 1
               FROM booking_items bi
               JOIN bookings b ON b.id = bi.booking_id
               WHERE bi.coach_user_id = u.id
                 AND b.status <> 'cancelled'
                 AND bi.booking_date = :booking_date
                 AND :start_time_overlap < bi.end_time
                 AND :end_time_overlap > bi.start_time
           )
         ORDER BY u.name ASC
         LIMIT 1"
    );
    $stmt->execute([
        'day_of_week' => $dayOfWeek,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'booking_date' => $bookingDate,
        'start_time_overlap' => $startTime,
        'end_time_overlap' => $endTime,
    ]);
    $coachId = (int) ($stmt->fetchColumn() ?: 0);
    return $coachId > 0 ? $coachId : null;
}

function booking_time_value(string $time): string {
    return substr($time, 0, 5);
}

function booking_time_label(string $startTime, string $endTime): string {
    return (new DateTimeImmutable('1970-01-01 ' . $startTime))->format('g:i A')
        . ' - '
        . (new DateTimeImmutable('1970-01-01 ' . $endTime))->format('g:i A');
}

function booking_duration_minutes(string $durationLabel): int {
    $duration = strtolower(trim($durationLabel));
    if (preg_match('/(\d+(?:\.\d+)?)\s*(hour|hr|hrs|hours)/', $duration, $match)) {
        return max(30, (int) round(((float) $match[1]) * 60));
    }
    if (preg_match('/(\d+)\s*(minute|min|mins|minutes)/', $duration, $match)) {
        return max(30, (int) $match[1]);
    }
    return 60;
}

function booking_walkin_requires_coach(array $variant): bool {
    $stored = strtolower((string) ($variant['coach_required'] ?? ''));
    if (in_array($stored, ['yes', 'required', '1', 'true'], true)) {
        return true;
    }
    $label = strtolower((string) ($variant['category'] ?? '') . ' ' . (string) ($variant['name'] ?? ''));
    foreach (['lesson', 'coaching', 'training', 'class', 'kids', 'youth', 'parent'] as $keyword) {
        if (str_contains($label, $keyword)) {
            return true;
        }
    }
    return false;
}

function booking_walkin_is_per_player(array $variant): bool {
    $pricingType = strtolower((string) ($variant['pricing_type'] ?? 'per_session'));
    $label = strtolower((string) ($variant['category'] ?? '') . ' ' . (string) ($variant['name'] ?? ''));
    if (str_contains($label, 'court rental') || str_contains($label, 'court rentals') || str_contains($label, 'reservation')) {
        return false;
    }
    return in_array($pricingType, ['per_player', 'per_participant', 'per_person'], true);
}

function booking_walkin_total(array $variant, int $players): float {
    $unitPrice = (float) ($variant['price'] ?? 0);
    return round($unitPrice * (booking_walkin_is_per_player($variant) ? max(1, $players) : 1), 2);
}

function booking_walkin_court_conflict(PDO $pdo, int $courtId, string $courtName, string $bookingDate, string $startTime, string $endTime): bool {
    $stmt = $pdo->prepare(
        "SELECT 1
         FROM booking_items bi
         JOIN bookings b ON b.id = bi.booking_id
         LEFT JOIN booking_variants v ON v.slug = bi.variant_slug
         WHERE bi.booking_date = :booking_date
           AND :start_time < bi.end_time
           AND :end_time > bi.start_time
           AND (v.court_id = :court_id OR bi.court = :court_name)
           AND (b.status IN ('pending', 'confirmed', 'completed')
                OR b.payment_status IN ('pending', 'approved', 'paid', 'verified'))
           AND b.status <> 'cancelled'
           AND b.payment_status NOT IN ('expired', 'refunded', 'rejected')
         LIMIT 1"
    );
    $stmt->execute([
        'booking_date' => $bookingDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'court_id' => $courtId,
        'court_name' => $courtName,
    ]);
    return (bool) $stmt->fetchColumn();
}

function booking_walkin_coaches_for_slot(PDO $pdo, string $bookingDate, string $startTime, string $endTime): array {
    $dayOfWeek = (int) (new DateTimeImmutable($bookingDate))->format('w');
    $timeOffSql = '';
    if (booking_table_exists($pdo, 'coach_time_off_requests')) {
        $timeOffSql = "AND NOT EXISTS (
               SELECT 1
               FROM coach_time_off_requests tor
               WHERE tor.coach_user_id = u.id
                 AND tor.status = 'approved'
                 AND :time_off_date BETWEEN tor.start_date AND tor.end_date
           )";
    }
    $stmt = $pdo->prepare(
        "SELECT u.id, u.name, COALESCE(cp.specialization, '') AS specialization
         FROM users u
         JOIN coach_availability ca ON ca.coach_user_id = u.id
         LEFT JOIN coach_profiles cp ON cp.user_id = u.id
         WHERE u.role = 'coach'
           AND (cp.status IS NULL OR cp.status = 'active')
           AND ca.status = 'available'
           AND ca.day_of_week = :day_of_week
           AND ca.start_time <= :start_time
           AND ca.end_time >= :end_time
           $timeOffSql
           AND NOT EXISTS (
               SELECT 1
               FROM booking_items bi
               JOIN bookings b ON b.id = bi.booking_id
               WHERE bi.coach_user_id = u.id
                 AND bi.booking_date = :booking_date
                 AND :start_time_overlap < bi.end_time
                 AND :end_time_overlap > bi.start_time
                 AND (b.status IN ('pending', 'confirmed', 'completed')
                      OR b.payment_status IN ('pending', 'approved', 'paid', 'verified'))
                 AND b.status <> 'cancelled'
                 AND b.payment_status NOT IN ('expired', 'refunded', 'rejected')
           )
           AND NOT EXISTS (
               SELECT 1
               FROM sessions s
               WHERE s.coach_user_id = u.id
                 AND s.session_date = :session_date
                 AND s.status IN ('open', 'full')
                 AND :session_start < s.end_time
                 AND :session_end > s.start_time
           )
         ORDER BY u.name ASC"
    );
    $params = [
        'day_of_week' => $dayOfWeek,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'booking_date' => $bookingDate,
        'start_time_overlap' => $startTime,
        'end_time_overlap' => $endTime,
        'session_date' => $bookingDate,
        'session_start' => $startTime,
        'session_end' => $endTime,
    ];
    if ($timeOffSql !== '') {
        $params['time_off_date'] = $bookingDate;
    }
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function booking_walkin_available_slots(PDO $pdo, array $variant, int $days = 21): array {
    $timezone = new DateTimeZone('Asia/Manila');
    $now = new DateTimeImmutable('now', $timezone);
    $today = new DateTimeImmutable('today', $timezone);
    $durationMinutes = booking_duration_minutes((string) ($variant['duration_label'] ?? '1 hour'));
    $requiresCoach = booking_walkin_requires_coach($variant);
    $slots = [];

    for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
        $date = $today->modify('+' . $dayOffset . ' days');
        $bookingDate = $date->format('Y-m-d');
        $dateLabel = $date->format('D, M j, Y');
        for ($start = $date->setTime(8, 0); $start < $date->setTime(22, 0); $start = $start->modify('+1 hour')) {
            $end = $start->modify('+' . $durationMinutes . ' minutes');
            if ($end > $date->setTime(22, 0) || $start <= $now) {
                continue;
            }
            $startTime = $start->format('H:i:s');
            $endTime = $end->format('H:i:s');
            if (booking_walkin_court_conflict($pdo, (int) $variant['court_id'], (string) $variant['court_name'], $bookingDate, $startTime, $endTime)) {
                continue;
            }
            $coaches = $requiresCoach ? booking_walkin_coaches_for_slot($pdo, $bookingDate, $startTime, $endTime) : [];
            if ($requiresCoach && !$coaches) {
                continue;
            }
            $slots[] = [
                'date' => $bookingDate,
                'date_label' => $dateLabel,
                'start' => booking_time_value($startTime),
                'end' => booking_time_value($endTime),
                'label' => $dateLabel . ' · ' . booking_time_label($startTime, $endTime),
                'time_label' => booking_time_label($startTime, $endTime),
                'coaches' => array_map(static fn(array $coach): array => [
                    'id' => (int) $coach['id'],
                    'name' => (string) $coach['name'],
                    'specialization' => (string) ($coach['specialization'] ?? ''),
                ], $coaches),
            ];
        }
    }

    return $slots;
}

function booking_create_walkin(PDO $pdo, array $input, int $adminId, array $files = []): int {
    $name = validateName($input['customer_name'] ?? '');
    $email = validateEmail($input['customer_email'] ?? '', false);
    $phone = validatePhonePH($input['customer_phone'] ?? '', false);
    $courtId = (int) ($input['court_id'] ?? 0);
    $variantId = (int) ($input['variant_id'] ?? 0);
    $bookingDate = trim((string) ($input['booking_date'] ?? ''));
    $startTime = trim((string) ($input['start_time'] ?? ''));
    $endTime = trim((string) ($input['end_time'] ?? ''));
    $coachUserId = empty($input['coach_user_id']) ? null : (int) $input['coach_user_id'];
    $players = validatePositiveInt($input['players'] ?? 1, null, 'Please enter a valid number of players.');
    $paymentMethod = trim((string) ($input['payment_method'] ?? 'Cash'));
    $paymentChoice = strtolower(trim((string) ($input['payment_status'] ?? 'pending')));
    $notes = validateText($input['notes'] ?? '', false, 1000);
    if (!in_array($paymentMethod, ['Cash', 'GCash'], true)) {
        throw new RuntimeException('Please choose a valid payment method.');
    }
    $paymentMethodStored = strtolower($paymentMethod);

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $bookingDate);
    if (!$date || $date->format('Y-m-d') !== $bookingDate) {
        throw new RuntimeException('Please choose a valid booking date.');
    }
    $todayLocal = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
    if ($date < $todayLocal) {
        throw new RuntimeException('Booking date cannot be in the past.');
    }
    if (preg_match('/^\d{2}:\d{2}$/', $startTime)) {
        $startTime .= ':00';
    }
    if (preg_match('/^\d{2}:\d{2}$/', $endTime)) {
        $endTime .= ':00';
    }
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $endTime) || $startTime >= $endTime) {
        throw new RuntimeException('Please select an available time slot.');
    }
    $slotStart = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $bookingDate . ' ' . $startTime, new DateTimeZone('Asia/Manila'));
    if (!$slotStart || $slotStart <= new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'))) {
        throw new RuntimeException('Please select a future time slot.');
    }

    $variantStmt = $pdo->prepare(
        "SELECT v.*, c.name AS court_name, c.status AS court_status
         FROM booking_variants v
         JOIN courts c ON c.id = v.court_id
         WHERE v.id = :variant_id
           AND c.id = :court_id
         LIMIT 1"
    );
    $variantStmt->execute(['variant_id' => $variantId, 'court_id' => $courtId]);
    $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);
    if (!$variant || (int) ($variant['active'] ?? 0) !== 1) {
        throw new RuntimeException('Selected service is unavailable.');
    }
    if (strtolower((string) ($variant['court_status'] ?? '')) !== 'active') {
        throw new RuntimeException('Selected court is unavailable.');
    }
    $slotEnd = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $bookingDate . ' ' . $endTime, new DateTimeZone('Asia/Manila'));
    $openTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $bookingDate . ' 08:00:00', new DateTimeZone('Asia/Manila'));
    $closeTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $bookingDate . ' 22:00:00', new DateTimeZone('Asia/Manila'));
    $expectedMinutes = booking_duration_minutes((string) ($variant['duration_label'] ?? '1 hour'));
    $actualMinutes = ($slotStart && $slotEnd) ? (int) (($slotEnd->getTimestamp() - $slotStart->getTimestamp()) / 60) : 0;
    if (!$slotEnd || !$openTime || !$closeTime || $slotStart < $openTime || $slotEnd > $closeTime || $actualMinutes !== $expectedMinutes) {
        throw new RuntimeException('Please select an available time slot.');
    }
    $playerLimit = max(1, min((int) ($variant['participants_limit'] ?? 1), (int) ($variant['capacity'] ?? 1)));
    if ($players > $playerLimit) {
        throw new RuntimeException('Number of players exceeds the selected service limit.');
    }

    if (booking_walkin_court_conflict($pdo, $courtId, (string) $variant['court_name'], $bookingDate, $startTime, $endTime)) {
        throw new RuntimeException('This time slot is already booked. Please select another schedule.');
    }

    if (booking_walkin_requires_coach($variant)) {
        if (!$coachUserId) {
            throw new RuntimeException('No coach is available for this time slot.');
        }
        $availableCoachIds = array_map(static fn(array $coach): int => (int) $coach['id'], booking_walkin_coaches_for_slot($pdo, $bookingDate, $startTime, $endTime));
        if (!in_array($coachUserId, $availableCoachIds, true)) {
            throw new RuntimeException('No coach is available for this time slot.');
        }
    } else {
        $coachUserId = null;
    }

    $bookingStatus = booking_admin_status_value($pdo, $paymentChoice === 'paid' ? 'confirmed' : 'pending');
    $paymentStatus = booking_payment_status_value($pdo, $paymentChoice === 'paid' ? 'approved' : 'pending');
    $reference = booking_generate_reference($pdo);
    $userId = booking_find_or_create_walkin_user($pdo, $name, $email, $phone, $reference);
    $subtotal = booking_walkin_total($variant, $players);

    $bookingStmt = $pdo->prepare(
        'INSERT INTO bookings
            (user_id, reference, status, subtotal, payment_fee, total, payment_method, payment_status, notes, cancellation_label)
         VALUES
            (:user_id, :reference, :status, :subtotal, 0, :total, :payment_method, :payment_status, :notes, :cancellation_label)'
    );
    $bookingStmt->execute([
        'user_id' => $userId,
        'reference' => $reference,
        'status' => $bookingStatus,
        'subtotal' => $subtotal,
        'total' => $subtotal,
        'payment_method' => $paymentMethodStored,
        'payment_status' => $paymentStatus,
        'notes' => $notes !== '' ? 'Walk-in booking. ' . $notes : 'Walk-in booking.',
        'cancellation_label' => 'Walk-in admin booking',
    ]);
    $bookingId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO booking_items
            (booking_id, session_id, coach_user_id, variant_slug, name, court, category, duration_label, booking_date, start_time, end_time, quantity, unit_price, image)
         VALUES
            (:booking_id, NULL, :coach_user_id, :variant_slug, :name, :court, :category, :duration_label, :booking_date, :start_time, :end_time, :quantity, :unit_price, :image)'
    );
    $itemStmt->execute([
        'booking_id' => $bookingId,
        'coach_user_id' => $coachUserId,
        'variant_slug' => (string) $variant['slug'],
        'name' => (string) $variant['name'],
        'court' => (string) $variant['court_name'],
        'category' => (string) $variant['category'],
        'duration_label' => (string) $variant['duration_label'],
        'booking_date' => $bookingDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'quantity' => $players,
        'unit_price' => (float) $variant['price'],
        'image' => $variant['image'] ?? null,
    ]);

    $proofColumn = booking_payment_proof_column($pdo);
    $proofPath = booking_payment_empty_proof($pdo);
    $receiptFile = $files['payment_receipt'] ?? [];
    if ($paymentMethod === 'GCash' && booking_upload_has_file($receiptFile)) {
        $proofPath = booking_store_payment_proof($bookingId, $receiptFile);
    }

    $paymentColumns = ['booking_id', $proofColumn, 'amount', 'payment_method', 'reference_number', 'status', 'reviewed_by', 'reviewed_at', 'remarks'];
    $paymentPlaceholders = [':booking_id', ':proof_image', ':amount', ':payment_method', ':reference_number', ':status', ':reviewed_by', ':reviewed_at', ':remarks'];
    $paymentParams = [
        'booking_id' => $bookingId,
        'proof_image' => $proofPath,
        'amount' => $subtotal,
        'payment_method' => $paymentMethodStored,
        'reference_number' => 'WALKIN-' . $reference,
        'status' => $paymentStatus,
        'reviewed_by' => $paymentStatus === 'approved' ? $adminId : null,
        'reviewed_at' => $paymentStatus === 'approved' ? date('Y-m-d H:i:s') : null,
        'remarks' => $paymentStatus === 'approved' ? 'Walk-in payment received by admin.' : 'Walk-in payment pending.',
    ];
    if (booking_column_exists($pdo, 'payments', 'payment_code')) {
        array_unshift($paymentColumns, 'payment_code');
        array_unshift($paymentPlaceholders, ':payment_code');
        $paymentParams['payment_code'] = booking_next_business_code($pdo, 'payments', 'payment_code', 'PAY');
    }
    $paymentStmt = $pdo->prepare(
        'INSERT INTO payments (' . implode(', ', $paymentColumns) . ')
         VALUES (' . implode(', ', $paymentPlaceholders) . ')'
    );
    $paymentStmt->execute($paymentParams);

    (new AdminLogService())->recordBookingCreated(['id' => $bookingId, 'reference' => $reference]);
    if ($paymentStatus === 'approved') {
        (new AdminLogService())->recordPaymentApproved(['id' => $bookingId, 'reference' => $reference], ['reference_number' => 'WALKIN-' . $reference], $adminId);
    }

    return $bookingId;
}

function booking_admin_notify(PDO $pdo, NotificationService $notificationService, int $bookingId, string $title, string $message, string $type): void {
    $stmt = $pdo->prepare('SELECT id, user_id, reference FROM bookings WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking || (int) ($booking['user_id'] ?? 0) <= 0) {
        return;
    }

    $reference = (string) ($booking['reference'] ?? ('Booking #' . $bookingId));
    $notificationService->createForUser(
        (int) $booking['user_id'],
        $title,
        str_replace('{reference}', $reference, $message),
        $type,
        'resident/booking-details.php?id=' . (int) $booking['id']
    );
}

function booking_admin_send_payment_email(PDO $pdo, int $bookingId, string $status, string $remarks = ''): void {
    try {
        $stmt = $pdo->prepare(
            'SELECT b.*, u.name AS user_name, u.email AS user_email
             FROM bookings b
             LEFT JOIN users u ON u.id = b.user_id
             WHERE b.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking || empty($booking['user_email'])) {
            return;
        }

        $itemsStmt = $pdo->prepare(
            "SELECT *,
                    DATE_FORMAT(booking_date, '%W, %M %e, %Y') AS booking_date,
                    CONCAT(TIME_FORMAT(start_time, '%h:%i %p'), ' - ', TIME_FORMAT(end_time, '%h:%i %p')) AS booking_time
             FROM booking_items
             WHERE booking_id = :booking_id
             ORDER BY id ASC"
        );
        $itemsStmt->execute(['booking_id' => $bookingId]);
        $booking['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $user = [
            'name' => (string) ($booking['user_name'] ?? 'Member'),
            'email' => (string) $booking['user_email'],
        ];
        $email = new EmailService();
        if ($status === 'approved') {
            $email->sendPaymentApproved($user, $booking);
        } else {
            $email->sendPaymentRejected($user, $booking, $remarks);
        }
    } catch (Throwable $e) {
        error_log('Admin payment email failed: ' . $e->getMessage());
    }
}

function booking_admin_mark_paid(PDO $pdo, int $bookingId, int $adminId, string $remarks = ''): bool {
    $started = !$pdo->inTransaction();
    if ($started) {
        $pdo->beginTransaction();
    }

    try {
        $bookingStmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1 FOR UPDATE');
        $bookingStmt->execute(['id' => $bookingId]);
        $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            throw new RuntimeException('Booking was not found.');
        }
        if (strtolower((string) $booking['status']) === 'cancelled') {
            throw new RuntimeException('Cancelled or rejected bookings cannot be marked as paid.');
        }

        $paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE booking_id = :booking_id ORDER BY created_at DESC, id DESC LIMIT 1 FOR UPDATE');
        $paymentStmt->execute(['booking_id' => $bookingId]);
        $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
        if ($payment) {
            $updatePayment = $pdo->prepare(
                "UPDATE payments
                 SET status = 'approved',
                     reviewed_by = :admin_id,
                     reviewed_at = NOW(),
                     remarks = :remarks
                 WHERE id = :payment_id"
            );
            $updatePayment->execute([
                'payment_id' => (int) $payment['id'],
                'admin_id' => $adminId,
                'remarks' => $remarks !== '' ? $remarks : 'Marked as paid by admin',
            ]);
        } else {
            $proofColumn = booking_payment_proof_column($pdo);
            $paymentColumns = ['booking_id', $proofColumn, 'amount', 'payment_method', 'reference_number', 'status', 'reviewed_by', 'reviewed_at', 'remarks'];
            $paymentPlaceholders = [':booking_id', ':proof_image', ':amount', ':payment_method', ':reference_number', "'approved'", ':admin_id', 'NOW()', ':remarks'];
            $paymentParams = [
                'booking_id' => $bookingId,
                'proof_image' => booking_payment_empty_proof($pdo),
                'amount' => (float) ($booking['total'] ?? 0),
                'payment_method' => (string) ($booking['payment_method'] ?? 'Admin verified'),
                'reference_number' => 'ADMIN-' . (string) ($booking['reference'] ?? $bookingId),
                'admin_id' => $adminId,
                'remarks' => $remarks !== '' ? $remarks : 'Marked as paid by admin',
            ];
            if (booking_column_exists($pdo, 'payments', 'payment_code')) {
                array_unshift($paymentColumns, 'payment_code');
                array_unshift($paymentPlaceholders, ':payment_code');
                $paymentParams['payment_code'] = booking_next_business_code($pdo, 'payments', 'payment_code', 'PAY');
            }
            $insertPayment = $pdo->prepare(
                'INSERT INTO payments (' . implode(', ', $paymentColumns) . ')
                 VALUES (' . implode(', ', $paymentPlaceholders) . ')'
            );
            $insertPayment->execute($paymentParams);
        }

        $updateBooking = $pdo->prepare(
            'UPDATE bookings
             SET status = :booking_status,
                 payment_status = :payment_status
             WHERE id = :booking_id'
        );
        $updateBooking->execute([
            'booking_id' => $bookingId,
            'booking_status' => booking_admin_status_value($pdo, 'confirmed'),
            'payment_status' => booking_payment_status_value($pdo, 'approved'),
        ]);

        if ($started) {
            $pdo->commit();
        }
        booking_admin_send_payment_email($pdo, $bookingId, 'approved', $remarks);
        return true;
    } catch (Throwable $e) {
        if ($started && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function booking_admin_append_note(PDO $pdo, int $bookingId, string $label, string $note, ?string $paymentStatus = null): bool {
    $sets = [
        'cancellation_label = :label',
        "notes = CASE
            WHEN notes IS NULL OR notes = '' THEN :note_first
            ELSE CONCAT(notes, CHAR(10), :note_append)
        END",
    ];
    $params = [
        'booking_id' => $bookingId,
        'label' => $label,
        'note_first' => $note,
        'note_append' => $note,
    ];
    if ($paymentStatus !== null) {
        $sets[] = 'payment_status = :payment_status';
        $params['payment_status'] = $paymentStatus;
    }

    $stmt = $pdo->prepare('UPDATE bookings SET ' . implode(', ', $sets) . ' WHERE id = :booking_id');
    return $stmt->execute($params);
}

if (($_GET['export'] ?? '') === 'csv' && $pdo) {
    try {
        booking_export_csv($pdo, $query, $statusFilter, $courtFilter, $programFilter, $dateFilter);
    } catch (Throwable $e) {
        error_log('Booking CSV export failed: ' . $e->getMessage());
        $errorMsg = 'Unable to export bookings right now.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['booking_id'] ?? 0);
        $paymentId = (int) ($_POST['payment_id'] ?? 0);
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        try {
            if ($action === 'create_walkin_booking' && $pdo) {
                $pdo->beginTransaction();
                try {
                    booking_create_walkin($pdo, $_POST, (int) $_SESSION['user']['id'], $_FILES);
                    $pdo->commit();
                    if (ob_get_length() !== false) {
                        ob_end_clean();
                    }
                    header('Location: ' . pickled_admin_url('manage-bookings.php?view=table&created=walkin'));
                    exit;
                } catch (Throwable $walkinError) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $walkinError;
                }
            } elseif ($action === 'approve_booking' && $id && $pdo) {
                $successMsg = booking_admin_update_booking($pdo, $adminService, $id, 'confirmed', (int) $_SESSION['user']['id'], $remarks) ? 'Booking approved.' : '';
                if ($successMsg) {
                    booking_admin_notify($pdo, $notificationService, $id, 'Booking Approved', 'Your booking {reference} has been approved.', 'booking_approved');
                }
                $errorMsg = $successMsg ? '' : 'Failed to approve booking.';
            } elseif ($action === 'reject_booking' && $id && $pdo) {
                if ($remarks === '') {
                    throw new RuntimeException('Please add an admin note before rejecting a booking.');
                }
                $successMsg = booking_admin_update_booking($pdo, $adminService, $id, 'rejected', (int) $_SESSION['user']['id'], $remarks) ? 'Booking rejected and slot released.' : '';
                if ($successMsg) {
                    booking_admin_notify($pdo, $notificationService, $id, 'Booking Rejected', 'Your booking {reference} was rejected. Please check the admin note for details.', 'booking_rejected');
                }
                $errorMsg = $successMsg ? '' : 'Failed to reject booking.';
            } elseif ($action === 'mark_paid' && $id && $pdo) {
                $successMsg = booking_admin_mark_paid($pdo, $id, (int) $_SESSION['user']['id'], $remarks) ? 'Payment marked as verified.' : '';
                if ($successMsg) {
                    booking_admin_notify($pdo, $notificationService, $id, 'Payment Verified', 'Payment for booking {reference} has been verified.', 'payment_verified');
                }
                $errorMsg = $successMsg ? '' : 'Failed to mark payment as verified.';
            } elseif ($action === 'cancel_booking' && $id && $pdo) {
                $successMsg = booking_admin_update_booking($pdo, $adminService, $id, 'cancelled', (int) $_SESSION['user']['id'], $remarks) ? 'Booking cancelled and slot released.' : '';
                $errorMsg = $successMsg ? '' : 'Failed to cancel booking.';
            } elseif ($action === 'approve_cancellation' && $id && $pdo) {
                $note = 'Cancellation approved by admin. Refund is not automatic; process any approved GCash refund manually.';
                if ($remarks !== '') {
                    $note .= ' Admin note: ' . $remarks;
                }
                $successMsg = booking_admin_append_note($pdo, $id, 'Cancellation approved - refund review', $note, 'refund_pending') ? 'Cancellation approved for admin refund review.' : '';
                if ($successMsg) {
                    booking_admin_notify($pdo, $notificationService, $id, 'Cancellation Approved', 'Your cancellation request for booking {reference} was approved. Refunds, if applicable, are processed manually through GCash.', 'booking_cancelled');
                }
                $errorMsg = $successMsg ? '' : 'Failed to approve cancellation.';
            } elseif ($action === 'mark_refund_processed' && $id && $pdo) {
                $note = 'Refund marked as processed by admin. Refund was manually processed through GCash.';
                if ($remarks !== '') {
                    $note .= ' Admin note: ' . $remarks;
                }
                $successMsg = booking_admin_append_note($pdo, $id, 'Refund processed manually through GCash', $note, 'refunded') ? 'Refund marked as processed.' : '';
                if ($successMsg) {
                    booking_admin_notify($pdo, $notificationService, $id, 'Refund Processed', 'Refund for booking {reference} has been marked as processed through GCash.', 'payment_refunded');
                }
                $errorMsg = $successMsg ? '' : 'Failed to mark refund as processed.';
            } elseif ($action === 'reject_cancellation' && $id && $pdo) {
                if ($remarks === '') {
                    throw new RuntimeException('Please add an admin note before rejecting a cancellation request.');
                }
                $successMsg = booking_admin_append_note($pdo, $id, 'Cancellation request rejected by admin', 'Cancellation request rejected by admin. Admin note: ' . $remarks, 'refund_rejected') ? 'Cancellation request rejected.' : '';
                if ($successMsg) {
                    booking_admin_notify($pdo, $notificationService, $id, 'Cancellation Request Rejected', 'Your cancellation request for booking {reference} was rejected. Please check the admin note for details.', 'booking_cancelled');
                }
                $errorMsg = $successMsg ? '' : 'Failed to reject cancellation request.';
            } elseif ($action === 'complete_booking' && $id && $pdo) {
                $successMsg = booking_admin_update_booking($pdo, $adminService, $id, 'completed', (int) $_SESSION['user']['id'], $remarks) ? 'Booking marked as completed.' : '';
                if ($successMsg) {
                    booking_admin_notify($pdo, $notificationService, $id, 'Booking Completed', 'Your booking {reference} has been marked as completed.', 'booking_completed');
                }
                $errorMsg = $successMsg ? '' : 'Failed to complete booking.';
            } elseif ($action === 'approve_payment' && $id && $pdo) {
                $successMsg = booking_admin_mark_paid($pdo, $id, (int) $_SESSION['user']['id'], $remarks) ? 'Payment marked as verified.' : '';
                if ($successMsg) {
                    booking_admin_notify($pdo, $notificationService, $id, 'Payment Verified', 'Payment for booking {reference} has been verified.', 'payment_verified');
                }
                $errorMsg = $successMsg ? '' : 'Failed to mark payment as verified.';
            } elseif ($action === 'reject_payment' && $id) {
                $reason = $remarks !== '' ? $remarks : trim((string) ($_POST['reason'] ?? 'Payment rejected by admin'));
                $successMsg = $adminService->rejectPayment($id, $reason, (int) $_SESSION['user']['id'], $paymentId ?: null) ? 'Payment rejected.' : '';
                $errorMsg = $successMsg ? '' : 'Failed to reject payment.';
            } elseif ($action === 'update_status' && $id && $pdo) {
                $status = trim((string) ($_POST['status'] ?? ''));
                $successMsg = booking_admin_update_booking($pdo, $adminService, $id, $status, (int) $_SESSION['user']['id'], $remarks) ? 'Booking status updated.' : '';
                $errorMsg = $successMsg ? '' : 'Failed to update booking.';
            }
        } catch (Throwable $e) {
            error_log('Admin booking action failed: ' . $e->getMessage());
            $errorMsg = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update booking.';
        }
        $bookingId = $id;
    }
}

$selectedWeekStart = booking_week_start($today, $weekStartFilter, $dateFilter);
$selectedWeekEnd = $selectedWeekStart->modify('+6 days');
$selectedWeekStartSql = $selectedWeekStart->format('Y-m-d');
$selectedWeekEndSql = $selectedWeekEnd->format('Y-m-d');
$selectedWeekLabel = $selectedWeekStart->format('M j') . ' – ' . $selectedWeekEnd->format('M j, Y');

[$whereSql, $params] = booking_filter_parts($query, $statusFilter, $courtFilter, $programFilter, $dateFilter);
if (isset($_GET['week_start']) && $dateFilter === '') {
    [$whereSql, $params] = booking_add_week_filter($whereSql, $params, $selectedWeekStartSql, $selectedWeekEndSql);
}

$bookings = booking_filtered_rows($pdo, $whereSql, $params, 10);

[$calendarWhereSql, $calendarParams] = booking_filter_parts($query, $statusFilter, $courtFilter, $programFilter, '');
$allBookingItems = booking_calendar_items($pdo, $calendarWhereSql, $calendarParams, $selectedWeekStartSql, $selectedWeekEndSql);

$currentBooking = $bookingId ? $adminService->getBookingDetail($bookingId) : null;

$totalBookings = (int) booking_scalar($pdo, 'SELECT COUNT(*) FROM bookings');
$weekBookings = (int) booking_scalar($pdo, 'SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$pendingPayments = (int) booking_scalar($pdo, "SELECT COUNT(*) FROM bookings b LEFT JOIN payments p ON p.id = (SELECT p2.id FROM payments p2 WHERE p2.booking_id = b.id ORDER BY p2.created_at DESC, p2.id DESC LIMIT 1) WHERE COALESCE(p.status, b.payment_status, 'pending') = 'pending' AND b.status <> 'cancelled'");
$expiredBookings = (int) booking_scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE status = 'cancelled' AND LOWER(cancellation_label) LIKE '%expired%'");
$todaySessions = (int) booking_scalar($pdo, "SELECT COUNT(DISTINCT bi.booking_id) FROM booking_items bi JOIN bookings b ON b.id = bi.booking_id WHERE bi.booking_date = ? AND b.status <> 'cancelled'", [$todaySql]);
$monthlyRevenue = (float) booking_scalar($pdo, "SELECT COALESCE(SUM(b.total), 0) FROM bookings b LEFT JOIN payments p ON p.id = (SELECT p2.id FROM payments p2 WHERE p2.booking_id = b.id ORDER BY p2.created_at DESC, p2.id DESC LIMIT 1) WHERE MONTH(b.created_at) = MONTH(CURRENT_DATE()) AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) AND (LOWER(b.payment_status) IN ('verified', 'paid', 'completed') OR p.status = 'approved')");
$courts = booking_rows($pdo, 'SELECT id, name FROM courts ORDER BY id ASC');
$activeCourts = booking_rows($pdo, "SELECT id, name FROM courts WHERE status = 'active' ORDER BY id ASC");
$programs = array_map(static fn(string $name): array => ['name' => $name], booking_program_filter_options());
$activeVariants = booking_rows($pdo, "
    SELECT v.id, v.slug, v.name, v.category, v.price, v.pricing_type, v.capacity, v.participants_limit, v.duration_label, v.coach_required, c.id AS court_id, c.name AS court_name
    FROM booking_variants v
    JOIN courts c ON c.id = v.court_id
    WHERE v.active = 1 AND c.status = 'active'
    ORDER BY c.name ASC, v.name ASC
");
$walkinServices = [];
$walkinAvailability = [];
foreach ($activeVariants as $variant) {
    $variantId = (int) $variant['id'];
    $playerLimit = max(1, min((int) ($variant['participants_limit'] ?? 1), (int) ($variant['capacity'] ?? 1)));
    $walkinServices[$variantId] = [
        'id' => $variantId,
        'court_id' => (int) $variant['court_id'],
        'name' => (string) $variant['name'],
        'court_name' => (string) $variant['court_name'],
        'category' => (string) ($variant['category'] ?? ''),
        'price' => (float) $variant['price'],
        'pricing_type' => (string) ($variant['pricing_type'] ?? 'per_session'),
        'per_player' => booking_walkin_is_per_player($variant),
        'duration_label' => (string) ($variant['duration_label'] ?? '1 hour'),
        'player_limit' => $playerLimit,
        'requires_coach' => booking_walkin_requires_coach($variant),
    ];
    $walkinAvailability[$variantId] = $pdo ? booking_walkin_available_slots($pdo, $variant) : [];
}
$bookingStatusOptions = $pdo ? booking_allowed_statuses($pdo) : ['pending', 'confirmed', 'completed', 'cancelled'];
$statusFilterOptions = booking_status_filter_options($bookingStatusOptions);
$showWalkinPanel = ($_GET['walkin'] ?? '') === '1';
$currentQueryParams = [
    'view' => $view,
    'q' => $query,
    'court' => $courtFilter,
    'program' => $programFilter,
    'status' => $statusFilter,
    'date' => $dateFilter,
    'week_start' => ($view === 'calendar' || $weekStartFilter !== '') ? $selectedWeekStartSql : '',
];
$exportQueryParams = array_filter($currentQueryParams + ['export' => 'csv'], static fn($value): bool => $value !== '' && $value !== null);
$newBookingQueryParams = array_filter($currentQueryParams + ['walkin' => '1'], static fn($value): bool => $value !== '' && $value !== null);
$tableViewUrl = booking_query_url(array_merge($currentQueryParams, ['view' => 'table']));
$calendarViewUrl = booking_query_url(array_merge($currentQueryParams, ['view' => 'calendar', 'week_start' => $selectedWeekStartSql]));
$formWeekStartValue = (string) ($currentQueryParams['week_start'] ?: ($view === 'calendar' ? $selectedWeekStartSql : ''));
$exportUrl = pickled_admin_url('manage-bookings.php?' . http_build_query($exportQueryParams));
$newBookingUrl = pickled_admin_url('manage-bookings.php?' . http_build_query($newBookingQueryParams));
$closePanelUrl = pickled_admin_url('manage-bookings.php?' . http_build_query(array_filter($currentQueryParams, static fn($value): bool => $value !== '' && $value !== null)));
$weekNavBaseParams = $currentQueryParams;
$weekNavBaseParams['view'] = 'calendar';
unset($weekNavBaseParams['date']);
$previousWeekUrl = booking_query_url(array_merge($weekNavBaseParams, ['week_start' => $selectedWeekStart->modify('-7 days')->format('Y-m-d')]));
$currentWeekUrl = booking_query_url(array_merge($weekNavBaseParams, ['week_start' => $today->modify('monday this week')->format('Y-m-d')]));
$nextWeekUrl = booking_query_url(array_merge($weekNavBaseParams, ['week_start' => $selectedWeekStart->modify('+7 days')->format('Y-m-d')]));

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="M22 2 12 12"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'chart' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
    'courts' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    'image' => '<rect x="3" y="5" width="18" height="16" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 21"/>',
    'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1.5"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.38.22.74.57 1 .95.26.38.4.8.4 1.2V12a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.31-.6Z"/>',
    'wallet' => '<path d="M3 7h18v13H3z"/><path d="M16 12h5v4h-5a2 2 0 0 1 0-4Z"/><path d="M3 7l3-4h12l3 4"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'export' => '<path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M5 21h14"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'peso' => '<path d="M8 5h6a4 4 0 0 1 0 8H8M8 5v14M5 9h12M5 13h9"/>',
];

function admin_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['home']) . '</svg>';
}

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [
        ['All Bookings', 'manage-bookings.php', 'table'],
        ['Calendar View', 'manage-bookings.php?view=calendar', 'calendar'],
    ]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php', 'key' => 'users', 'icon' => 'users', 'children' => [
        ['Players', 'manage-users.php?role=player', ''],
        ['Coaches', 'manage-users.php?role=coach', ''],
    ]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php', 'key' => 'courts', 'icon' => 'courts', 'children' => [
        ['Court Green', 'manage-events.php?court=green', ''],
        ['Court Pink', 'manage-events.php?court=pink', ''],
    ]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php', 'key' => 'events', 'icon' => 'target', 'children' => [
        ['Social Play', 'manage-events.php?program=social-play', ''],
        ['Private Packages', 'private-sessions.php', ''],
    ]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];

$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $day = $selectedWeekStart->modify('+' . $i . ' days');
    $weekDays[] = [
        'label' => strtoupper($day->format('D')),
        'date' => $day->format('M j'),
        'match_sql' => $day->format('Y-m-d'),
        'today' => $day->format('Y-m-d') === $todaySql,
    ];
}

$calendarLanes = [
    ['Court Green', 'green', 'img/court/court green-1.png', 'Court Green'],
    ['Court Pink', 'pink', 'img/court/court pink-1.webp', 'Court Pink'],
    ['Social Play Events', 'orange', 'img/court/social play-1.png', 'social-play'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>">
            <img src="<?php echo booking_asset('img/WM-DGreen.png'); ?>" alt="Pickled" />
            <span>Admin</span>
        </a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group">
                        <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>">
                            <?php echo admin_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                        <div class="admin-nav-children">
                            <?php foreach ($item['children'] as [$childLabel, $childHref, $childView]): ?>
                                <a class="<?php echo $childView && $view === $childView ? 'active-child' : ''; ?>" href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>">
                        <?php echo admin_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main bookings-main">
        <header class="admin-topbar">
            <div><h1><?php echo $view === 'calendar' ? 'Calendar View' : 'All Bookings'; ?></h1></div>
            <div class="admin-topbar-actions">
                <button class="admin-date-pill" type="button"><?php echo admin_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button>
                <a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>" aria-label="Notifications"><?php echo admin_icon($icons, 'bell'); ?><?php if ($pendingPayments > 0): ?><span><?php echo min($pendingPayments, 9); ?></span><?php endif; ?>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <section class="bookings-hero admin-page-actions">
            <div class="bookings-hero-actions">
                <form class="booking-search" method="get">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                    <input type="hidden" name="court" value="<?php echo htmlspecialchars($courtFilter); ?>">
                    <input type="hidden" name="program" value="<?php echo htmlspecialchars($programFilter); ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                    <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($formWeekStartValue); ?>">
                    <?php echo admin_icon($icons, 'search'); ?>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search bookings">
                </form>
                <a class="bookings-button ghost" href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo admin_icon($icons, 'export'); ?> Export</a>
                <a class="bookings-button primary" href="<?php echo htmlspecialchars($newBookingUrl, ENT_QUOTES, 'UTF-8'); ?>">New Booking</a>
            </div>
        </section>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

        <?php if ($view === 'table'): ?>
            <section class="booking-summary-grid">
                <article><span>Total Bookings</span><strong><?php echo number_format($totalBookings); ?></strong><small>+<?php echo number_format($weekBookings); ?> this week</small></article>
                <article><span>Pending Payments</span><strong><?php echo number_format($pendingPayments); ?></strong><small>Need Review</small></article>
                <article><span>Expired Bookings</span><strong><?php echo number_format($expiredBookings); ?></strong><small>Capacity released</small></article>
                <article><span>Today's Sessions</span><strong><?php echo number_format($todaySessions); ?></strong><small>Across all courts</small></article>
                <article><span>Revenue</span><strong>₱<?php echo number_format($monthlyRevenue, 0); ?></strong><small>This Month</small></article>
            </section>
        <?php endif; ?>

        <form class="booking-filter-bar" method="get">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($formWeekStartValue); ?>">
            <div class="booking-filter-search-row">
                <label class="filter-search"><?php echo admin_icon($icons, 'search'); ?><input type="search" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search by reference, user, or email"></label>
                <div class="view-toggle"><a class="<?php echo $view === 'table' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($tableViewUrl, ENT_QUOTES, 'UTF-8'); ?>">Table View</a><a class="<?php echo $view === 'calendar' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($calendarViewUrl, ENT_QUOTES, 'UTF-8'); ?>">Calendar View</a></div>
            </div>
            <div class="booking-filter-controls-row">
                <select name="court"><option value="all">All Courts</option><?php foreach ($courts as $court): ?><option value="<?php echo htmlspecialchars($court['name']); ?>" <?php echo $courtFilter === $court['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($court['name']); ?></option><?php endforeach; ?></select>
                <select name="program"><option value="all">All Programs & Events</option><?php foreach ($programs as $program): ?><option value="<?php echo htmlspecialchars($program['name']); ?>" <?php echo $programFilter === $program['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($program['name']); ?></option><?php endforeach; ?></select>
                <select name="status"><option value="all">All Statuses</option><?php foreach ($statusFilterOptions as $status => $label): ?><option value="<?php echo htmlspecialchars($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select>
                <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                <button type="submit">Apply</button>
            </div>
        </form>

        <?php if ($view === 'table'): ?>
            <section class="booking-table-card">
                <div class="booking-management-table">
                    <div class="booking-management-row booking-management-head"><span>Reference</span><span>Player</span><span>Program</span><span>Court</span><span>Date</span><span>Time</span><span>Players</span><span>Status</span><span>Payment</span><span>Amount</span><span>Actions</span></div>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                            $statusKey = booking_status_key((string) $booking['status']);
                            $isExpiredBooking = strtolower((string) $booking['status']) === 'cancelled' && str_contains(strtolower((string) ($booking['cancellation_label'] ?? '')), 'expired');
                            $bookingStatusLabel = $isExpiredBooking ? 'Expired' : booking_display_status_label($booking);
                            $displayPaymentStatus = booking_has_cancellation_refund_request($booking) ? (string) ($booking['payment_status'] ?: 'refund_pending') : (string) ($booking['latest_payment_status'] ?: $booking['payment_status']);
                            $paymentKey = booking_payment_key($displayPaymentStatus);
                            $canReviewPayment = $displayPaymentStatus === 'pending' && !empty($booking['latest_payment_id']);
                            $bookingStatus = strtolower((string) $booking['status']);
                            $closedBooking = in_array($bookingStatus, ['cancelled'], true);
                            $canComplete = false;
                            if (!empty($booking['booking_date_raw']) && !empty($booking['booking_end_time_raw'])) {
                                $canComplete = strtotime((string) $booking['booking_date_raw'] . ' ' . (string) $booking['booking_end_time_raw']) !== false
                                    && strtotime((string) $booking['booking_date_raw'] . ' ' . (string) $booking['booking_end_time_raw']) <= time();
                            }
                        ?>
                        <div class="booking-management-row">
                            <span class="booking-ref"><?php echo htmlspecialchars($booking['reference']); ?></span>
                            <span><strong><?php echo htmlspecialchars($booking['user_name'] ?? 'Guest'); ?></strong><small><?php echo htmlspecialchars($booking['user_email'] ?? ''); ?></small><small><?php echo htmlspecialchars($booking['user_phone'] ?: 'No phone number'); ?></small></span>
                            <span><?php echo htmlspecialchars($booking['program_names'] ?: 'Booking'); ?></span>
                            <span><?php echo htmlspecialchars($booking['courts'] ?: 'Any Court'); ?></span>
                            <span><?php echo htmlspecialchars($booking['booking_date'] ?: date('M j, Y', strtotime($booking['created_at']))); ?></span>
                            <span><?php echo htmlspecialchars($booking['booking_time'] ?: '-'); ?></span>
                            <span><?php echo (int) ($booking['players'] ?? 1); ?></span>
                            <span><em class="status-pill status-<?php echo $statusKey; ?>"><?php echo htmlspecialchars($bookingStatusLabel); ?></em><?php if ($isExpiredBooking): ?><small><?php echo htmlspecialchars($booking['cancellation_label']); ?></small><?php endif; ?></span>
                            <span><em class="status-pill payment-<?php echo $paymentKey; ?>"><?php echo htmlspecialchars(booking_payment_label($displayPaymentStatus)); ?></em><?php if (booking_has_cancellation_refund_request($booking)): ?><small>Cancellation/refund review</small><?php elseif (!empty($booking['latest_payment_reference'])): ?><small><?php echo htmlspecialchars($booking['latest_payment_reference']); ?></small><?php endif; ?></span>
                            <span>₱<?php echo number_format((float) $booking['total'], 2); ?></span>
                            <span class="row-actions">
                                <a href="<?php echo pickled_admin_url('manage-bookings.php?view=table&id=' . (int) $booking['id']); ?>">Review Booking</a>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$bookings): ?><p class="empty-state">No bookings found.</p><?php endif; ?>
                </div>
                <footer class="table-pagination"><span>Showing <?php echo count($bookings); ?> of <?php echo number_format($totalBookings); ?> bookings</span><div><button disabled>‹</button><button class="active">1</button><button>2</button><button>3</button><button>›</button></div></footer>
            </section>
        <?php else: ?>
            <nav class="booking-week-nav" aria-label="Calendar week navigation">
                <a class="bookings-button ghost" href="<?php echo htmlspecialchars($previousWeekUrl, ENT_QUOTES, 'UTF-8'); ?>">Previous Week</a>
                <a class="bookings-button ghost" href="<?php echo htmlspecialchars($currentWeekUrl, ENT_QUOTES, 'UTF-8'); ?>">Current Week</a>
                <a class="bookings-button ghost" href="<?php echo htmlspecialchars($nextWeekUrl, ENT_QUOTES, 'UTF-8'); ?>">Next Week</a>
                <strong><?php echo htmlspecialchars($selectedWeekLabel); ?></strong>
            </nav>
            <section class="calendar-workspace">
                <aside class="court-lane-cards">
                    <?php foreach ($calendarLanes as [$courtName, $tone, $image, $filterValue]): ?>
                        <?php
                            $laneParams = $currentQueryParams;
                            $laneParams['view'] = 'calendar';
                            $laneParams['court'] = $filterValue;
                            $laneUrl = booking_query_url($laneParams);
                            $laneActive = $courtFilter === $filterValue;
                        ?>
                        <a class="court-mini-card <?php echo $tone; ?> <?php echo $laneActive ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($laneUrl, ENT_QUOTES, 'UTF-8'); ?>"><img src="<?php echo booking_asset($image); ?>" alt="<?php echo htmlspecialchars($courtName); ?>"><strong><?php echo htmlspecialchars($courtName); ?></strong><span>Filter calendar</span><small>View bookings</small></a>
                    <?php endforeach; ?>
                </aside>
                <div class="week-calendar">
                    <div class="calendar-header"><span>Time</span><?php foreach ($weekDays as $day): ?><strong class="<?php echo $day['today'] ? 'today' : ''; ?>"><?php echo $day['label']; ?><small><?php echo $day['date']; ?></small></strong><?php endforeach; ?></div>
                    <?php foreach (range(8, 21) as $hour): ?>
                        <div class="calendar-hour"><time><?php echo date('g:00 A', strtotime($hour . ':00')); ?></time><?php foreach ($weekDays as $day): ?><div class="calendar-cell">
                            <?php foreach ($allBookingItems as $item): ?>
                                <?php if (($item['schedule_date'] ?? '') === $day['match_sql'] && str_starts_with((string) $item['schedule_time'], date('h:00 A', strtotime($hour . ':00')))): ?>
                                    <?php $itemText = strtolower(($item['name'] ?? '') . ' ' . ($item['category'] ?? '') . ' ' . ($item['court'] ?? '')); $tone = (str_contains($itemText, 'private') || str_contains($itemText, 'coach')) ? 'purple' : (str_contains($itemText, 'pink') ? 'pink' : (str_contains($itemText, 'social') ? 'orange' : 'green')); ?>
                                    <a class="calendar-event <?php echo $tone; ?>" href="<?php echo htmlspecialchars(booking_query_url(array_merge($currentQueryParams, ['id' => (int) $item['booking_id'], 'view' => 'calendar'])), ENT_QUOTES, 'UTF-8'); ?>"><strong><?php echo htmlspecialchars($item['name']); ?></strong><span><?php echo htmlspecialchars($item['schedule_time']); ?></span><small><?php echo htmlspecialchars($item['user_name'] ?? 'Guest'); ?><?php echo !empty($item['user_phone']) ? ' · ' . htmlspecialchars($item['user_phone']) : ''; ?></small><small><?php echo htmlspecialchars($item['court'] ?? 'Any Court'); ?> · <?php echo htmlspecialchars(booking_display_status_label($item)); ?></small></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div><?php endforeach; ?></div>
                    <?php endforeach; ?>
                    <?php if (!$allBookingItems): ?><p class="calendar-empty-state">No bookings for this week and filter set.</p><?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php if ($showWalkinPanel): ?>
    <div class="booking-drawer-backdrop"><a href="<?php echo htmlspecialchars($closePanelUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Close"></a></div>
    <aside class="booking-drawer walkin-booking-drawer" role="dialog" aria-modal="true" aria-label="Create Walk-in Booking">
        <header>
            <div><span>Admin Booking</span><h2>Create Walk-in Booking</h2></div>
            <a href="<?php echo htmlspecialchars($closePanelUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Close">&times;</a>
        </header>
        <form class="walkin-booking-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
            <input type="hidden" name="action" value="create_walkin_booking">

            <section>
                <h3>Customer Details</h3>
                <label><span>Customer Name</span><input type="text" name="customer_name" required minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z .'\-]*" title="Please enter a valid name." autocomplete="name"></label>
                <label><span>Email optional</span><input type="email" name="customer_email" maxlength="150" autocomplete="email"></label>
                <label><span>Phone optional</span><input type="tel" name="customer_phone" inputmode="tel" pattern="(09[0-9]{9}|\+639[0-9]{9}|639[0-9]{9})" maxlength="13" autocomplete="tel"></label>
            </section>

            <section>
                <h3>Booking Selection</h3>
                <label><span>Court</span><select name="court_id" data-walkin-court required><option value="">Choose court</option><?php foreach ($activeCourts as $court): ?><option value="<?php echo (int) $court['id']; ?>"><?php echo htmlspecialchars($court['name']); ?></option><?php endforeach; ?></select></label>
                <label><span>Service / Program</span><select name="variant_id" data-walkin-service required><option value="">Choose service</option><?php foreach ($activeVariants as $variant): ?><option value="<?php echo (int) $variant['id']; ?>" data-court-id="<?php echo (int) $variant['court_id']; ?>"><?php echo htmlspecialchars($variant['name'] . ' - ₱' . number_format((float) $variant['price'], 2)); ?></option><?php endforeach; ?></select></label>
                <div class="walkin-form-grid">
                    <label><span>Number of Players</span><input type="number" name="players" min="1" max="1" value="1" required data-walkin-players></label>
                    <label><span>Available Date</span><select name="booking_date" data-walkin-date required><option value="">Choose service first</option></select></label>
                </div>
                <label><span>Available Time Slot</span><select data-walkin-slot required><option value="">Choose date first</option></select></label>
                <input type="hidden" name="start_time" data-walkin-start>
                <input type="hidden" name="end_time" data-walkin-end>
                <label data-walkin-coach-wrap hidden><span>Coach</span><select name="coach_user_id" data-walkin-coach><option value="">Choose time slot first</option></select></label>
                <p class="walkin-slot-message" data-walkin-slot-message>No service selected.</p>
            </section>

            <section class="walkin-price-summary">
                <h3>Price Summary</h3>
                <p><span>Service price</span><strong data-walkin-price>₱0.00</strong></p>
                <p><span>Quantity / Players</span><strong data-walkin-quantity>1 player</strong></p>
                <p><span>Total amount</span><strong data-walkin-total>₱0.00</strong></p>
            </section>

            <section>
                <h3>Payment</h3>
                <div class="walkin-form-grid">
                    <label><span>Method</span><select name="payment_method" required data-walkin-payment-method><option value="Cash">Cash</option><option value="GCash">GCash</option></select></label>
                    <label><span>Payment Status</span><select name="payment_status" required><option value="pending">Pending</option><option value="paid">Paid</option></select></label>
                </div>
                <label data-walkin-receipt-wrap hidden><span>Optional GCash receipt</span><input type="file" name="payment_receipt" accept="image/png,image/jpeg,image/webp,application/pdf,.pdf"></label>
            </section>

            <section>
                <h3>Notes</h3>
                <label><span>Optional admin notes</span><textarea name="notes" rows="3" maxlength="1000" placeholder="Add walk-in notes"></textarea></label>
            </section>

            <footer class="walkin-form-actions">
                <a class="bookings-button ghost" href="<?php echo htmlspecialchars($closePanelUrl, ENT_QUOTES, 'UTF-8'); ?>">Cancel</a>
                <button class="bookings-button primary" type="submit">Create Walk-in Booking</button>
            </footer>
        </form>
    </aside>
<?php endif; ?>

<?php if ($currentBooking): ?>
    <?php
        $latestPayment = $currentBooking['latest_payment'] ?? null;
        $paymentRows = $currentBooking['payments'] ?? [];
        $currentItems = $currentBooking['items'] ?? [];
        $firstItem = $currentItems[0] ?? [];
        $playersTotal = array_sum(array_map(static fn($item): int => (int) ($item['quantity'] ?? 0), $currentItems));
        $currentPaymentStatus = booking_has_cancellation_refund_request($currentBooking) ? (string) ($currentBooking['payment_status'] ?? 'refund_pending') : (string) (($latestPayment['status'] ?? '') ?: ($currentBooking['payment_status'] ?? 'pending'));
        $currentStatus = strtolower((string) ($currentBooking['status'] ?? 'pending'));
        $currentPaymentNormalized = strtolower($currentPaymentStatus);
        $currentIsPaid = in_array($currentPaymentNormalized, ['approved', 'paid', 'verified'], true);
        $currentIsApproved = $currentStatus === 'confirmed';
        $currentIsPending = $currentStatus === 'pending';
        $currentIsCompleted = $currentStatus === 'completed';
        $currentIsRejected = booking_is_rejected_action($currentBooking);
        $currentIsCancelled = $currentStatus === 'cancelled' && !$currentIsRejected;
        $currentHasRefundRequest = booking_has_cancellation_refund_request($currentBooking);
        $currentClosed = $currentIsCompleted || $currentIsRejected || $currentIsCancelled;
        $currentCanComplete = !empty($firstItem['booking_date_raw']) && !empty($firstItem['end_time'])
            && strtotime((string) $firstItem['booking_date_raw'] . ' ' . (string) $firstItem['end_time']) !== false
            && strtotime((string) $firstItem['booking_date_raw'] . ' ' . (string) $firstItem['end_time']) <= time();
        $terminalLabel = $currentIsCompleted ? 'Completed' : ($currentIsRejected ? 'Rejected' : ($currentIsCancelled ? booking_admin_label($currentStatus) : ''));
    ?>
    <div class="booking-drawer-backdrop"><a href="<?php echo htmlspecialchars($closePanelUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Close"></a></div>
    <aside class="booking-drawer booking-detail-modal" role="dialog" aria-modal="true" aria-label="Booking management">
        <header><div><span>Review Booking</span><h2><?php echo htmlspecialchars($currentBooking['reference']); ?></h2></div><a href="<?php echo htmlspecialchars($closePanelUrl, ENT_QUOTES, 'UTF-8'); ?>">×</a></header>
        <section><h3>Booking Information</h3><p><strong>Reference</strong><?php echo htmlspecialchars($currentBooking['reference']); ?></p><p><strong>Court</strong><?php echo htmlspecialchars($firstItem['court'] ?? 'Any Court'); ?></p><p><strong>Program / Service</strong><?php echo htmlspecialchars($firstItem['name'] ?? 'Booking'); ?></p><p><strong>Date</strong><?php echo htmlspecialchars($firstItem['booking_date'] ?? date('M j, Y', strtotime($currentBooking['created_at']))); ?></p><p><strong>Time</strong><?php echo htmlspecialchars($firstItem['booking_time'] ?? '-'); ?></p><p><strong>Number of Players</strong><?php echo number_format($playersTotal ?: 1); ?></p></section>
        <section><h3>Player Information</h3><p><strong>Player Name</strong><?php echo htmlspecialchars($currentBooking['user']['name'] ?? 'Guest'); ?></p><p><strong>Email</strong><?php echo htmlspecialchars($currentBooking['user']['email'] ?? '-'); ?></p><p><strong>Phone Number</strong><?php echo htmlspecialchars(($currentBooking['user']['phone'] ?? '') !== '' ? $currentBooking['user']['phone'] : '-'); ?></p></section>
        <section><h3>Payment Information</h3><p><strong>Payment Method</strong><?php echo htmlspecialchars($currentBooking['payment_method'] ?? '-'); ?></p><p><strong>Payment Status</strong><em class="status-pill payment-<?php echo booking_payment_key($currentPaymentStatus); ?>"><?php echo htmlspecialchars(booking_payment_label($currentPaymentStatus)); ?></em></p><?php if ($latestPayment): ?><?php $latestProofPath = booking_payment_proof_path($latestPayment); ?><p><strong>Reference No.</strong><?php echo htmlspecialchars($latestPayment['reference_number'] ?? '-'); ?></p><p><strong>Amount</strong>&#8369;<?php echo number_format((float) ($latestPayment['amount'] ?? $currentBooking['total']), 2); ?></p><?php if (!empty($latestPayment['reviewed_at'])): ?><p><strong>Reviewed At</strong><?php echo htmlspecialchars((string) $latestPayment['reviewed_at']); ?></p><?php endif; ?><?php if ($latestProofPath !== ''): ?><p><a href="<?php echo booking_public_url($latestProofPath); ?>" target="_blank" rel="noopener">View proof of payment</a></p><?php if (booking_proof_is_image($latestProofPath)): ?><img src="<?php echo booking_public_url($latestProofPath); ?>" alt="Proof of payment" style="max-width:100%;border-radius:8px;margin-top:10px;"><?php endif; ?><?php else: ?><p><strong>Proof of Payment</strong>No uploaded proof.</p><?php endif; ?><?php else: ?><p>No payment record yet.</p><?php endif; ?></section>
        <section><h3>Status Information</h3><p><strong>Booking Status</strong><em class="status-pill status-<?php echo booking_status_key((string) $currentBooking['status']); ?>"><?php echo htmlspecialchars(booking_display_status_label($currentBooking)); ?></em></p><p><strong>Total</strong>₱<?php echo number_format((float) $currentBooking['total'], 2); ?></p><?php if ($currentHasRefundRequest): ?><p><strong>Cancellation / Refund</strong>Refund review may be required. Refunds are manually processed by admin through GCash.</p><?php endif; ?><?php if (!empty($currentBooking['cancellation_label']) && $currentStatus === 'cancelled'): ?><p><strong>Admin Note</strong><?php echo htmlspecialchars($currentBooking['cancellation_label']); ?></p><?php endif; ?></section>
        <?php if ($paymentRows): ?><section><h3>Receipt History</h3><?php foreach ($paymentRows as $payment): ?><p><strong><?php echo htmlspecialchars(booking_payment_label((string) $payment['status'])); ?></strong> <?php echo htmlspecialchars($payment['reference_number']); ?> - &#8369;<?php echo number_format((float) $payment['amount'], 2); ?><?php if (!empty($payment['reviewer_name'])): ?><br><small>Reviewed by <?php echo htmlspecialchars($payment['reviewer_name']); ?></small><?php endif; ?><?php if (!empty($payment['remarks'])): ?><br><small><?php echo htmlspecialchars($payment['remarks']); ?></small><?php endif; ?></p><?php endforeach; ?></section><?php endif; ?>
        <form class="drawer-actions" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
            <input type="hidden" name="booking_id" value="<?php echo (int) $currentBooking['id']; ?>">
            <?php if ($latestPayment): ?>
                <input type="hidden" name="payment_id" value="<?php echo (int) $latestPayment['id']; ?>">
            <?php endif; ?>
            <section class="drawer-note-section"><h3>Admin Notes</h3><p>Required when rejecting a booking. Optional for other actions.</p><textarea name="remarks" rows="3" placeholder="Add an admin note"></textarea></section>
            <div class="drawer-action-buttons">
                <?php if ($currentHasRefundRequest): ?>
                    <button name="action" value="approve_cancellation" class="approve">Approve Cancellation</button>
                    <button name="action" value="mark_refund_processed" class="approve">Mark Refund Processed</button>
                    <button name="action" value="reject_cancellation" class="reject" data-requires-note="true">Reject Cancellation</button>
                <?php elseif ($currentIsPending && !$currentIsPaid): ?>
                    <button name="action" value="approve_booking" class="approve">Approve Booking</button>
                    <button name="action" value="reject_booking" class="reject" data-requires-note="true">Reject Booking</button>
                <?php elseif (($currentIsApproved || !$currentIsPending) && !$currentIsPaid && !$currentClosed): ?>
                    <button name="action" value="mark_paid" class="approve">Mark as Paid</button>
                    <button name="action" value="cancel_booking" class="reject">Cancel Booking</button>
                <?php elseif ($currentIsPaid && !$currentClosed): ?>
                    <button name="action" value="complete_booking" class="approve" <?php echo $currentCanComplete ? '' : 'disabled title="Available after the scheduled time"'; ?>>Mark Completed</button>
                    <button name="action" value="cancel_booking" class="reject">Cancel Booking</button>
                <?php else: ?>
                    <span class="drawer-terminal-badge status-pill status-<?php echo booking_status_key($currentStatus); ?>"><?php echo htmlspecialchars($terminalLabel ?: booking_admin_label($currentStatus)); ?></span>
                <?php endif; ?>
            </div>
        </form>
    </aside>
<?php endif; ?>

<script>
document.querySelectorAll('.drawer-actions').forEach(form => {
    const note = form.querySelector('textarea[name="remarks"]');
    form.querySelectorAll('[data-requires-note]').forEach(button => {
        button.addEventListener('click', () => {
            if (note) note.required = true;
        });
    });
    form.querySelectorAll('button:not([data-requires-note])').forEach(button => {
        button.addEventListener('click', () => {
            if (note) note.required = false;
        });
    });
});

document.querySelectorAll('.walkin-booking-form').forEach(form => {
    const services = <?php echo json_encode($walkinServices, JSON_UNESCAPED_SLASHES); ?>;
    const availability = <?php echo json_encode($walkinAvailability, JSON_UNESCAPED_SLASHES); ?>;
    const court = form.querySelector('[data-walkin-court]');
    const service = form.querySelector('[data-walkin-service]');
    const players = form.querySelector('[data-walkin-players]');
    const dateSelect = form.querySelector('[data-walkin-date]');
    const slotSelect = form.querySelector('[data-walkin-slot]');
    const startInput = form.querySelector('[data-walkin-start]');
    const endInput = form.querySelector('[data-walkin-end]');
    const coachWrap = form.querySelector('[data-walkin-coach-wrap]');
    const coachSelect = form.querySelector('[data-walkin-coach]');
    const slotMessage = form.querySelector('[data-walkin-slot-message]');
    const priceText = form.querySelector('[data-walkin-price]');
    const quantityText = form.querySelector('[data-walkin-quantity]');
    const totalText = form.querySelector('[data-walkin-total]');
    const paymentMethod = form.querySelector('[data-walkin-payment-method]');
    const receiptWrap = form.querySelector('[data-walkin-receipt-wrap]');
    if (!court || !service) return;

    const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    const selectedService = () => services[service.value] || null;
    const serviceSlots = () => availability[service.value] || [];
    const clearOptions = (select, label) => {
        if (!select) return;
        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = label;
        select.appendChild(option);
    };
    const updatePaymentReceipt = () => {
        if (!paymentMethod || !receiptWrap) return;
        receiptWrap.hidden = paymentMethod.value !== 'GCash';
        if (receiptWrap.hidden) {
            const input = receiptWrap.querySelector('input[type="file"]');
            if (input) input.value = '';
        }
    };
    const updatePrice = () => {
        const current = selectedService();
        const playerCount = Math.max(1, Number.parseInt(players?.value || '1', 10));
        const price = current ? Number(current.price || 0) : 0;
        const total = current && current.per_player ? price * playerCount : price;
        if (priceText) priceText.textContent = peso.format(price);
        if (quantityText) {
            quantityText.textContent = current && current.per_player
                ? `${playerCount} ${playerCount === 1 ? 'player' : 'players'}`
                : '1 session';
        }
        if (totalText) totalText.textContent = peso.format(total);
    };
    const updateCoachOptions = (slot) => {
        if (!coachWrap || !coachSelect) return;
        const current = selectedService();
        const requiresCoach = !!(current && current.requires_coach);
        coachWrap.hidden = !requiresCoach;
        coachSelect.required = requiresCoach;
        clearOptions(coachSelect, requiresCoach ? 'Choose coach' : 'Coach not required');
        if (!requiresCoach) {
            coachSelect.value = '';
            return;
        }
        const coaches = slot ? slot.coaches || [] : [];
        coaches.forEach(coach => {
            const option = document.createElement('option');
            option.value = String(coach.id);
            option.textContent = coach.specialization ? `${coach.name} - ${coach.specialization}` : coach.name;
            coachSelect.appendChild(option);
        });
        if (coaches.length === 1) {
            coachSelect.value = String(coaches[0].id);
        }
        if (slotMessage && slot && coaches.length === 0) {
            slotMessage.textContent = 'No coach is available for this time slot.';
        }
    };
    const updateSlots = () => {
        clearOptions(slotSelect, dateSelect?.value ? 'Choose time slot' : 'Choose date first');
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
        updateCoachOptions(null);
        const selectedDate = dateSelect?.value || '';
        if (!selectedDate) return;
        const slots = serviceSlots().filter(slot => slot.date === selectedDate);
        slots.forEach((slot, index) => {
            const option = document.createElement('option');
            option.value = String(index);
            option.dataset.start = slot.start;
            option.dataset.end = slot.end;
            option.textContent = slot.time_label;
            slotSelect.appendChild(option);
        });
        if (slotMessage) {
            slotMessage.textContent = slots.length ? `${slots.length} available slot${slots.length === 1 ? '' : 's'} for this date.` : 'No available slots for this date.';
        }
    };
    const updateDates = () => {
        clearOptions(dateSelect, service.value ? 'Choose date' : 'Choose service first');
        clearOptions(slotSelect, 'Choose date first');
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
        updateCoachOptions(null);
        const slots = serviceSlots();
        const dates = new Map();
        slots.forEach(slot => {
            if (!dates.has(slot.date)) dates.set(slot.date, slot.date_label);
        });
        dates.forEach((label, value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            dateSelect.appendChild(option);
        });
        if (slotMessage) {
            if (!service.value) {
                slotMessage.textContent = 'No service selected.';
            } else if (!slots.length && selectedService()?.requires_coach) {
                slotMessage.textContent = 'No coach is available for upcoming time slots.';
            } else {
                slotMessage.textContent = slots.length ? 'Choose an available date and time slot.' : 'No available slots for this service.';
            }
        }
    };
    const syncServices = () => {
        const courtId = court.value;
        [...service.options].forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            option.hidden = courtId === '' || option.dataset.courtId !== courtId;
        });
        service.disabled = courtId === '';
        if (service.selectedOptions[0] && service.selectedOptions[0].hidden) {
            service.value = '';
        }
        updateService();
    };
    const updateService = () => {
        const current = selectedService();
        if (players) {
            const maxPlayers = current ? Number(current.player_limit || 1) : 1;
            players.max = String(maxPlayers);
            if (Number.parseInt(players.value || '1', 10) > maxPlayers) {
                players.value = String(maxPlayers);
            }
        }
        updatePrice();
        updateDates();
    };

    service.addEventListener('change', updateService);
    players?.addEventListener('input', updatePrice);
    dateSelect?.addEventListener('change', updateSlots);
    slotSelect?.addEventListener('change', () => {
        const slots = serviceSlots().filter(slot => slot.date === (dateSelect?.value || ''));
        const slot = slots[Number.parseInt(slotSelect.value || '-1', 10)] || null;
        if (startInput) startInput.value = slot ? slot.start : '';
        if (endInput) endInput.value = slot ? slot.end : '';
        updateCoachOptions(slot);
    });
    paymentMethod?.addEventListener('change', updatePaymentReceipt);
    form.addEventListener('submit', event => {
        if (!startInput?.value || !endInput?.value || (selectedService()?.requires_coach && !coachSelect?.value)) {
            event.preventDefault();
            if (slotMessage) slotMessage.textContent = selectedService()?.requires_coach ? 'Please select an available slot and coach.' : 'Please select an available time slot.';
        }
    });
    court.addEventListener('change', syncServices);
    syncServices();
    updatePaymentReceipt();
});
</script>
<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>

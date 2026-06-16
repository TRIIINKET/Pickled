<?php
$pageTitle = 'All Bookings';
$activePage = 'bookings';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../app/services/AdminService.php';
require_once __DIR__ . '/../app/services/BookingExpiryService.php';
require_once __DIR__ . '/../app/services/NotificationService.php';
require_once __DIR__ . '/../app/services/EmailService.php';
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
$weekInput = trim((string) ($_GET['week_start'] ?? ''));
try {
    $selectedWeekDate = $weekInput !== ''
        ? new DateTimeImmutable($weekInput, new DateTimeZone('Asia/Manila'))
        : $today;
} catch (Throwable) {
    $selectedWeekDate = $today;
}
$weekStart = $selectedWeekDate->modify('monday this week');
$weekEnd = $weekStart->modify('+6 days');
$weekStartSql = $weekStart->format('Y-m-d');
$weekEndSql = $weekEnd->format('Y-m-d');
$weekRangeLabel = $weekStart->format('M j') . ' - ' . $weekEnd->format('M j, Y');
$view = ($_GET['view'] ?? 'table') === 'calendar' ? 'calendar' : 'table';
$query = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$courtFilter = trim((string) ($_GET['court'] ?? 'all'));
$programFilter = trim((string) ($_GET['program'] ?? 'all'));
$dateFilter = trim((string) ($_GET['date'] ?? ''));
$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$successMsg = '';
$errorMsg = '';

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
        'confirmed' => 'Approved',
        'approved' => 'Approved',
        'paid' => 'Approved',
        default => ucwords(str_replace('_', ' ', $status ?: 'pending')),
    };
}

function booking_payment_label(string $status): string {
    $status = strtolower(trim($status));
    return match ($status) {
        'approved', 'paid', 'verified' => 'Verified',
        default => ucwords(str_replace('_', ' ', $status ?: 'pending')),
    };
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

function booking_admin_query_path(array $overrides = []): string {
    $query = $_GET;
    unset($query['id']);
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return 'manage-bookings.php' . ($query ? '?' . http_build_query($query) : '');
}

function booking_proof_is_image(string $path): bool {
    return (bool) preg_match('/\.(jpe?g|png|webp)$/i', $path);
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

function booking_admin_update_booking(PDO $pdo, AdminService $adminService, int $bookingId, string $status, int $adminId, string $note = ''): bool {
    $status = strtolower(trim($status));
    if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled', 'completed'], true)) {
        throw new RuntimeException('Choose a valid booking status.');
    }

    $ok = $adminService->updateBookingStatus($bookingId, $status, $adminId);
    if (!$ok) {
        return false;
    }

    if ($note !== '' || in_array($status, ['rejected', 'cancelled'], true)) {
        $label = $note !== '' ? $note : ucfirst($status) . ' by admin';
        $stmt = $pdo->prepare(
            "UPDATE bookings
             SET cancellation_label = CASE WHEN :status_label IN ('rejected', 'cancelled') THEN :label ELSE cancellation_label END,
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
            'note_first' => 'Admin note: ' . $note,
            'note_append' => 'Admin note: ' . $note,
        ]);
    }

    return true;
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
        if (in_array(strtolower((string) $booking['status']), ['cancelled', 'rejected', 'expired', 'refunded'], true)) {
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
            $insertPayment = $pdo->prepare(
                "INSERT INTO payments (booking_id, proof_image, amount, payment_method, reference_number, status, reviewed_by, reviewed_at, remarks)
                 VALUES (:booking_id, NULL, :amount, :payment_method, :reference_number, 'approved', :admin_id, NOW(), :remarks)"
            );
            $insertPayment->execute([
                'booking_id' => $bookingId,
                'amount' => (float) ($booking['total'] ?? 0),
                'payment_method' => (string) ($booking['payment_method'] ?? 'Admin verified'),
                'reference_number' => 'ADMIN-' . (string) ($booking['reference'] ?? $bookingId),
                'admin_id' => $adminId,
                'remarks' => $remarks !== '' ? $remarks : 'Marked as paid by admin',
            ]);
        }

        $updateBooking = $pdo->prepare("UPDATE bookings SET payment_status = 'verified' WHERE id = :id");
        $updateBooking->execute(['id' => $bookingId]);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['booking_id'] ?? 0);
        $paymentId = (int) ($_POST['payment_id'] ?? 0);
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        try {
            if ($action === 'approve_booking' && $id && $pdo) {
                $successMsg = booking_admin_update_booking($pdo, $adminService, $id, 'approved', (int) $_SESSION['user']['id'], $remarks) ? 'Booking approved.' : '';
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

$where = [];
$params = [];
if ($query !== '') {
    $where[] = "(b.reference LIKE :q OR u.name LIKE :q OR u.email LIKE :q)";
    $params['q'] = '%' . $query . '%';
}
if ($statusFilter === 'expired') {
    $where[] = "b.status = 'cancelled' AND LOWER(b.cancellation_label) LIKE '%expired%'";
} elseif ($statusFilter !== 'all') {
    $where[] = "LOWER(b.status) LIKE :status";
    $params['status'] = '%' . strtolower($statusFilter) . '%';
}
if ($courtFilter !== 'all') {
    $where[] = "bi.court = :court";
    $params['court'] = $courtFilter;
}
if ($programFilter !== 'all') {
    $where[] = "bi.name = :program";
    $params['program'] = $programFilter;
}
if ($dateFilter !== '') {
    $where[] = "COALESCE(bi.booking_date, sched.session_date) = :date_filter_exact";
    $params['date_filter_exact'] = $dateFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$bookings = booking_rows($pdo, "
    SELECT b.*, u.name AS user_name, u.email AS user_email,
           GROUP_CONCAT(DISTINCT bi.name ORDER BY bi.id SEPARATOR ', ') AS program_names,
           GROUP_CONCAT(DISTINCT bi.court ORDER BY bi.id SEPARATOR ', ') AS courts,
           SUM(bi.quantity) AS players,
           MIN(COALESCE(bi.booking_date, sched.session_date)) AS booking_date_raw,
           MIN(bi.end_time) AS booking_end_time_raw,
           DATE_FORMAT(MIN(COALESCE(bi.booking_date, sched.session_date)), '%W, %M %e, %Y') AS booking_date,
           MIN(CONCAT(TIME_FORMAT(bi.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(bi.end_time, '%h:%i %p'))) AS booking_time,
            lp.id AS latest_payment_id,
            lp.status AS latest_payment_status,
            lp.reference_number AS latest_payment_reference,
            lp.proof_image AS latest_payment_proof,
            lp.reviewed_by AS latest_payment_reviewed_by,
            lp.reviewed_at AS latest_payment_reviewed_at
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    LEFT JOIN booking_items bi ON bi.booking_id = b.id
    LEFT JOIN sessions sched ON sched.id = bi.session_id
    LEFT JOIN payments lp ON lp.id = (
        SELECT p2.id FROM payments p2 WHERE p2.booking_id = b.id ORDER BY p2.created_at DESC, p2.id DESC LIMIT 1
    )
    $whereSql
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT 10
", $params);

$allBookingItems = booking_rows($pdo, "
    SELECT bi.*,
           COALESCE(bi.booking_date, sched.session_date) AS booking_date_sql,
           DATE_FORMAT(COALESCE(bi.booking_date, sched.session_date), '%W, %M %e, %Y') AS booking_date,
           CONCAT(TIME_FORMAT(bi.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(bi.end_time, '%h:%i %p')) AS booking_time,
           b.id AS booking_id, b.reference, b.status, b.payment_status, b.total, u.name AS user_name, u.email AS user_email
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    LEFT JOIN sessions sched ON sched.id = bi.session_id
    LEFT JOIN users u ON u.id = b.user_id
    WHERE COALESCE(bi.booking_date, sched.session_date) BETWEEN :week_start AND :week_end
      AND b.status NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
      " . ($where ? 'AND ' . implode(' AND ', $where) : '') . "
    ORDER BY COALESCE(bi.booking_date, sched.session_date) ASC, bi.start_time ASC, bi.id ASC
", [
    'week_start' => $weekStartSql,
    'week_end' => $weekEndSql,
] + $params);

$currentBooking = $bookingId ? $adminService->getBookingDetail($bookingId) : null;

$totalBookings = (int) booking_scalar($pdo, 'SELECT COUNT(*) FROM bookings');
$weekBookings = (int) booking_scalar($pdo, 'SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$pendingPayments = (int) booking_scalar($pdo, "SELECT COUNT(*) FROM bookings b LEFT JOIN payments p ON p.id = (SELECT p2.id FROM payments p2 WHERE p2.booking_id = b.id ORDER BY p2.created_at DESC, p2.id DESC LIMIT 1) WHERE COALESCE(p.status, b.payment_status, 'pending') = 'pending' AND b.status NOT IN ('cancelled', 'rejected', 'expired', 'refunded')");
$expiredBookings = (int) booking_scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE status = 'cancelled' AND LOWER(cancellation_label) LIKE '%expired%'");
$todaySessions = (int) booking_scalar($pdo, "SELECT COUNT(DISTINCT bi.booking_id) FROM booking_items bi JOIN bookings b ON b.id = bi.booking_id LEFT JOIN sessions s ON s.id = bi.session_id WHERE COALESCE(bi.booking_date, s.session_date) = ? AND b.status NOT IN ('cancelled', 'rejected', 'expired', 'refunded')", [$todaySql]);
$monthlyRevenue = (float) booking_scalar($pdo, "SELECT COALESCE(SUM(b.total), 0) FROM bookings b LEFT JOIN payments p ON p.id = (SELECT p2.id FROM payments p2 WHERE p2.booking_id = b.id ORDER BY p2.created_at DESC, p2.id DESC LIMIT 1) WHERE MONTH(b.created_at) = MONTH(CURRENT_DATE()) AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) AND (LOWER(b.payment_status) IN ('verified', 'paid', 'completed') OR p.status = 'approved')");
$courts = booking_rows($pdo, 'SELECT name FROM courts ORDER BY id ASC');
$programs = booking_rows($pdo, 'SELECT DISTINCT name FROM booking_items ORDER BY name ASC');

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
    $day = $weekStart->modify('+' . $i . ' days');
    $weekDays[] = [
        'label' => strtoupper($day->format('D')),
        'date' => $day->format('M j'),
        'match' => $day->format('l, F j, Y'),
        'match_sql' => $day->format('Y-m-d'),
        'today' => $day->format('Y-m-d') === $todaySql,
    ];
}

$calendarLanes = [
    ['Court Green', 'green', 'img/court/court green-1.png'],
    ['Court Pink', 'pink', 'img/court/court pink-1.webp'],
    ['Social Play Area', 'orange', 'img/court/social play-1.png'],
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
                    <?php echo admin_icon($icons, 'search'); ?>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search bookings">
                </form>
                <button class="bookings-button ghost" type="button"><?php echo admin_icon($icons, 'export'); ?> Export</button>
                <a class="bookings-button primary" href="<?php echo pickled_admin_url('manage-bookings.php'); ?>">New Booking</a>
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
            <div class="booking-filter-search-row">
                <label class="filter-search"><?php echo admin_icon($icons, 'search'); ?><input type="search" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search by reference, user, or email"></label>
                <div class="view-toggle"><a class="<?php echo $view === 'table' ? 'active' : ''; ?>" href="<?php echo pickled_admin_url('manage-bookings.php?view=table'); ?>">Table View</a><a class="<?php echo $view === 'calendar' ? 'active' : ''; ?>" href="<?php echo pickled_admin_url('manage-bookings.php?view=calendar'); ?>">Calendar View</a></div>
            </div>
            <div class="booking-filter-controls-row">
                <select name="court"><option value="all">All Courts</option><?php foreach ($courts as $court): ?><option value="<?php echo htmlspecialchars($court['name']); ?>" <?php echo $courtFilter === $court['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($court['name']); ?></option><?php endforeach; ?></select>
                <select name="program"><option value="all">All Programs & Events</option><?php foreach ($programs as $program): ?><option value="<?php echo htmlspecialchars($program['name']); ?>" <?php echo $programFilter === $program['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($program['name']); ?></option><?php endforeach; ?></select>
                <select name="status"><option value="all">All Statuses</option><?php foreach (['pending', 'approved', 'rejected', 'cancelled', 'completed', 'expired'] as $status): ?><option value="<?php echo $status; ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars(booking_admin_label($status)); ?></option><?php endforeach; ?></select>
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
                            $bookingStatusLabel = $isExpiredBooking ? 'Expired' : booking_admin_label((string) $booking['status']);
                            $displayPaymentStatus = (string) ($booking['latest_payment_status'] ?: $booking['payment_status']);
                            $paymentKey = booking_payment_key($displayPaymentStatus);
                            $canReviewPayment = $displayPaymentStatus === 'pending' && !empty($booking['latest_payment_id']);
                            $bookingStatus = strtolower((string) $booking['status']);
                            $closedBooking = in_array($bookingStatus, ['cancelled', 'rejected', 'expired', 'refunded'], true);
                            $canComplete = false;
                            if (!empty($booking['booking_date_raw']) && !empty($booking['booking_end_time_raw'])) {
                                $canComplete = strtotime((string) $booking['booking_date_raw'] . ' ' . (string) $booking['booking_end_time_raw']) !== false
                                    && strtotime((string) $booking['booking_date_raw'] . ' ' . (string) $booking['booking_end_time_raw']) <= time();
                            }
                        ?>
                        <div class="booking-management-row">
                            <span class="booking-ref"><?php echo htmlspecialchars($booking['reference']); ?></span>
                            <span><strong><?php echo htmlspecialchars($booking['user_name'] ?? 'Guest'); ?></strong><small><?php echo htmlspecialchars($booking['user_email'] ?? ''); ?></small></span>
                            <span><?php echo htmlspecialchars($booking['program_names'] ?: 'Booking'); ?></span>
                            <span><?php echo htmlspecialchars($booking['courts'] ?: 'Any Court'); ?></span>
                            <span><?php echo htmlspecialchars($booking['booking_date'] ?: date('M j, Y', strtotime($booking['created_at']))); ?></span>
                            <span><?php echo htmlspecialchars($booking['booking_time'] ?: '-'); ?></span>
                            <span><?php echo (int) ($booking['players'] ?? 1); ?></span>
                            <span><em class="status-pill status-<?php echo $statusKey; ?>"><?php echo htmlspecialchars($bookingStatusLabel); ?></em><?php if ($isExpiredBooking): ?><small><?php echo htmlspecialchars($booking['cancellation_label']); ?></small><?php endif; ?></span>
                            <span><em class="status-pill payment-<?php echo $paymentKey; ?>"><?php echo htmlspecialchars(booking_payment_label($displayPaymentStatus)); ?></em><?php if (!empty($booking['latest_payment_reference'])): ?><small><?php echo htmlspecialchars($booking['latest_payment_reference']); ?></small><?php endif; ?></span>
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
                <a class="bookings-button ghost" href="<?php echo pickled_admin_url(booking_admin_query_path(['view' => 'calendar', 'week_start' => $weekStart->modify('-7 days')->format('Y-m-d')])); ?>">Previous Week</a>
                <strong><?php echo htmlspecialchars($weekRangeLabel); ?></strong>
                <a class="bookings-button ghost" href="<?php echo pickled_admin_url(booking_admin_query_path(['view' => 'calendar', 'week_start' => $today->modify('monday this week')->format('Y-m-d')])); ?>">Current Week</a>
                <a class="bookings-button ghost" href="<?php echo pickled_admin_url(booking_admin_query_path(['view' => 'calendar', 'week_start' => $weekStart->modify('+7 days')->format('Y-m-d')])); ?>">Next Week</a>
            </nav>
            <section class="calendar-workspace">
                <aside class="court-lane-cards">
                    <?php foreach ($calendarLanes as [$courtName, $tone, $image]): ?>
                        <article class="court-mini-card <?php echo $tone; ?>"><img src="<?php echo booking_asset($image); ?>" alt="<?php echo htmlspecialchars($courtName); ?>"><strong><?php echo htmlspecialchars($courtName); ?></strong><span>Capacity: 24 slots</span><div><i style="width: <?php echo $tone === 'orange' ? 60 : ($tone === 'pink' ? 45 : 75); ?>%"></i></div></article>
                    <?php endforeach; ?>
                </aside>
                <div class="week-calendar">
                    <div class="calendar-header"><span>Time</span><?php foreach ($weekDays as $day): ?><strong class="<?php echo $day['today'] ? 'today' : ''; ?>"><?php echo $day['label']; ?><small><?php echo $day['date']; ?></small></strong><?php endforeach; ?></div>
                    <?php foreach (range(8, 21) as $hour): ?>
                        <div class="calendar-hour"><time><?php echo date('g:00 A', strtotime($hour . ':00')); ?></time><?php foreach ($weekDays as $day): ?><div class="calendar-cell">
                            <?php foreach ($allBookingItems as $item): ?>
                                <?php if (($item['booking_date_sql'] ?? '') === $day['match_sql'] && str_starts_with((string) $item['booking_time'], date('h:00 A', strtotime($hour . ':00')))): ?>
                                    <?php $itemText = strtolower(($item['name'] ?? '') . ' ' . ($item['category'] ?? '') . ' ' . ($item['court'] ?? '')); $tone = (str_contains($itemText, 'private') || str_contains($itemText, 'coach')) ? 'purple' : (str_contains($itemText, 'pink') ? 'pink' : (str_contains($itemText, 'social') ? 'orange' : 'green')); ?>
                                    <a class="calendar-event <?php echo $tone; ?>" href="<?php echo pickled_admin_url('manage-bookings.php?view=calendar&id=' . (int) $item['booking_id']); ?>"><strong><?php echo htmlspecialchars($item['name']); ?></strong><span><?php echo htmlspecialchars($item['booking_time']); ?></span><small><?php echo htmlspecialchars($item['user_name'] ?? 'Guest'); ?></small></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div><?php endforeach; ?></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php if ($currentBooking): ?>
    <?php
        $latestPayment = $currentBooking['latest_payment'] ?? null;
        $paymentRows = $currentBooking['payments'] ?? [];
        $currentItems = $currentBooking['items'] ?? [];
        $firstItem = $currentItems[0] ?? [];
        $playersTotal = array_sum(array_map(static fn($item): int => (int) ($item['quantity'] ?? 0), $currentItems));
        $currentPaymentStatus = (string) (($latestPayment['status'] ?? '') ?: ($currentBooking['payment_status'] ?? 'pending'));
        $currentStatus = strtolower((string) ($currentBooking['status'] ?? 'pending'));
        $currentPaymentNormalized = strtolower($currentPaymentStatus);
        $currentIsPaid = in_array($currentPaymentNormalized, ['approved', 'paid', 'verified'], true) || in_array($currentStatus, ['paid'], true);
        $currentIsApproved = in_array($currentStatus, ['approved', 'confirmed'], true);
        $currentIsPending = $currentStatus === 'pending';
        $currentIsCompleted = $currentStatus === 'completed';
        $currentIsRejected = $currentStatus === 'rejected';
        $currentIsCancelled = in_array($currentStatus, ['cancelled', 'expired', 'refunded'], true);
        $currentClosed = $currentIsCompleted || $currentIsRejected || $currentIsCancelled;
        $currentCanComplete = !empty($firstItem['booking_date_raw']) && !empty($firstItem['end_time'])
            && strtotime((string) $firstItem['booking_date_raw'] . ' ' . (string) $firstItem['end_time']) !== false
            && strtotime((string) $firstItem['booking_date_raw'] . ' ' . (string) $firstItem['end_time']) <= time();
        $terminalLabel = $currentIsCompleted ? 'Completed' : ($currentIsRejected ? 'Rejected' : ($currentIsCancelled ? booking_admin_label($currentStatus) : ''));
    ?>
    <div class="booking-drawer-backdrop"><a href="<?php echo pickled_admin_url('manage-bookings.php?view=' . $view); ?>" aria-label="Close"></a></div>
    <aside class="booking-drawer booking-detail-modal" role="dialog" aria-modal="true" aria-label="Booking management">
        <header><div><span>Review Booking</span><h2><?php echo htmlspecialchars($currentBooking['reference']); ?></h2></div><a href="<?php echo pickled_admin_url('manage-bookings.php?view=' . $view); ?>">×</a></header>
        <section><h3>Booking Information</h3><p><strong>Reference</strong><?php echo htmlspecialchars($currentBooking['reference']); ?></p><p><strong>Court</strong><?php echo htmlspecialchars($firstItem['court'] ?? 'Any Court'); ?></p><p><strong>Program / Service</strong><?php echo htmlspecialchars($firstItem['name'] ?? 'Booking'); ?></p><p><strong>Date</strong><?php echo htmlspecialchars($firstItem['booking_date'] ?? date('M j, Y', strtotime($currentBooking['created_at']))); ?></p><p><strong>Time</strong><?php echo htmlspecialchars($firstItem['booking_time'] ?? '-'); ?></p><p><strong>Number of Players</strong><?php echo number_format($playersTotal ?: 1); ?></p></section>
        <section><h3>Player Information</h3><p><strong>Player Name</strong><?php echo htmlspecialchars($currentBooking['user']['name'] ?? 'Guest'); ?></p><p><strong>Email</strong><?php echo htmlspecialchars($currentBooking['user']['email'] ?? '-'); ?></p></section>
        <section><h3>Payment Information</h3><p><strong>Payment Method</strong><?php echo htmlspecialchars($currentBooking['payment_method'] ?? '-'); ?></p><p><strong>Payment Status</strong><em class="status-pill payment-<?php echo booking_payment_key($currentPaymentStatus); ?>"><?php echo htmlspecialchars(booking_payment_label($currentPaymentStatus)); ?></em></p><?php if ($latestPayment): ?><p><strong>Reference No.</strong><?php echo htmlspecialchars($latestPayment['reference_number'] ?? '-'); ?></p><p><strong>Amount</strong>&#8369;<?php echo number_format((float) ($latestPayment['amount'] ?? $currentBooking['total']), 2); ?></p><?php if (!empty($latestPayment['reviewed_at'])): ?><p><strong>Reviewed At</strong><?php echo htmlspecialchars((string) $latestPayment['reviewed_at']); ?></p><?php endif; ?><?php if (!empty($latestPayment['proof_image'])): ?><p><a href="<?php echo booking_public_url($latestPayment['proof_image']); ?>" target="_blank" rel="noopener">View proof of payment</a></p><?php if (booking_proof_is_image((string) $latestPayment['proof_image'])): ?><img src="<?php echo booking_public_url($latestPayment['proof_image']); ?>" alt="Proof of payment" style="max-width:100%;border-radius:8px;margin-top:10px;"><?php endif; ?><?php else: ?><p><strong>Proof of Payment</strong>No uploaded proof.</p><?php endif; ?><?php else: ?><p>No payment record yet.</p><?php endif; ?></section>
        <section><h3>Status Information</h3><p><strong>Booking Status</strong><em class="status-pill status-<?php echo booking_status_key((string) $currentBooking['status']); ?>"><?php echo htmlspecialchars(booking_admin_label((string) $currentBooking['status'])); ?></em></p><p><strong>Total</strong>₱<?php echo number_format((float) $currentBooking['total'], 2); ?></p><?php if (!empty($currentBooking['cancellation_label']) && in_array($currentStatus, ['cancelled', 'rejected'], true)): ?><p><strong>Admin Note</strong><?php echo htmlspecialchars($currentBooking['cancellation_label']); ?></p><?php endif; ?></section>
        <?php if ($paymentRows): ?><section><h3>Receipt History</h3><?php foreach ($paymentRows as $payment): ?><p><strong><?php echo htmlspecialchars(booking_payment_label((string) $payment['status'])); ?></strong> <?php echo htmlspecialchars($payment['reference_number']); ?> - &#8369;<?php echo number_format((float) $payment['amount'], 2); ?><?php if (!empty($payment['reviewer_name'])): ?><br><small>Reviewed by <?php echo htmlspecialchars($payment['reviewer_name']); ?></small><?php endif; ?><?php if (!empty($payment['remarks'])): ?><br><small><?php echo htmlspecialchars($payment['remarks']); ?></small><?php endif; ?></p><?php endforeach; ?></section><?php endif; ?>
        <form class="drawer-actions" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
            <input type="hidden" name="booking_id" value="<?php echo (int) $currentBooking['id']; ?>">
            <?php if ($latestPayment): ?>
                <input type="hidden" name="payment_id" value="<?php echo (int) $latestPayment['id']; ?>">
            <?php endif; ?>
            <section class="drawer-note-section"><h3>Admin Notes</h3><p>Required when rejecting a booking. Optional for other actions.</p><textarea name="remarks" rows="3" placeholder="Add an admin note"></textarea></section>
            <div class="drawer-action-buttons">
                <?php if ($currentIsPending && !$currentIsPaid): ?>
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
</script>
<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>

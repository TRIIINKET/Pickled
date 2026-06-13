<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/NotificationRepository.php';

final class NotificationService
{
    public function __construct(private readonly NotificationRepository $notifications = new NotificationRepository()) {}

    public function createForUser(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): int
    {
        return $this->notifications->create($userId, $title, $message, $type, $link);
    }

    public function notifyAdmins(string $title, string $message, string $type, ?string $link = null): int
    {
        $created = 0;
        foreach ($this->notifications->usersByRole('admin') as $admin) {
            if ($this->createForUser((int) $admin['id'], $title, $message, $type, $link) > 0) {
                $created++;
            }
        }
        return $created;
    }

    public function notifyBookingCreated(array $booking): int
    {
        $reference = (string) ($booking['reference'] ?? 'your booking');
        $created = $this->createForUser(
            (int) ($booking['user_id'] ?? 0),
            'Booking Created',
            'Your booking ' . $reference . ' has been created and is waiting for payment review.',
            'booking_created',
            $this->residentBookingLink($booking)
        );

        return $created + $this->notifyAdmins(
            'New Booking',
            'A new booking was created: ' . $reference . '.',
            'booking_created',
            $this->adminBookingLink($booking)
        );
    }

    public function notifyPaymentUploaded(array $booking, array $payment): int
    {
        $reference = (string) ($booking['reference'] ?? ('Booking #' . ($booking['id'] ?? '')));
        return $this->notifyAdmins(
            'Payment Uploaded',
            'A payment receipt was uploaded for ' . $reference . ' with reference number ' . (string) ($payment['reference_number'] ?? '-') . '.',
            'payment_uploaded',
            $this->adminBookingLink($booking)
        );
    }

    public function notifyPaymentApproved(array $booking): int
    {
        $reference = (string) ($booking['reference'] ?? 'your booking');
        $created = $this->createForUser(
            (int) ($booking['user_id'] ?? 0),
            'Payment Approved',
            'Your payment for ' . $reference . ' was approved.',
            'payment_approved',
            $this->residentBookingLink($booking)
        );
        $created += $this->createForUser(
            (int) ($booking['user_id'] ?? 0),
            'Booking Confirmed',
            'Your booking ' . $reference . ' is now confirmed.',
            'booking_confirmed',
            $this->residentBookingLink($booking)
        );

        return $created;
    }

    public function notifyPaymentRejected(array $booking, string $remarks = ''): int
    {
        $reference = (string) ($booking['reference'] ?? 'your booking');
        $message = 'Your payment for ' . $reference . ' was rejected.';
        if (trim($remarks) !== '') {
            $message .= ' Remarks: ' . trim($remarks);
        }

        return $this->createForUser(
            (int) ($booking['user_id'] ?? 0),
            'Payment Rejected',
            $message,
            'payment_rejected',
            $this->residentBookingLink($booking)
        );
    }

    public function notifyBookingExpired(array $booking): int
    {
        $reference = (string) ($booking['reference'] ?? 'your booking');
        $created = $this->createForUser(
            (int) ($booking['user_id'] ?? 0),
            'Booking Expired',
            'Your pending booking ' . $reference . ' expired because payment was not completed in time.',
            'booking_expired',
            $this->residentBookingLink($booking)
        );

        return $created + $this->notifyAdmins(
            'Booking Expired',
            'Pending booking ' . $reference . ' expired and its reserved capacity was released.',
            'booking_expired',
            $this->adminBookingLink($booking)
        );
    }

    public function notifyBookingCancelled(array $booking): int
    {
        return $this->createForUser(
            (int) ($booking['user_id'] ?? 0),
            'Booking Cancelled',
            'Your booking ' . (string) ($booking['reference'] ?? '') . ' was cancelled.',
            'booking_cancelled',
            $this->residentBookingLink($booking)
        );
    }

    public function notifySessionUpdated(array $session, string $action = 'updated'): int
    {
        $coachUserId = (int) ($session['coach_user_id'] ?? 0);
        if ($coachUserId <= 0) {
            return 0;
        }

        $name = (string) ($session['name'] ?? 'A session');
        $date = (string) ($session['display_date'] ?? $session['session_date'] ?? '');
        $time = (string) ($session['session_time'] ?? '');

        return $this->createForUser(
            $coachUserId,
            'Session ' . ucfirst($action),
            trim($name . ' ' . $date . ' ' . $time) . ' was ' . $action . '.',
            'session_updated',
            'coach/schedule.php'
        );
    }

    public function notificationsForUser(int $userId, int $limit = 50): array
    {
        return $this->notifications->findByUserId($userId, $limit);
    }

    public function allNotifications(int $limit = 100): array
    {
        return $this->notifications->findAll($limit);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->unreadCount($userId);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->notifications->markAsRead($notificationId, $userId);
    }

    public function markAllAsRead(int $userId): bool
    {
        return $this->notifications->markUserNotificationsAsRead($userId);
    }

    public function delete(int $notificationId): bool
    {
        return $this->notifications->delete($notificationId);
    }

    private function residentBookingLink(array $booking): string
    {
        $bookingId = (int) ($booking['id'] ?? 0);
        return $bookingId > 0 ? 'resident/booking-details.php?id=' . $bookingId : 'resident/booking.php';
    }

    private function adminBookingLink(array $booking): string
    {
        $bookingId = (int) ($booking['id'] ?? 0);
        return $bookingId > 0 ? 'admin/manage-bookings.php?id=' . $bookingId : 'admin/manage-bookings.php';
    }
}

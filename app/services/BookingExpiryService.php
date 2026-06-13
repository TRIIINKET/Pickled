<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/AdminLogService.php';

final class BookingExpiryService
{
    private const DEFAULT_PENDING_EXPIRY_HOURS = 24;

    public function __construct(
        private readonly BookingRepository $bookings = new BookingRepository(),
        private readonly NotificationService $notifications = new NotificationService(),
        private readonly AdminLogService $adminLogs = new AdminLogService()
    ) {}

    public function processExpiredPendingBookings(?DateTimeImmutable $now = null, int $limit = 100): int
    {
        $cutoff = $this->cutoff($now);
        $expired = 0;

        foreach ($this->bookings->findExpiredPendingIds($cutoff, $limit) as $bookingId) {
            if ($this->bookings->expirePendingBooking($bookingId, $cutoff)) {
                $booking = $this->bookings->findById($bookingId);
                if ($booking) {
                    $this->adminLogs->recordBookingExpired($booking);
                    $this->notifications->notifyBookingExpired($booking);
                }
                $expired++;
            }
        }

        return $expired;
    }

    public function pendingExpiryHours(): int
    {
        $config = require __DIR__ . '/../../includes/config.php';
        return max(1, (int) ($config['booking']['pending_expiry_hours'] ?? self::DEFAULT_PENDING_EXPIRY_HOURS));
    }

    public function cutoff(?DateTimeImmutable $now = null): DateTimeImmutable
    {
        $config = require __DIR__ . '/../../includes/config.php';
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
        $now ??= new DateTimeImmutable('now', $timezone);

        return $now->setTimezone($timezone)->modify('-' . $this->pendingExpiryHours() . ' hours');
    }
}

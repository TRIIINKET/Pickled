<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/AdminLogService.php';

final class BookingExpiryService
{
    private const DEFAULT_PENDING_EXPIRY_MINUTES = 30;

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

    public function pendingExpiryMinutes(): int
    {
        $config = require __DIR__ . '/../../includes/config.php';
        if (isset($config['booking']['pending_expiry_minutes'])) {
            return max(1, (int) $config['booking']['pending_expiry_minutes']);
        }
        if (isset($config['booking']['pending_expiry_hours'])) {
            return max(1, (int) $config['booking']['pending_expiry_hours']) * 60;
        }
        return self::DEFAULT_PENDING_EXPIRY_MINUTES;
    }

    public function cutoff(?DateTimeImmutable $now = null): DateTimeImmutable
    {
        $config = require __DIR__ . '/../../includes/config.php';
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
        $now ??= new DateTimeImmutable('now', $timezone);

        return $now->setTimezone($timezone)->modify('-' . $this->pendingExpiryMinutes() . ' minutes');
    }

    public function deadlineForBooking(array $booking): ?DateTimeImmutable
    {
        $createdAt = trim((string) ($booking['created_at'] ?? ''));
        if ($createdAt === '') {
            return null;
        }

        $config = require __DIR__ . '/../../includes/config.php';
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
        try {
            return (new DateTimeImmutable($createdAt, $timezone))->modify('+' . $this->pendingExpiryMinutes() . ' minutes');
        } catch (Throwable) {
            return null;
        }
    }
}

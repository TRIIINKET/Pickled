<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/AdminLogRepository.php';

final class AdminLogService
{
    public function __construct(private readonly AdminLogRepository $logs = new AdminLogRepository()) {}

    public function record(int $adminId, string $action, string $entityType, ?int $entityId = null, ?string $description = null): int
    {
        if ($adminId <= 0) {
            return 0;
        }

        try {
            return $this->logs->create($adminId, $action, $entityType, $entityId, $description);
        } catch (Throwable $e) {
            error_log('AdminLogService::record - ' . $e->getMessage());
            return 0;
        }
    }

    public function recordBookingCreated(array $booking): int
    {
        return $this->record(
            $this->actorFrom($booking['user_id'] ?? null),
            'booking_created',
            'booking',
            $this->entityId($booking),
            'Booking ' . $this->reference($booking) . ' was created.'
        );
    }

    public function recordBookingConfirmed(array $booking, int $adminId): int
    {
        return $this->record(
            $adminId,
            'booking_confirmed',
            'booking',
            $this->entityId($booking),
            'Booking ' . $this->reference($booking) . ' was confirmed.'
        );
    }

    public function recordBookingCancelled(array $booking, int $adminId): int
    {
        return $this->record(
            $adminId,
            'booking_cancelled',
            'booking',
            $this->entityId($booking),
            'Booking ' . $this->reference($booking) . ' was cancelled.'
        );
    }

    public function recordBookingExpired(array $booking): int
    {
        return $this->record(
            $this->systemActorId(),
            'booking_expired',
            'booking',
            $this->entityId($booking),
            'Booking ' . $this->reference($booking) . ' expired automatically.'
        );
    }

    public function recordPaymentUploaded(array $booking, array $payment): int
    {
        $reference = (string) ($payment['reference_number'] ?? '-');
        return $this->record(
            $this->actorFrom($booking['user_id'] ?? null),
            'payment_uploaded',
            'payment',
            $this->entityId($payment),
            'Payment receipt ' . $reference . ' was uploaded for booking ' . $this->reference($booking) . '.'
        );
    }

    public function recordPaymentApproved(array $booking, array $payment, int $adminId): int
    {
        return $this->record(
            $adminId,
            'payment_approved',
            'payment',
            $this->entityId($payment),
            'Payment for booking ' . $this->reference($booking) . ' was approved.'
        );
    }

    public function recordPaymentRejected(array $booking, array $payment, int $adminId, string $remarks = ''): int
    {
        $description = 'Payment for booking ' . $this->reference($booking) . ' was rejected.';
        if (trim($remarks) !== '') {
            $description .= ' Reason: ' . trim($remarks);
        }

        return $this->record($adminId, 'payment_rejected', 'payment', $this->entityId($payment), $description);
    }

    public function recordCourtCreated(int $adminId, int $courtId, string $name): int
    {
        return $this->record($adminId, 'court_created', 'court', $courtId, 'Court ' . $name . ' was created.');
    }

    public function recordCourtUpdated(int $adminId, int $courtId, string $name): int
    {
        return $this->record($adminId, 'court_updated', 'court', $courtId, 'Court ' . $name . ' was updated.');
    }

    public function recordCourtDisabled(int $adminId, int $courtId, string $status): int
    {
        return $this->record($adminId, 'court_disabled', 'court', $courtId, 'Court status was changed to ' . $status . '.');
    }

    public function recordVariantCreated(int $adminId, int $variantId, string $name): int
    {
        return $this->record($adminId, 'variant_created', 'booking_variant', $variantId, 'Variant ' . $name . ' was created.');
    }

    public function recordVariantUpdated(int $adminId, int $variantId, string $name): int
    {
        return $this->record($adminId, 'variant_updated', 'booking_variant', $variantId, 'Variant ' . $name . ' was updated.');
    }

    public function recordSessionCreated(int $adminId, array $session): int
    {
        return $this->record($adminId, 'session_created', 'session', $this->entityId($session), $this->sessionDescription($session, 'created'));
    }

    public function recordSessionUpdated(int $adminId, array $session): int
    {
        return $this->record($adminId, 'session_updated', 'session', $this->entityId($session), $this->sessionDescription($session, 'updated'));
    }

    public function recordSessionCancelled(int $adminId, array $session): int
    {
        return $this->record($adminId, 'session_cancelled', 'session', $this->entityId($session), $this->sessionDescription($session, 'cancelled'));
    }

    public function recordAdminNotificationSent(int $adminId, ?int $notificationId, string $description): int
    {
        return $this->record($adminId, 'admin_notification_sent', 'notification', $notificationId, $description);
    }

    public function logs(array $filters = [], int $limit = 100, string $sort = 'desc'): array
    {
        return $this->logs->findAll($filters, $limit, $sort);
    }

    public function logsForAdmin(int $adminId, int $limit = 50, string $sort = 'desc'): array
    {
        return $this->logs->findByAdminId($adminId, $limit, $sort);
    }

    public function actionOptions(): array
    {
        return $this->logs->actionOptions();
    }

    public function entityTypeOptions(): array
    {
        return $this->logs->entityTypeOptions();
    }

    private function actorFrom(mixed $value): int
    {
        return max(0, (int) $value);
    }

    private function entityId(array $entity): ?int
    {
        $id = (int) ($entity['id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function reference(array $booking): string
    {
        $reference = trim((string) ($booking['reference'] ?? ''));
        return $reference !== '' ? $reference : ('#' . (string) ($booking['id'] ?? ''));
    }

    private function sessionDescription(array $session, string $action): string
    {
        $name = trim((string) ($session['name'] ?? 'Session'));
        $date = trim((string) ($session['display_date'] ?? $session['session_date'] ?? ''));
        $time = trim((string) ($session['session_time'] ?? ''));
        return trim($name . ' ' . $date . ' ' . $time) . ' was ' . $action . '.';
    }

    private function systemActorId(): int
    {
        return $this->logs->firstAdminId() ?? 0;
    }
}

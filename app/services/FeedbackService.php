<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/FeedbackRepository.php';
require_once __DIR__ . '/../../includes/validation.php';

final class FeedbackService
{
    public function __construct(private readonly FeedbackRepository $feedback = new FeedbackRepository()) {}

    public function feedbackForBooking(int $bookingId, int $userId): ?array
    {
        if ($bookingId <= 0 || $userId <= 0) {
            return null;
        }

        return $this->feedback->findByBookingIdForUser($bookingId, $userId);
    }

    public function targetsForBooking(int $bookingId, int $userId): array
    {
        if (!$this->feedback->bookingForUser($bookingId, $userId)) {
            return [];
        }

        return $this->feedback->targetsForBooking($bookingId);
    }

    public function canLeaveFeedback(int $bookingId, int $userId): bool
    {
        if ($bookingId <= 0 || $userId <= 0) {
            return false;
        }

        return $this->feedback->bookingForUser($bookingId, $userId) !== null
            && $this->feedback->bookingEligibleForFeedback($bookingId);
    }

    public function submit(int $userId, int $bookingId, ?int $bookingItemId, int $rating, string $comment): int
    {
        $booking = $this->assertFeedbackBooking($bookingId, $userId);
        if ($this->feedback->findByBookingId((int) $booking['id'])) {
            throw new RuntimeException('Feedback has already been submitted for this booking.');
        }

        $target = $this->resolveTarget((int) $booking['id'], $bookingItemId);

        try {
            return $this->feedback->create([
                'booking_id' => (int) $booking['id'],
                'booking_item_id' => $target['booking_item_id'],
                'user_id' => $userId,
                'coach_user_id' => $target['coach_user_id'],
                'rating' => $this->rating($rating),
                'comment' => $this->comment($comment),
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Feedback has already been submitted for this booking.');
            }
            throw $e;
        }
    }

    public function update(int $userId, int $bookingId, ?int $bookingItemId, int $rating, string $comment): bool
    {
        $booking = $this->assertFeedbackBooking($bookingId, $userId);
        $existing = $this->feedback->findByBookingIdForUser((int) $booking['id'], $userId);
        if (!$existing) {
            throw new RuntimeException('Feedback was not found for this booking.');
        }

        $target = $this->resolveTarget((int) $booking['id'], $bookingItemId);

        return $this->feedback->updateForUser((int) $existing['id'], $userId, [
            'booking_item_id' => $target['booking_item_id'],
            'coach_user_id' => $target['coach_user_id'],
            'rating' => $this->rating($rating),
            'comment' => $this->comment($comment),
        ]);
    }

    public function recentForCoach(int $coachUserId, int $limit = 5): array
    {
        return $coachUserId > 0 ? $this->feedback->forCoach($coachUserId, $limit) : [];
    }

    public function statsForCoach(int $coachUserId): array
    {
        return $coachUserId > 0
            ? $this->feedback->statsForCoach($coachUserId)
            : ['average_rating' => 0.0, 'total_reviews' => 0];
    }

    public function coachSummary(): array
    {
        return $this->feedback->coachSummary();
    }

    public function platformStats(): array
    {
        return $this->feedback->platformStats();
    }

    public function allFeedback(?int $rating = null, string $search = '', int $limit = 100): array
    {
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            $rating = null;
        }

        return $this->feedback->all($rating, $search, $limit);
    }

    private function assertFeedbackBooking(int $bookingId, int $userId): array
    {
        if ($bookingId <= 0 || $userId <= 0) {
            throw new RuntimeException('Booking is required.');
        }

        $booking = $this->feedback->bookingForUser($bookingId, $userId);
        if (!$booking) {
            throw new RuntimeException('Booking not found.');
        }

        if (!$this->feedback->bookingEligibleForFeedback((int) $booking['id'])) {
            throw new RuntimeException('Feedback is not available for this booking.');
        }

        return $booking;
    }

    private function resolveTarget(int $bookingId, ?int $bookingItemId): array
    {
        if ($bookingItemId !== null && $bookingItemId > 0) {
            $target = $this->feedback->targetForBookingItem($bookingId, $bookingItemId);
            if (!$target) {
                throw new RuntimeException('Selected feedback session was not found for this booking.');
            }

            return [
                'booking_item_id' => (int) $target['booking_item_id'],
                'coach_user_id' => $this->isCoachFeedbackTarget($target) ? (int) $target['coach_user_id'] : null,
            ];
        }

        foreach ($this->feedback->targetsForBooking($bookingId) as $target) {
            if ($this->isCoachFeedbackTarget($target)) {
                return [
                    'booking_item_id' => (int) $target['booking_item_id'],
                    'coach_user_id' => (int) $target['coach_user_id'],
                ];
            }
        }

        return [
            'booking_item_id' => null,
            'coach_user_id' => null,
        ];
    }

    private function isCoachFeedbackTarget(array $target): bool
    {
        if (empty($target['coach_user_id'])) {
            return false;
        }
        $label = strtolower((string) ($target['name'] ?? '') . ' ' . (string) ($target['category'] ?? ''));
        if (str_contains($label, 'court rental') || str_contains($label, 'social play') || str_contains($label, 'match-play') || str_contains($label, 'tournament') || str_contains($label, 'private package')) {
            return false;
        }

        return str_contains($label, 'training') || str_contains($label, 'lesson') || str_contains($label, 'private coaching');
    }

    private function rating(int $rating): int
    {
        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('Rating must be between 1 and 5.');
        }

        return $rating;
    }

    private function comment(string $comment): string
    {
        $comment = validateText($comment, true, 1000);
        if ($comment === '') {
            throw new RuntimeException('Feedback comment is required.');
        }

        return $comment;
    }

}

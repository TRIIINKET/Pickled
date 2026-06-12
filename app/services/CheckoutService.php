<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/CartService.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';
require_once __DIR__ . '/../../includes/booking-system.php';

final class CheckoutService
{
    public function __construct(
        private readonly BookingRepository $bookings = new BookingRepository(),
        private readonly CartService $cart = new CartService()
    ) {}

    public function createBooking(int $userId, array $items, string $customerName, string $paymentMethod, string $notes): array
    {
        $cartState = $this->cart->restoreForUser($userId);
        $items = $cartState['items'];
        if (!$items) {
            throw new RuntimeException('Your cart is empty. Add a booking before checkout.');
        }

        $subtotal = array_reduce($items, static fn(float $sum, array $item): float => $sum + ((float) $item['price'] * (int) $item['quantity']), 0.0);
        $paymentFee = CheckoutController::feeFor($paymentMethod, $subtotal);
        $policy = pickled_cancellation_policy($this->firstBookingTimestamp($items));
        $booking = [
            'reference' => $this->generateReference(),
            'items' => $items,
            'customer_name' => $customerName,
            'subtotal' => $subtotal,
            'payment_fee' => $paymentFee,
            'total' => $subtotal + $paymentFee,
            'status' => 'pending',
            'payment_method' => CheckoutController::methodLabel($paymentMethod),
            'payment_status' => 'pending',
            'cancellation_policy' => $policy,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $stored = $this->bookings->create($userId, $booking);
        $this->cart->clearForUser($userId);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['cart'] = [];
            unset($_SESSION['cart_started_at'], $_SESSION['cart_expires_at']);
        }

        return $stored;
    }

    private function generateReference(): string
    {
        do {
            $reference = 'PKL-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->bookings->referenceExists($reference));

        return $reference;
    }

    private function firstBookingTimestamp(array $items): int
    {
        $config = require __DIR__ . '/../../includes/config.php';
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
        $timestamps = [];

        foreach ($items as $item) {
            $date = (string) ($item['booking_date'] ?? $item['date'] ?? '');
            $time = (string) ($item['start_time'] ?? '');
            if ($date && $time) {
                $slot = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time, $timezone)
                    ?: DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $timezone);
                if ($slot instanceof DateTimeImmutable) {
                    $timestamps[] = $slot->getTimestamp();
                }
            }
        }

        return $timestamps ? min($timestamps) : (new DateTimeImmutable('+3 days', $timezone))->getTimestamp();
    }
}

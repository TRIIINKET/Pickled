<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/../repositories/CatalogRepository.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';

final class CheckoutService
{
    public function __construct(
        private readonly BookingRepository $bookings = new BookingRepository(),
        private readonly CatalogRepository $catalog = new CatalogRepository()
    ) {}

    public function createBooking(int $userId, array $items, string $customerName, string $paymentMethod, string $notes): array
    {
        $subtotal = array_reduce($items, static fn(float $sum, array $item): float => $sum + ((float) $item['price'] * (int) $item['quantity']), 0.0);
        $paymentFee = CheckoutController::feeFor($paymentMethod, $subtotal);
        $policy = pickled_cancellation_policy((new DateTimeImmutable('+3 days'))->getTimestamp());
        $booking = [
            'reference' => 'PKL-' . strtoupper(substr(sha1(uniqid('', true)), 0, 8)),
            'items' => $items,
            'customer_name' => $customerName,
            'subtotal' => $subtotal,
            'payment_fee' => $paymentFee,
            'total' => $subtotal + $paymentFee,
            'status' => $paymentMethod === 'cash' ? 'Pending Payment' : 'Confirmed',
            'payment_method' => CheckoutController::methodLabel($paymentMethod),
            'payment_status' => $paymentMethod === 'cash' ? 'pay on site' : 'paid demo checkout',
            'cancellation_policy' => $policy,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->bookings->create($userId, $booking);
    }
}

<?php
declare(strict_types=1);

final class CheckoutController
{
    public const GCASH_METHOD = 'gcash';
    public const GCASH_LABEL = 'GCash';

    public static function defaultPaymentMethod(): string
    {
        return self::GCASH_METHOD;
    }

    public static function paymentMethods(): array
    {
        $config = require __DIR__ . '/../../includes/config.php';
        $methods = $config['payment_methods'] ?? [];

        return [
            self::GCASH_METHOD => [
                'label' => self::GCASH_LABEL,
                'fee_rate' => (float) ($methods[self::GCASH_METHOD]['fee_rate'] ?? 0.00),
            ],
        ];
    }

    public static function isValidMethod(string $method): bool
    {
        return $method === self::GCASH_METHOD;
    }

    public static function assertValidMethod(string $method): void
    {
        if (!self::isValidMethod($method)) {
            throw new RuntimeException('GCash is the only accepted payment method.');
        }
    }

    public static function feeFor(string $method, float $subtotal): float
    {
        self::assertValidMethod($method);
        $methods = self::paymentMethods();
        $rate = (float) $methods[self::GCASH_METHOD]['fee_rate'];
        return round($subtotal * $rate, 2);
    }

    public static function methodLabel(string $method): string
    {
        self::assertValidMethod($method);
        return self::GCASH_LABEL;
    }
}

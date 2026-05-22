<?php
declare(strict_types=1);

final class CheckoutController
{
    public static function paymentMethods(): array
    {
        $config = require __DIR__ . '/../config/app.php';
        return $config['payment_methods'];
    }

    public static function feeFor(string $method, float $subtotal): float
    {
        $methods = self::paymentMethods();
        $rate = (float) ($methods[$method]['fee_rate'] ?? 0);
        return round($subtotal * $rate, 2);
    }

    public static function methodLabel(string $method): string
    {
        $methods = self::paymentMethods();
        return $methods[$method]['label'] ?? 'GCash';
    }
}

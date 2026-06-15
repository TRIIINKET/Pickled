<?php
declare(strict_types=1);

require_once __DIR__ . '/EmailService.php';

final class EmailVerification
{
    public const SESSION_KEY = 'email_verification_otp';
    public const EXPIRES_SECONDS = 600;
    public const MAX_ATTEMPTS = 3;

    public static function issue(array $user): bool
    {
        $otp = (string) random_int(100000, 999999);
        $_SESSION[self::SESSION_KEY] = [
            'user_id' => (int) ($user['id'] ?? $user['user_id'] ?? 0),
            'name' => (string) ($user['name'] ?? 'Member'),
            'email' => strtolower((string) ($user['email'] ?? '')),
            'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
            'expires_at' => time() + self::EXPIRES_SECONDS,
            'attempts' => 0,
        ];

        return (new EmailService())->sendOtp(
            (string) $_SESSION[self::SESSION_KEY]['email'],
            (string) $_SESSION[self::SESSION_KEY]['name'],
            $otp
        );
    }

    public static function pending(): ?array
    {
        $pending = $_SESSION[self::SESSION_KEY] ?? null;
        return is_array($pending) ? $pending : null;
    }

    public static function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function verify(string $otp): array
    {
        $pending = self::pending();
        if (!$pending) {
            return ['ok' => false, 'message' => 'No verification request was found. Please request a new OTP.'];
        }

        if ((int) ($pending['expires_at'] ?? 0) < time()) {
            return ['ok' => false, 'message' => 'Your OTP has expired. Please request a new one.'];
        }

        if ((int) ($pending['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            return ['ok' => false, 'message' => 'Maximum OTP attempts reached. Please resend a new OTP.'];
        }

        $otp = trim($otp);
        if (!preg_match('/^\d{6}$/', $otp) || !password_verify($otp, (string) ($pending['otp_hash'] ?? ''))) {
            $_SESSION[self::SESSION_KEY]['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            $remaining = max(0, self::MAX_ATTEMPTS - (int) $_SESSION[self::SESSION_KEY]['attempts']);
            return ['ok' => false, 'message' => 'Invalid OTP. Attempts remaining: ' . $remaining . '.'];
        }

        return ['ok' => true, 'message' => 'Email verified.'];
    }
}

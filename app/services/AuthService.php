<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/PasswordResetRepository.php';

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly PasswordResetRepository $resets = new PasswordResetRepository()
    ) {}

    public function register(string $name, string $email, string $password, array $profile = []): array
    {
        if ($this->users->findByEmail($email)) {
            throw new RuntimeException('Email is already registered. Please log in.');
        }

        return $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $profile);
    }

    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->users->findByEmail($email);
    }

    public function isVerified(array $user): bool
    {
        return $this->users->isVerified($user);
    }

    public function markVerified(int $userId): bool
    {
        return $this->users->markVerified($userId);
    }

    public function issuePasswordReset(string $email): ?string
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $config = require __DIR__ . '/../../includes/config.php';
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
        $expiresAt = (new DateTimeImmutable('now', $timezone))->modify('+1 hour');
        $this->resets->create((int) $user['id'], (string) $user['email'], $token, $expiresAt);
        return $token;
    }

    public function isPasswordResetTokenValid(string $token): bool
    {
        if ($token === '' || !preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
            return false;
        }

        return (bool) $this->resets->findValid($token);
    }

    public function resetPassword(string $token, string $password): bool
    {
        if ($token === '' || !preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
            return false;
        }

        $reset = $this->resets->findValid($token);
        if (!$reset) {
            return false;
        }

        if (!empty($reset['user_id'])) {
            $this->users->updatePassword((int) $reset['user_id'], password_hash($password, PASSWORD_DEFAULT));
        } elseif (!empty($reset['email'])) {
            $this->users->updatePasswordByEmail((string) $reset['email'], password_hash($password, PASSWORD_DEFAULT));
        } else {
            return false;
        }

        $this->resets->markUsed((int) $reset['id']);
        return true;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->users->findById($userId);
        if (!$user || !password_verify($currentPassword, (string) $user['password_hash'])) {
            return false;
        }

        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
        $this->resets->markUsedForUser($userId, (string) ($user['email'] ?? ''));
        return true;
    }
}

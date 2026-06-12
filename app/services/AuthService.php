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

    public function issuePasswordReset(string $email): ?string
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $config = require __DIR__ . '/../../includes/config.php';
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
        $expiresAt = (new DateTimeImmutable('now', $timezone))->modify('+30 minutes');
        $this->resets->create((int) $user['id'], hash('sha256', $token), $expiresAt);
        return $token;
    }

    public function resetPassword(string $token, string $password): bool
    {
        $reset = $this->resets->findValid(hash('sha256', $token));
        if (!$reset) {
            return false;
        }

        $this->users->updatePassword((int) $reset['user_id'], password_hash($password, PASSWORD_DEFAULT));
        $this->resets->markUsed((int) $reset['id']);
        return true;
    }
}

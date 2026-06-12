<?php
\declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class PasswordResetRepository
{
    public function create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findValid(string $tokenHash): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM password_resets WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $reset = $stmt->fetch();
        return $reset ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

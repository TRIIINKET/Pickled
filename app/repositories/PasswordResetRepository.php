<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../support/DatabaseRedesign.php';

final class PasswordResetRepository
{
    public function create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        if (DatabaseRedesign::active()) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['redesign_password_resets'][$tokenHash] = [
                    'id' => count($_SESSION['redesign_password_resets'] ?? []) + 1,
                    'user_id' => $userId,
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'used_at' => null,
                ];
            }
            return;
        }

        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id')->execute(['user_id' => $userId]);
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
        if (DatabaseRedesign::active()) {
            $reset = session_status() === PHP_SESSION_ACTIVE ? ($_SESSION['redesign_password_resets'][$tokenHash] ?? null) : null;
            if (!$reset || !empty($reset['used_at']) || strtotime((string) $reset['expires_at']) <= time()) {
                return null;
            }

            return $reset;
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM password_resets WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $reset = $stmt->fetch();
        return $reset ?: null;
    }

    public function markUsed(int $id): void
    {
        if (DatabaseRedesign::active()) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                foreach ($_SESSION['redesign_password_resets'] ?? [] as $tokenHash => $reset) {
                    if ((int) ($reset['id'] ?? 0) === $id) {
                        $_SESSION['redesign_password_resets'][$tokenHash]['used_at'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
            }
            return;
        }

        $stmt = Database::connection()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

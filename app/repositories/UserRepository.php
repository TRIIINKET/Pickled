<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, array $profile = []): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
            $stmt->execute([
                'name' => $name,
                'email' => strtolower($email),
                'password_hash' => $passwordHash,
                'role' => 'player',
            ]);
            $userId = (int) $pdo->lastInsertId();
            $this->createProfile($userId, $profile);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $this->findById($userId);
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute(['id' => $id, 'password_hash' => $passwordHash]);
    }

    public function updatePasswordByEmail(string $email, string $passwordHash): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET password_hash = :password_hash WHERE email = :email');
        $stmt->execute([
            'email' => strtolower($email),
            'password_hash' => $passwordHash,
        ]);
    }

    public function findByResetId(int $resetId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*
             FROM users u
             INNER JOIN password_reset pr ON pr.user_id = u.id
             WHERE pr.id = :reset_id
             LIMIT 1'
        );
        $stmt->execute(['reset_id' => $resetId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Admin methods
    public function findAll(): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function findByRole(string $role): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE role = :role ORDER BY created_at DESC');
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll() ?: [];
    }

    public function updateRole(int $id, string $role): bool
    {
        $stmt = Database::connection()->prepare('UPDATE users SET role = :role WHERE id = :id');
        return $stmt->execute(['id' => $id, 'role' => $role]);
    }

    public function update(int $id, string $name, string $email): bool
    {
        $stmt = Database::connection()->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
        return $stmt->execute(['id' => $id, 'name' => $name, 'email' => strtolower($email)]);
    }

    public function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function getTotalCount(): int
    {
        $stmt = Database::connection()->query('SELECT COUNT(*) as count FROM users');
        $result = $stmt->fetch();
        return (int) ($result['count'] ?? 0);
    }

    public function getCountByRole(string $role): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) as count FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);
        $result = $stmt->fetch();
        return (int) ($result['count'] ?? 0);
    }

    public function createProfile(int $userId, array $profile = []): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO user_profiles (user_id, phone, city, province, avatar)
             VALUES (:user_id, :phone, :city, :province, :avatar)
             ON DUPLICATE KEY UPDATE
                user_id = user_id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'phone' => $profile['phone'] ?? '',
            'city' => $profile['city'] ?? '',
            'province' => $profile['province'] ?? '',
            'avatar' => $profile['avatar'] ?? 'avatars/default.png',
        ]);
    }
}

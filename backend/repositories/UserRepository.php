<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(string $name, string $email, string $passwordHash): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
        $stmt->execute([
            'name' => $name,
            'email' => strtolower($email),
            'password_hash' => $passwordHash,
        ]);

        return $this->findById((int) $pdo->lastInsertId());
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
}

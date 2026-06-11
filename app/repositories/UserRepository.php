<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../support/DatabaseRedesign.php';

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::userByEmail($email);
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(string $name, string $email, string $passwordHash): array
    {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::createUser($name, $email, $passwordHash);
        }

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
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::userById($id);
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        if (DatabaseRedesign::active()) {
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['redesign_users'][$id])) {
                $_SESSION['redesign_users'][$id]['password_hash'] = $passwordHash;
            }
            return;
        }

        $stmt = Database::connection()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute(['id' => $id, 'password_hash' => $passwordHash]);
    }

    // Admin methods
    public function findAll(): ?array
    {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::users();
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function findByRole(string $role): ?array
    {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::usersByRole($role);
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE role = :role ORDER BY created_at DESC');
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll() ?: [];
    }

    public function updateRole(int $id, string $role): bool
    {
        if (DatabaseRedesign::active()) {
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['redesign_users'][$id])) {
                $_SESSION['redesign_users'][$id]['role'] = $role;
            }
            return true;
        }

        $stmt = Database::connection()->prepare('UPDATE users SET role = :role WHERE id = :id');
        return $stmt->execute(['id' => $id, 'role' => $role]);
    }

    public function update(int $id, string $name, string $email): bool
    {
        if (DatabaseRedesign::active()) {
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['redesign_users'][$id])) {
                $_SESSION['redesign_users'][$id]['name'] = $name;
                $_SESSION['redesign_users'][$id]['email'] = strtolower($email);
            }
            return true;
        }

        $stmt = Database::connection()->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
        return $stmt->execute(['id' => $id, 'name' => $name, 'email' => strtolower($email)]);
    }

    public function delete(int $id): bool
    {
        if (DatabaseRedesign::active()) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                unset($_SESSION['redesign_users'][$id]);
            }
            return true;
        }

        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function getTotalCount(): int
    {
        if (DatabaseRedesign::active()) {
            return count(DatabaseRedesign::users());
        }

        $stmt = Database::connection()->query('SELECT COUNT(*) as count FROM users');
        $result = $stmt->fetch();
        return (int) ($result['count'] ?? 0);
    }

    public function getCountByRole(string $role): int
    {
        if (DatabaseRedesign::active()) {
            return count(DatabaseRedesign::usersByRole($role));
        }

        $stmt = Database::connection()->prepare('SELECT COUNT(*) as count FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);
        $result = $stmt->fetch();
        return (int) ($result['count'] ?? 0);
    }
}

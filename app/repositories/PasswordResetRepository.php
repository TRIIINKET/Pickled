<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class PasswordResetRepository
{
    private ?string $tableName = null;
    private array $columns = [];

    public function create(int $userId, string $email, string $token, DateTimeImmutable $expiresAt): void
    {
        try {
            $this->markUsedForUser($userId, $email);

            $columns = [];
            $params = [];

            if ($this->hasColumn('user_id')) {
                $columns['user_id'] = ':user_id';
                $params['user_id'] = $userId;
            }

            if ($this->hasColumn('email')) {
                $columns['email'] = ':email';
                $params['email'] = strtolower($email);
            }

            $tokenColumn = $this->tokenColumn();
            $columns[$tokenColumn] = ':token';
            $params['token'] = $this->storedTokenValue($token);

            $columns['expires_at'] = ':expires_at';
            $params['expires_at'] = $expiresAt->format('Y-m-d H:i:s');

            if ($this->hasColumn('used')) {
                $columns['used'] = ':used';
                $params['used'] = 0;
            }

            if ($this->hasColumn('status')) {
                $columns['status'] = ':status';
                $params['status'] = 'active';
            }

            $sql = 'INSERT INTO ' . $this->table() . ' (' . implode(', ', array_keys($columns)) . ')
                    VALUES (' . implode(', ', array_values($columns)) . ')';
            $stmt = Database::connection()->prepare($sql);
            $stmt->execute($params);
        } catch (Throwable $e) {
            error_log('Password reset create failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function findValid(string $token): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT *
                 FROM ' . $this->table() . '
                 WHERE ' . $this->tokenColumn() . ' = :token
                   AND expires_at > NOW()
                   AND ' . $this->unusedCondition() . '
                 LIMIT 1'
            );
            $stmt->execute(['token' => $this->storedTokenValue($token)]);
            $reset = $stmt->fetch();
            return $reset ?: null;
        } catch (Throwable $e) {
            error_log('Password reset lookup failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function markUsed(int $id): void
    {
        try {
            $sets = $this->usedSetClauses();
            if (!$sets) {
                return;
            }

            $stmt = Database::connection()->prepare(
                'UPDATE ' . $this->table() . '
                 SET ' . implode(', ', $sets) . '
                 WHERE id = :id'
            );
            $stmt->execute(['id' => $id]);
        } catch (Throwable $e) {
            error_log('Password reset mark used failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function markUsedForUser(int $userId, string $email = ''): void
    {
        try {
            $sets = $this->usedSetClauses();
            if (!$sets) {
                return;
            }

            $conditions = [];
            $params = [];

            if ($this->hasColumn('user_id')) {
                $conditions[] = 'user_id = :user_id';
                $params['user_id'] = $userId;
            }

            if ($email !== '' && $this->hasColumn('email')) {
                $conditions[] = 'LOWER(email) = :email';
                $params['email'] = strtolower($email);
            }

            if (!$conditions) {
                return;
            }

            $stmt = Database::connection()->prepare(
                'UPDATE ' . $this->table() . '
                 SET ' . implode(', ', $sets) . '
                 WHERE (' . implode(' OR ', $conditions) . ')
                   AND ' . $this->unusedCondition()
            );
            $stmt->execute($params);
        } catch (Throwable $e) {
            error_log('Password reset invalidate failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function table(): string
    {
        if ($this->tableName !== null) {
            return $this->tableName;
        }

        $this->ensurePasswordResetTable();
        $this->tableName = 'password_reset';
        return $this->tableName;
    }

    private function ensurePasswordResetTable(): void
    {
        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS password_reset (
              id INT NOT NULL AUTO_INCREMENT,
              user_id INT NULL,
              email VARCHAR(160) NOT NULL,
              token VARCHAR(128) NOT NULL,
              expires_at DATETIME NOT NULL,
              used TINYINT(1) NOT NULL DEFAULT 0,
              used_at DATETIME NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_password_reset_token (token),
              KEY idx_password_reset_email (email),
              KEY idx_password_reset_user_id (user_id),
              KEY idx_password_reset_expires_at (expires_at),
              KEY idx_password_reset_used (used)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function hasColumn(string $column): bool
    {
        $table = $this->table();
        if (!isset($this->columns[$table])) {
            $stmt = Database::connection()->prepare(
                'SELECT column_name
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = :table_name'
            );
            $stmt->execute(['table_name' => $table]);
            $this->columns[$table] = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [], true);
        }

        return isset($this->columns[$table][$column]);
    }

    private function tokenColumn(): string
    {
        if ($this->hasColumn('token')) {
            return 'token';
        }

        if ($this->hasColumn('token_hash')) {
            return 'token_hash';
        }

        throw new RuntimeException('Password reset token column is missing.');
    }

    private function storedTokenValue(string $token): string
    {
        return $this->tokenColumn() === 'token_hash' ? hash('sha256', $token) : $token;
    }

    private function unusedCondition(): string
    {
        $conditions = [];

        if ($this->hasColumn('used_at')) {
            $conditions[] = 'used_at IS NULL';
        }

        if ($this->hasColumn('used')) {
            $conditions[] = '(used = 0 OR used IS NULL)';
        }

        if ($this->hasColumn('status')) {
            $conditions[] = "LOWER(status) NOT IN ('used', 'expired')";
        }

        return $conditions ? implode(' AND ', $conditions) : '1 = 1';
    }

    private function usedSetClauses(): array
    {
        $sets = [];

        if ($this->hasColumn('used_at')) {
            $sets[] = 'used_at = NOW()';
        }

        if ($this->hasColumn('used')) {
            $sets[] = 'used = 1';
        }

        if ($this->hasColumn('status')) {
            $sets[] = "status = 'used'";
        }

        return $sets;
    }
}

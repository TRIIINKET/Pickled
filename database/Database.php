<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;
    private static bool $connectionLogged = false;

    public static function enabled(): bool
    {
        $config = require __DIR__ . '/../includes/config.php';
        return !empty($config['database']['enabled']);
    }

    public static function connection(): PDO
    {
        if (!self::enabled()) {
            throw new RuntimeException('Database connections are disabled.');
        }

        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = require __DIR__ . '/../includes/database.php';
        self::$pdo = new PDO(
            $config['dsn'],
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        if (!self::$connectionLogged) {
            self::$connectionLogged = true;
            try {
                $database = (string) self::$pdo->query('SELECT DATABASE()')->fetchColumn();
                error_log(
                    'PICKLED DB connection active. config=' . __DIR__ . '/../includes/database.php'
                    . '; dsn=' . (string) $config['dsn']
                    . '; username=' . (string) $config['username']
                    . '; selected_database=' . $database
                );
            } catch (Throwable $e) {
                error_log('PICKLED DB connection diagnostic failed: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }
}

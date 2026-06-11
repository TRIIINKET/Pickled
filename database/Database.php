<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/support/DatabaseRedesign.php';

final class Database
{
    private static ?PDO $pdo = null;

    public static function enabled(): bool
    {
        return !DatabaseRedesign::active();
    }

    public static function redesignMode(): bool
    {
        return DatabaseRedesign::active();
    }

    public static function connection(): PDO
    {
        if (!self::enabled()) {
            throw new RuntimeException('Database connections are disabled while the schema redesign is in progress.');
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

        return self::$pdo;
    }
}

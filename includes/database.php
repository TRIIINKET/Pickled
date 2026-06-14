<?php
declare(strict_types=1);

return [
    'dsn' => getenv('PICKLED_DB_DSN')
        ?: 'mysql:host=127.0.0.1;dbname=pickled;charset=utf8mb4',

    'username' => getenv('PICKLED_DB_USER')
        ?: 'root',

    'password' => getenv('PICKLED_DB_PASS')
        ?: '',
];
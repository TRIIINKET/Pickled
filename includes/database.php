<?php
declare(strict_types=1);

return [
    'dsn' => getenv('PICKLED_DB_DSN') 
        ?: 'mysql:host=sql308.byetcluster.com;dbname=if0_42175212_pickled;charset=utf8mb4',

    'username' => getenv('PICKLED_DB_USER') 
        ?: 'if0_42175212',

    'password' => getenv('PICKLED_DB_PASS') 
        ?: 'J6OcrzEwXNMPryl',
];
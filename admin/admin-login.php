<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/paths.php';

header('Location: ' . pickled_frontend_url('auth/login.php?role=admin'));
exit;

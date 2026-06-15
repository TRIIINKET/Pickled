<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/AvailabilityService.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$variant = trim((string) ($_GET['variant'] ?? ''));
$year = max(2020, (int) ($_GET['year'] ?? date('Y')));
$month = min(12, max(1, (int) ($_GET['month'] ?? date('n'))));

echo json_encode((new AvailabilityService())->month($variant, $year, $month));

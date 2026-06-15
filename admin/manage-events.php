<?php
$programSlug = $_GET['program'] ?? '';
$isSocialPlay = $programSlug === 'social-play';
$courtSlug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_GET['court'] ?? 'green'))) ?: 'green';
$pageTitle = $isSocialPlay ? 'Social Play' : ($courtSlug === 'pink' ? 'Court Pink' : 'Court Green');
$activePage = $isSocialPlay ? 'events' : 'courts';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/services/CatalogService.php';
require_once __DIR__ . '/../app/services/SchedulingService.php';

pickled_init_csrf();

$catalogService = new CatalogService();
$schedulingService = new SchedulingService();
$adminId = (int) ($_SESSION['user']['id'] ?? 0);
$successMsg = '';
$errorMsg = '';
$pdo = null;

try {
    $pdo = Database::connection();
} catch (Throwable $e) {
    error_log('Court catalog database connection failed: ' . $e->getMessage());
}

function court_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare('
            SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
            LIMIT 1
        ');
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Court schema check failed: ' . $e->getMessage());
        return false;
    }
}

function court_ensure_customization_schema(?PDO $pdo): void {
    if (!$pdo) {
        return;
    }

    $courtColumns = [
        'description' => 'ADD COLUMN description TEXT NULL AFTER status',
        'base_price' => 'ADD COLUMN base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER description',
        'capacity' => 'ADD COLUMN capacity INT UNSIGNED NOT NULL DEFAULT 1 AFTER base_price',
        'operating_hours' => 'ADD COLUMN operating_hours VARCHAR(100) NULL AFTER capacity',
        'court_type' => 'ADD COLUMN court_type VARCHAR(100) NULL AFTER operating_hours',
    ];
    foreach ($courtColumns as $column => $definition) {
        if (!court_column_exists($pdo, 'courts', $column)) {
            $pdo->exec("ALTER TABLE courts $definition");
        }
    }

    if (!court_column_exists($pdo, 'booking_variants', 'sort_order')) {
        $pdo->exec('ALTER TABLE booking_variants ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER active');
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS court_media (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            court_id INT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            image_type VARCHAR(50) NOT NULL DEFAULT 'gallery',
            is_hero TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_court_media_court (court_id, status, sort_order),
            CONSTRAINT fk_court_media_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function court_normalize_image_path(string $path): string {
    $path = str_replace('\\', '/', trim($path));
    $path = preg_replace('#^(\.\./)?assets/#', '', $path) ?? $path;
    return ltrim($path, '/');
}

function court_upload_image(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a valid image file.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $mime = function_exists('mime_content_type') ? (string) mime_content_type($tmp) : '';
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WEBP, and GIF photos are allowed.');
    }

    $dir = __DIR__ . '/../assets/img/court/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create the court upload folder.');
    }

    $filename = 'court-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    $destination = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Unable to save uploaded court photo.');
    }

    return 'img/court/uploads/' . $filename;
}

function court_media_rows(?PDO $pdo, int $courtId, bool $includeInactive = false): array {
    if (!$pdo || $courtId <= 0) {
        return [];
    }

    try {
        $sql = 'SELECT * FROM court_media WHERE court_id = :court_id';
        if (!$includeInactive) {
            $sql .= " AND status = 'active'";
        }
        $sql .= ' ORDER BY is_hero DESC, sort_order ASC, id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['court_id' => $courtId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Court media load failed: ' . $e->getMessage());
        return [];
    }
}

function court_seed_default_media(?PDO $pdo, int $courtId, array $paths): void {
    if (!$pdo || $courtId <= 0 || court_media_rows($pdo, $courtId, true)) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO court_media (court_id, image_path, image_type, is_hero, sort_order, status) VALUES (:court_id, :image_path, :image_type, :is_hero, :sort_order, "active")');
    foreach ($paths as $index => $path) {
        $stmt->execute([
            'court_id' => $courtId,
            'image_path' => court_normalize_image_path((string) $path),
            'image_type' => $index === 0 ? 'hero' : 'gallery',
            'is_hero' => $index === 0 ? 1 : 0,
            'sort_order' => $index,
        ]);
    }
}

function court_set_hero_media(?PDO $pdo, int $courtId, int $mediaId): void {
    if (!$pdo || $courtId <= 0 || $mediaId <= 0) {
        throw new RuntimeException('Photo is required.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE court_media SET is_hero = 0, image_type = IF(image_type = "hero", "gallery", image_type) WHERE court_id = :court_id')->execute(['court_id' => $courtId]);
        $pdo->prepare('UPDATE court_media SET is_hero = 1, image_type = "hero", status = "active" WHERE id = :id AND court_id = :court_id')->execute(['id' => $mediaId, 'court_id' => $courtId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function court_update_media_order(?PDO $pdo, int $courtId, array $orders): void {
    if (!$pdo || $courtId <= 0) {
        return;
    }

    $stmt = $pdo->prepare('UPDATE court_media SET sort_order = :sort_order WHERE id = :id AND court_id = :court_id');
    foreach ($orders as $id => $sortOrder) {
        $stmt->execute(['id' => (int) $id, 'court_id' => $courtId, 'sort_order' => max(0, (int) $sortOrder)]);
    }
}

function court_soft_delete_media(?PDO $pdo, int $courtId, int $mediaId): void {
    if (!$pdo || $courtId <= 0 || $mediaId <= 0) {
        return;
    }

    $pdo->prepare('UPDATE court_media SET status = "deleted", is_hero = 0 WHERE id = :id AND court_id = :court_id')->execute(['id' => $mediaId, 'court_id' => $courtId]);
}

court_ensure_customization_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Invalid form submission. Please try again.';
    } else {
        try {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'create_court') {
                $catalogService->createCourt($_POST, $adminId);
                $courtSlug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['slug'] ?? $_POST['name'] ?? $courtSlug))) ?: $courtSlug;
                $successMsg = 'Court added successfully.';
            } elseif ($action === 'update_court' || $action === 'update_court_details') {
                $catalogService->updateCourt((int) ($_POST['court_id'] ?? 0), $_POST, $adminId);
                $courtSlug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['slug'] ?? $courtSlug))) ?: $courtSlug;
                $successMsg = 'Court updated successfully.';
            } elseif ($action === 'set_court_status') {
                $catalogService->setCourtStatus((int) ($_POST['court_id'] ?? 0), (string) ($_POST['status'] ?? 'inactive'), $adminId);
                $successMsg = 'Court status updated successfully.';
            } elseif ($action === 'create_variant') {
                $catalogService->createVariant($_POST, $adminId);
                $successMsg = 'Booking variant added successfully.';
            } elseif ($action === 'update_variant') {
                $catalogService->updateVariant((int) ($_POST['variant_id'] ?? 0), $_POST, $adminId);
                $successMsg = 'Booking variant updated successfully.';
            } elseif ($action === 'set_variant_active') {
                $catalogService->setVariantActive((int) ($_POST['variant_id'] ?? 0), (string) ($_POST['active'] ?? '0') === '1', $adminId);
                $successMsg = 'Booking variant status updated successfully.';
            } elseif ($action === 'create_session') {
                $schedulingService->createSession($_POST, $adminId);
                $successMsg = 'Session created successfully.';
            } elseif ($action === 'update_session') {
                $schedulingService->updateSession((int) ($_POST['session_id'] ?? 0), $_POST, $adminId);
                $successMsg = 'Session updated successfully.';
            } elseif ($action === 'set_session_status') {
                $schedulingService->setSessionStatus((int) ($_POST['session_id'] ?? 0), (string) ($_POST['status'] ?? 'cancelled'), $adminId);
                $successMsg = 'Session status updated successfully.';
            } elseif (!$isSocialPlay && $action === 'upload_court_photo') {
                $courtId = (int) ($_POST['court_id'] ?? 0);
                $path = court_upload_image($_FILES['court_photo'] ?? []);
                $isHero = !empty($_POST['is_hero']);
                if ($isHero) {
                    $pdo?->prepare('UPDATE court_media SET is_hero = 0, image_type = IF(image_type = "hero", "gallery", image_type) WHERE court_id = :court_id')->execute(['court_id' => $courtId]);
                }
                $stmt = $pdo?->prepare('INSERT INTO court_media (court_id, image_path, image_type, is_hero, sort_order, status) VALUES (:court_id, :image_path, :image_type, :is_hero, :sort_order, "active")');
                $stmt?->execute([
                    'court_id' => $courtId,
                    'image_path' => $path,
                    'image_type' => $isHero ? 'hero' : 'gallery',
                    'is_hero' => $isHero ? 1 : 0,
                    'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                ]);
                $successMsg = 'Court photo uploaded successfully.';
            } elseif (!$isSocialPlay && $action === 'replace_court_media') {
                $courtId = (int) ($_POST['court_id'] ?? 0);
                $mediaId = (int) ($_POST['media_id'] ?? 0);
                $path = court_upload_image($_FILES['replacement_photo'] ?? []);
                $pdo?->prepare('UPDATE court_media SET image_path = :image_path, status = "active" WHERE id = :id AND court_id = :court_id')->execute([
                    'image_path' => $path,
                    'id' => $mediaId,
                    'court_id' => $courtId,
                ]);
                $successMsg = 'Court photo replaced successfully.';
            } elseif (!$isSocialPlay && $action === 'set_hero_media') {
                court_set_hero_media($pdo, (int) ($_POST['court_id'] ?? 0), (int) ($_POST['media_id'] ?? 0));
                $successMsg = 'Hero image updated successfully.';
            } elseif (!$isSocialPlay && $action === 'delete_court_media') {
                court_soft_delete_media($pdo, (int) ($_POST['court_id'] ?? 0), (int) ($_POST['media_id'] ?? 0));
                $successMsg = 'Court photo removed from the active gallery.';
            } elseif (!$isSocialPlay && $action === 'update_media_order') {
                court_update_media_order($pdo, (int) ($_POST['court_id'] ?? 0), $_POST['media_order'] ?? []);
                $successMsg = 'Photo order saved successfully.';
            }
        } catch (Throwable $e) {
            error_log('Court catalog action failed: ' . $e->getMessage());
            $errorMsg = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to save catalog changes.';
        }
    }
}

$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');

function court_rows(?PDO $pdo, string $sql, array $params = []): array {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Court page query failed: ' . $e->getMessage());
        return [];
    }
}

function court_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url(court_normalize_image_path($path)), ENT_QUOTES, 'UTF-8');
}

function court_public_url(string $path): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/manage-events.php');
    $position = strpos($script, '/admin/');
    $base = $position === false ? rtrim(dirname($script), '/') . '/' : substr($script, 0, $position + 1);
    return htmlspecialchars($base . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
}

function court_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['courts']) . '</svg>';
}

function service_icon_name(string $name): string {
    $lower = strtolower($name);
    if (str_contains($lower, 'lesson') || str_contains($lower, 'class')) return 'users';
    if (str_contains($lower, 'training') || str_contains($lower, 'tournament')) return 'target';
    if (str_contains($lower, 'private') || str_contains($lower, 'coaching')) return 'user';
    return 'courts';
}

function court_h(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function court_csrf_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . court_h(pickled_csrf_token()) . '">';
}

function court_stats(?PDO $pdo, int $courtId): array {
    $stats = [
        'bookings_month' => 0,
        'revenue_month' => 0.0,
        'upcoming_sessions' => 0,
        'most_booked_service' => 'No bookings yet',
    ];
    if (!$pdo || $courtId <= 0) {
        return $stats;
    }

    $monthStart = (new DateTimeImmutable('first day of this month 00:00:00', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
    $nextMonth = (new DateTimeImmutable('first day of next month 00:00:00', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT b.id) AS total_bookings,
                   COALESCE(SUM(bi.quantity * bi.unit_price), 0) AS revenue
            FROM bookings b
            JOIN booking_items bi ON bi.booking_id = b.id
            JOIN sessions s ON s.id = bi.session_id
            JOIN booking_variants v ON v.id = s.variant_id
            LEFT JOIN payments p ON p.booking_id = b.id
            WHERE v.court_id = :court_id
              AND b.created_at >= :start_date
              AND b.created_at < :end_date
              AND b.status NOT IN ('cancelled', 'rejected')
        ");
        $stmt->execute(['court_id' => $courtId, 'start_date' => $monthStart, 'end_date' => $nextMonth]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats['bookings_month'] = (int) ($row['total_bookings'] ?? 0);
        $stats['revenue_month'] = (float) ($row['revenue'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS upcoming
            FROM sessions s
            JOIN booking_variants v ON v.id = s.variant_id
            WHERE v.court_id = :court_id
              AND CONCAT(s.session_date, ' ', s.start_time) >= NOW()
              AND s.status IN ('open', 'full')
        ");
        $stmt->execute(['court_id' => $courtId]);
        $stats['upcoming_sessions'] = (int) ($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare("
            SELECT bi.name, COALESCE(SUM(bi.quantity), 0) AS slots
            FROM booking_items bi
            JOIN bookings b ON b.id = bi.booking_id
            JOIN sessions s ON s.id = bi.session_id
            JOIN booking_variants v ON v.id = s.variant_id
            WHERE v.court_id = :court_id
              AND b.status NOT IN ('cancelled', 'rejected')
            GROUP BY bi.name
            ORDER BY slots DESC, bi.name ASC
            LIMIT 1
        ");
        $stmt->execute(['court_id' => $courtId]);
        $stats['most_booked_service'] = (string) ($stmt->fetchColumn() ?: 'No bookings yet');
    } catch (Throwable $e) {
        error_log('Court stats query failed: ' . $e->getMessage());
    }

    return $stats;
}

$allCourts = [];
$allVariants = [];
$court = ['id' => 0, 'name' => $pageTitle, 'slug' => $courtSlug, 'status' => 'inactive'];
$services = [];
$socialServices = [];
$allSessions = [];
$coaches = [];

try {
    $allCourts = $catalogService->courts(true);
    $selectedCourt = $catalogService->courtBySlug($courtSlug, true);
    if (!$selectedCourt && $allCourts) {
        $courtSlug = (string) $allCourts[0]['slug'];
        $selectedCourt = $catalogService->courtBySlug($courtSlug, true);
    }
    if ($selectedCourt) {
        $court = $selectedCourt;
    }
    $services = $catalogService->variantsForCourtSlug($courtSlug, true);
    $socialServices = $catalogService->socialVariants(true);
    $allVariants = $catalogService->variants(true);
    $allSessions = $schedulingService->allSessions(true);
    $coaches = $schedulingService->coaches(false);
} catch (Throwable $e) {
    error_log('Court catalog load failed: ' . $e->getMessage());
    $errorMsg = $errorMsg ?: 'Catalog data is unavailable. Please apply the Court & Service Catalog schema.';
}

$pageTitle = $isSocialPlay ? 'Social Play' : (string) ($court['name'] ?? $pageTitle);
$socialSessions = court_rows($pdo, "
    SELECT s.*,
           DATE_FORMAT(s.session_date, '%W, %M %e, %Y') AS session_date,
           CONCAT(TIME_FORMAT(s.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(s.end_time, '%h:%i %p')) AS session_time,
           bv.name,
           bv.price,
           bv.duration_label,
           bv.capacity AS variant_capacity,
           c.name AS court_name
    FROM sessions s
    JOIN booking_variants bv ON bv.id = s.variant_id
    JOIN courts c ON c.id = bv.court_id
    WHERE bv.category = 'Social Play'
       OR bv.name LIKE '%Match%'
       OR bv.name LIKE '%Tournament%'
    ORDER BY s.session_date DESC, s.start_time DESC
    LIMIT 4
");
$socialParticipants = array_sum(array_map(fn($row) => (int) ($row['booked_count'] ?? 0), $socialSessions));
$socialRevenue = array_sum(array_map(fn($row) => (float) ($row['price'] ?? 0) * (int) ($row['booked_count'] ?? 0), $socialSessions));

$defaultHeroImage = $courtSlug === 'pink' ? 'img/court/court pink-1.webp' : 'img/court/court green-1.png';
$defaultGallery = $courtSlug === 'pink'
    ? ['img/court/court pink-1.webp', 'img/court/court pink-2.png', 'img/court/court pink-3.png', 'img/court/academy.png']
    : ['img/court/court green-1.png', 'img/court/court green-2.png', 'img/court/court green-3.png', 'img/court/social play-1.png'];
$socialGallery = ['img/court/social play-1.png', 'img/court/social play-2.png', 'img/court/social play-3.png', 'img/court/court green-1.png', 'img/court/court pink-1.webp'];

$activeServices = array_values(array_filter($services, static fn(array $service): bool => !empty($service['active'])));
court_seed_default_media($pdo, (int) ($court['id'] ?? 0), $defaultGallery);
$mediaRows = court_media_rows($pdo, (int) ($court['id'] ?? 0), false);
$heroRow = null;
foreach ($mediaRows as $mediaRow) {
    if (!empty($mediaRow['is_hero'])) {
        $heroRow = $mediaRow;
        break;
    }
}
$heroImage = court_normalize_image_path((string) ($heroRow['image_path'] ?? $defaultHeroImage));
$gallery = array_values(array_map(static fn(array $media): string => court_normalize_image_path((string) $media['image_path']), $mediaRows));
if (!$gallery) {
    $gallery = $defaultGallery;
}

$fallbackBasePrice = $activeServices ? min(array_map(fn($service) => (float) $service['price'], $activeServices)) : ($courtSlug === 'pink' ? 400 : 600);
$fallbackCapacity = $activeServices ? max(array_map(fn($service) => (int) $service['capacity'], $activeServices)) : 24;
$basePrice = (float) ($court['base_price'] ?? 0) > 0 ? (float) $court['base_price'] : $fallbackBasePrice;
$capacity = (int) ($court['capacity'] ?? 0) > 0 ? (int) $court['capacity'] : $fallbackCapacity;
$subtitle = trim((string) ($court['description'] ?? '')) ?: ($courtSlug === 'pink' ? 'Youth-friendly indoor court' : 'Main standard indoor court');
$operatingHours = trim((string) ($court['operating_hours'] ?? '')) ?: '8AM - 10PM';
$courtType = trim((string) ($court['court_type'] ?? '')) ?: 'Indoor';
$courtStats = court_stats($pdo, (int) ($court['id'] ?? 0));
$courtVariantIds = array_map(static fn(array $service): int => (int) ($service['id'] ?? 0), $services);
$courtSessions = array_values(array_filter($allSessions, static fn(array $session): bool => in_array((int) ($session['variant_id'] ?? 0), $courtVariantIds, true)));
$sessionsByVariant = [];
$upcomingSessionsByVariant = [];
$todayDate = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
foreach ($courtSessions as $courtSession) {
    $variantId = (int) ($courtSession['variant_id'] ?? 0);
    $sessionsByVariant[$variantId][] = $courtSession;
    if (($courtSession['session_date'] ?? '') >= $todayDate && in_array((string) ($courtSession['status'] ?? ''), ['open', 'full'], true)) {
        $upcomingSessionsByVariant[$variantId] = ($upcomingSessionsByVariant[$variantId] ?? 0) + 1;
    }
}
$accent = $courtSlug === 'pink' ? 'pink' : 'green';

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
    'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="M22 2 12 12"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'chart' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
    'courts' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    'image' => '<rect x="3" y="5" width="18" height="16" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 21"/>',
    'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1.5"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.38.22.74.57 1 .95.26.38.4.8.4 1.2V12a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.31-.6Z"/>',
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'edit' => '<path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
];

$courtNavChildren = array_map(
    static fn(array $item): array => [(string) $item['name'], 'manage-events.php?court=' . rawurlencode((string) $item['slug']), (string) $item['slug']],
    $allCourts
);

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php', ''], ['Calendar View', 'manage-bookings.php?view=calendar', '']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player', ''], ['Coaches', 'manage-users.php?role=coach', '']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php?court=green', 'key' => 'courts', 'icon' => 'courts', 'children' => $courtNavChildren],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php?program=social-play', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play', 'social-play'], ['Private Sessions', 'private-sessions.php', 'private']]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo court_asset('img/WM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo court_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref, $childKey]): ?><a class="<?php echo $childKey && (($activePage === 'courts' && $courtSlug === $childKey) || ($activePage === 'events' && $programSlug === $childKey)) ? 'active-child' : ''; ?>" href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo court_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main court-manager-main">
        <header class="admin-topbar">
            <div><h1><?php echo htmlspecialchars($pageTitle); ?> <span class="court-title-badge"><?php echo $isSocialPlay ? 'Active' : court_h(ucfirst((string) ($court['status'] ?? 'inactive'))); ?></span></h1><p class="court-breadcrumb"><?php echo $isSocialPlay ? 'Programs' : 'Courts'; ?> <?php echo court_icon($icons, 'arrow'); ?> <?php echo htmlspecialchars($pageTitle); ?></p><?php if ($isSocialPlay): ?><p class="program-subtitle">Community-driven pickleball sessions</p><?php endif; ?></div>
            <div class="admin-topbar-actions"><button class="admin-date-pill" type="button"><?php echo court_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button><a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>"><?php echo court_icon($icons, 'bell'); ?>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo court_h($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo court_h($errorMsg); ?></div><?php endif; ?>

        <?php if ($isSocialPlay): ?>
        <section class="catalog-admin-panel" aria-label="Court and service catalog management">
            <details id="catalog-add-court">
                <summary><?php echo court_icon($icons, 'plus'); ?> Add Court</summary>
                <form class="catalog-admin-form" method="post">
                    <?php echo court_csrf_input(); ?>
                    <label><span>Name</span><input type="text" name="name" placeholder="Court Blue" required></label>
                    <label><span>Slug</span><input type="text" name="slug" placeholder="blue" required></label>
                    <label><span>Status</span><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="maintenance">Maintenance</option></select></label>
                    <button class="bookings-button primary" type="submit" name="action" value="create_court">Add Court</button>
                </form>
            </details>

            <details id="catalog-add-variant">
                <summary><?php echo court_icon($icons, 'plus'); ?> Add Booking Variant</summary>
                <form class="catalog-admin-form catalog-admin-form-wide" method="post">
                    <?php echo court_csrf_input(); ?>
                    <label><span>Court</span><select name="court_id" required><?php foreach ($allCourts as $selectCourt): ?><option value="<?php echo (int) $selectCourt['id']; ?>"><?php echo court_h($selectCourt['name']); ?></option><?php endforeach; ?></select></label>
                    <label><span>Name</span><input type="text" name="name" placeholder="Court Rentals" required></label>
                    <label><span>Slug</span><input type="text" name="slug" placeholder="blue-court-rentals" required></label>
                    <label><span>Category</span><input type="text" name="category" placeholder="Court Rental" required></label>
                    <label><span>Duration</span><input type="text" name="duration_label" placeholder="1 hour" required></label>
                    <label><span>Price</span><input type="number" name="price" step="0.01" min="0" value="0.00" required></label>
                    <label><span>Limit</span><input type="number" name="participants_limit" min="1" value="1" required></label>
                    <label><span>Capacity</span><input type="number" name="capacity" min="1" value="8" required></label>
                    <label><span>Sort</span><input type="number" name="sort_order" min="0" value="0"></label>
                    <label><span>Image</span><input type="text" name="image" placeholder="assets/img/court/example.png"></label>
                    <label class="catalog-check"><input type="checkbox" name="active" value="1" checked> Active</label>
                    <button class="bookings-button primary" type="submit" name="action" value="create_variant">Add Variant</button>
                </form>
            </details>

            <details id="catalog-add-session">
                <summary><?php echo court_icon($icons, 'calendar'); ?> Add Session</summary>
                <form class="catalog-admin-form catalog-admin-form-wide" method="post">
                    <?php echo court_csrf_input(); ?>
                    <label><span>Variant</span><select name="variant_id" required><?php foreach ($allVariants as $variant): ?><option value="<?php echo (int) $variant['id']; ?>"><?php echo court_h($variant['court'] . ' - ' . $variant['name']); ?></option><?php endforeach; ?></select></label>
                    <label><span>Coach</span><select name="coach_user_id"><option value="">Unassigned</option><?php foreach ($coaches as $coach): ?><option value="<?php echo (int) $coach['id']; ?>"><?php echo court_h($coach['name']); ?></option><?php endforeach; ?></select></label>
                    <label><span>Date</span><input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required></label>
                    <label><span>Start</span><input type="time" name="start_time" value="09:00" required></label>
                    <label><span>End</span><input type="time" name="end_time" value="10:00" required></label>
                    <label><span>Capacity</span><input type="number" name="capacity" min="1" value="8" required></label>
                    <label><span>Booked</span><input type="number" name="booked_count" min="0" value="0" required></label>
                    <label><span>Status</span><select name="status"><option value="open">Open</option><option value="full">Full</option><option value="cancelled">Cancelled</option><option value="completed">Completed</option></select></label>
                    <button class="bookings-button primary" type="submit" name="action" value="create_session">Add Session</button>
                </form>
            </details>

            <details>
                <summary><?php echo court_icon($icons, 'edit'); ?> Edit Courts</summary>
                <div class="catalog-admin-list">
                    <?php foreach ($allCourts as $catalogCourt): $status = (string) ($catalogCourt['status'] ?? 'active'); ?>
                        <article class="catalog-admin-row">
                            <form class="catalog-admin-form catalog-inline-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="court_id" value="<?php echo (int) $catalogCourt['id']; ?>">
                                <label><span>Name</span><input type="text" name="name" value="<?php echo court_h($catalogCourt['name']); ?>" required></label>
                                <label><span>Slug</span><input type="text" name="slug" value="<?php echo court_h($catalogCourt['slug']); ?>" required></label>
                                <label><span>Status</span><select name="status"><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option><option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option></select></label>
                                <button type="submit" name="action" value="update_court">Save</button>
                            </form>
                            <form class="catalog-status-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="court_id" value="<?php echo (int) $catalogCourt['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $status === 'active' ? 'inactive' : 'active'; ?>">
                                <button class="<?php echo $status === 'active' ? 'danger' : ''; ?>" type="submit" name="action" value="set_court_status"><?php echo $status === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$allCourts): ?><p class="catalog-empty-state">No courts yet. Add the first court above.</p><?php endif; ?>
                </div>
            </details>

            <details>
                <summary><?php echo court_icon($icons, 'edit'); ?> Edit Booking Variants</summary>
                <div class="catalog-admin-list">
                    <?php foreach ($allVariants as $variant): $variantActive = !empty($variant['active']); ?>
                        <article class="catalog-admin-row">
                            <form class="catalog-admin-form catalog-inline-form catalog-variant-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="variant_id" value="<?php echo (int) $variant['id']; ?>">
                                <label><span>Court</span><select name="court_id" required><?php foreach ($allCourts as $selectCourt): ?><option value="<?php echo (int) $selectCourt['id']; ?>" <?php echo (int) $variant['court_id'] === (int) $selectCourt['id'] ? 'selected' : ''; ?>><?php echo court_h($selectCourt['name']); ?></option><?php endforeach; ?></select></label>
                                <label><span>Name</span><input type="text" name="name" value="<?php echo court_h($variant['name']); ?>" required></label>
                                <label><span>Slug</span><input type="text" name="slug" value="<?php echo court_h($variant['slug']); ?>" required></label>
                                <label><span>Category</span><input type="text" name="category" value="<?php echo court_h($variant['category']); ?>" required></label>
                                <label><span>Duration</span><input type="text" name="duration_label" value="<?php echo court_h($variant['duration_label']); ?>" required></label>
                                <label><span>Price</span><input type="number" name="price" step="0.01" min="0" value="<?php echo court_h($variant['price']); ?>" required></label>
                                <label><span>Limit</span><input type="number" name="participants_limit" min="1" value="<?php echo (int) $variant['participants_limit']; ?>" required></label>
                                <label><span>Capacity</span><input type="number" name="capacity" min="1" value="<?php echo (int) $variant['capacity']; ?>" required></label>
                                <label><span>Sort</span><input type="number" name="sort_order" min="0" value="<?php echo (int) ($variant['sort_order'] ?? 0); ?>"></label>
                                <label><span>Image</span><input type="text" name="image" value="<?php echo court_h($variant['image'] ?? ''); ?>"></label>
                                <label class="catalog-check"><input type="checkbox" name="active" value="1" <?php echo $variantActive ? 'checked' : ''; ?>> Active</label>
                                <button type="submit" name="action" value="update_variant">Save</button>
                            </form>
                            <form class="catalog-status-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="variant_id" value="<?php echo (int) $variant['id']; ?>">
                                <input type="hidden" name="active" value="<?php echo $variantActive ? '0' : '1'; ?>">
                                <button class="<?php echo $variantActive ? 'danger' : ''; ?>" type="submit" name="action" value="set_variant_active"><?php echo $variantActive ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$allVariants): ?><p class="catalog-empty-state">No booking variants yet. Add the first service above.</p><?php endif; ?>
                </div>
            </details>

            <details>
                <summary><?php echo court_icon($icons, 'edit'); ?> Edit Sessions</summary>
                <div class="catalog-admin-list">
                    <?php foreach ($allSessions as $session): $sessionStatus = (string) ($session['status'] ?? 'open'); ?>
                        <article class="catalog-admin-row">
                            <form class="catalog-admin-form catalog-inline-form catalog-variant-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="session_id" value="<?php echo (int) $session['id']; ?>">
                                <label><span>Variant</span><select name="variant_id" required><?php foreach ($allVariants as $variant): ?><option value="<?php echo (int) $variant['id']; ?>" <?php echo (int) $session['variant_id'] === (int) $variant['id'] ? 'selected' : ''; ?>><?php echo court_h($variant['court'] . ' - ' . $variant['name']); ?></option><?php endforeach; ?></select></label>
                                <label><span>Coach</span><select name="coach_user_id"><option value="">Unassigned</option><?php foreach ($coaches as $coach): ?><option value="<?php echo (int) $coach['id']; ?>" <?php echo (int) ($session['coach_user_id'] ?? 0) === (int) $coach['id'] ? 'selected' : ''; ?>><?php echo court_h($coach['name']); ?></option><?php endforeach; ?></select></label>
                                <label><span>Date</span><input type="date" name="session_date" value="<?php echo court_h($session['session_date']); ?>" required></label>
                                <label><span>Start</span><input type="time" name="start_time" value="<?php echo court_h(substr((string) $session['start_time'], 0, 5)); ?>" required></label>
                                <label><span>End</span><input type="time" name="end_time" value="<?php echo court_h(substr((string) $session['end_time'], 0, 5)); ?>" required></label>
                                <label><span>Capacity</span><input type="number" name="capacity" min="1" value="<?php echo (int) $session['capacity']; ?>" required></label>
                                <label><span>Booked</span><input type="number" name="booked_count" min="0" value="<?php echo (int) $session['booked_count']; ?>" required></label>
                                <label><span>Status</span><select name="status"><option value="open" <?php echo $sessionStatus === 'open' ? 'selected' : ''; ?>>Open</option><option value="full" <?php echo $sessionStatus === 'full' ? 'selected' : ''; ?>>Full</option><option value="cancelled" <?php echo $sessionStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option><option value="completed" <?php echo $sessionStatus === 'completed' ? 'selected' : ''; ?>>Completed</option></select></label>
                                <button type="submit" name="action" value="update_session">Save</button>
                            </form>
                            <form class="catalog-status-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="session_id" value="<?php echo (int) $session['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $sessionStatus === 'cancelled' ? 'open' : 'cancelled'; ?>">
                                <button class="<?php echo $sessionStatus === 'cancelled' ? '' : 'danger'; ?>" type="submit" name="action" value="set_session_status"><?php echo $sessionStatus === 'cancelled' ? 'Reopen' : 'Disable'; ?></button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$allSessions): ?><p class="catalog-empty-state">No sessions yet. Add the first schedule above.</p><?php endif; ?>
                </div>
            </details>
        </section>
        <?php endif; ?>

        <?php if ($isSocialPlay): ?>
        <section class="social-play-layout">
            <div class="social-main-column">
                <div class="social-actions-row"><a class="bookings-button ghost" href="<?php echo court_public_url('courts.php#social-play'); ?>"><?php echo court_icon($icons, 'eye'); ?> Preview on Website</a><button class="bookings-button primary" type="button">Save Changes</button></div>
                <section class="social-stat-grid">
                    <article class="user-stat green"><div><?php echo court_icon($icons, 'users'); ?></div><span>Participants This Month</span><strong><?php echo number_format(max($socialParticipants, 128)); ?></strong><small>↑ 18% vs last month</small></article>
                    <article class="user-stat orange"><div><?php echo court_icon($icons, 'calendar'); ?></div><span>Sessions This Month</span><strong><?php echo number_format(max(count($socialSessions), 12)); ?></strong><small>↑ 9% vs last month</small></article>
                    <article class="user-stat pink"><div><?php echo court_icon($icons, 'tag'); ?></div><span>Revenue This Month</span><strong>₱<?php echo number_format(max($socialRevenue, 18400), 0); ?></strong><small>↑ 22% vs last month</small></article>
                    <article class="user-stat orange"><div><?php echo court_icon($icons, 'bell'); ?></div><span>Upcoming Sessions</span><strong><?php echo number_format(max(count($socialSessions), 4)); ?></strong><small>Next: Jun 10, 6:00 PM</small></article>
                </section>

                <article class="social-panel">
                    <header><div><h2>Booking Types</h2><p>Manage available social play products and booking options.</p></div><button type="button"><?php echo court_icon($icons, 'plus'); ?> Add Booking Type</button></header>
                    <div class="social-type-list">
                        <?php foreach ($socialServices as $index => $service): ?>
                            <article class="social-type-card <?php echo $index % 2 ? 'purple' : 'pink'; ?>">
                                <span><?php echo court_icon($icons, $index % 2 ? 'target' : 'courts'); ?></span>
                                <div><h3><?php echo htmlspecialchars(strtoupper($service['name'])); ?> <em>₱<?php echo number_format((float) $service['price'], 0); ?></em></h3><p><?php echo $index % 2 ? "Compete in this week's Court Green bracket." : 'Meet new partners, rotate games, and level up with peers.'; ?></p></div>
                                <p><small>Capacity</small><strong><?php echo number_format((int) $service['capacity']); ?> Players</strong></p>
                                <p><small>Duration</small><strong><?php echo htmlspecialchars($service['duration_label']); ?></strong></p>
                                <p><small>Status</small><b class="status-pill status-<?php echo !empty($service['active']) ? 'success' : 'warning'; ?>"><?php echo !empty($service['active']) ? 'Active' : 'Inactive'; ?></b></p>
                                <div class="service-actions"><button type="button"><?php echo court_icon($icons, 'edit'); ?> Edit</button><button class="icon-button danger" type="button" aria-label="Archive service"><?php echo court_icon($icons, 'trash'); ?></button></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="social-panel">
                    <header><div><h2>Upcoming Sessions</h2><p>Manage and schedule upcoming social play sessions.</p></div><button type="button"><?php echo court_icon($icons, 'plus'); ?> Add New Session</button></header>
                    <div class="social-session-table">
                        <div class="social-session-row head"><span>Date</span><span>Time</span><span>Session Type</span><span>Court</span><span>Players</span><span>Status</span><span>Actions</span></div>
                        <?php foreach ($socialSessions as $session): ?>
                            <div class="social-session-row"><span><?php echo court_icon($icons, 'calendar'); ?> <strong><?php echo htmlspecialchars($session['session_date']); ?></strong></span><span><?php echo court_icon($icons, 'bell'); ?> <?php echo htmlspecialchars($session['session_time']); ?></span><span><em class="social-chip"><?php echo htmlspecialchars($session['name']); ?></em></span><span><em class="court-chip"><?php echo htmlspecialchars($session['court_name']); ?></em></span><span><?php echo number_format((int) $session['booked_count']); ?> / <?php echo number_format((int) ($session['variant_capacity'] ?? 16)); ?></span><span><b class="status-pill status-success">Open</b></span><span class="social-row-actions"><button><?php echo court_icon($icons, 'edit'); ?></button><button><?php echo court_icon($icons, 'plus'); ?></button><button><?php echo court_icon($icons, 'more'); ?></button></span></div>
                        <?php endforeach; ?>
                        <?php if (!$socialSessions): ?><div class="social-session-row"><span>No sessions scheduled yet.</span><span></span><span></span><span></span><span></span><span></span><span></span></div><?php endif; ?>
                    </div>
                    <a class="social-view-all" href="#">View All Sessions <?php echo court_icon($icons, 'arrow'); ?></a>
                </article>
            </div>

            <aside class="social-content-column">
                <article class="court-photo-card"><header><h2>Hero Image</h2><button type="button"><?php echo court_icon($icons, 'plus'); ?> Change Photo</button></header><div class="hero-photo social-hero"><img src="<?php echo court_asset('img/court/social play-2.png'); ?>" alt="Social Play hero"></div></article>
                <article class="court-photo-card"><header><h2>Gallery</h2><button type="button">Manage Photos</button></header><div class="gallery-grid social-gallery"><?php foreach ($socialGallery as $photo): ?><img src="<?php echo court_asset($photo); ?>" alt="Social Play photo"><?php endforeach; ?></div></article>
                <details class="website-preview-card social-preview-card"><summary>Website Preview</summary><div class="social-preview"><h3>SOCIAL PLAY</h3><p>Community-driven pickleball sessions</p><?php foreach ($socialServices as $index => $service): ?><article><span><?php echo court_icon($icons, $index % 2 ? 'target' : 'courts'); ?></span><div><strong><?php echo htmlspecialchars(strtoupper($service['name'])); ?></strong><small><?php echo $index % 2 ? "Compete in this week's Court Green bracket." : 'Meet new partners, rotate games, and level up with peers.'; ?></small></div><b>₱<?php echo number_format((float) $service['price'], 0); ?><small>/ session</small></b></article><?php endforeach; ?><button type="button">Book Now</button></div><footer><span>This is how Social Play looks on the public website.</span><a href="<?php echo court_public_url('courts.php#social-play'); ?>">View Full Page</a></footer></details>
            </aside>
        </section>
        <?php else: ?>
        <section class="court-actions-row">
            <nav class="court-tabs" aria-label="Court sections">
                <button class="active" type="button" data-court-tab="catalogs">Catalogs</button>
                <button type="button" data-court-tab="details">Details</button>
                <button type="button" data-court-tab="photos">Photos</button>
            </nav>
            <div><a class="bookings-button ghost" href="<?php echo court_public_url('courts.php#' . $courtSlug); ?>" target="_blank" rel="noopener"><?php echo court_icon($icons, 'eye'); ?> Preview on Website</a><button class="bookings-button primary" type="submit" form="courtDetailsForm">Save Changes</button></div>
        </section>

        <section class="court-editor-layout court-accent-<?php echo $accent; ?>">
            <section class="court-stats-grid" aria-label="<?php echo court_h($pageTitle); ?> live stats">
                <article><span>Total bookings this month</span><strong><?php echo number_format($courtStats['bookings_month']); ?></strong></article>
                <article><span>Revenue this month</span><strong>₱<?php echo number_format((float) $courtStats['revenue_month'], 2); ?></strong></article>
                <article><span>Upcoming sessions</span><strong><?php echo number_format($courtStats['upcoming_sessions']); ?></strong></article>
                <article><span>Most booked service</span><strong><?php echo court_h($courtStats['most_booked_service']); ?></strong></article>
            </section>

            <div class="court-tab-panels">
                <section class="court-tab-panel is-active" data-court-panel="catalogs">
                    <article class="catalog-manager-card">
                        <header>
                            <div><h2>Services</h2><p>Manage the services and offers for <?php echo htmlspecialchars($pageTitle); ?>.</p></div>
                            <details class="inline-create-panel">
                                <summary><?php echo court_icon($icons, 'plus'); ?> Add New Service</summary>
                                <form class="catalog-admin-form catalog-admin-form-wide" method="post">
                                    <?php echo court_csrf_input(); ?>
                                    <input type="hidden" name="court_id" value="<?php echo (int) ($court['id'] ?? 0); ?>">
                                    <label><span>Name</span><input type="text" name="name" placeholder="Court Rental" required></label>
                                    <label><span>Slug</span><input type="text" name="slug" placeholder="<?php echo court_h($courtSlug); ?>-court-rental" required></label>
                                    <label><span>Category</span><input type="text" name="category" placeholder="Court Reservation" required></label>
                                    <label><span>Duration</span><input type="text" name="duration_label" placeholder="1 hour" required></label>
                                    <label><span>Price</span><input type="number" name="price" step="0.01" min="0" value="0.00" required></label>
                                    <label><span>Limit</span><input type="number" name="participants_limit" min="1" value="1" required></label>
                                    <label><span>Capacity</span><input type="number" name="capacity" min="1" value="<?php echo (int) $capacity; ?>" required></label>
                                    <label><span>Sort</span><input type="number" name="sort_order" min="0" value="<?php echo count($services) * 10; ?>"></label>
                                    <label class="catalog-check"><input type="checkbox" name="active" value="1" checked> Active</label>
                                    <button class="bookings-button primary" type="submit" name="action" value="create_variant">Add Service</button>
                                </form>
                            </details>
                        </header>
                        <div class="service-list service-table-list">
                            <div class="service-table-head"><span></span><span>Service</span><span>Category</span><span>Price</span><span>Duration</span><span>Status</span><span>Actions</span></div>
                            <?php foreach ($services as $service): ?>
                                <article class="service-card">
                                    <span class="service-icon"><?php echo court_icon($icons, service_icon_name($service['name'])); ?></span>
                                    <?php $serviceSessionCount = (int) ($upcomingSessionsByVariant[(int) $service['id']] ?? 0); ?>
                                    <div class="service-main"><strong><?php echo htmlspecialchars($service['name']); ?></strong><small><?php echo htmlspecialchars($service['category']); ?> for <?php echo htmlspecialchars($pageTitle); ?></small><small class="service-session-summary"><?php echo $serviceSessionCount > 0 ? number_format($serviceSessionCount) . ' upcoming session' . ($serviceSessionCount === 1 ? '' : 's') : 'No sessions created'; ?></small></div>
                                    <p class="service-category-cell"><small>Category</small><strong><?php echo htmlspecialchars($service['category']); ?></strong></p>
                                    <p><small>Price</small><strong>₱<?php echo number_format((float) $service['price'], 2); ?></strong></p>
                                    <p><small>Duration</small><strong><?php echo htmlspecialchars($service['duration_label']); ?></strong></p>
                                    <p><small>Status</small><em class="status-pill status-<?php echo !empty($service['active']) ? 'success' : 'warning'; ?>"><?php echo !empty($service['active']) ? 'Active' : 'Inactive'; ?></em></p>
                                    <div class="service-actions">
                                        <details class="service-edit-details">
                                            <summary><?php echo court_icon($icons, 'edit'); ?> Edit</summary>
                                            <form class="service-edit-form" method="post">
                                                <?php echo court_csrf_input(); ?>
                                                <input type="hidden" name="variant_id" value="<?php echo (int) $service['id']; ?>">
                                                <input type="hidden" name="court_id" value="<?php echo (int) ($court['id'] ?? 0); ?>">
                                                <label><span>Name</span><input type="text" name="name" value="<?php echo court_h($service['name']); ?>" required></label>
                                                <label><span>Slug</span><input type="text" name="slug" value="<?php echo court_h($service['slug']); ?>" required></label>
                                                <label><span>Category</span><input type="text" name="category" value="<?php echo court_h($service['category']); ?>" required></label>
                                                <label><span>Duration</span><input type="text" name="duration_label" value="<?php echo court_h($service['duration_label']); ?>" required></label>
                                                <label><span>Price</span><input type="number" name="price" step="0.01" min="0" value="<?php echo court_h($service['price']); ?>" required></label>
                                                <label><span>Limit</span><input type="number" name="participants_limit" min="1" value="<?php echo (int) $service['participants_limit']; ?>" required></label>
                                                <label><span>Capacity</span><input type="number" name="capacity" min="1" value="<?php echo (int) $service['capacity']; ?>" required></label>
                                                <label><span>Sort</span><input type="number" name="sort_order" min="0" value="<?php echo (int) ($service['sort_order'] ?? 0); ?>"></label>
                                                <input type="hidden" name="image" value="<?php echo court_h($service['image'] ?? ''); ?>">
                                                <label class="catalog-check"><input type="checkbox" name="active" value="1" <?php echo !empty($service['active']) ? 'checked' : ''; ?>> Active on website</label>
                                                <button type="submit" name="action" value="update_variant">Save Service</button>
                                            </form>
                                        </details>
                                        <form method="post">
                                            <?php echo court_csrf_input(); ?>
                                            <input type="hidden" name="variant_id" value="<?php echo (int) $service['id']; ?>">
                                            <input type="hidden" name="active" value="<?php echo !empty($service['active']) ? '0' : '1'; ?>">
                                            <button class="archive-service-button danger" type="submit" name="action" value="set_variant_active"><?php echo court_icon($icons, 'trash'); ?> Archive</button>
                                        </form>
                                        <button class="session-manage-button" type="button" data-open-session-modal="<?php echo (int) $service['id']; ?>"><?php echo court_icon($icons, 'calendar'); ?> Sessions</button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <footer><?php echo court_icon($icons, 'target'); ?> Use the Sort field inside Edit to reorder services. Only active services appear publicly.</footer>
                    </article>
                </section>

                <section class="court-tab-panel" data-court-panel="details">
                    <article class="court-info-card">
                    <header>
                        <h2>Court Details</h2>
                        <button class="bookings-button primary" type="submit" form="courtDetailsForm">Save Details</button>
                    </header>
                    <form class="court-details-form court-details-form-static" id="courtDetailsForm" method="post">
                        <?php echo court_csrf_input(); ?>
                        <input type="hidden" name="court_id" value="<?php echo (int) ($court['id'] ?? 0); ?>">
                        <input type="hidden" name="slug" value="<?php echo court_h($courtSlug); ?>">
                        <label><span>Court name</span><input type="text" name="name" value="<?php echo court_h($pageTitle); ?>" data-live-field="name" required></label>
                        <label><span>Description</span><textarea name="description" data-live-field="description" rows="3"><?php echo court_h($subtitle); ?></textarea></label>
                        <label><span>Base price</span><input type="number" name="base_price" step="0.01" min="0" value="<?php echo court_h((string) $basePrice); ?>" data-live-field="price"></label>
                        <label><span>Capacity</span><input type="number" name="capacity" min="1" value="<?php echo (int) $capacity; ?>" data-live-field="capacity"></label>
                        <label><span>Status</span><select name="status" data-live-field="status"><option value="active" <?php echo ($court['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo ($court['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option><option value="maintenance" <?php echo ($court['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option></select></label>
                        <label><span>Operating hours</span><input type="text" name="operating_hours" value="<?php echo court_h($operatingHours); ?>" data-live-field="hours"></label>
                        <label><span>Court type</span><input type="text" name="court_type" value="<?php echo court_h($courtType); ?>" data-live-field="type"></label>
                        <button class="bookings-button primary" type="submit" name="action" value="update_court_details">Save Changes</button>
                    </form>
                    <div class="court-info-grid">
                        <div class="court-info-title"><span><?php echo court_icon($icons, 'courts'); ?></span><div><strong data-preview-name><?php echo htmlspecialchars($pageTitle); ?></strong><small data-preview-description><?php echo htmlspecialchars($subtitle); ?></small></div></div>
                        <p><small>Base Price</small><strong><span data-preview-price>₱<?php echo number_format($basePrice, 2); ?></span> / session</strong></p>
                        <p><small>Capacity</small><strong><span data-preview-capacity><?php echo number_format($capacity); ?></span> Players</strong></p>
                        <p><small>Status</small><strong class="dot-status" data-preview-status><?php echo court_h(ucfirst((string) ($court['status'] ?? 'inactive'))); ?></strong></p>
                        <p><small>Type</small><strong data-preview-type><?php echo court_h($courtType); ?></strong></p>
                        <p><small>Operating</small><strong data-preview-hours><?php echo court_h($operatingHours); ?></strong></p>
                    </div>
                    </article>
                </section>

                <section class="court-tab-panel" data-court-panel="photos">
                    <article class="court-photo-card court-photo-card-full">
                        <header>
                            <div><h2>Photos</h2><p>Manage the hero image and gallery for <?php echo htmlspecialchars($pageTitle); ?>.</p></div>
                            <button class="manage-photos-trigger" type="button" data-open-photo-modal><?php echo court_icon($icons, 'gear'); ?> Manage Photos</button>
                        </header>
                        <div class="photos-tab-grid">
                            <div><h3>Hero Image</h3><div class="hero-photo"><img data-preview-hero src="<?php echo court_asset($heroImage); ?>" alt="<?php echo htmlspecialchars($pageTitle); ?>"><span>Hero Image</span></div></div>
                            <div><h3>Gallery</h3><div class="gallery-grid"><?php foreach (array_slice($gallery, 0, 8) as $index => $photo): ?><img src="<?php echo court_asset($photo); ?>" alt="Court photo <?php echo $index + 1; ?>"><?php endforeach; ?></div></div>
                        </div>
                    </article>
                </section>

            </div>
        </section>
        <?php foreach ($services as $service): ?>
            <?php
                $serviceId = (int) $service['id'];
                $serviceSessions = $sessionsByVariant[$serviceId] ?? [];
            ?>
            <div class="court-session-modal" id="sessionModal<?php echo $serviceId; ?>" aria-hidden="true" data-session-modal="<?php echo $serviceId; ?>">
                <div class="court-session-modal-backdrop" data-close-session-modal></div>
                <section class="court-session-modal-panel" role="dialog" aria-modal="true" aria-labelledby="sessionModalTitle<?php echo $serviceId; ?>">
                    <header>
                        <div><h2 id="sessionModalTitle<?php echo $serviceId; ?>">Manage Sessions</h2><p><?php echo court_h($service['name']); ?></p></div>
                        <button type="button" data-close-session-modal aria-label="Close session manager">&times;</button>
                    </header>

                    <details class="session-create-panel">
                        <summary><?php echo court_icon($icons, 'plus'); ?> Add Session</summary>
                        <form class="session-edit-form" method="post">
                            <?php echo court_csrf_input(); ?>
                            <input type="hidden" name="variant_id" value="<?php echo $serviceId; ?>">
                            <input type="hidden" name="booked_count" value="0">
                            <label><span>Session Date</span><input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required></label>
                            <label><span>Start Time</span><input type="time" name="start_time" value="09:00" required></label>
                            <label><span>End Time</span><input type="time" name="end_time" value="10:00" required></label>
                            <label><span>Capacity</span><input type="number" name="capacity" min="1" value="<?php echo (int) ($service['capacity'] ?? $capacity); ?>" required></label>
                            <label><span>Status</span><select name="status"><option value="open">Available</option><option value="full">Full</option><option value="cancelled">Closed</option></select></label>
                            <button class="bookings-button primary" type="submit" name="action" value="create_session">Create Session</button>
                        </form>
                    </details>

                    <div class="session-list-table">
                        <div class="session-list-head"><span>Date</span><span>Start Time</span><span>End Time</span><span>Capacity</span><span>Booked</span><span>Status</span><span>Actions</span></div>
                        <?php foreach ($serviceSessions as $session): ?>
                            <?php
                                $rawStatus = (string) ($session['status'] ?? 'open');
                                $statusLabel = $rawStatus === 'open' ? 'Available' : ($rawStatus === 'full' ? 'Full' : 'Closed');
                                $statusTone = $rawStatus === 'open' ? 'success' : 'warning';
                            ?>
                            <article>
                                <span><?php echo court_h($session['display_date'] ?? $session['session_date'] ?? ''); ?></span>
                                <span><?php echo court_h((new DateTimeImmutable('1970-01-01 ' . (string) $session['start_time']))->format('h:i A')); ?></span>
                                <span><?php echo court_h((new DateTimeImmutable('1970-01-01 ' . (string) $session['end_time']))->format('h:i A')); ?></span>
                                <span><?php echo number_format((int) ($session['capacity'] ?? 0)); ?></span>
                                <span><?php echo number_format((int) ($session['booked_count'] ?? 0)); ?> booked</span>
                                <span><em class="status-pill status-<?php echo $statusTone; ?>"><?php echo court_h($statusLabel); ?></em></span>
                                <span class="session-row-actions">
                                    <details class="session-edit-panel">
                                        <summary><?php echo court_icon($icons, 'edit'); ?> Edit</summary>
                                        <form class="session-edit-form" method="post">
                                            <?php echo court_csrf_input(); ?>
                                            <input type="hidden" name="session_id" value="<?php echo (int) $session['id']; ?>">
                                            <input type="hidden" name="variant_id" value="<?php echo $serviceId; ?>">
                                            <input type="hidden" name="coach_user_id" value="<?php echo (int) ($session['coach_user_id'] ?? 0); ?>">
                                            <input type="hidden" name="booked_count" value="<?php echo (int) ($session['booked_count'] ?? 0); ?>">
                                            <label><span>Date</span><input type="date" name="session_date" value="<?php echo court_h($session['session_date']); ?>" required></label>
                                            <label><span>Start</span><input type="time" name="start_time" value="<?php echo court_h(substr((string) $session['start_time'], 0, 5)); ?>" required></label>
                                            <label><span>End</span><input type="time" name="end_time" value="<?php echo court_h(substr((string) $session['end_time'], 0, 5)); ?>" required></label>
                                            <label><span>Capacity</span><input type="number" name="capacity" min="<?php echo max(1, (int) ($session['booked_count'] ?? 0)); ?>" value="<?php echo (int) $session['capacity']; ?>" required></label>
                                            <label><span>Status</span><select name="status"><option value="open" <?php echo $rawStatus === 'open' ? 'selected' : ''; ?>>Available</option><option value="full" <?php echo $rawStatus === 'full' ? 'selected' : ''; ?>>Full</option><option value="cancelled" <?php echo $rawStatus === 'cancelled' ? 'selected' : ''; ?>>Closed</option></select></label>
                                            <button type="submit" name="action" value="update_session">Save Session</button>
                                        </form>
                                    </details>
                                    <form method="post">
                                        <?php echo court_csrf_input(); ?>
                                        <input type="hidden" name="session_id" value="<?php echo (int) $session['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $rawStatus === 'cancelled' ? 'open' : 'cancelled'; ?>">
                                        <button class="icon-button danger" type="submit" name="action" value="set_session_status" aria-label="Archive session"><?php echo court_icon($icons, 'trash'); ?></button>
                                    </form>
                                </span>
                            </article>
                        <?php endforeach; ?>
                        <?php if (!$serviceSessions): ?><p class="catalog-empty-state">No sessions created for this service yet.</p><?php endif; ?>
                    </div>
                </section>
            </div>
        <?php endforeach; ?>
        <div class="court-photo-modal" id="courtPhotoModal" aria-hidden="true">
            <div class="court-photo-modal-backdrop" data-close-photo-modal></div>
            <section class="court-photo-modal-panel" role="dialog" aria-modal="true" aria-labelledby="courtPhotoModalTitle">
                <header>
                    <div><h2 id="courtPhotoModalTitle">Manage Photos</h2><p>Upload, set hero image, reorder, and soft delete photos for <?php echo court_h($pageTitle); ?>.</p></div>
                    <button type="button" data-close-photo-modal aria-label="Close photo manager">&times;</button>
                </header>

                <form class="court-photo-upload" method="post" enctype="multipart/form-data">
                    <?php echo court_csrf_input(); ?>
                    <input type="hidden" name="court_id" value="<?php echo (int) ($court['id'] ?? 0); ?>">
                    <label><span>Choose File</span><input type="file" name="court_photo" accept="image/*" required></label>
                    <label><span>Sort Order</span><input type="number" name="sort_order" min="0" value="<?php echo count($mediaRows); ?>"></label>
                    <label class="catalog-check"><input type="checkbox" name="is_hero" value="1"> Set as Hero Image</label>
                    <button type="submit" name="action" value="upload_court_photo">Upload Photo</button>
                </form>

                <?php if ($mediaRows): ?>
                    <div class="court-media-list">
                        <form id="photoOrderForm" method="post">
                            <?php echo court_csrf_input(); ?>
                            <input type="hidden" name="court_id" value="<?php echo (int) ($court['id'] ?? 0); ?>">
                        </form>
                        <div class="court-media-grid">
                            <?php foreach ($mediaRows as $media): ?>
                                <article class="court-media-card" draggable="true" data-media-card>
                                    <span class="media-drag-handle" aria-label="Sort handle">Drag</span>
                                    <img src="<?php echo court_asset((string) $media['image_path']); ?>" alt="Court media">
                                    <?php if (!empty($media['is_hero'])): ?><b>Hero Image</b><?php endif; ?>
                                    <label><span>Sort</span><input form="photoOrderForm" type="number" name="media_order[<?php echo (int) $media['id']; ?>]" min="0" value="<?php echo (int) $media['sort_order']; ?>"></label>
                                    <details class="media-action-menu">
                                        <summary>Actions</summary>
                                        <form method="post">
                                            <?php echo court_csrf_input(); ?>
                                            <input type="hidden" name="court_id" value="<?php echo (int) ($court['id'] ?? 0); ?>">
                                            <input type="hidden" name="media_id" value="<?php echo (int) $media['id']; ?>">
                                            <button type="submit" name="action" value="set_hero_media" <?php echo !empty($media['is_hero']) ? 'disabled' : ''; ?>>Set as Hero</button>
                                        </form>
                                        <form method="post" enctype="multipart/form-data">
                                            <?php echo court_csrf_input(); ?>
                                            <input type="hidden" name="court_id" value="<?php echo (int) ($court['id'] ?? 0); ?>">
                                            <input type="hidden" name="media_id" value="<?php echo (int) $media['id']; ?>">
                                            <label class="replace-photo-control"><span>Replace</span><input type="file" name="replacement_photo" accept="image/*" required></label>
                                            <button type="submit" name="action" value="replace_court_media">Replace Photo</button>
                                        </form>
                                        <form method="post">
                                            <?php echo court_csrf_input(); ?>
                                            <input type="hidden" name="court_id" value="<?php echo (int) ($court['id'] ?? 0); ?>">
                                            <input type="hidden" name="media_id" value="<?php echo (int) $media['id']; ?>">
                                            <button class="danger" type="submit" name="action" value="delete_court_media">Delete</button>
                                        </form>
                                    </details>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <footer>
                            <button class="bookings-button ghost" type="button" data-close-photo-modal>Close</button>
                            <button class="bookings-button primary" form="photoOrderForm" type="submit" name="action" value="update_media_order">Save Photo Order</button>
                        </footer>
                    </div>
                <?php else: ?>
                    <p class="catalog-empty-state">No photos yet. Upload the first court photo above.</p>
                <?php endif; ?>
            </section>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
(function(){
    const form = document.getElementById('courtDetailsForm');
    if (!form) return;

    const money = value => '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const writeAll = (selector, value) => document.querySelectorAll(selector).forEach(node => { node.textContent = value; });
    const sync = () => {
        const data = new FormData(form);
        writeAll('[data-preview-name]', data.get('name') || 'Court');
        writeAll('[data-preview-description]', data.get('description') || 'Court description');
        writeAll('[data-preview-price]', money(data.get('base_price')));
        writeAll('[data-preview-capacity]', Number(data.get('capacity') || 0).toLocaleString('en-PH'));
        writeAll('[data-preview-status]', String(data.get('status') || 'active').replace(/^\w/, letter => letter.toUpperCase()));
        writeAll('[data-preview-type]', data.get('court_type') || 'Indoor');
        writeAll('[data-preview-hours]', data.get('operating_hours') || '8AM - 10PM');
    };
    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
})();
(function(){
    const tabs = document.querySelectorAll('[data-court-tab]');
    const panels = document.querySelectorAll('[data-court-panel]');
    if (!tabs.length || !panels.length) return;

    const activate = key => {
        tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.courtTab === key));
        panels.forEach(panel => panel.classList.toggle('is-active', panel.dataset.courtPanel === key));
    };

    tabs.forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.courtTab)));
})();
(function(){
    const modal = document.getElementById('courtPhotoModal');
    if (!modal) return;

    const openButtons = document.querySelectorAll('[data-open-photo-modal]');
    const closeButtons = modal.querySelectorAll('[data-close-photo-modal]');
    const open = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('photo-modal-open');
    };
    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('photo-modal-open');
    };

    openButtons.forEach(button => button.addEventListener('click', open));
    closeButtons.forEach(button => button.addEventListener('click', close));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });

    const grid = modal.querySelector('.court-media-grid');
    if (!grid) return;

    let draggedCard = null;
    const syncSortInputs = () => {
        grid.querySelectorAll('[data-media-card]').forEach((card, index) => {
            const input = card.querySelector('input[name^="media_order"]');
            if (input) input.value = String(index * 10);
        });
    };

    grid.addEventListener('dragstart', event => {
        const card = event.target.closest('[data-media-card]');
        if (!card) return;
        draggedCard = card;
        card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    grid.addEventListener('dragover', event => {
        event.preventDefault();
        const target = event.target.closest('[data-media-card]');
        if (!draggedCard || !target || target === draggedCard) return;
        const box = target.getBoundingClientRect();
        const insertAfter = event.clientY > box.top + box.height / 2;
        grid.insertBefore(draggedCard, insertAfter ? target.nextSibling : target);
    });

    grid.addEventListener('dragend', () => {
        if (draggedCard) draggedCard.classList.remove('is-dragging');
        draggedCard = null;
        syncSortInputs();
    });
})();
(function(){
    const modals = document.querySelectorAll('[data-session-modal]');
    if (!modals.length) return;

    const closeAll = () => {
        modals.forEach(modal => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });
        document.body.classList.remove('session-modal-open');
    };

    document.querySelectorAll('[data-open-session-modal]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = document.querySelector('[data-session-modal="' + button.dataset.openSessionModal + '"]');
            if (!modal) return;
            closeAll();
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('session-modal-open');
        });
    });

    document.querySelectorAll('[data-close-session-modal]').forEach(button => {
        button.addEventListener('click', closeAll);
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeAll();
    });
})();
</script>
<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>

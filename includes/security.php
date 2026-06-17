<?php
declare(strict_types=1);

require_once __DIR__ . '/validation.php';

function pickled_start_secure_session(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function pickled_secure_cookie(string $name, string $value, int $ttl): void {
    $options = [
        'expires' => time() + $ttl,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $options['secure'] = true;
    }

    setcookie($name, $value, $options);
}

function pickled_init_csrf(): void {
    pickled_start_secure_session();

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function pickled_csrf_token(): string {
    pickled_init_csrf();
    return $_SESSION['csrf_token'];
}

function pickled_validate_csrf_token(?string $value): bool {
    pickled_init_csrf();
    return is_string($value) && hash_equals($_SESSION['csrf_token'], $value);
}

function pickled_normalize_role(?string $role): string {
    $role = strtolower(trim((string) $role));
    return in_array($role, ['admin', 'coach', 'player'], true) ? $role : 'player';
}

function pickled_session_user(array $user): array {
    return [
        'id' => (int) ($user['id'] ?? 0),
        'email' => strtolower(trim((string) ($user['email'] ?? ''))),
        'name' => trim((string) ($user['name'] ?? '')),
        'role' => pickled_normalize_role($user['role'] ?? null),
    ];
}

function pickled_default_redirect_for_role(?string $role): string {
    return match (pickled_normalize_role($role)) {
        'admin' => 'admin/admin-dashboard.php',
        'coach' => 'coach/dashboard.php',
        default => 'resident/index.php',
    };
}

function pickled_safe_redirect(string $redirect): string {
    $allowedPages = [
        'index.php',
        'login.php',
        'cart.php',
        'booking.php',
        'booking-details.php',
        'notifications.php',
        'profile.php',
        'courts.php',
        'social-play.php',
        'private.php',
        'contact.php',
        'forgot-password.php',
        'reset-password.php',
        'change-password.php',
    ];

    $redirect = trim($redirect);
    if ($redirect === '') {
        return 'resident/index.php';
    }

    $parts = parse_url($redirect);
    $path = $parts['path'] ?? '';
    $query = isset($parts['query']) ? '?' . preg_replace('/[^A-Za-z0-9_=&%-]/', '', $parts['query']) : '';
    $fragment = isset($parts['fragment']) ? '#' . preg_replace('/[^A-Za-z0-9_-]/', '', $parts['fragment']) : '';

    $path = preg_replace('/\\\\+/', '/', $path);
    $path = str_replace('//', '/', $path);
    $path = ltrim($path, '/');

    if (strpos($path, 'resident/') === 0) {
        $subpath = substr($path, strlen('resident/'));
        if (in_array($subpath, $allowedPages, true)) {
            return 'resident/' . $subpath . $query . $fragment;
        }
    }

    if (in_array($path, $allowedPages, true)) {
        return $path . $query . $fragment;
    }

    return 'resident/index.php';
}

function pickled_login_redirect_for_role(?string $role, string $requestedRedirect = ''): string {
    $role = pickled_normalize_role($role);

    if ($role === 'admin' || $role === 'coach') {
        return pickled_default_redirect_for_role($role);
    }

    $redirect = pickled_safe_redirect($requestedRedirect);
    return $redirect === 'index.php' ? pickled_default_redirect_for_role('player') : $redirect;
}

// Admin security functions
function pickled_require_admin(): void {
    pickled_start_secure_session();
    
    if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: admin-login.php');
        exit;
    }
}

function pickled_is_admin(): bool {
    pickled_start_secure_session();
    return !empty($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function pickled_admin_redirect_if_not_admin(): void {
    if (!pickled_is_admin()) {
        header('Location: admin-login.php');
        exit;
    }
}

<?php
declare(strict_types=1);

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

function pickled_safe_redirect(string $redirect): string {
    $allowedPages = [
        'index.php',
        'login.php',
        'cart.php',
        'booking.php',
        'profile.php',
        'courts.php',
        'social-play.php',
        'private.php',
        'contact.php',
        'forgot-password.php',
        'reset-password.php',
    ];

    $redirect = trim($redirect);
    if ($redirect === '') {
        return 'index.php';
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

    return 'index.php';
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

<?php
declare(strict_types=1);

function pickled_frontend_base_url(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/frontend/index.php');
    $marker = '/frontend/';
    $position = strpos($script, $marker);

    if ($position === false) {
        return '/frontend/';
    }

    return substr($script, 0, $position + strlen($marker));
}

function pickled_frontend_url(string $path = ''): string {
    return pickled_frontend_base_url() . ltrim($path, '/');
}

function pickled_asset_url(string $path): string {
    return pickled_frontend_url($path);
}

<?php
declare(strict_types=1);

function pickled_app_base_url(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');

    foreach (['/admin/', '/coach/', '/resident/', '/auth/'] as $marker) {
        $position = strpos($script, $marker);
        if ($position !== false) {
            return substr($script, 0, $position + 1);
        }
    }

    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return ($base === '' ? '' : $base) . '/';
}

function pickled_frontend_base_url(): string {
    return pickled_app_base_url();
}

function pickled_frontend_url(string $path = ''): string {
    return pickled_frontend_base_url() . ltrim($path, '/');
}

function pickled_asset_url(string $path): string {
    return pickled_frontend_url('assets/' . ltrim($path, '/'));
}

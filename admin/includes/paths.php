<?php
function pickled_admin_asset_url($path) {
    $base = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function pickled_admin_url($page) {
    $base = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($base, '/') . '/' . ltrim($page, '/');
}
?>
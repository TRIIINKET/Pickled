<?php
declare(strict_types=1);

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/upload-helper.php';
require_once __DIR__ . '/validation.php';

function pickled_avatar_default_path(): string
{
    return 'avatars/default.png';
}

function pickled_avatar_url(?string $avatar): string
{
    $avatar = trim((string) $avatar);
    if ($avatar === '' || $avatar === pickled_avatar_default_path()) {
        return pickled_asset_url('img/nav-logo-lpink.png');
    }

    if (str_starts_with($avatar, 'assets/') || str_starts_with($avatar, 'uploads/')) {
        return pickled_frontend_url($avatar);
    }

    return pickled_asset_url('uploads/' . ltrim($avatar, '/'));
}

function pickled_avatar_profile_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

function pickled_store_avatar_upload(array $file, int $userId, string $role = 'user'): ?string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $safeRole = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($role)) ?: 'user';
    error_log(
        'Avatar upload received: user_id=' . $userId
        . '; role=' . $safeRole
        . '; filename=' . (string) ($file['name'] ?? '')
        . '; size=' . (string) ($file['size'] ?? '')
        . '; client_mime=' . (string) ($file['type'] ?? '')
        . '; php_error=' . $error
        . '; tmp=' . (string) ($file['tmp_name'] ?? '')
    );

    validateUploadedFile($file, 'avatar');

    return pickled_upload_file(
        $file,
        'assets/uploads/avatars',
        [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
        ],
        2 * 1024 * 1024,
        'avatar_' . $safeRole . '_' . $userId
    );
}

function pickled_upsert_user_avatar(PDO $pdo, int $userId, string $avatarPath): bool
{
    $stmt = $pdo->prepare(
        'INSERT INTO user_profiles (user_id, phone, city, province, avatar)
         VALUES (:user_id, \'\', \'\', \'\', :avatar)
         ON DUPLICATE KEY UPDATE avatar = VALUES(avatar)'
    );
    $result = $stmt->execute([
        'user_id' => $userId,
        'avatar' => $avatarPath,
    ]);
    error_log('Avatar database update result: user_profiles.avatar user_id=' . $userId . '; avatar=' . $avatarPath . '; result=' . ($result ? 'success' : 'failed') . '; row_count=' . $stmt->rowCount());
    return $result;
}

function pickled_update_coach_profile_image_if_available(PDO $pdo, int $userId, string $avatarPath): bool
{
    if (!pickled_avatar_profile_column_exists($pdo, 'coach_profiles', 'profile_image')) {
        error_log('Coach avatar database update skipped: coach_profiles.profile_image column is not available.');
        return false;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO coach_profiles (user_id, status, profile_image)
         VALUES (:user_id, \'active\', :profile_image)
         ON DUPLICATE KEY UPDATE profile_image = VALUES(profile_image)'
    );
    $result = $stmt->execute([
        'user_id' => $userId,
        'profile_image' => $avatarPath,
    ]);
    error_log('Avatar database update result: coach_profiles.profile_image user_id=' . $userId . '; avatar=' . $avatarPath . '; result=' . ($result ? 'success' : 'failed') . '; row_count=' . $stmt->rowCount());
    return $result;
}

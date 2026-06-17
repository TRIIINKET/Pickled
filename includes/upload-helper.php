<?php
declare(strict_types=1);

function pickled_upload_file(array $file, string $targetDir, array $allowedTypes, int $maxSize, string $prefix): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        error_log('Upload failed before validation. PHP upload error code: ' . $errorCode . '; upload_max_filesize=' . ini_get('upload_max_filesize') . '; post_max_size=' . ini_get('post_max_size'));
        if (in_array($errorCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('Uploaded file is too large.');
        }
        throw new RuntimeException('Please choose a valid file to upload.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $clientType = (string) ($file['type'] ?? '');

    $hasUpload = PHP_SAPI === 'cli' ? is_file($tmpName) : is_uploaded_file($tmpName);
    if ($tmpName === '' || !$hasUpload) {
        error_log('Upload failed because temporary file is missing or is not an uploaded file. tmp=' . $tmpName . '; original=' . $originalName . '; size=' . $size . '; client_mime=' . $clientType);
        throw new RuntimeException('Please choose a valid file to upload.');
    }

    if ($size <= 0 || $size > $maxSize) {
        error_log('Upload failed because file size is invalid: ' . $size . ' bytes; max=' . $maxSize . '; original=' . $originalName);
        throw new RuntimeException('Uploaded file is too large.');
    }

    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '' || !isset($allowedTypes[$extension])) {
        error_log('Upload failed because extension is not allowed: ' . $extension . '; original=' . $originalName);
        throw new RuntimeException('Uploaded file type is not allowed.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '';
    if (!in_array($mime, (array) $allowedTypes[$extension], true)) {
        error_log('Upload failed because MIME type is not allowed. Extension=' . $extension . '; detected_mime=' . $mime . '; client_mime=' . $clientType . '; original=' . $originalName);
        throw new RuntimeException('Uploaded file type is not allowed.');
    }

    $relativeDir = trim(str_replace('\\', '/', $targetDir), '/');
    if ($relativeDir === '') {
        error_log('Upload failed because target directory is empty.');
        throw new RuntimeException('Upload folder is invalid.');
    }

    $rootDir = dirname(__DIR__);
    $absoluteDir = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    error_log(
        'Upload target prepared: original=' . $originalName
        . '; size=' . $size
        . '; client_mime=' . $clientType
        . '; detected_mime=' . $mime
        . '; php_error=' . (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)
        . '; target_relative=' . $relativeDir
        . '; target_absolute=' . $absoluteDir
        . '; is_dir_before=' . (is_dir($absoluteDir) ? 'yes' : 'no')
        . '; is_writable_before=' . (is_writable($absoluteDir) ? 'yes' : 'no')
    );

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
        error_log('Upload failed because folder could not be created: ' . $absoluteDir);
        throw new RuntimeException('Upload folder is unavailable.');
    }

    $indexPath = $absoluteDir . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($indexPath)) {
        @file_put_contents($indexPath, '');
        @chmod($indexPath, 0644);
    }

    if (!is_writable($absoluteDir)) {
        @chmod($absoluteDir, 0755);
    }

    if (!is_writable($absoluteDir)) {
        @chmod($absoluteDir, 0775);
    }

    if (!is_writable($absoluteDir)) {
        @chmod($absoluteDir, 0777);
    }

    if (!is_writable($absoluteDir)) {
        $owner = function_exists('posix_getpwuid') ? (posix_getpwuid((int) @fileowner($absoluteDir))['name'] ?? 'unknown') : 'unknown';
        $perms = substr(sprintf('%o', (int) @fileperms($absoluteDir)), -4);
        error_log('Upload failed because folder is not writable: ' . $absoluteDir . '; owner=' . $owner . '; perms=' . $perms . '; php_user=' . get_current_user());
        throw new RuntimeException(str_contains($relativeDir, 'avatars') ? 'Avatar upload folder is not writable.' : 'Upload folder is not writable.');
    }

    $safePrefix = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($prefix)) ?: 'upload';
    $filename = $safePrefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

    $stored = PHP_SAPI === 'cli' ? copy($tmpName, $destination) : move_uploaded_file($tmpName, $destination);
    error_log('Upload move result: ' . ($stored ? 'success' : 'failed') . '; dir=' . $absoluteDir . '; is_dir=' . (is_dir($absoluteDir) ? 'yes' : 'no') . '; is_writable=' . (is_writable($absoluteDir) ? 'yes' : 'no') . '; filename=' . $filename . '; size=' . $size . '; detected_mime=' . $mime . '; destination=' . $destination);
    if (!$stored) {
        error_log('Upload failed while moving file to: ' . $destination);
        throw new RuntimeException('Uploaded file could not be saved.');
    }

    @chmod($destination, 0644);

    return $relativeDir . '/' . $filename;
}

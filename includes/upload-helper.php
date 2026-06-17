<?php
declare(strict_types=1);

function pickled_upload_file(array $file, string $targetDir, array $allowedTypes, int $maxSize, string $prefix): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        error_log('Upload failed before move. PHP upload error code: ' . $errorCode);
        throw new RuntimeException('Please choose a valid file to upload.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);

    $hasUpload = PHP_SAPI === 'cli' ? is_file($tmpName) : is_uploaded_file($tmpName);
    if ($tmpName === '' || !$hasUpload) {
        error_log('Upload failed because temporary file is missing or is not an uploaded file.');
        throw new RuntimeException('Please choose a valid file to upload.');
    }

    if ($size <= 0 || $size > $maxSize) {
        error_log('Upload failed because file size is invalid: ' . $size . ' bytes.');
        throw new RuntimeException('Uploaded file is too large.');
    }

    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '' || !isset($allowedTypes[$extension])) {
        error_log('Upload failed because extension is not allowed: ' . $extension);
        throw new RuntimeException('Uploaded file type is not allowed.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '';
    if (!in_array($mime, (array) $allowedTypes[$extension], true)) {
        error_log('Upload failed because MIME type is not allowed. Extension: ' . $extension . '; MIME: ' . $mime);
        throw new RuntimeException('Uploaded file type is not allowed.');
    }

    $relativeDir = trim(str_replace('\\', '/', $targetDir), '/');
    if ($relativeDir === '') {
        error_log('Upload failed because target directory is empty.');
        throw new RuntimeException('Upload folder is invalid.');
    }

    $rootDir = dirname(__DIR__);
    $absoluteDir = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
        error_log('Upload failed because folder could not be created: ' . $absoluteDir);
        throw new RuntimeException('Upload folder is unavailable.');
    }

    if (!is_writable($absoluteDir)) {
        @chmod($absoluteDir, 0755);
    }

    if (!is_writable($absoluteDir)) {
        error_log('Upload failed because folder is not writable: ' . $absoluteDir);
        throw new RuntimeException('Upload folder is not writable.');
    }

    $safePrefix = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($prefix)) ?: 'upload';
    $filename = $safePrefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

    $stored = PHP_SAPI === 'cli' ? copy($tmpName, $destination) : move_uploaded_file($tmpName, $destination);
    if (!$stored) {
        error_log('Upload failed while moving file to: ' . $destination);
        throw new RuntimeException('Uploaded file could not be saved.');
    }

    return $relativeDir . '/' . $filename;
}

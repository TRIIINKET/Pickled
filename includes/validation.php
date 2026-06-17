<?php
declare(strict_types=1);

if (!function_exists('validateName')) {
    function validateName(mixed $value, bool $required = true): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            if (!$required) {
                return '';
            }
            throw new RuntimeException('Please enter a valid name.');
        }

        if (strlen($value) < 2 || strlen($value) > 80 || !preg_match("/^[A-Za-z][A-Za-z .'-]*$/", $value)) {
            throw new RuntimeException('Please enter a valid name.');
        }

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail(mixed $value, bool $required = true): string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            if (!$required) {
                return '';
            }
            throw new RuntimeException('Please enter a valid email address.');
        }

        if (strlen($value) > 150 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        return $value;
    }
}

if (!function_exists('validatePhonePH')) {
    function validatePhonePH(mixed $value, bool $required = true): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            if (!$required) {
                return '';
            }
            throw new RuntimeException('Please enter a valid Philippine mobile number.');
        }

        if (preg_match('/^9\d{9}$/', $value)) {
            return '0' . $value;
        }
        if (preg_match('/^09\d{9}$/', $value)) {
            return $value;
        }
        if (preg_match('/^\+639\d{9}$/', $value)) {
            return '0' . substr($value, 3);
        }
        if (preg_match('/^639\d{9}$/', $value)) {
            return '0' . substr($value, 2);
        }

        throw new RuntimeException('Please enter a valid Philippine mobile number.');
    }
}

if (!function_exists('formatPhonePH')) {
    function formatPhonePH(mixed $value): string
    {
        try {
            $phone = validatePhonePH($value, false);
        } catch (RuntimeException) {
            return trim((string) $value);
        }

        if ($phone === '') {
            return '';
        }

        return '+63 ' . substr($phone, 1, 3) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7, 4);
    }
}

if (!function_exists('validatePassword')) {
    function validatePassword(mixed $value): string
    {
        $value = (string) $value;
        if (strlen($value) < 8 || strlen($value) > 72 || !preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
            throw new RuntimeException('Password must be at least 8 characters and include letters and numbers.');
        }

        return $value;
    }
}

if (!function_exists('validateMoney')) {
    function validateMoney(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new RuntimeException('Please enter a valid amount.');
        }

        $amount = (float) $value;
        if ($amount < 0 || $amount > 999999.99) {
            throw new RuntimeException('Please enter a valid amount.');
        }

        return number_format($amount, 2, '.', '');
    }
}

if (!function_exists('validatePositiveInt')) {
    function validatePositiveInt(mixed $value, ?int $max = null, string $message = 'Please enter a valid number of players.'): int
    {
        $text = trim((string) $value);
        if ($text === '' || !preg_match('/^\d+$/', $text)) {
            throw new RuntimeException($message);
        }

        $number = (int) $text;
        if ($number < 1 || ($max !== null && $number > $max)) {
            throw new RuntimeException($message);
        }

        return $number;
    }
}

if (!function_exists('validateDate')) {
    function validateDate(mixed $value, bool $allowPast = true, string $message = 'Please select a valid date.'): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            throw new RuntimeException($message);
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Manila'));
        if (!$date || $date->format('Y-m-d') !== $value) {
            try {
                $date = new DateTimeImmutable($value, new DateTimeZone('Asia/Manila'));
            } catch (Throwable) {
                throw new RuntimeException($message);
            }
        }

        $date = $date->setTime(0, 0);
        $today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->setTime(0, 0);
        if (!$allowPast && $date < $today) {
            throw new RuntimeException($message);
        }

        return $date->format('Y-m-d');
    }
}

if (!function_exists('validateTime')) {
    function validateTime(mixed $start, mixed $end = null, ?string $date = null, string $message = 'Please select a valid time.'): string|array
    {
        $parse = static function (mixed $value) use ($message): DateTimeImmutable {
            $value = trim((string) $value);
            foreach (['H:i:s', 'H:i', 'g:i A', 'h:i A', 'g A', 'h A'] as $format) {
                $time = DateTimeImmutable::createFromFormat($format, $value, new DateTimeZone('Asia/Manila'));
                if ($time instanceof DateTimeImmutable) {
                    return $time;
                }
            }
            throw new RuntimeException($message);
        };

        $startTime = $parse($start);
        $open = $parse('08:00');
        $close = $parse('22:00');
        if ($startTime < $open || $startTime >= $close) {
            throw new RuntimeException($message);
        }

        if ($end === null) {
            return $startTime->format('H:i:s');
        }

        $endTime = $parse($end);
        if ($endTime <= $startTime || $endTime > $close) {
            throw new RuntimeException($message);
        }

        if ($date !== null && $date !== '') {
            $bookingDate = validateDate($date, true, $message);
            $startDateTime = new DateTimeImmutable($bookingDate . ' ' . $startTime->format('H:i:s'), new DateTimeZone('Asia/Manila'));
            if ($startDateTime <= new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'))) {
                throw new RuntimeException($message);
            }
        }

        return [$startTime->format('H:i:s'), $endTime->format('H:i:s')];
    }
}

if (!function_exists('validateText')) {
    function validateText(mixed $value, bool $required = false, int $max = 1000): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            if (!$required) {
                return '';
            }
            throw new RuntimeException('Please enter valid text.');
        }

        if (strlen($value) > $max || $value !== strip_tags($value) || preg_match('/<\s*script/i', $value)) {
            throw new RuntimeException('Please enter valid text.');
        }

        return $value;
    }
}

if (!function_exists('validateUploadedFile')) {
    function validateUploadedFile(array $file, string $kind = 'receipt', bool $required = true): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            if (!$required) {
                return [];
            }
            throw new RuntimeException($kind === 'avatar' ? 'Please choose a profile photo.' : 'Please choose a receipt file before submitting.');
        }
        if ($error !== UPLOAD_ERR_OK || trim((string) ($file['tmp_name'] ?? '')) === '') {
            throw new RuntimeException($kind === 'avatar' ? 'Please choose a valid profile photo.' : 'Please choose a receipt file before submitting.');
        }

        $kind = strtolower($kind);
        $allowed = $kind === 'avatar'
            ? ['jpg', 'jpeg', 'png', 'webp']
            : ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $max = $kind === 'avatar' ? 2 * 1024 * 1024 : 5 * 1024 * 1024;
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if ((int) ($file['size'] ?? 0) <= 0 || (int) ($file['size'] ?? 0) > $max) {
            throw new RuntimeException($kind === 'avatar' ? 'Profile photo must be 2MB or smaller.' : 'Receipt file must be 5MB or smaller.');
        }
        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException($kind === 'avatar' ? 'Profile photo must be JPG, JPEG, PNG, or WEBP.' : 'Receipt must be a JPG, JPEG, PNG, WEBP, or PDF file.');
        }

        return $file;
    }
}

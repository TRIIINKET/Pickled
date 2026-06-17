<?php
declare(strict_types=1);

function pickled_schedule_timezone(): DateTimeZone
{
    $config = require __DIR__ . '/config.php';
    return new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
}

function pickled_schedule_date_sql(string $date): string
{
    $date = trim($date);
    if ($date === '') {
        throw new RuntimeException('Date is required.');
    }

    try {
        return (new DateTimeImmutable($date, pickled_schedule_timezone()))->format('Y-m-d');
    } catch (Throwable) {
        throw new RuntimeException('Date is invalid.');
    }
}

function pickled_schedule_time_sql(string $time): string
{
    $time = trim($time);
    foreach (['H:i:s', 'H:i', 'g:i A', 'h:i A', 'g A', 'h A'] as $format) {
        $parsed = DateTimeImmutable::createFromFormat($format, $time, pickled_schedule_timezone());
        if ($parsed instanceof DateTimeImmutable) {
            return $parsed->format('H:i:s');
        }
    }

    throw new RuntimeException('Time is invalid.');
}

function pickled_schedule_start_at(string $date, string $startTime): DateTimeImmutable
{
    $timezone = pickled_schedule_timezone();
    return new DateTimeImmutable(
        pickled_schedule_date_sql($date) . ' ' . pickled_schedule_time_sql($startTime),
        $timezone
    );
}

function pickled_schedule_now(?DateTimeInterface $now = null): DateTimeImmutable
{
    $timezone = pickled_schedule_timezone();
    if ($now instanceof DateTimeImmutable) {
        return $now->setTimezone($timezone);
    }
    if ($now instanceof DateTimeInterface) {
        return (new DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone);
    }

    return new DateTimeImmutable('now', $timezone);
}

function pickled_schedule_starts_in_future(string $date, string $startTime, ?DateTimeInterface $now = null): bool
{
    return pickled_schedule_start_at($date, $startTime) > pickled_schedule_now($now);
}

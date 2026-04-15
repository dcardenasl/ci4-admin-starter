<?php

declare(strict_types=1);

namespace App\Support;

final class FileSizeLimits
{
    public const DEFAULT_MAX_BYTES = 10485760; // 10 MB

    public static function configuredMaxBytes(): int
    {
        $configured = (int) (config('Validation')->maxFileSizeBytes ?? self::DEFAULT_MAX_BYTES);

        return $configured > 0 ? $configured : self::DEFAULT_MAX_BYTES;
    }

    public static function phpUploadMaxBytes(): int
    {
        return self::parseIniSize(ini_get('upload_max_filesize'));
    }

    public static function phpPostMaxBytes(): int
    {
        return self::parseIniSize(ini_get('post_max_size'));
    }

    public static function effectiveMaxBytes(): int
    {
        $limits = array_filter([
            self::configuredMaxBytes(),
            self::phpUploadMaxBytes(),
            self::phpPostMaxBytes(),
        ], static fn (int $value): bool => $value > 0);

        return $limits === [] ? self::DEFAULT_MAX_BYTES : min($limits);
    }

    public static function bytesToMb(int $bytes): float
    {
        return round($bytes / 1024 / 1024, 1);
    }

    /**
     * Parse shorthand php.ini values like "2M", "8K", "1G".
     */
    public static function parseIniSize(string|int|float|false $value): int
    {
        if ($value === false) {
            return 0;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return 0;
        }

        if (is_numeric($normalized)) {
            $bytes = (int) $normalized;
            return $bytes > 0 ? $bytes : 0;
        }

        $unit = strtolower(substr($normalized, -1));
        $number = (float) substr($normalized, 0, -1);

        $multiplier = match ($unit) {
            'k' => 1024,
            'm' => 1024 * 1024,
            'g' => 1024 * 1024 * 1024,
            default => 1,
        };

        $bytes = (int) round($number * $multiplier);

        return $bytes > 0 ? $bytes : 0;
    }
}

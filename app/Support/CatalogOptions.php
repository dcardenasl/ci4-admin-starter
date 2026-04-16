<?php

declare(strict_types=1);

namespace App\Support;

final class CatalogOptions
{
    /**
     * @param array<string, mixed> $catalogs
     * @param array<int, array{value:string,label:string}> $fallback
     * @return array<int, array{value:string,label:string}>
     */
    public static function options(array $catalogs, string $path, array $fallback = []): array
    {
        $node = self::valueAtPath($catalogs, $path);
        if (! is_array($node) || $node === []) {
            return $fallback;
        }

        $normalized = [];
        foreach ($node as $raw) {
            if (! is_array($raw)) {
                if (is_scalar($raw)) {
                    $value = trim((string) $raw);
                    if ($value !== '') {
                        $normalized[] = ['value' => $value, 'label' => $value];
                    }
                }

                continue;
            }

            $value = trim((string) ($raw['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $label = trim((string) ($raw['label'] ?? ''));
            $labelKey = trim((string) ($raw['label_key'] ?? ''));

            if ($label === '' && $labelKey !== '') {
                $label = lang($labelKey);
            }

            if ($label === '') {
                $label = $value;
            }

            $count = isset($raw['count']) && is_numeric($raw['count']) ? (int) $raw['count'] : null;
            if ($count !== null && $count > 0) {
                $label .= ' (' . $count . ')';
            }

            $normalized[] = ['value' => $value, 'label' => $label];
        }

        return $normalized === [] ? $fallback : $normalized;
    }

    /**
     * @param array<string, mixed> $catalogs
     * @param array<int, int> $fallback
     * @return array<int, int>
     */
    public static function limitOptions(array $catalogs, array $fallback = [10, 25, 50, 100]): array
    {
        $node = self::valueAtPath($catalogs, 'pagination.limit_options');
        if (! is_array($node) || $node === []) {
            return $fallback;
        }

        $options = [];
        foreach ($node as $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $normalized = (int) $value;
            if ($normalized > 0) {
                $options[] = $normalized;
            }
        }

        $options = array_values(array_unique($options));
        sort($options);

        return $options === [] ? $fallback : $options;
    }

    /**
     * @param array<string, mixed> $catalogs
     */
    private static function valueAtPath(array $catalogs, string $path): mixed
    {
        $value = $catalogs;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

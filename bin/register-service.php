#!/usr/bin/env php
<?php

/**
 * Auto-register a module service in app/Config/Services.php.
 *
 * Usage:
 *   php bin/register-service.php <Module> <ServiceClass> <ServiceInterface> <ServiceKey>
 *
 * Example:
 *   php bin/register-service.php Catalog ProductApiService ProductApiServiceInterface productApiService
 */

declare(strict_types=1);

if ($argc < 5) {
    echo "Usage: php bin/register-service.php <Module> <ServiceClass> <ServiceInterface> <ServiceKey>\n";
    exit(1);
}

[, $module, $serviceClass, $serviceInterface, $serviceKey] = $argv;

$servicesFile = __DIR__ . '/../app/Config/Services.php';

if (! file_exists($servicesFile)) {
    echo "ERROR: Services.php not found at {$servicesFile}\n";
    exit(2);
}

$content = file_get_contents($servicesFile);

$expectedFqcn = "App\\Modules\\{$module}\\Services\\{$serviceInterface}";

// Idempotency: skip only when an existing factory points to the SAME FQCN.
// Matching purely on factory name is unsafe — two resources from different
// modules can map to the same camelCase key (e.g. APIKey/ApiKeys both yield
// 'apiKeyApiService') and silently mis-wire the new controller to the wrong
// module's service.
if (preg_match('/function\s+' . preg_quote($serviceKey, '/') . '\s*\([^)]*\)\s*:\s*([\\\\A-Za-z0-9_]+)/', $content, $m) === 1) {
    $existingShortType = $m[1];
    $existingFqcn      = resolveFqcn($content, $existingShortType);

    if ($existingFqcn === ltrim($expectedFqcn, '\\')) {
        echo "SKIP: {$serviceKey} already registered in Services.php\n";
        exit(0);
    }

    fwrite(STDERR, sprintf(
        "ERROR: factory '%s' is already registered for '%s', refusing to overwrite with '%s'.\n"
        . "Pick a different resource name or remove the conflicting registration first.\n",
        $serviceKey,
        $existingFqcn ?? $existingShortType,
        $expectedFqcn,
    ));
    exit(4);
}

/**
 * Resolve a short class name to its FQCN by inspecting the file's `use` block.
 * Falls back to null when the alias cannot be resolved.
 */
function resolveFqcn(string $content, string $shortType): ?string
{
    $shortType = ltrim($shortType, '\\');

    if (str_contains($shortType, '\\')) {
        return $shortType;
    }

    $pattern = '/^use\s+([A-Za-z0-9_\\\\]+)(?:\s+as\s+([A-Za-z0-9_]+))?\s*;/m';
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) > 0) {
        foreach ($matches as $match) {
            $fqcn  = $match[1];
            $alias = $match[2] ?? null;

            if ($alias !== null && $alias === $shortType) {
                return $fqcn;
            }

            if ($alias === null) {
                $segments = explode('\\', $fqcn);
                if (end($segments) === $shortType) {
                    return $fqcn;
                }
            }
        }
    }

    return null;
}

// ─── 1. Inject use statements ────────────────────────────────────────────────
//
// Strategy: locate the last `use ` line and splice the two new ones immediately
// after it. This preserves blank-line separators between namespace groups, the
// existing ordering, and any inline comments — non-destructive by design.

$useClass = "use App\\Modules\\{$module}\\Services\\{$serviceClass};";
$useIface = "use App\\Modules\\{$module}\\Services\\{$serviceInterface};";

if (! str_contains($content, $useClass)) {
    $lines        = explode("\n", $content);
    $lastUseIndex = null;

    foreach ($lines as $i => $line) {
        if (str_starts_with(trim($line), 'use ')) {
            $lastUseIndex = $i;
        }
    }

    if ($lastUseIndex !== null) {
        array_splice($lines, $lastUseIndex + 1, 0, [$useClass, $useIface]);
        $content = implode("\n", $lines);
    }
    // If no use lines were found at all, the method injection below still runs
    // and the developer sees the manual instructions in make-module.sh's summary.
}

// ─── 2. Inject the service method before the closing } ───────────────────────

$method = <<<PHP

    public static function {$serviceKey}(bool \$getShared = true): {$serviceInterface}
    {
        if (\$getShared) {
            /** @var {$serviceClass} */
            return static::getSharedInstance('{$serviceKey}');
        }

        return new {$serviceClass}(static::apiClient());
    }
PHP;

// Find the last closing brace of the class
$lastBrace = strrpos($content, "\n}");
if ($lastBrace === false) {
    echo "ERROR: Could not locate closing brace of Services class\n";
    exit(3);
}

$content = substr($content, 0, $lastBrace) . $method . "\n}" . substr($content, $lastBrace + 2);

file_put_contents($servicesFile, $content);
echo "OK: {$serviceKey} registered in Services.php\n";

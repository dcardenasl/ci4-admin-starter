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

// Idempotency: skip if already registered
if (str_contains($content, "function {$serviceKey}(")) {
    echo "SKIP: {$serviceKey} already registered in Services.php\n";
    exit(0);
}

// ─── 1. Inject use statements ────────────────────────────────────────────────

$useClass = "use App\\Modules\\{$module}\\Services\\{$serviceClass};";
$useIface = "use App\\Modules\\{$module}\\Services\\{$serviceInterface};";

if (! str_contains($content, $useClass)) {
    // Insert the two use lines in alphabetical order before the first non-App use, or before class declaration
    $lines = explode("\n", $content);
    $lastUseIndex = null;
    foreach ($lines as $i => $line) {
        if (str_starts_with(trim($line), 'use ')) {
            $lastUseIndex = $i;
        }
    }

    if ($lastUseIndex !== null) {
        // Collect all use lines, add new ones, sort, deduplicate, put back
        $useLines = [];
        $otherLines = [];
        $inUseBlock = false;
        $firstUseIndex = null;

        foreach ($lines as $i => $line) {
            if (str_starts_with(trim($line), 'use ') && ! str_contains($line, 'use \\')) {
                if ($firstUseIndex === null) {
                    $firstUseIndex = $i;
                }
                $useLines[] = trim($line);
            }
        }

        $useLines[] = $useClass;
        $useLines[] = $useIface;
        $useLines = array_unique($useLines);
        sort($useLines);

        // Rebuild: replace the use block
        $rebuilt = [];
        $usePlaced = false;
        foreach ($lines as $i => $line) {
            if (str_starts_with(trim($line), 'use ') && ! $usePlaced) {
                if (! $usePlaced) {
                    foreach ($useLines as $ul) {
                        $rebuilt[] = $ul;
                    }
                    $usePlaced = true;
                }
                // Skip original use lines (we've replaced them)
            } elseif (str_starts_with(trim($line), 'use ') && $usePlaced) {
                // skip duplicate original use lines
            } else {
                $rebuilt[] = $line;
            }
        }
        $content = implode("\n", $rebuilt);
    }
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

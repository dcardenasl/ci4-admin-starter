#!/usr/bin/env php
<?php

/**
 * Auto-register a module service in app/Config/Services.php.
 *
 * Usage:
 *   php bin/register-service.php <Module> <ServiceClass> <ServiceInterface> <ServiceKey> [--client=hub|domain|event-domain]
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

$client = 'hub';
$positional = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--client=')) {
        $client = substr($arg, strlen('--client='));
    } else {
        $positional[] = $arg;
    }
}

if (count($positional) < 4) {
    echo "Usage: php bin/register-service.php <Module> <ServiceClass> <ServiceInterface> <ServiceKey> [--client=hub|domain|event-domain]\n";
    exit(1);
}

if (! in_array($client, ['hub', 'domain', 'event-domain'], true)) {
    fwrite(STDERR, "ERROR: --client must be 'hub', 'domain' or 'event-domain', got '{$client}'\n");
    exit(1);
}

[$module, $serviceClass, $serviceInterface, $serviceKey] = $positional;

$clientFactory = match ($client) {
    'domain' => 'domainApiClient',
    'event-domain' => 'eventDomainApiClient',
    default => 'apiClient',
};

$servicesFile = __DIR__ . '/../app/Config/Services.php';

if (! file_exists($servicesFile)) {
    fwrite(STDERR, "ERROR: Services.php not found at {$servicesFile}\n");
    exit(2);
}

$content = file_get_contents($servicesFile);

$expectedFqcn = "App\\Modules\\{$module}\\Services\\{$serviceInterface}";

// Idempotency: skip only when an existing factory points to the SAME FQCN.
if (preg_match(
    '/public\s+static\s+function\s+' . preg_quote($serviceKey, '/') . '\s*\([^)]*\)\s*:\s*([\\\\A-Za-z0-9_]+)\s*\{[^}]*?return new[^(]+\(static::(apiClient|domainApiClient|eventDomainApiClient)\(\)\)/s',
    $content,
    $m,
) === 1) {
    $existingShortType    = $m[1];
    $existingClientFactory = $m[2];
    $existingFqcn         = resolveFqcn($content, $existingShortType);

    if ($existingFqcn === ltrim($expectedFqcn, '\\') && $existingClientFactory === $clientFactory) {
        echo "SKIP: {$serviceKey} already registered in Services.php\n";
        exit(0);
    }

    if ($existingFqcn === ltrim($expectedFqcn, '\\') && $existingClientFactory !== $clientFactory) {
        fwrite(STDERR, sprintf(
            "ERROR: factory '%s' is already registered wired to '%s()' but the new registration requested '%s()'.\n"
            . "Remove the existing factory first (or rerun with the matching --client flag).\n",
            $serviceKey,
            $existingClientFactory,
            $clientFactory,
        ));
        exit(5);
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

if (preg_match('/public\s+static\s+function\s+' . preg_quote($serviceKey, '/') . '\s*\([^)]*\)\s*:\s*([\\\\A-Za-z0-9_]+)/', $content, $m) === 1) {
    $existingShortType = $m[1];
    $existingFqcn      = resolveFqcn($content, $existingShortType);

    if ($existingFqcn === ltrim($expectedFqcn, '\\')) {
        echo "SKIP: {$serviceKey} already registered in Services.php (custom body)\n";
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

// ─── AST Mutation using nikic/php-parser ─────────────────────────────────────

$parser = (new ParserFactory())->createForHostVersion();

try {
    $origStmts = $parser->parse($content);
    $origTokens = $parser->getTokens();
} catch (Error $e) {
    fwrite(STDERR, "ERROR: Failed to parse Services.php: {$e->getMessage()}\n");
    exit(3);
}

if ($origStmts === null) {
    fwrite(STDERR, "ERROR: Services.php was parsed as empty\n");
    exit(3);
}

$traverser = new NodeTraverser(new CloningVisitor());
$newStmts = $traverser->traverse($origStmts);

$finder = new NodeFinder();

// Find the Namespace node
$ns = $finder->findFirstInstanceOf($newStmts, Node\Stmt\Namespace_::class);
if ($ns === null) {
    fwrite(STDERR, "ERROR: Could not locate Namespace in Services.php\n");
    exit(3);
}

// Find the Class Services node
$class = $finder->findFirstInstanceOf($ns->stmts, Node\Stmt\Class_::class);
if ($class === null || $class->name?->name !== 'Services') {
    fwrite(STDERR, "ERROR: Could not locate 'class Services' in Services.php\n");
    exit(3);
}

// 1. Inject use statements if not already present
$useClassFqcn = "App\\Modules\\{$module}\\Services\\{$serviceClass}";
$useIfaceFqcn = "App\\Modules\\{$module}\\Services\\{$serviceInterface}";

$hasUseClass = false;
$hasUseIface = false;

$lastUseIndex = -1;
foreach ($ns->stmts as $i => $stmt) {
    if ($stmt instanceof Node\Stmt\Use_) {
        $lastUseIndex = $i;
        foreach ($stmt->uses as $use) {
            if ($use->name->toString() === $useClassFqcn) {
                $hasUseClass = true;
            }
            if ($use->name->toString() === $useIfaceFqcn) {
                $hasUseIface = true;
            }
        }
    }
}

$newUses = [];
if (!$hasUseClass) {
    $newUses[] = new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name($useClassFqcn))]);
}
if (!$hasUseIface) {
    $newUses[] = new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name($useIfaceFqcn))]);
}

if (count($newUses) > 0) {
    if ($lastUseIndex >= 0) {
        array_splice($ns->stmts, $lastUseIndex + 1, 0, $newUses);
    } else {
        // Prepend before the class statement
        $classPos = 0;
        foreach ($ns->stmts as $i => $stmt) {
            if ($stmt instanceof Node\Stmt\Class_) {
                $classPos = $i;
                break;
            }
        }
        array_splice($ns->stmts, $classPos, 0, $newUses);
    }
}

// 2. Inject class method
$methodCode = <<<PHP
<?php
class __Tmp {
    public static function {$serviceKey}(bool \$getShared = true): {$serviceInterface}
    {
        if (\$getShared) {
            /** @var {$serviceClass} */
            return static::getSharedInstance('{$serviceKey}');
        }

        return new {$serviceClass}(static::{$clientFactory}());
    }
}
PHP;

try {
    $tmpStmts = $parser->parse($methodCode);
    $tmpClass = $finder->findFirstInstanceOf($tmpStmts, Node\Stmt\Class_::class);
    $methodNode = $finder->findFirstInstanceOf($tmpClass->stmts, Node\Stmt\ClassMethod::class);

    if ($methodNode === null) {
        fwrite(STDERR, "ERROR: Could not construct class method AST node\n");
        exit(3);
    }

    $class->stmts[] = $methodNode;
} catch (\Throwable $t) {
    fwrite(STDERR, "ERROR: Failed to construct AST nodes: {$t->getMessage()}\n");
    exit(3);
}

// Print and write back
$printer = new Standard();
$editedContent = $printer->printFormatPreserving($newStmts, $origStmts, $origTokens);

file_put_contents($servicesFile, $editedContent);
echo "OK: {$serviceKey} registered in Services.php via AST\n";

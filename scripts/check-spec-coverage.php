<?php

declare(strict_types=1);

/**
 * Fails (exit 1) when an operation in spec/openapi.json has no SDK method.
 * Usage: php scripts/check-spec-coverage.php
 */

use StampMitra\Tests\Support\SpecCoverage;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$spec = SpecCoverage::specOperations($root);
$missing = SpecCoverage::missing($root);

if ($missing !== []) {
    fwrite(STDERR, "Spec operations without an SDK method:\n  " . implode("\n  ", $missing) . "\n");
    exit(1);
}

echo 'OK: all ' . count($spec) . " spec operations are covered by the SDK.\n";

<?php
declare(strict_types=1);

/**
 * Test runner — no external dependencies.
 * Usage:  php tests/run.php
 */

require __DIR__ . '/bootstrap.php';

$suites = [
    'DetectionEngine + SOC + Scoring' => __DIR__ . '/DetectionEngineTest.php',
    'Models + SQL (SQLite)'           => __DIR__ . '/ModelTest.php',
];

foreach ($suites as $name => $file) {
    fwrite(STDOUT, "\n\033[36m# {$name}\033[0m\n");
    $test = require $file;
    $test();
}

exit(T::summary());

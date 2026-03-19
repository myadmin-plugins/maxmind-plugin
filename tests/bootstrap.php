<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file for the myadmin-maxmind-plugin test suite.
 *
 * Attempts to locate the Composer autoloader from either:
 *   1. The package's own vendor directory (standalone install)
 *   2. The parent project's vendor directory (installed as a dependency)
 */

$autoloaders = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

$loaded = false;
foreach ($autoloaders as $autoloader) {
    if (file_exists($autoloader)) {
        require_once $autoloader;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    fwrite(STDERR, "Could not find Composer autoloader. Run 'composer install' first.\n");
    exit(1);
}

// Stub for myadmin_unstringify used by maxmind_decode in the helpers file.
if (!function_exists('myadmin_unstringify')) {
    /**
     * @param string $data
     * @return array|null
     */
    function myadmin_unstringify(string $data)
    {
        return json_decode($data, true);
    }
}

// Load extracted pure functions for testing.
require_once __DIR__ . '/helpers/maxmind_functions.php';

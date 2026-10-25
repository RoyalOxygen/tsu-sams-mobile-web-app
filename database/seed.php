<?php
declare(strict_types=1);

/**
 * TSU-SAMS CLI sample-data seeder.
 *
 * Usage:
 *   php database/seed.php                 # seed reference + demo data
 *   php database/seed.php --no-face       # skip the demo face template
 *   php database/seed.php --fresh         # create schema first if missing
 *
 * The seeder is idempotent: running it repeatedly will not duplicate rows.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This seeder must be run from the command line.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Installer;
use App\Services\Seeder;

$args = array_slice($argv, 1);
$withFace = !in_array('--no-face', $args, true);

echo "TSU-SAMS seeder starting...\n";

Installer::ensure();

$stats = Seeder::run($withFace);

echo "Seed complete. Inserted rows:\n";
foreach ($stats as $key => $value) {
    printf("  %-16s %d\n", $key, $value);
}
echo "Done.\n";

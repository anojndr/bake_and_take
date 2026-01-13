<?php
/**
 * Run All Database Updates
 *
 * Runs the incremental schema updater and then performs cleanup to remove
 * legacy/unused columns and redundant indexes.
 *
 * Usage (CLI):
 *   php database/run_update_all.php
 */

$php = escapeshellarg(PHP_BINARY);

function runScript(string $php, string $scriptPath): int {
    $script = escapeshellarg($scriptPath);
    echo "\n============================================================\n";
    echo "Running: " . basename($scriptPath) . "\n";
    echo "============================================================\n\n";

    passthru("{$php} {$script}", $exitCode);
    return (int)$exitCode;
}

$baseDir = __DIR__;

$exit1 = runScript($php, $baseDir . DIRECTORY_SEPARATOR . 'run_schema_update.php');
if ($exit1 !== 0) {
    echo "\nAborting: run_schema_update.php failed (exit {$exit1}).\n";
    exit($exit1);
}

$exit2 = runScript($php, $baseDir . DIRECTORY_SEPARATOR . 'run_cleanup_all.php');
if ($exit2 !== 0) {
    echo "\nAborting: run_cleanup_all.php failed (exit {$exit2}).\n";
    exit($exit2);
}

echo "\n✓ All database updates completed successfully.\n";
exit(0);

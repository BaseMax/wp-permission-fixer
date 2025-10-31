<?php
/**
 * wp-permission-fixer.php
 * https://github.com/BaseMax/wp-permission-fixer
 *
 * Safely reset WordPress file and directory permissions.
 * Works in both CLI and Web (HTTP) modes.
 *
 * @license MIT
 * @copyright 2025 Seyyed Ali Mohammadiyeh (Max Base)
 */

set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = __DIR__;
$isCli = (php_sapi_name() === 'cli');

$args = [];
if ($isCli && isset($argv) && is_array($argv)) {
    $args = $argv;
} else {
    $args = [];
}

function logMessage($message)
{
    global $isCli;
    if ($isCli) {
        echo $message . PHP_EOL;
    } else {
        echo nl2br(htmlentities($message)) . "<br>";
        @ob_flush();
        @flush();
    }
}

function setPermissionsRecursively($dir)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();

        if (in_array(basename($path), ['.git', '.svn', 'node_modules'])) {
            continue;
        }

        if ($item->isDir()) {
            @chmod($path, 0755);
        } else {
            @chmod($path, 0644);
        }
    }
}

logMessage("🔧 Starting WordPress Permission Fixer...");

$dryRun = in_array('--dry-run', $args, true);
if ($dryRun) {
    logMessage("🧪 Running in DRY-RUN mode (no actual chmods).");
}

logMessage("📂 Target directory: {$root}");

if (!$dryRun) {
    setPermissionsRecursively($root);
}

$specialFiles = [
    'wp-config.php' => 0600,
    '.htaccess'     => 0644,
    'index.php'     => 0644,
];

foreach ($specialFiles as $file => $mode) {
    $path = $root . DIRECTORY_SEPARATOR . $file;
    if (file_exists($path)) {
        if (!$dryRun) {
            @chmod($path, $mode);
        }
        logMessage("   → {$file} set to " . decoct($mode));
    }
}

logMessage("✅ Permissions fixed successfully!");
logMessage("   Directories: 755 | Files: 644 | wp-config.php: 600");
logMessage("   Run with --dry-run to preview changes (CLI only).");

if (!$isCli) {
    logMessage("<hr><strong>Executed via Web Interface</strong><br>");
    logMessage("For advanced usage, run via CLI:");
    logMessage("<code>php wp-permission-fixer.php --dry-run</code>");
}

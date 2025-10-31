<?php
/**
 * wp-permission-fixer.php
 * https://github.com/BaseMax/wp-permission-fixer
 * 
 * Safely reset WordPress file and directory permissions.
 * 
 * ✅ Cross-platform (Linux, macOS, Windows)
 * ⚙️ Directories: 755 | Files: 644 | wp-config.php: 600
 * 🧰 Includes dry-run, optional ownership fix, progress display, and statistics
 * 
 * Usage:
 *   php fix-permissions.php
 *   php fix-permissions.php --dry-run
 *   php fix-permissions.php --chown=www-data:www-data
 */

set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ------------------------------------------------------------
// Parse CLI arguments
// ------------------------------------------------------------
$dryRun = in_array('--dry-run', $argv);
$chownArg = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--chown=')) {
        $chownArg = substr($arg, 8);
    }
}

$root = realpath(__DIR__);
if (!$root || !file_exists("$root/wp-config.php")) {
    fwrite(STDERR, "❌  Error: Must be placed in your WordPress root directory.\n");
    exit(1);
}

// ------------------------------------------------------------
// Global counters
// ------------------------------------------------------------
$stats = [
    'dirsFixed'   => 0,
    'filesFixed'  => 0,
    'skipped'     => 0,
    'errors'      => 0,
];
$changes = [];
$errors = [];
$startTime = microtime(true);

// ------------------------------------------------------------
// Helper: Terminal color (optional)
// ------------------------------------------------------------
function color($text, $color = 'reset')
{
    static $colors = [
        'red'    => "\033[31m",
        'green'  => "\033[32m",
        'yellow' => "\033[33m",
        'blue'   => "\033[34m",
        'reset'  => "\033[0m",
    ];
    return (PHP_SAPI === 'cli') ? $colors[$color] . $text . $colors['reset'] : $text;
}

// ------------------------------------------------------------
// Helper: Recursively fix permissions
// ------------------------------------------------------------
function fixPermissions($dir, $dryRun, &$stats, &$changes, &$errors, $excludedDirs)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();

        // Skip excluded or symbolic links
        if (is_link($path)) {
            $stats['skipped']++;
            continue;
        }

        foreach ($excludedDirs as $skip) {
            if (str_contains($path, DIRECTORY_SEPARATOR . $skip . DIRECTORY_SEPARATOR)) {
                $stats['skipped']++;
                continue 2;
            }
        }

        $desiredMode = $item->isDir() ? 0755 : 0644;
        $currentMode = fileperms($path) & 0777;

        if ($currentMode !== $desiredMode) {
            if (!$dryRun) {
                if (!@chmod($path, $desiredMode)) {
                    $errors[] = "Failed to chmod: $path";
                    $stats['errors']++;
                    continue;
                }
            }
            $changes[] = [
                'path' => $path,
                'from' => decoct($currentMode),
                'to'   => decoct($desiredMode)
            ];
            $item->isDir() ? $stats['dirsFixed']++ : $stats['filesFixed']++;
        }
    }
}

// ------------------------------------------------------------
// Helper: Fix special WordPress files
// ------------------------------------------------------------
function fixSpecialFiles($root, $dryRun, &$stats, &$changes, &$errors)
{
    $special = [
        'wp-config.php' => 0600,
        '.htaccess'     => 0644,
        'index.php'     => 0644,
    ];
    foreach ($special as $file => $mode) {
        $path = "$root/$file";
        if (file_exists($path) && !is_link($path)) {
            $perm = fileperms($path) & 0777;
            if ($perm !== $mode) {
                if (!$dryRun) {
                    if (!@chmod($path, $mode)) {
                        $errors[] = "Failed to chmod: $path";
                        $stats['errors']++;
                        continue;
                    }
                }
                $changes[] = [
                    'path' => $path,
                    'from' => decoct($perm),
                    'to'   => decoct($mode)
                ];
            }
        }
    }
}

// ------------------------------------------------------------
// Helper: Change ownership if requested
// ------------------------------------------------------------
function applyChown($root, $chownArg, $dryRun, &$errors)
{
    if (!$chownArg) return;

    [$user, $group] = array_pad(explode(':', $chownArg, 2), 2, $chownArg);
    echo color("🔑 Applying ownership to $user:$group ...\n", 'yellow');

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();
        if (is_link($path)) continue;
        if (!$dryRun) {
            if (!@chown($path, $user) || ($group && !@chgrp($path, $group))) {
                $errors[] = "Failed to chown: $path";
            }
        }
    }
    if (!$dryRun) {
        echo color("✅ Ownership updated successfully.\n", 'green');
    }
}

// ------------------------------------------------------------
// Execution
// ------------------------------------------------------------
echo color("🔧 Starting WordPress permission fixer\n", 'blue');
echo "📁 Root: $root\n";
if ($dryRun) echo color("🧪 Dry-run mode enabled (no actual changes)\n", 'yellow');
if ($chownArg) echo color("⚙️ Ownership fix enabled for: $chownArg\n", 'yellow');

// Skip typical transient or system directories
$excludedDirs = ['.git', 'node_modules', 'vendor', 'cache', 'tmp', 'log'];

fixPermissions($root, $dryRun, $stats, $changes, $errors, $excludedDirs);
fixSpecialFiles($root, $dryRun, $stats, $changes, $errors);
if ($chownArg) applyChown($root, $chownArg, $dryRun, $errors);

$duration = round(microtime(true) - $startTime, 2);
$serverUser = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] ?? 'unknown' : 'unknown';
echo "👤 Running as user: $serverUser\n";

// ------------------------------------------------------------
// Summary Output
// ------------------------------------------------------------
echo "\n" . color("📊 Summary\n", 'blue');
echo "   🗂️  Directories fixed: {$stats['dirsFixed']}\n";
echo "   📄  Files fixed: {$stats['filesFixed']}\n";
echo "   🚫  Skipped: {$stats['skipped']}\n";
echo "   ⚠️  Errors: {$stats['errors']}\n";
echo "   ⏱️  Time: {$duration}s\n\n";

if (!empty($changes)) {
    echo color("🔍 Changed items:\n", 'blue');
    foreach ($changes as $change) {
        echo "   → {$change['path']} ({$change['from']} → {$change['to']})\n";
    }
}

if (!empty($errors)) {
    echo color("\n⚠️  Errors encountered:\n", 'red');
    foreach ($errors as $err) echo "   - $err\n";
}

if ($dryRun) {
    echo color("\n🧪 Dry-run complete. No changes were made.\n", 'yellow');
} else {
    echo color("\n✅ Permissions successfully fixed.\n", 'green');
    echo "   Directories: 755 | Files: 644 | wp-config.php: 600\n";
    echo color("💡 Delete this script after use for better security.\n", 'yellow');
}

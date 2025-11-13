<?php
// Handle GitHub webhook redirects
if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'GitHub-Hookshot') !== false) {
    http_response_code(200);
    header('Content-Type: text/plain');
}

/**
 * GitHub Auto-Deploy Script
 *
 * This script automatically pulls the latest changes from GitHub
 * and updates your WordPress plugin.
 *
 * SETUP INSTRUCTIONS:
 * 1. Upload this file to your WordPress root directory
 * 2. Set a secret key below (change 'your-secret-key-here')
 * 3. Create a GitHub webhook:
 *    - Go to your repo Settings → Webhooks → Add webhook
 *    - Payload URL: https://yoursite.com/deploy.php?secret=your-secret-key-here
 *    - Content type: application/json
 *    - Events: Just the push event
 * 4. Push to your branch and it will auto-deploy!
 */

// ============================================
// CONFIGURATION - CHANGE THESE VALUES
// ============================================

// Secret key for security (change this!)
define('DEPLOY_SECRET', '2K@LIspera1821');

// Your GitHub repository
define('GITHUB_REPO', 'ioanniskon12/weather_app');

// Branch to deploy
define('GITHUB_BRANCH', 'claude/wordpress-plugin-creation-011CUx8gR5uvBA93cZiAZmjk');

// Path to your plugin directory (relative to WordPress root)
define('PLUGIN_PATH', 'wp-content/plugins/woocommerce-shop-crm');

// Log file location
define('LOG_FILE', __DIR__ . '/deploy.log');

// ============================================
// DO NOT EDIT BELOW THIS LINE
// ============================================

// Check if secret is provided
if (!isset($_GET['secret']) || $_GET['secret'] !== DEPLOY_SECRET) {
    http_response_code(403);
    die('Forbidden: Invalid secret key');
}

// Function to log messages
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    echo $logEntry;
}

// Start deployment
logMessage("=== Deployment Started ===");

// Get the WordPress root directory
$wp_root = __DIR__;
$plugin_dir = $wp_root . '/' . PLUGIN_PATH;

logMessage("WordPress Root: {$wp_root}");
logMessage("Plugin Directory: {$plugin_dir}");

// Check if plugin directory exists
if (!is_dir($plugin_dir)) {
    logMessage("Creating plugin directory...");
    if (!mkdir($plugin_dir, 0755, true)) {
        logMessage("ERROR: Failed to create plugin directory");
        http_response_code(500);
        die("Failed to create plugin directory");
    }
}

// Change to plugin directory
chdir($plugin_dir);
logMessage("Changed to plugin directory");

// Check if it's a git repository
if (!is_dir('.git')) {
    logMessage("Initializing Git repository...");

    // Initialize git and add remote
    exec('git init 2>&1', $output, $return);
    logMessage("Git init: " . implode("\n", $output));

    exec('git remote add origin https://github.com/' . GITHUB_REPO . '.git 2>&1', $output, $return);
    logMessage("Added remote origin");

    exec('git fetch origin 2>&1', $output, $return);
    logMessage("Fetched from origin");

    exec('git checkout -b ' . GITHUB_BRANCH . ' origin/' . GITHUB_BRANCH . ' 2>&1', $output, $return);
    logMessage("Checked out branch: " . GITHUB_BRANCH);
} else {
    logMessage("Git repository exists, pulling latest changes...");

    // Fetch latest changes
    exec('git fetch origin 2>&1', $output, $return);
    logMessage("Fetch output: " . implode("\n", $output));

    // Reset to latest commit (overwrite any local changes)
    exec('git reset --hard origin/' . GITHUB_BRANCH . ' 2>&1', $output, $return);
    logMessage("Reset output: " . implode("\n", $output));

    // Clean untracked files
    exec('git clean -fd 2>&1', $output, $return);
    logMessage("Clean output: " . implode("\n", $output));
}

// Get current commit hash
exec('git rev-parse HEAD 2>&1', $output, $return);
$commit_hash = isset($output[0]) ? substr($output[0], 0, 7) : 'unknown';
logMessage("Deployed commit: {$commit_hash}");

// Clear any WordPress caches (if using cache plugins)
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    logMessage("WordPress cache flushed");
}

logMessage("=== Deployment Completed Successfully ===");

// Return success response
http_response_code(200);
echo "\nDeployment successful! Commit: {$commit_hash}";

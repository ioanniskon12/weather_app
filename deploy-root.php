<?php
/**
 * Root deployment script for WooCommerce Shop CRM
 * Place this file in your website ROOT directory (same level as wp-config.php)
 */

// Configuration
define('PLUGIN_DIR', __DIR__ . '/wp-content/plugins/woocommerce-shop-crm');
define('GITHUB_REPO', 'https://github.com/ioanniskon12/woocommerce-shop-crm.git');
define('GITHUB_BRANCH', 'claude/woocommerce-crm-final-updates-011CV5rtKaWmdVvCkhqodDWH');
define('DEPLOY_KEY', 'deploy123'); // Change this!
define('WEBHOOK_SECRET', 'your_webhook_secret_here'); // Set this to match GitHub webhook

// Check authorization
$is_manual = isset($_GET['deploy']) && $_GET['deploy'] === DEPLOY_KEY;
$is_webhook = $_SERVER['REQUEST_METHOD'] === 'POST';

if (!$is_manual && !$is_webhook) {
    http_response_code(403);
    die('Access denied');
}

// For webhook, verify signature
if ($is_webhook && WEBHOOK_SECRET !== 'your_webhook_secret_here') {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

    if (!hash_equals($expected, $signature)) {
        http_response_code(403);
        die('Invalid signature');
    }
}

echo "<h2>🚀 Deploying WooCommerce Shop CRM...</h2>";

// Change to plugin directory
if (!is_dir(PLUGIN_DIR)) {
    mkdir(PLUGIN_DIR, 0755, true);
    echo "<p>✓ Created plugin directory</p>";
}

chdir(PLUGIN_DIR);

// Check if git repo exists
if (!is_dir('.git')) {
    echo "<p>→ Initializing new Git repository...</p>";
    exec('git init 2>&1', $output);
    exec('git remote add origin ' . GITHUB_REPO . ' 2>&1', $output);
    echo "<p>✓ Git initialized</p>";
}

// Fetch latest changes
echo "<p>→ Fetching latest changes...</p>";
exec('git fetch origin ' . GITHUB_BRANCH . ' 2>&1', $output, $return);
if ($return !== 0) {
    echo "<p>❌ Fetch failed:</p><pre>" . implode("\n", $output) . "</pre>";
    exit;
}
echo "<p>✓ Fetched from GitHub</p>";

// Checkout the branch
echo "<p>→ Checking out branch...</p>";
exec('git checkout -B ' . GITHUB_BRANCH . ' origin/' . GITHUB_BRANCH . ' 2>&1', $output, $return);
if ($return !== 0) {
    echo "<p>❌ Checkout failed:</p><pre>" . implode("\n", $output) . "</pre>";
    exit;
}
echo "<p>✓ Checked out " . GITHUB_BRANCH . "</p>";

// Get current commit
$commit = trim(shell_exec('git rev-parse --short HEAD'));
$commitMsg = trim(shell_exec('git log -1 --pretty=%B'));

echo "<h3>✅ Deployment Successful!</h3>";
echo "<p><strong>Current commit:</strong> {$commit}</p>";
echo "<p><strong>Message:</strong> {$commitMsg}</p>";
echo "<p><a href='/wp-admin/plugins.php'>→ Go to WordPress Plugins</a></p>";
?>

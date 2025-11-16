<?php
/**
 * Auto-deployment script for WooCommerce Shop CRM
 *
 * This script pulls the latest changes from GitHub when triggered by a webhook
 * or when accessed manually with the correct deploy key.
 */

// Configuration
define('GITHUB_REPO', 'ioanniskon12/woocommerce-shop-crm');
define('GITHUB_BRANCH', 'claude/woocommerce-crm-final-updates-011CV5rtKaWmdVvCkhqodDWH');
define('WEBHOOK_SECRET', 'your_webhook_secret_here'); // Set this to match your GitHub webhook secret
define('DEPLOY_KEY', 'deploy123'); // Manual deployment key - change this to something secure

// Path to the WordPress plugin directory
define('PLUGIN_PATH', dirname(__FILE__));

// Log file
define('LOG_FILE', PLUGIN_PATH . '/deploy.log');

/**
 * Log messages
 */
function log_message($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND);
    echo $log_entry;
}

/**
 * Execute deployment
 */
function deploy() {
    log_message("Starting deployment...");

    // Change to plugin directory
    chdir(PLUGIN_PATH);

    // Fetch latest changes
    log_message("Fetching from origin...");
    $output = [];
    $return_var = 0;
    exec('git fetch origin ' . GITHUB_BRANCH . ' 2>&1', $output, $return_var);
    log_message("Fetch output: " . implode("\n", $output));

    if ($return_var !== 0) {
        log_message("ERROR: Git fetch failed with code {$return_var}");
        return false;
    }

    // Reset to latest
    log_message("Resetting to origin/" . GITHUB_BRANCH . "...");
    $output = [];
    exec('git reset --hard origin/' . GITHUB_BRANCH . ' 2>&1', $output, $return_var);
    log_message("Reset output: " . implode("\n", $output));

    if ($return_var !== 0) {
        log_message("ERROR: Git reset failed with code {$return_var}");
        return false;
    }

    // Clean up any untracked files
    log_message("Cleaning untracked files...");
    exec('git clean -fd 2>&1', $output, $return_var);

    log_message("Deployment completed successfully!");
    log_message("Current commit: " . trim(shell_exec('git rev-parse --short HEAD')));

    return true;
}

// Check if this is a manual deployment
if (isset($_GET['deploy']) && $_GET['deploy'] === DEPLOY_KEY) {
    log_message("=== Manual deployment triggered ===");

    if (deploy()) {
        echo "<h2>✅ Deployment Successful!</h2>";
        echo "<p>Plugin updated to latest version from GitHub.</p>";
        echo "<p><a href='/wp-admin/plugins.php'>Go to Plugins</a></p>";
    } else {
        echo "<h2>❌ Deployment Failed</h2>";
        echo "<p>Check the log file for details.</p>";
    }

    echo "<hr><h3>Deployment Log:</h3><pre>";
    echo htmlspecialchars(file_get_contents(LOG_FILE));
    echo "</pre>";
    exit;
}

// Check if this is a webhook request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_message("=== Webhook deployment triggered ===");

    // Get the payload
    $payload = file_get_contents('php://input');

    // Verify webhook secret
    if (defined('WEBHOOK_SECRET') && WEBHOOK_SECRET !== 'your_webhook_secret_here') {
        $signature = isset($_SERVER['HTTP_X_HUB_SIGNATURE_256']) ? $_SERVER['HTTP_X_HUB_SIGNATURE_256'] : '';

        if (empty($signature)) {
            log_message("ERROR: No signature provided");
            http_response_code(403);
            die('Forbidden: No signature');
        }

        $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

        if (!hash_equals($expected_signature, $signature)) {
            log_message("ERROR: Invalid signature");
            http_response_code(403);
            die('Forbidden: Invalid secret key');
        }

        log_message("Webhook signature verified");
    }

    // Parse payload
    $data = json_decode($payload, true);

    // Check if this is a push to our branch
    if (isset($data['ref']) && $data['ref'] === 'refs/heads/' . GITHUB_BRANCH) {
        log_message("Push detected to branch: " . GITHUB_BRANCH);

        if (deploy()) {
            http_response_code(200);
            echo "Deployment successful";
        } else {
            http_response_code(500);
            echo "Deployment failed";
        }
    } else {
        $ref = isset($data['ref']) ? $data['ref'] : 'unknown';
        log_message("Ignoring push to different branch: {$ref}");
        echo "Ignored: Different branch";
    }

    exit;
}

// If accessed directly without deploy key
http_response_code(403);
log_message("ERROR: Accessed without valid credentials");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Deploy Script</title>
</head>
<body>
    <h1>🚀 WooCommerce Shop CRM Deployment</h1>
    <p>This script is used for automatic deployment from GitHub.</p>
    <p>To manually deploy, use: <code>?deploy=DEPLOY_KEY</code></p>
</body>
</html>

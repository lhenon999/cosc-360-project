<?php
/**
 * Stripe Webhook Monitor
 * 
 * This script can be run by a cron job or other scheduler to ensure 
 * the Stripe webhook service is running.
 * 
 * Example cron entry (runs every 10 minutes):
 * */10 * * * * php /path/to/cosc-360-project/handmade_goods/payments/webhook_monitor.php
 */

// Set to true to see more detailed output
$verbose = isset($argv[1]) && $argv[1] == '--verbose';

// Log function
function log_message($message, $is_error = false) {
    global $verbose;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    if ($verbose || $is_error) {
        echo $log_entry;
    }
    
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $log_file = $log_dir . '/webhook_monitor.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

try {
    // Include the Stripe CLI Manager
    require_once __DIR__ . '/stripe_cli_manager.php';
    
    // Get manager instance
    $manager = getStripeCliManager();
    
    // Check if webhook is running
    if (!$manager->isWebhookRunning()) {
        log_message("Webhook not running, attempting to start...");
        
        // Start webhook
        if ($manager->startWebhook()) {
            log_message("Successfully started Stripe webhook service");
        } else {
            log_message("Failed to start Stripe webhook service", true);
        }
    } else {
        log_message("Stripe webhook service is already running");
    }
    
    // Get status for more detailed info
    $status = $manager->getStatus();
    if ($verbose) {
        echo "Webhook URL: " . $status['webhook_url'] . "\n";
        echo "CLI path: " . $status['cli_path'] . "\n";
        echo "Running: " . ($status['is_running'] ? "Yes" : "No") . "\n";
        
        if (!empty($status['last_log'])) {
            echo "\nLast log entries:\n";
            foreach ($status['last_log'] as $log_entry) {
                echo "  " . $log_entry . "\n";
            }
        }
    }
    
    exit($status['is_running'] ? 0 : 1);
} catch (Exception $e) {
    log_message("Error: " . $e->getMessage(), true);
    exit(1);
} 
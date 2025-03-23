<?php
/**
 * Stripe Webhook Autostart Script
 * 
 * This script checks if the Stripe webhook listener is running and starts it if needed.
 * Include this file in your website's bootstrap process to ensure webhooks are always running.
 */

// Only run this check occasionally to reduce overhead
$checkInterval = 60; // seconds
$webhookStatusFile = __DIR__ . '/webhook_status.json';

// Create the webhooks directory if it doesn't exist
if (!is_dir(__DIR__)) {
    mkdir(__DIR__, 0777, true);
}

// Function to check if we should run the webhook check
function should_check_webhook() {
    global $checkInterval, $webhookStatusFile;
    
    // If the status file doesn't exist, we should check
    if (!file_exists($webhookStatusFile)) {
        return true;
    }
    
    // Read the status file
    $status = json_decode(file_get_contents($webhookStatusFile), true);
    
    // If the status is invalid, we should check
    if (!$status || !isset($status['last_check'])) {
        return true;
    }
    
    // Check if enough time has passed since the last check
    return (time() - $status['last_check']) > $checkInterval;
}

// Function to update the webhook status
function update_webhook_status($running) {
    global $webhookStatusFile;
    
    $status = [
        'last_check' => time(),
        'running' => $running
    ];
    
    file_put_contents($webhookStatusFile, json_encode($status));
}

// Only run the check if needed
if (should_check_webhook()) {
    // Start a background process to check and start the webhook listener if needed
    $phpBinary = PHP_BINARY;
    $webhookScript = __DIR__ . '/stripe_webhook_manager.php';
    
    // Ensure the webhook manager script exists
    if (file_exists($webhookScript)) {
        // Execute the webhook manager in auto mode
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $command = "start /B \"\" \"$phpBinary\" \"$webhookScript\" auto > nul 2>&1";
            pclose(popen($command, 'r'));
        } else {
            // Linux/Mac
            $command = "$phpBinary \"$webhookScript\" auto > /dev/null 2>&1 &";
            exec($command);
        }
        
        // Update the status
        update_webhook_status(true);
    }
}

// Ensure Stripe login is also checked
$stripeLoginScript = __DIR__ . '/stripe_login.php';
if (file_exists($stripeLoginScript) && should_check_webhook()) {
    // Check Stripe login status in the background
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $command = "start /B \"\" \"" . PHP_BINARY . "\" \"$stripeLoginScript\" > nul 2>&1";
        pclose(popen($command, 'r'));
    } else {
        // Linux/Mac
        $command = PHP_BINARY . " \"$stripeLoginScript\" > /dev/null 2>&1 &";
        exec($command);
    }
} 
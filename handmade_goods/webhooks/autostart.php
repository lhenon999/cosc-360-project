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

// Helper function to fix file permissions on Windows
function fix_windows_permissions($path) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Try using shell commands to set permissions on Windows
        $path = str_replace('/', '\\', $path);
        // Remove read-only attribute
        @shell_exec('attrib -R "' . $path . '"');
        
        // For directories, try to set full control for IIS_IUSRS or IUSR
        if (is_dir($path)) {
            @shell_exec('icacls "' . $path . '" /grant "IUSR:(OI)(CI)F" /T /Q');
            @shell_exec('icacls "' . $path . '" /grant "IIS_IUSRS:(OI)(CI)F" /T /Q');
            @shell_exec('icacls "' . $path . '" /grant "NETWORK SERVICE:(OI)(CI)F" /T /Q');
            @shell_exec('icacls "' . $path . '" /grant "Everyone:(OI)(CI)F" /T /Q');
        } else {
            // For files
            @shell_exec('icacls "' . $path . '" /grant "IUSR:F" /Q');
            @shell_exec('icacls "' . $path . '" /grant "IIS_IUSRS:F" /Q');
            @shell_exec('icacls "' . $path . '" /grant "NETWORK SERVICE:F" /Q');
            @shell_exec('icacls "' . $path . '" /grant "Everyone:F" /Q');
        }
    } else {
        // For Unix-like systems
        if (is_dir($path)) {
            @chmod($path, 0777);
        } else {
            @chmod($path, 0666);
        }
    }
}

// Create the webhooks directory if it doesn't exist
if (!is_dir(__DIR__)) {
    @mkdir(__DIR__, 0777, true);
}

// Always try to fix permissions on the directory itself
fix_windows_permissions(__DIR__);

// Create webhook_status.json file with proper permissions if it doesn't exist
if (!file_exists($webhookStatusFile)) {
    $initial_status = [
        'last_check' => 0,
        'running' => false
    ];
    
    // Try creating file
    $result = @file_put_contents($webhookStatusFile, json_encode($initial_status));
    
    if ($result !== false) {
        // Fix permissions on the new file
        fix_windows_permissions($webhookStatusFile);
    }
} else {
    // File exists, make sure it has correct permissions
    fix_windows_permissions($webhookStatusFile);
    
    // Also try to make it writable using PHP's built-in function
    @chmod($webhookStatusFile, 0666);
}

// Function to check if we should run the webhook check
function should_check_webhook() {
    global $checkInterval, $webhookStatusFile;
    
    // If the status file doesn't exist, we should check
    if (!file_exists($webhookStatusFile)) {
        return true;
    }
    
    // Try to read the status file
    $statusContent = @file_get_contents($webhookStatusFile);
    if ($statusContent === false) {
        // Cannot read file, assume we should check
        return true;
    }
    
    // Read the status file
    $status = json_decode($statusContent, true);
    
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
    
    // Try to write to a temporary file first
    $tempFile = $webhookStatusFile . '.tmp';
    $result = @file_put_contents($tempFile, json_encode($status));
    
    if ($result !== false) {
        // Fix permissions on the temp file
        fix_windows_permissions($tempFile);
        
        // Try to rename it (safer than direct write)
        if (!@rename($tempFile, $webhookStatusFile)) {
            // If rename fails, try direct write as fallback
            $result = @file_put_contents($webhookStatusFile, json_encode($status));
            if ($result === false) {
                error_log("Warning: Could not write to webhook_status.json. Please check file permissions.");
            }
        }
    } else {
        error_log("Warning: Could not create temporary status file. Please check directory permissions.");
    }
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
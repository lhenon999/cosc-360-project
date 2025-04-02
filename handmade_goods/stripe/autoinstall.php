<?php
/**
 * Auto-installer for webhook directories and permissions
 * This file is included in the main application bootstrap and runs automatically
 */

// Only run this once per session to avoid performance impact
if (!isset($_SESSION['webhook_dir_checked'])) {
    $webhooksDir = __DIR__ . '/../webhooks';
    $statusFile = $webhooksDir . '/webhook_status.json';
    $logFile = __DIR__ . '/../logs/autoinstall.log';
    
    // Function to log messages to file
    function logAutoInstall($message) {
        global $logFile;
        $timestamp = date('Y-m-d H:i:s');
        $logDir = dirname($logFile);
        
        // Create log directory if it doesn't exist
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        
        // Append to log file
        @file_put_contents(
            $logFile, 
            "[$timestamp] $message" . PHP_EOL, 
            FILE_APPEND
        );
    }
    
    // Create webhooks directory if it doesn't exist
    if (!is_dir($webhooksDir)) {
        if (@mkdir($webhooksDir, 0755, true)) {
            logAutoInstall("Created webhooks directory: $webhooksDir");
        } else {
            logAutoInstall("Failed to create webhooks directory: $webhooksDir");
        }
    }
    
    // Create webhook_status.json if it doesn't exist
    if (!file_exists($statusFile)) {
        $defaultContent = json_encode([
            "lastChecked" => null,
            "active" => false,
            "lastEvent" => null
        ], JSON_PRETTY_PRINT);
        
        if (@file_put_contents($statusFile, $defaultContent) !== false) {
            logAutoInstall("Created webhook status file: $statusFile");
        } else {
            logAutoInstall("Failed to create webhook status file: $statusFile");
        }
    }
    
    // Try to set permissions
    if (file_exists($statusFile)) {
        // Try with chmod (works on Unix-like systems)
        if (function_exists('chmod')) {
            @chmod($webhooksDir, 0755);
            @chmod($statusFile, 0666);
            logAutoInstall("Set permissions on webhooks directory and status file");
        }
        
        // On Windows, try to make file writable
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            logAutoInstall("Windows system detected, checking if files are writable");
            
            // For Windows, we'll just check if it's writable
            if (!is_writable($statusFile)) {
                logAutoInstall("Warning: status file is not writable on Windows");
            }
        }
    }
    
    // For the future - try to initialize webhook_status.json if it exists but isn't writable
    if (file_exists($statusFile) && !is_writable($statusFile)) {
        // Try a different approach on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // On Windows, attempt to use icacls via shell_exec if allowed
            if (function_exists('shell_exec')) {
                $command = 'icacls "' . str_replace('/', '\\', $webhooksDir) . '" /grant "Everyone:(OI)(CI)F" /T';
                @shell_exec($command);
                logAutoInstall("Attempted to set permissions with icacls: $command");
            }
        }
    }
    
    // Mark as checked in this session
    $_SESSION['webhook_dir_checked'] = true;
    logAutoInstall("Webhook directory check completed");
}
?> 
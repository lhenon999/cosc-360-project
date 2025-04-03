<?php
/**
 * Stripe Webhook Manager
 * 
 * This script manages the Stripe webhook listener. It can:
 * 1. Check if the webhook listener is running
 * 2. Start the webhook listener if it's not running
 * 3. Restart the webhook listener if it's stuck
 */

// Configuration
$stripeCliPath = 'C:\Users\USER\Downloads\stripe_1.25.1_windows_x86_64\stripe.exe';
$webhookEndpoint = 'http://localhost/handmade_goods/payments/stripe_webhook.php';
$logFile = __DIR__ . '/../logs/stripe_webhook_manager.log';
$pidFile = __DIR__ . '/stripe_webhook.pid';

// Create logs directory if it doesn't exist
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Function to log messages
function log_message($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

// Function to check if the webhook listener is running
function is_webhook_running() {
    global $pidFile;
    
    if (!file_exists($pidFile)) {
        return false;
    }
    
    $pid = file_get_contents($pidFile);
    
    if (empty($pid)) {
        return false;
    }
    
    // Check if process is running
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $output = [];
        exec("tasklist /FI \"PID eq $pid\" /NH", $output);
        
        // Check if output contains process info (not "No tasks are running")
        if (count($output) > 0 && !strpos(implode(' ', $output), 'No tasks')) {
            return true;
        }
        
        // Alternative check: look for stripe.exe
        $output = [];
        exec("tasklist /FI \"IMAGENAME eq stripe.exe\" /NH", $output);
        if (count($output) > 0 && !strpos(implode(' ', $output), 'No tasks')) {
            return true;
        }
        
        return false;
    } else {
        // Linux/Mac
        return file_exists("/proc/$pid");
    }
}

// Function to start the webhook listener
function start_webhook_listener() {
    global $stripeCliPath, $webhookEndpoint, $pidFile;
    
    log_message("Starting Stripe webhook listener...");
    
    // Build the command
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - use start /B to run in background
        $command = "start /B \"Stripe Webhook\" \"$stripeCliPath\" listen --forward-to \"$webhookEndpoint\"";
        
        // Execute the command and save PID
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open("$command > nul 2>&1", $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            $status = proc_get_status($process);
            $pid = $status['pid'];
            file_put_contents($pidFile, $pid);
            proc_close($process);
            log_message("Webhook listener started with PID: $pid");
            return true;
        }
    } else {
        // Linux/Mac
        $command = "nohup $stripeCliPath listen --forward-to \"$webhookEndpoint\" > /dev/null 2>&1 & echo $!";
        $pid = trim(shell_exec($command));
        
        if (!empty($pid)) {
            file_put_contents($pidFile, $pid);
            log_message("Webhook listener started with PID: $pid");
            return true;
        }
    }
    
    log_message("Failed to start webhook listener");
    return false;
}

// Function to stop the webhook listener
function stop_webhook_listener() {
    global $pidFile;
    
    if (!file_exists($pidFile)) {
        log_message("No PID file found");
        return false;
    }
    
    $pid = file_get_contents($pidFile);
    
    if (empty($pid)) {
        log_message("Empty PID file");
        unlink($pidFile);
        return false;
    }
    
    log_message("Stopping webhook listener with PID: $pid");
    
    // Kill the process
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        exec("taskkill /F /PID $pid 2>&1", $output, $exitCode);
        $success = $exitCode === 0;
    } else {
        // Linux/Mac
        exec("kill -9 $pid 2>&1", $output, $exitCode);
        $success = $exitCode === 0;
    }
    
    if ($success) {
        log_message("Webhook listener stopped successfully");
        unlink($pidFile);
        return true;
    } else {
        log_message("Failed to stop webhook listener: " . implode("\n", $output));
        return false;
    }
}

// Function to restart the webhook listener
function restart_webhook_listener() {
    stop_webhook_listener();
    sleep(2); // Give it time to fully stop
    return start_webhook_listener();
}

// Main execution
// Check command line arguments
$action = isset($argv[1]) ? $argv[1] : 'auto';

switch ($action) {
    case 'start':
        if (is_webhook_running()) {
            log_message("Webhook listener is already running");
        } else {
            start_webhook_listener();
        }
        break;
        
    case 'stop':
        stop_webhook_listener();
        break;
        
    case 'restart':
        restart_webhook_listener();
        break;
        
    case 'status':
        $status = is_webhook_running() ? "running" : "not running";
        log_message("Webhook listener is $status");
        break;
        
    case 'auto':
    default:
        // Auto mode - ensure the webhook is running
        if (!is_webhook_running()) {
            log_message("Webhook listener is not running, starting it now");
            start_webhook_listener();
        } else {
            log_message("Webhook listener is already running");
        }
        break;
} 
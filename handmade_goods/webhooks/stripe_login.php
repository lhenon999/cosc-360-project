<?php
/**
 * Stripe Auto Login Script
 * 
 * This script automatically logs in to Stripe CLI if not already logged in.
 * It uses the Stripe CLI to check if the user is logged in and logs in if needed.
 */

// Configuration
$stripeCliPath = 'C:\Users\USER\Downloads\stripe_1.25.1_windows_x86_64\stripe.exe';
$logFile = __DIR__ . '/../logs/stripe_login.log';

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

// Function to check if Stripe CLI is logged in
function is_logged_in() {
    global $stripeCliPath;
    
    log_message("Checking if logged in to Stripe CLI...");
    
    // Run Stripe CLI command to check if logged in
    $output = [];
    exec("\"$stripeCliPath\" config", $output, $exitCode);
    
    // Check if the config command outputs account information
    $loggedIn = false;
    foreach ($output as $line) {
        if (strpos($line, 'acct_') !== false) {
            $loggedIn = true;
            log_message("Already logged in: $line");
            break;
        }
    }
    
    return $loggedIn;
}

// Function to log in to Stripe CLI
function login() {
    global $stripeCliPath;
    
    log_message("Logging in to Stripe CLI...");
    
    // Start the login process
    $descriptorspec = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w']   // stderr
    ];
    
    // Open process
    $process = proc_open("\"$stripeCliPath\" login", $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        // Wait for the pairing code
        $output = '';
        $startTime = time();
        $pairingCode = null;
        
        while (time() - $startTime < 30) { // Timeout after 30 seconds
            $output .= fread($pipes[1], 8192);
            
            // Extract pairing code
            if (preg_match('/Your pairing code is: ([\w-]+)/', $output, $matches)) {
                $pairingCode = $matches[1];
                log_message("Extracted pairing code: $pairingCode");
                
                // Save pairing code to file for manual login later if needed
                file_put_contents(__DIR__ . '/stripe_pairing_code.txt', $pairingCode);
                break;
            }
            
            usleep(100000); // Sleep for 100ms
        }
        
        if ($pairingCode) {
            log_message("Pairing code obtained. Automatic login is not possible. Please complete login manually using: \"$stripeCliPath\" login");
            log_message("Pairing code has been saved to: " . __DIR__ . '/stripe_pairing_code.txt');
            log_message("Press Enter in the Stripe CLI window to open the browser and complete login");
        } else {
            log_message("Failed to obtain pairing code within timeout period");
        }
        
        // Clean up
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($process);
    } else {
        log_message("Failed to start Stripe login process");
        return false;
    }
    
    return false;
}

// Main execution
if (!is_logged_in()) {
    log_message("Not logged in to Stripe CLI. Attempting to log in...");
    login();
} else {
    log_message("Already logged in to Stripe CLI");
} 
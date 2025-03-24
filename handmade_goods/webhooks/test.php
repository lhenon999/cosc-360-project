<?php
/**
 * Stripe Webhook Automation Test Script
 * 
 * This script tests whether the webhook automation is working properly.
 * Run this script from the command line: php test.php
 */

echo "Testing Stripe Webhook Automation\n";
echo "--------------------------------\n\n";

// Test 1: Check if the webhook manager script exists
echo "Test 1: Checking webhook manager script... ";
$webhookManagerScript = __DIR__ . '/stripe_webhook_manager.php';
if (file_exists($webhookManagerScript)) {
    echo "PASS\n";
} else {
    echo "FAIL - Script not found at: $webhookManagerScript\n";
    exit(1);
}

// Test 2: Check if the login script exists
echo "Test 2: Checking login script... ";
$loginScript = __DIR__ . '/stripe_login.php';
if (file_exists($loginScript)) {
    echo "PASS\n";
} else {
    echo "FAIL - Script not found at: $loginScript\n";
    exit(1);
}

// Test 3: Check if the autostart script exists
echo "Test 3: Checking autostart script... ";
$autostartScript = __DIR__ . '/autostart.php';
if (file_exists($autostartScript)) {
    echo "PASS\n";
} else {
    echo "FAIL - Script not found at: $autostartScript\n";
    exit(1);
}

// Test 4: Check if Stripe CLI is installed
echo "Test 4: Checking Stripe CLI installation... ";
// Extract Stripe CLI path from webhook manager script
$stripeCliPath = null;
$content = file_get_contents($webhookManagerScript);
if (preg_match('/\$stripeCliPath\s*=\s*[\'"](.*?)[\'"];/', $content, $matches)) {
    $stripeCliPath = $matches[1];
}

if ($stripeCliPath && file_exists($stripeCliPath)) {
    echo "PASS - Found at: $stripeCliPath\n";
} else {
    echo "FAIL - Stripe CLI not found at: $stripeCliPath\n";
    echo "       Please update the \$stripeCliPath variable in the scripts.\n";
    exit(1);
}

// Test 5: Check if Stripe CLI is working
echo "Test 5: Checking Stripe CLI functionality... ";
$output = [];
exec("\"$stripeCliPath\" --version 2>&1", $output, $exitCode);
if ($exitCode === 0) {
    echo "PASS - Version: " . implode(' ', $output) . "\n";
} else {
    echo "FAIL - Could not execute Stripe CLI. Error: " . implode(' ', $output) . "\n";
    exit(1);
}

// Test 6: Check if logged in to Stripe
echo "Test 6: Checking Stripe login status... ";
$output = [];
exec("\"$stripeCliPath\" config 2>&1", $output, $exitCode);
$loggedIn = false;
foreach ($output as $line) {
    if (strpos($line, 'acct_') !== false) {
        $loggedIn = true;
        break;
    }
}

if ($loggedIn) {
    echo "PASS - Logged in to Stripe\n";
} else {
    echo "WARNING - Not logged in to Stripe. Please run: \"$stripeCliPath\" login\n";
}

// Test 7: Try running the webhook manager
echo "Test 7: Testing webhook manager (status check)... ";
$output = [];
exec("\"" . PHP_BINARY . "\" \"$webhookManagerScript\" status 2>&1", $output, $exitCode);
echo "Result: " . implode(' ', $output) . "\n";

// Test 8: Check if the logs directory exists
echo "Test 8: Checking logs directory... ";
$logDir = dirname(__DIR__) . '/logs';
if (is_dir($logDir)) {
    echo "PASS - Found at: $logDir\n";
} else {
    echo "WARNING - Logs directory not found. It will be created when needed.\n";
}

// Test 9: Check if webhook endpoint exists
echo "Test 9: Checking webhook endpoint... ";
$webhookEndpoint = null;
$content = file_get_contents($webhookManagerScript);
if (preg_match('/\$webhookEndpoint\s*=\s*[\'"](.*?)[\'"];/', $content, $matches)) {
    $webhookEndpoint = $matches[1];
}

if ($webhookEndpoint) {
    $endpointPath = parse_url($webhookEndpoint, PHP_URL_PATH);
    $endpointPath = preg_replace('/^\/cosc-360-project\//', '', $endpointPath);
    $fullPath = dirname(__DIR__) . '/' . $endpointPath;
    
    if (file_exists($fullPath)) {
        echo "PASS - Webhook endpoint found at: $fullPath\n";
    } else {
        echo "WARNING - Webhook endpoint file not found at: $fullPath\n";
    }
} else {
    echo "WARNING - Could not determine webhook endpoint from script\n";
}

echo "\nAll tests completed. If all tests passed, the automation should work correctly.\n";
echo "To set up automatic startup on Windows, follow the instructions in README.md\n"; 
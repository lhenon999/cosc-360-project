<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root";
$password = "";
$database = "rsodhi03";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include permission helper for Mac users
require_once __DIR__ . '/bootstrap/permission_helper.php';

// Auto-create and set permissions on required directories
function ensureDirectoriesExist() {
    $baseDir = dirname(__FILE__);
    $requiredDirs = [
        // Log directories
        $baseDir . '/logs',
        $baseDir . '/logs/stripe',
        $baseDir . '/logs/inventory',
        
        // Temp and bin directories
        $baseDir . '/bin',
        $baseDir . '/temp',
        
        // Image upload directories
        $baseDir . '/assets/images/uploads',
        $baseDir . '/assets/images/uploads/product_images'
    ];
    
    foreach ($requiredDirs as $dir) {
        if (!file_exists($dir)) {
            // Try to create the directory with full permissions
            @mkdir($dir, 0777, true);
            
            // For Mac/Unix systems, try to chmod after creating
            if (file_exists($dir) && stripos(PHP_OS, 'win') === false) {
                @shell_exec("chmod -R 777 " . escapeshellarg($dir));
            }
        } else if (is_dir($dir) && !is_writable($dir)) {
            // Try to make the directory writable if it exists but isn't writable
            @chmod($dir, 0777);
            
            // For Mac/Unix systems, use shell_exec as fallback for more reliable permissions
            if (stripos(PHP_OS, 'win') === false) {
                @shell_exec("chmod -R 777 " . escapeshellarg($dir));
            }
        }
    }
    
    // On non-Windows systems, try to ensure permissions using shell commands as a last resort
    if (stripos(PHP_OS, 'win') === false) {
        // Get Apache/PHP process user to check permissions
        $webServerUser = trim(@shell_exec('whoami'));
        
        // If we're dealing with a Mac and XAMPP
        if (stripos(PHP_OS, 'darwin') !== false && file_exists('/Applications/XAMPP')) {
            foreach ($requiredDirs as $dir) {
                if (file_exists($dir) && !is_writable($dir)) {
                    error_log("Trying to fix permissions for: " . $dir);
                    @shell_exec("chmod -R 777 " . escapeshellarg($dir));
                }
            }
        }
    }
}

// Run directory check on every page load to ensure directories exist
ensureDirectoriesExist();

// Run Mac-specific permission fixes if needed
PermissionHelper::ensureMacPermissions();

// Run auto-installer for webhooks directory
include_once __DIR__ . '/stripe/autoinstall.php';

// Include Stripe webhook autostart if the file exists
$webhookAutostart = __DIR__ . '/webhooks/autostart.php';
if (file_exists($webhookAutostart)) {
    include_once $webhookAutostart;
}
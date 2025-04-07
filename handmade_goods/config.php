<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "rsodhi03";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
        } else if (is_dir($dir) && !is_writable($dir)) {
            // Try to make the directory writable if it exists but isn't writable
            @chmod($dir, 0777);
        }
    }
}

// Run directory check on every page load to ensure directories exist
ensureDirectoriesExist();

// Include Stripe webhook autostart if the file exists
$webhookAutostart = __DIR__ . '/webhooks/autostart.php';
if (file_exists($webhookAutostart)) {
    include_once $webhookAutostart;
}
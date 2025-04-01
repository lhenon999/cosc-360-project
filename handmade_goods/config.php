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

// Run auto-installer for webhooks directory
include_once __DIR__ . '/config/autoinstall.php';

// Include Stripe webhook autostart if the file exists
$webhookAutostart = __DIR__ . '/webhooks/autostart.php';
if (file_exists($webhookAutostart)) {
    include_once $webhookAutostart;
}
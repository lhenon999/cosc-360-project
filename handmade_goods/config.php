<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "handmade_goods";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include Stripe webhook autostart if the file exists
$webhookAutostart = __DIR__ . '/webhooks/autostart.php';
if (file_exists($webhookAutostart)) {
    include_once $webhookAutostart;
}
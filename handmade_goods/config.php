<?php
$servername = "localhost";
$username = "rsodhi03";
$password = "rsodhi03";
$database = "rsodhi03";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

if (isset($_POST['dark_mode'])) {
    $_SESSION['dark_mode'] = $_POST['dark_mode'] ? 1 : 0;
    echo json_encode(["success" => "Dark mode updated"]);
    exit();
}

echo json_encode(["error" => "Invalid request"]);
exit();

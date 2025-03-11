<?php
session_start();
session_destroy();

setcookie("remember_token", "", time() - 3600, "/", "", false, true);
setcookie("user_email", "", time() - 3600, "/", "", false, true);

//remove cookies
require_once '../config.php';
if (isset($_SESSION["user_email"])) {
    $email = $_SESSION["user_email"];
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
}

header("Location: login.php");
exit();
?>

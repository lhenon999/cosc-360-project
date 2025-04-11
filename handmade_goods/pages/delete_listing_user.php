<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

if (!isset($_POST["id"]) || !is_numeric($_POST["id"])) {
    header("Location: my_shop.php");
    exit();
}
$product_id = intval($_POST["id"]);

if (isset($_SESSION["is_frozen"]) && $_SESSION["is_frozen"] == 1) {
    $_SESSION["error"] = "Your account is frozen; you cannot delete listings.";
    header("Location: my_shop.php");
    exit();
}

$stmt = $conn->prepare("SELECT id FROM ITEMS WHERE id = ? AND user_id = ?");
$stmt->bind_param("is", $product_id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    $stmt->close();
    header("Location: my_shop.php");
    exit();
}
$stmt->close();

$delete_stmt = $conn->prepare("DELETE FROM ITEMS WHERE id=? AND user_id=?");
$delete_stmt->bind_param("is", $product_id, $user_id);
$delete_stmt->execute();
$delete_stmt->close();

$_SESSION["success"] = "Listing deleted successfully.";
header("Location: my_shop.php");
exit();
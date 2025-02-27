<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["product_id"], $_POST["quantity"])) {
    $id = $_POST["product_id"];
    $quantity = max(1, intval($_POST["quantity"]));

    if (isset($_SESSION["cart"][$id])) {
        $_SESSION["cart"][$id]["quantity"] = $quantity;
    }
}
header("Location: basket.php");
exit();
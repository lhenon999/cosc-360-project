<?php
session_start();
include '../config.php';

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $stock = trim($_POST["stock"]);
    $category = trim($_POST["category"]);
    $user_email = $_SESSION["user_id"]; // User ID is actually the email

    // Image upload handling
    $target_dir = "../uploads/";
    $image_name = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . time() . "_" . $image_name; // Prevent duplicate names
    $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a real image
    if (getimagesize($_FILES["image"]["tmp_name"]) === false) {
        die("Error: Uploaded file is not a valid image.");
    }

    // Allow only certain file formats
    if (!in_array($image_type, ["jpg", "jpeg", "png", "gif"])) {
        die("Error: Only JPG, JPEG, PNG & GIF files are allowed.");
    }

    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        die("Error: Failed to upload image.");
    }

    // Insert product into database
    $stmt = $conn->prepare("INSERT INTO items (name, description, price, stock, category, img, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiiss", $name, $description, $price, $stock, $category, $target_file, $user_email);

    if ($stmt->execute()) {
        header("Location: myshop.php"); // Redirect to user's shop page
        exit();
    } else {
        echo "Error: Failed to add product.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Listing</title>
   

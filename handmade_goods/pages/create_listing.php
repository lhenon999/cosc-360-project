<?php
session_start();
include '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $stock = trim($_POST["stock"]);
    $category = trim($_POST["category"]);
    $user_email = $_SESSION["user_id"];

    $target_dir = "../uploads/";
    $image_name = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . time() . "_" . $image_name;
    $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (getimagesize($_FILES["image"]["tmp_name"]) === false) {
        die("Error: Uploaded file is not a valid image.");
    }

    if (!in_array($image_type, ["jpg", "jpeg", "png", "gif"])) {
        die("Error: Only JPG, JPEG, PNG & GIF files are allowed.");
    }

    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        die("Error: Failed to upload image.");
    }

    $stmt = $conn->prepare("INSERT INTO items (name, description, price, stock, category, img, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiiss", $name, $description, $price, $stock, $category, $target_file, $user_email);

    if ($stmt->execute()) {
        header("Location: my_shop.php");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <?php include '../assets/html/navbar.php'; ?>

    <div class="container mt-5">
        <h2>Create a New Listing</h2>
        <form action="create_listing.php" method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" required></textarea>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price ($)</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" name="category" id="category" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Product Image</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*" required>
            </div>

            <button type="submit" class="btn btn-primary">Create Listing</button>
        </form>
    </div>

    <?php include '../assets/html/footer.php'; ?>
</body>
</html>

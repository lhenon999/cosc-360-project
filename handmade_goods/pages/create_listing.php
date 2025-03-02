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
        header("Location: myshop.php");
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
    <title>Handmade Goods - Browse</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/products.css?v=1">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/product_card.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>

    <h1 class="text-center">Create a New Listing</h1>
    <p class="text-center">Fill in the details to add your product</p>
    <br>

    <div class="container mt-4 d-flex justify-content-center">
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

            <div class="d-flex justify-content-center">
                <a class="cta hover-raise" href="myshop.php">
                    <span class="material-symbols-outlined"></span> Create Listing
                </a>
            </div>
        </form>
    </div>

    <?php include '../assets/html/footer.php'; ?>
</body>

</html>
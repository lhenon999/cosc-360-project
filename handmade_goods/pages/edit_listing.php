<?php
session_start();
include '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: myshop.php");
    exit();
}

$product_id = intval($_GET["id"]);
$user_email = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
$stmt->bind_param("is", $product_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: myshop.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $stock = trim($_POST["stock"]);
    $category = trim($_POST["category"]);
    $image_path = $product["img"]; 

    if (!empty($_FILES["image"]["name"])) {
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

        $image_path = $target_file;
    }

    $stmt = $conn->prepare("UPDATE items SET name=?, description=?, price=?, stock=?, category=?, img=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssdiisis", $name, $description, $price, $stock, $category, $image_path, $product_id, $user_email);

    if ($stmt->execute()) {
        header("Location: myshop.php");
        exit();
    } else {
        echo "Error: Failed to update product.";
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

    <h1 class="text-center">Edit Listing</h1>
    <p class="text-center">Modify your product details below</p>
    <br>

    <div class="container mt-4 d-flex justify-content-center">
        <form action="edit_listing.php?id=<?= $product_id ?>" method="POST" enctype="multipart/form-data" class="mt-4 p-4 shadow rounded bg-white" style="width: 50%;">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price ($)</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" value="<?= htmlspecialchars($product['stock']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" name="category" id="category" class="form-control" value="<?= htmlspecialchars($product['category']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Product Image (Leave blank to keep current image)</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*">
                <br>
                <img src="<?= htmlspecialchars($product['img']) ?>" alt="Current Image" class="img-fluid" style="max-width: 100px;">
            </div>

            <div class="d-flex justify-content-center">
                <button type="submit" class="cta hover-raise">
                    <span class="material-symbols-outlined"></span> Save Changes
                </button>
            </div>
        </form>
    </div>

</body>
</html>

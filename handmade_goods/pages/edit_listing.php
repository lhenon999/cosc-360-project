<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: my_shop.php");
    exit();
}

$product_id = intval($_GET["id"]);
$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT * FROM ITEMS WHERE id = ? AND user_id = ?");
$stmt->bind_param("is", $product_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: my_shop.php");
    exit();
}

$errors = [];
$image_path = $product["img"];
$categories = ["Kitchenware", "Accessories", "Apparel", "Home Decor", "Personal Care", "Stationery", "Toys", "Art", "Seasonal", "Gift Sets", "Wallets and Purses", "Storage"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $stock = trim($_POST["stock"]);
    $category = trim($_POST["category"]);

    if (empty($name))
        $errors[] = "Product name is required.";
    if (empty($description))
        $errors[] = "Description is required.";
    if (empty($price) || !is_numeric($price) || $price <= 0)
        $errors[] = "Price must be a positive number.";
    if (empty($stock) || !ctype_digit($stock) || $stock <= 0)
        $errors[] = "Stock must be a positive integer.";
    if (empty($category) || !in_array($category, $categories))
        $errors[] = "Please select a valid category.";

    if (!empty($_FILES["image"]["name"])) {
        $maxSize = 2 * 1024 * 1024;
        $target_dir = "../assets/images/uploads/product_images/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . time() . "_" . $image_name;
        $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (getimagesize($_FILES["image"]["tmp_name"]) === false)
            $errors[] = "Uploaded file is not a valid image.";
        if ($_FILES["image"]["size"] > $maxSize)
            $errors[] = "File exceeds maximum allowed size of 2MB.";
        if (!in_array($image_type, ["jpg", "jpeg", "webp", "png"]))
            $errors[] = "Only JPG, JPEG, WEBP, and PNG files are allowed.";

        if (empty($errors)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE ITEMS SET name=?, description=?, price=?, stock=?, category=?, img=? WHERE id=? AND user_id=?");
        $stmt->bind_param("ssdissii", $name, $description, $price, $stock, $category, $image_path, $product_id, $user_id);

        if ($stmt->execute()) {
            header("Location: my_shop.php");
            exit();
        } else {
            $errors[] = "Error: Failed to update product.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handmade Goods - Edit Listing</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/create_listing.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <h1 class="text-center mt-5">Edit Listing</h1>
    <p class="text-center">Modify your product details below</p>
    <br>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section>
        <div class="preview-container">
            <div class="listing-item-container" id="productPreview">
                <div class="product-image-container">
                    <img src="<?= htmlspecialchars($image_path) ?>" id="previewImage" class="card-img-top"
                        style="width: 100%; height: 100%;">
                </div>
                <div class="product-info">
                    <h1 id="previewTitle"><?= htmlspecialchars($product['name']) ?></h1>
                    <h2>$<span id="previewPrice"><?= htmlspecialchars($product['price']) ?></span></h2>
                </div>
            </div>
        </div>

        <div class="listing-form-container">
            <form action="edit_listing.php?id=<?= $product_id ?>" id="listingForm" method="POST"
                enctype="multipart/form-data" class="mt-4">
                <div class="mb-3">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" name="name" id="name" class="form-control" required
                        value="<?= htmlspecialchars($product['name']) ?>"
                        oninput="updatePreview();  checkFormChanged();">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" required
                        oninput="checkFormChanged();"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price ($)</label>
                    <input type="number" name="price" id="price" class="form-control" step="0.01" required min="0.01"
                        value="<?= htmlspecialchars($product['price']) ?>"
                        oninput="updatePreview();  checkFormChanged();">
                </div>

                <div class="mb-3">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" name="stock" id="stock" class="form-control" required min="1"
                        value="<?= htmlspecialchars($product['stock']) ?>" oninput="checkFormChanged();">

                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-control" required onchange="checkFormChanged();">
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($product['category'] === $cat) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*"
                        onchange="previewImage(); checkFormChanged();">

                </div>

                <div class="buttons-div">
                    <a class="m-btn" href="my_shop.php">
                        <span class="material-symbols-outlined">close</span> Cancel
                    </a>
                    <button type="submit" id="submitButton" class="m-btn g" disabled>
                        <span class="material-symbols-outlined">add</span> Submit Product
                    </button>
                </div>
            </form>
        </div>
    </section>
    <?php include __DIR__ . '/../assets/html/footer.php'; ?>
    <script>
        function updatePreview() {
            let nameInput = document.getElementById("name").value.trim();
            let priceInput = document.getElementById("price").value.trim();
            let formattedPrice = (priceInput === "" || isNaN(priceInput)) ? "0.00" : parseFloat(priceInput).toFixed(2);

            document.getElementById("previewTitle").innerText = nameInput || "Product Preview";
            document.getElementById("previewPrice").innerText = formattedPrice;
        }

        function previewImage() {
            const file = document.getElementById("image").files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const previewImage = document.getElementById("previewImage");
                    previewImage.src = e.target.result;
                    previewImage.style.display = "block";
                };
                reader.readAsDataURL(file);
            }
        }

        document.getElementById("previewTitle").innerText = "<?= htmlspecialchars($product['name']) ?>";
        document.getElementById("previewPrice").innerText = "<?= htmlspecialchars($product['price']) ?>";
    </script>
    <script>
        var initialName = "<?= htmlspecialchars($product['name']) ?>";
        var initialDescription = "<?= htmlspecialchars($product['description']) ?>";
        var initialPrice = "<?= htmlspecialchars($product['price']) ?>";
        var initialStock = "<?= htmlspecialchars($product['stock']) ?>";
        var initialCategory = "<?= htmlspecialchars($product['category']) ?>";
        function checkFormChanged() {
            const currentName = document.getElementById("name").value;
            const currentDescription = document.getElementById("description").value;
            const currentPrice = document.getElementById("price").value;
            const currentStock = document.getElementById("stock").value;
            const currentCategory = document.getElementById("category").value;
            const imageInput = document.getElementById("image");

            let hasChanged = (currentName !== initialName) ||
                (currentDescription !== initialDescription) ||
                (currentPrice !== initialPrice) ||
                (currentStock !== initialStock) ||
                (currentCategory !== initialCategory) ||
                (imageInput.files.length > 0);

            document.getElementById("submitButton").disabled = !hasChanged;
        }

        document.addEventListener("DOMContentLoaded", function () {
            checkFormChanged();
        });

    </script>
</body>
</html>
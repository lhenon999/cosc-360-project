<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$is_frozen = isset($_SESSION["is_frozen"]) && $_SESSION["is_frozen"] == 1;

// If this is a POST request and the user's account is frozen, prevent creating listings
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_frozen) {
    $errors[] = "Your account is currently frozen. You cannot create new listings at this time.";
    // Don't process the form submission
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $stock = trim($_POST["stock"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $user_id = $_SESSION["user_id"];

    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    if (empty($price)) {
        $errors[] = "Price is required.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $errors[] = "Price must be a positive number.";
    }
    if (empty($stock)) {
        $errors[] = "Stock is required.";
    } elseif (!ctype_digit($stock) || $stock <= 0) {
        $errors[] = "Stock must be a positive integer.";
    }
    if (empty($category)) {
        $errors[] = "Please select a category.";
    }
    if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != UPLOAD_ERR_OK) {
        $errors[] = "Product image is required.";
    }

    if (empty($errors)) {
        $maxSize = 2 * 1024 * 1024;
        $target_dir = "../assets/images/uploads/product_images/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . time() . "_" . $image_name;
        $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != UPLOAD_ERR_OK) {
            $errors[] = "Product image is required. Error Code: " . $_FILES["image"]["error"];
        }
        if (getimagesize($_FILES["image"]["tmp_name"]) === false) {
            $errors[] = "Uploaded file is not a valid image.";
        }
        if ($_FILES["image"]["size"] > $maxSize) {
            $errors[] = "File exceeds maximum allowed size of 2MB.";
        }
        if ($_FILES["image"]["size"] === 0) {
            $errors[] = "File is empty or not properly uploaded.";
        }
        if (!in_array($image_type, ["jpg", "jpeg", "webp", "png"])) {
            $errors[] = "Only JPG, JPEG, WEBP, and PNG files are allowed.";
        }
        if (empty($errors)) {
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO ITEMS (name, description, price, stock, category, img, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category, $target_file, $user_id);

        if ($stmt->execute()) {
            $activity_details = "New Listing: " . $name;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $activity_stmt = $conn->prepare("INSERT INTO ACCOUNT_ACTIVITY (user_id, event_type, ip_address, user_agent, details) VALUES (?, 'listing', ?, ?, ?)");
            $activity_stmt->bind_param("isss", $user_id, $ip_address, $user_agent, $activity_details);
            $activity_stmt->execute();
            $activity_stmt->close();

            header("Location: my_shop.php");
            exit();
        } else {
            $errors[] = "Error: Failed to add product.";
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
    <title>Handmade Goods - Create Listing</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/create_listing.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <h1 class="text-center mt-5">Create a New Listing</h1>
    <p class="text-center">Fill in the details to add your product to our directory!</p>
    <br>

    <?php if ($is_frozen): ?>
        <div class="alert alert-warning text-center">
            <strong>Account Notice:</strong> Your account is currently frozen. You can view your existing listings, but you cannot create new listings or modify existing ones. Any existing listings are not visible to other users.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section>
        <div class="preview-container">
            <div class="listing-item-container" id="productPreview">
                <div class="product-image-container">
                    <img src="" id="previewDiv" class="card-img-top"
                        style="background-color: #BBB; width: 100%; height: 100%;">
                    <img src="" id="previewImage" class="card-img-top" style="diplay:none;">
                </div>
                <div class="product-info">
                    <h1 id="previewTitle">Product Preview</h1>
                    <h2>$<span id="previewPrice">0.01</span></h2>
                </div>
            </div>
        </div>

        <div class="listing-form-container">
            <form action="create_listing.php" id="listingForm" method="POST" enctype="multipart/form-data" class="mt-4">
                <div class="mb-3">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" name="name" id="name" class="form-control" required
                        oninput="updatePreview(); validateForm();"
                        value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" required
                        oninput="updatePreview(); validateForm();"><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price ($)</label>
                    <input type="number" name="price" id="price" class="form-control" step="0.01" required min="0.01"
                        value="<?= isset($price) ? htmlspecialchars($price) : '' ?>"
                        oninput="updatePreview(); validateForm();">
                </div>

                <div class="mb-3">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" name="stock" id="stock" class="form-control" required min="1"
                        value="<?= isset($stock) ? htmlspecialchars($stock) : '' ?>" oninput="validateForm();">
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-control" required onchange="validateForm();">
                        <option value="">Select a Category</option>
                        <option value="Kitchenware" <?= (isset($category) && $category === 'Kitchenware') ? 'selected' : '' ?>>Kitchenware</option>
                        <option value="Accessories" <?= (isset($category) && $category === 'Accessories') ? 'selected' : '' ?>>Accessories</option>
                        <option value="Apparel" <?= (isset($category) && $category === 'Apparel') ? 'selected' : '' ?>>
                            Apparel</option>
                        <option value="Home Decor" <?= (isset($category) && $category === 'Home Decor') ? 'selected' : '' ?>>Home Decor</option>
                        <option value="Personal Care" <?= (isset($category) && $category === 'Personal Care') ? 'selected' : '' ?>>Personal Care</option>
                        <option value="Stationery" <?= (isset($category) && $category === 'Stationery') ? 'selected' : '' ?>>Stationery</option>
                        <option value="Toys" <?= (isset($category) && $category === 'Toys') ? 'selected' : '' ?>>Toys
                        </option>
                        <option value="Art" <?= (isset($category) && $category === 'Art') ? 'selected' : '' ?>>Art</option>
                        <option value="Seasonal" <?= (isset($category) && $category === 'Seasonal') ? 'selected' : '' ?>>
                            Seasonal</option>
                        <option value="Gift Sets" <?= (isset($category) && $category === 'Gift Sets') ? 'selected' : '' ?>>
                            Gift Sets</option>
                        <option value="Wallets and Purses" <?= (isset($category) && $category === 'Wallets and Purses') ? 'selected' : '' ?>>Wallets and Purses</option>
                        <option value="Storage" <?= (isset($category) && $category === 'Storage') ? 'selected' : '' ?>>
                            Storage</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*" required
                        onchange="previewImage(); validateForm();">
                </div>

                <div class="buttons-div">
                    <a class="m-btn" href="my_shop.php">
                        <span class="material-symbols-outlined">close</span> Cancel
                    </a>

                    <button type="button" id="clearButton" class="m-btn" onclick="clearFormAndErrors()" disabled>
                        <span class="material-symbols-outlined">ink_eraser</span>Clear
                    </button>

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
            let priceInput = document.getElementById("price").value.trim();
            let formattedPrice = (priceInput === "" || isNaN(priceInput)) ? "0.00" : parseFloat(priceInput).toFixed(2);

            document.getElementById("previewTitle").innerText = document.getElementById("name").value || "Product Preview";
            document.getElementById("previewPrice").innerText = `${formattedPrice}`;
        }

        function previewImage() {
            const file = document.getElementById("image").files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const previewImage = document.getElementById("previewImage");
                    const previewDiv = document.getElementById("previewDiv");
                    previewImage.src = e.target.result;
                    previewImage.style.display = "block";
                    previewDiv.style.display = "none";
                };
                reader.readAsDataURL(file);
            }
        }

        function clearFormAndErrors() {
            const errorAlert = document.querySelector('.alert.alert-danger');
            if (errorAlert) {
                errorAlert.remove();
            }
            const form = document.getElementById('listingForm');
            if (form) {
                form.reset();
            }
            const fileInput = document.getElementById('image');
            if (fileInput) {
                fileInput.value = "";
            }
            window.location.href = window.location.pathname;
        }

        document.getElementById("previewTitle").innerText = "Product Preview";
        document.getElementById("previewPrice").innerText = "0.00";
        document.getElementById("previewImage").style.display = "none";
    </script>
    <script>
        function validateForm() {
            const name = document.getElementById("name").value.trim();
            const description = document.getElementById("description").value.trim();
            const price = parseFloat(document.getElementById("price").value);
            const stock = document.getElementById("stock").value;
            const category = document.getElementById("category").value;
            const image = document.getElementById("image").files[0];

            let isValid = name !== "" &&
                description !== "" &&
                !isNaN(price) && price > 0 &&
                stock !== "" && parseInt(stock) > 0 &&
                category !== "" &&
                image !== undefined;

            document.getElementById("submitButton").disabled = !isValid;

            const imageFiles = document.getElementById("image").files;
            const isAnyFilled = name !== "" || description !== "" || document.getElementById("price").value.trim() !== "" ||
                document.getElementById("stock").value.trim() !== "" || category !== "" ||
                (imageFiles && imageFiles.length > 0);

            document.getElementById("clearButton").disabled = !isAnyFilled;
        }

        document.addEventListener("DOMContentLoaded", function () {
            validateForm();
        });

    </script>
</body>

</html>
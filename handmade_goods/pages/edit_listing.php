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

/**
 * Resizes and crops an image to fit the product card dimensions
 * @param string $source_path Path to the source image
 * @param string $target_path Path to save the processed image
 * @param int $width Target width
 * @param int $height Target height
 * @return bool True if successful, false otherwise
 */
function processImage($source_path, $target_path, $width = 320, $height = 224) {
    // Check if GD extension is available
    if (!extension_loaded('gd')) {
        return false;
    }
    
    // Get image type
    $image_info = getimagesize($source_path);
    if ($image_info === false) {
        return false;
    }
    
    $mime = $image_info['mime'];
    
    // Create image resource based on type
    switch ($mime) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source_path);
            break;
        case 'image/webp':
            $source_image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    if (!$source_image) {
        return false;
    }
    
    // Get original dimensions
    $src_width = imagesx($source_image);
    $src_height = imagesy($source_image);
    
    // Create new image with transparent background (will appear as white in browsers)
    $new_image = imagecreatetruecolor($width, $height);
    $transparent = imagecolorallocate($new_image, 245, 245, 245); // Light gray background
    imagefill($new_image, 0, 0, $transparent);
    
    // Handle transparency for PNG images
    if ($mime == 'image/png') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 245, 245, 245, 0);
        imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
    }
    
    // Calculate scaling factors
    $scale_w = $width / $src_width;
    $scale_h = $height / $src_height;
    
    // Use the smaller scaling factor to ensure the entire image fits
    $scale = min($scale_w, $scale_h);
    
    // Calculate new dimensions
    $new_w = ceil($src_width * $scale);
    $new_h = ceil($src_height * $scale);
    
    // Center the image
    $x = ($width - $new_w) / 2;
    $y = ($height - $new_h) / 2;
    
    // Resize and copy the image onto the new canvas
    imagecopyresampled(
        $new_image, $source_image,
        $x, $y, 0, 0,
        $new_w, $new_h, $src_width, $src_height
    );
    
    // Save the image
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($new_image, $target_path, 90); // 90% quality
            break;
        case 'image/png':
            $result = imagepng($new_image, $target_path, 9); // Maximum compression
            break;
        case 'image/webp':
            $result = imagewebp($new_image, $target_path, 90); // 90% quality
            break;
    }
    
    // Free up memory
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return $result;
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

    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($price) || !is_numeric($price) || $price <= 0) $errors[] = "Price must be a positive number.";
    if (empty($stock) || !ctype_digit($stock) || $stock <= 0) $errors[] = "Stock must be a positive integer.";
    if (empty($category) || !in_array($category, $categories)) $errors[] = "Please select a valid category.";

    if (!empty($_FILES["image"]["name"])) {
        $maxSize = 2 * 1024 * 1024;
        
        // Create paths using directory constants
        $base_dir = dirname(dirname(__FILE__));
        $upload_dir = $base_dir . "/assets/images/uploads/product_images/";
        $web_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $base_dir) . "/assets/images/uploads/product_images/";
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $image_name = basename($_FILES["image"]["name"]);
        $file_name = time() . "_" . $image_name;
        $temp_file = $upload_dir . "temp_" . $file_name;
        $target_file = $web_path . $file_name;
        $full_target_path = $base_dir . "/assets/images/uploads/product_images/" . $file_name;
        $image_type = strtolower(pathinfo($full_target_path, PATHINFO_EXTENSION));

        if (getimagesize($_FILES["image"]["tmp_name"]) === false) $errors[] = "Uploaded file is not a valid image.";
        if ($_FILES["image"]["size"] > $maxSize) $errors[] = "File exceeds maximum allowed size of 2MB.";
        if (!in_array($image_type, ["jpg", "jpeg", "webp", "png"])) $errors[] = "Only JPG, JPEG, WEBP, and PNG files are allowed.";

        if (empty($errors)) {
            // First move the uploaded file to a temporary location
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $temp_file)) {
                // Try to process the image (resize and crop)
                $processed = processImage($temp_file, $full_target_path);
                
                // If processing failed, just use the original file
                if (!$processed) {
                    if (!rename($temp_file, $full_target_path)) {
                        $errors[] = "Failed to save image.";
                    }
                } else {
                    // Remove the temporary file
                    @unlink($temp_file);
                }
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
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
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
                    <img src="<?= htmlspecialchars($image_path) ?>" id="previewImage" class="card-img-top" style="width: 100%; height: 100%;">
                </div>
                <div class="product-info">
                    <h1 id="previewTitle"><?= htmlspecialchars($product['name']) ?></h1>
                    <h2>$<span id="previewPrice"><?= htmlspecialchars($product['price']) ?></span></h2>
                </div>
            </div>
        </div>

        <div class="listing-form-container">
            <form action="edit_listing.php?id=<?= $product_id ?>" id="listingForm" method="POST" enctype="multipart/form-data" class="mt-4">
                <div class="mb-3">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($product['name']) ?>" oninput="updatePreview()">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" required><?= htmlspecialchars($product['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price ($)</label>
                    <input type="number" name="price" id="price" class="form-control" step="0.01" required min="0.01" value="<?= htmlspecialchars($product['price']) ?>" oninput="updatePreview()">
                </div>

                <div class="mb-3">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" name="stock" id="stock" class="form-control" required min="1" value="<?= htmlspecialchars($product['stock']) ?>">
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-control" required>
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($product['category'] === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*" onchange="previewImage()">
                </div>

                <div class="d-flex justify-content-center gap-3 mb-3">
                    <a class="cta-2 hover-raise" href="my_shop.php">Cancel</a>
                    <button type="submit" class="cta hover-raise">Save Changes</button>
                    <button type="button" class="btn btn-danger hover-raise" onclick="showDeleteConfirmation()">Delete Listing</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background-color: white; padding: 20px; border-radius: 5px; width: 80%; max-width: 500px; margin: auto;">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this listing? This action cannot be undone.</p>
            <form method="POST" action="user_delete_listing.php">
                <input type="hidden" name="item_id" value="<?= $product_id ?>">
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="cta-2" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

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

        function showDeleteConfirmation() {
            document.getElementById("deleteModal").style.display = "flex";
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").style.display = "none";
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById("deleteModal");
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }

        document.getElementById("previewTitle").innerText = "<?= htmlspecialchars($product['name']) ?>";
        document.getElementById("previewPrice").innerText = "<?= htmlspecialchars($product['price']) ?>";
    </script>
</body>
</html>
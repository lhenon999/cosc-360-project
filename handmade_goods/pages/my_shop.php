<?php
session_start();
$is_logged_in = isset($_SESSION["user_id"]);

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// include __DIR__ . '/../config.php';

// var_dump($_SESSION);


include __DIR__ . '/../config.php';

$user_email = $_SESSION["user_id"];
$products = [];

$stmt = $conn->prepare("SELECT id, name, price, img, stock FROM ITEMS WHERE user_id = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();
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
    <link rel="stylesheet" href="../assets/css/product_card.css?v=2">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <h1 class="text-center">My Shop</h1>
    <p class="text-center">Browse and edit your listings</p>
    <br>
    <div class="d-flex justify-content-center gap-3 mb-5">
        <a class="cta hover-raise" href="create_listing.php">
            <span class="material-symbols-outlined">add</span> Create a new listing
        </a>
        
        <?php if (!empty($products)): ?>
        <button class="cta-2 hover-raise" onclick="showRegenerateModal()">
            <span class="material-symbols-outlined">refresh</span> Fix Product Images
        </button>
        <?php endif; ?>
    </div>

    <!-- Regenerate Images Modal -->
    <div id="regenerateModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background-color: white; padding: 20px; border-radius: 5px; width: 80%; max-width: 500px; margin: auto;">
            <h3>Fix Product Images</h3>
            <p>This will update how your product images are displayed. To fix current images, please re-upload them by editing each product.</p>
            <p>For best results:</p>
            <ol>
                <li>Click "Edit" on each product</li>
                <li>Re-upload the product image</li>
                <li>Save your changes</li>
            </ol>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="cta-2" onclick="closeRegenerateModal()">Close</button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="scrollable-container">
            <div class="listing-grid">
                <?php if (!empty($products)): ?>
                    <?php $isFromProfile = true; ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $id = htmlspecialchars($product["id"]);
                        $name = htmlspecialchars($product["name"]);
                        $price = number_format($product["price"], 2);
                        $image = htmlspecialchars($product["img"]);
                        $stock = intval($product["stock"]);
                        $stock_class = $stock > 5 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                        $from_profile = "my_shop";
                        include "../assets/html/product_card.php";
                        ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">You have no current listings</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . "/../assets/html/footer.php"; ?>

    <script>
        function showRegenerateModal() {
            document.getElementById("regenerateModal").style.display = "flex";
        }

        function closeRegenerateModal() {
            document.getElementById("regenerateModal").style.display = "none";
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById("regenerateModal");
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
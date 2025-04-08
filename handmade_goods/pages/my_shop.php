<?php
session_start();
$is_logged_in = isset($_SESSION["user_id"]);

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/../config.php';

// Check if the account is frozen
$is_frozen = isset($_SESSION["is_frozen"]) && $_SESSION["is_frozen"] == 1;

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
    <link rel="stylesheet" href="../assets/css/product_card.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <h1 class="text-center">My Shop</h1>
    <p class="text-center">Browse and edit your listings</p>
    <br>

    <?php if ($is_frozen): ?>
        <div class="alert alert-warning text-center container">
            <strong>Account Notice:</strong> Your account is currently frozen. You cannot create new listings or edit existing ones. 
            Your listings are not visible to other users until your account is unfrozen by an administrator.
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger text-center container">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-center mb-5">
        <a class="m-btn g <?= $is_frozen ? 'disabled' : '' ?>" href="<?= $is_frozen ? '#' : 'create_listing.php' ?>" 
           <?= $is_frozen ? 'style="opacity: 0.6; cursor: not-allowed;" onclick="return false;"' : '' ?>>
            <span class="material-symbols-outlined">add</span> Create a new listing
        </a>
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

</body>

<?php include __DIR__ . "/../assets/html/footer.php"; ?>

</html>
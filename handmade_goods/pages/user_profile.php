<?php
    session_start();
    include __DIR__ . '/../config.php';

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: home.php");
    }

    $from_product = isset($_GET['from_product']) ? $_GET['from_product'] : null;

    $user_id = intval($_GET['id']);

    $query = "SELECT name, email, profile_picture FROM USERS WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        die("User not found.");
    }

    $first_name = explode(' ', trim($user['name']))[0];

    $query = "SELECT id, name, img, price, stock FROM ITEMS WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $products_result = $stmt->get_result();
    $products = $products_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($user['name']) ?>'s Profile</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
        </style>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/css/globals.css">
        <link rel="stylesheet" href="../assets/css/products.css">
        <link rel="stylesheet" href="../assets/css/navbar.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/product_card.css">
        <link rel="stylesheet" href="../assets/css/user_profile.css">
    </head>

    <body>
        <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

        <main class="mt-5">
            <?php if ($from_product): ?>
                <a href="<?= htmlspecialchars($from_product) ?>" class="cta-2 hover-raise back-btn">Go Back</a>
            <?php endif; ?>

            <section class="profile-header">
                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="profile-pic">
                <div class="profile-info">
                    <h1><?= htmlspecialchars($user['name']) ?></h1>
                    <a href="mailto:<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></a>
                </div>
            </section>

            <section class="reviews-containers">
                <div class="rating-summary">
                    <h3>Review Summary</h3>
                    <div class="rating-overall">
                        <span class="rating-score">4.1</span>
                        <span class="stars">★★★★☆</span>
                        <span class="rating-count">167 reviews</span>
                    </div>

                    <div class="rating-bars">
                        <div class="rating-row">
                            <span>5</span>
                            <div class="bar">
                                <div class="filled" style="width: 80%;"></div>
                            </div>
                        </div>
                        <div class="rating-row">
                            <span>4</span>
                            <div class="bar">
                                <div class="filled" style="width: 40%;"></div>
                            </div>
                        </div>
                        <div class="rating-row">
                            <span>3</span>
                            <div class="bar">
                                <div class="filled" style="width: 20%;"></div>
                            </div>
                        </div>
                        <div class="rating-row">
                            <span>2</span>
                            <div class="bar">
                                <div class="filled" style="width: 10%;"></div>
                            </div>
                        </div>
                        <div class="rating-row">
                            <span>1</span>
                            <div class="bar">
                                <div class="filled" style="width: 30%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reviews-summary">
                    <h3>Recent reviews</h3>
                </div>
            </section>


            <?php if (!empty($products)): ?>
                <section class="listings-container">
                    <div class="listings-title">
                        <h3 class="text-center mb-3"><?= htmlspecialchars($first_name) ?>'s Listings</h3>
                    </div>
                    <div class="scrollable-container">
                        <div class="listing-grid">
                            <?php $isFromProfile = true; ?>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $id = htmlspecialchars($product["id"]);
                                $name = htmlspecialchars($product["name"]);
                                $price = number_format($product["price"], 2);
                                $image = htmlspecialchars($product["img"]);
                                $stock = intval($product["stock"]);
                                $stock_class = $stock > 5 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                                $from_profile = "user_profile";
                                include "../assets/html/product_card.php";
                                ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <p class="text-center">User has no current listings</p>
            <?php endif; ?>
        </main>

        <?php include __DIR__ . '/../assets/html/footer.php'; ?>
    </body>
</html>
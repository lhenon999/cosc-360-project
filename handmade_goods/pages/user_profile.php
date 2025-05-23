<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
}

$from_product = isset($_GET['from_product']) ? $_GET['from_product'] : null;
$from_admin = isset($_GET['from']) && $_GET['from'] === 'admin';
$from_profile_listings = isset($_GET['from']) && $_GET['from'] === 'profile_listings';
$from_profile_listings_user = isset($_GET['from']) && $_GET['from'] === 'profile_listing_users';
$from_profile_users = isset($_GET['from']) && $_GET['from'] === 'profile_users';
$ref = isset($_GET['ref']) ? $_GET['ref'] : null;
$user_id = intval($_GET['id']);


$query = "SELECT name, email, profile_picture, is_frozen FROM USERS WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

$user_frozen = isset($user['is_frozen']) && $user['is_frozen'] == 1;

$first_name = explode(' ', trim($user['name']))[0];

$query = "SELECT id, name, img, price, stock FROM ITEMS WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$products_result = $stmt->get_result();
$products = $products_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$distStmt = $conn->prepare("
        SELECT r.rating, COUNT(*) AS rating_count
        FROM REVIEWS r
        JOIN ITEMS i ON r.item_id = i.id
        WHERE i.user_id = ?
        GROUP BY r.rating
    ");
$distStmt->bind_param("i", $user_id);
$distStmt->execute();
$distResult = $distStmt->get_result();
$distStmt->close();

$ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$totalReviews = 0;
$sumRatings = 0;

while ($rowDist = $distResult->fetch_assoc()) {
    $star = (int) $rowDist['rating'];
    $count = (int) $rowDist['rating_count'];
    $ratingCounts[$star] = $count;
    $totalReviews += $count;
    $sumRatings += ($star * $count);
}

$averageRating = 0;
if ($totalReviews > 0) {
    $averageRating = round($sumRatings / $totalReviews, 1);
}

$reviewsStmt = $conn->prepare("
        SELECT 
            r.rating, 
            r.comment, 
            r.created_at,
            u.id AS reviewer_id,
            u.name AS reviewer_name,
            u.profile_picture AS reviewer_pic,
            i.id AS item_id,
            i.name AS item_name
        FROM REVIEWS r
        JOIN USERS u   ON r.user_id = u.id
        JOIN ITEMS i   ON r.item_id = i.id
        WHERE i.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
$reviewsStmt->bind_param("i", $user_id);
$reviewsStmt->execute();
$recentReviews = $reviewsStmt->get_result();
$reviewsStmt->close();
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

    <main class="mt-5 <?= $user_frozen ? 'frozen-user' : '' ?>">
        <section class="profile-header">
            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="profile-pic">

            <div class="profile-info">
                <h1><?= htmlspecialchars($user['name']) ?></h1>
                <div class="profile-details">
                    <p>
                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                            <?= htmlspecialchars($user['email']) ?>
                        </a>
                    </p>
                </div>
                <?php if ($from_admin): ?>
                    <a href="profile.php" class="m-btn b w-100">Back</a>
                <?php elseif ($from_product): ?>
                    <a href="<?= htmlspecialchars($from_product . ($from_profile_listings ? (strpos($from_product, '?') !== false ? '&' : '?') . 'from=profile_listings' : '')) ?>"
                        class="m-btn b w-100" onclick="goBack(event)">Back</a>
                <?php elseif ($from_profile_listings_user): ?>
                    <a href="profile.php#listings" class="m-btn b w-100">Back</a>
                <?php elseif ($from_profile_users): ?>
                    <a href="profile.php#users" class="m-btn b w-100">Back</a>
                <?php elseif ($ref): ?>
                    <a href="<?= htmlspecialchars($ref) ?>" class="m-btn w-100">Back</a>
                <?php endif; ?>


            </div>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                <a href="profile.php?from=admin&user=<?= urlencode($user['name']) ?>" class="manage-btn">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i> Moderate
                </a>
            <?php endif; ?>
            </div>
        </section>
        <?php if ($totalReviews > 0): ?>
            <section class="reviews-containers">
                <div class="rating-summary">
                    <h3>Reviews Summary</h3>
                    <div class="rating-overall">
                        <span class="rating-score"><?= $averageRating ?></span>
                        <?php
                        $filledStars = floor($averageRating);
                        $emptyStars = 5 - $filledStars;
                        $starString = str_repeat('★', $filledStars) . str_repeat('☆', $emptyStars);
                        ?>
                        <span class="stars"><?= $starString ?></span>
                        <span class="rating-count"><?= $totalReviews ?> reviews</span>
                    </div>

                    <div class="rating-bars">
                        <?php
                        for ($star = 5; $star >= 1; $star--):
                            $count = $ratingCounts[$star];
                            $percent = ($totalReviews > 0)
                                ? round(($count / $totalReviews) * 100)
                                : 0;
                            ?>
                            <div class="rating-row">
                                <span><?= $star ?></span>
                                <div class="bar">
                                    <div class="filled" style="width: <?= $percent ?>%;"></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="reviews-summary-outer">
                    <h3>Recent Reviews on their Listings</h3>
                    <div class="reviews-summary">

                        <?php if ($recentReviews->num_rows > 0): ?>
                            <?php while ($rev = $recentReviews->fetch_assoc()):
                                $revRating = (int) $rev['rating'];
                                $revComment = htmlspecialchars($rev['comment']);
                                $revDate = date('M j, Y', strtotime($rev['created_at']));

                                $reviewerId = (int) $rev['reviewer_id'];
                                $reviewerName = htmlspecialchars($rev['reviewer_name']);
                                $reviewerPic = htmlspecialchars($rev['reviewer_pic']);

                                $nameParts = explode(" ", $reviewerName);
                                if (count($nameParts) > 1) {
                                    $firstName = $nameParts[0];
                                    $lastName = end($nameParts);
                                    $initial = strtoupper(substr($lastName, 0, 1));
                                    $reviewerNameFormatted = $firstName . ' ' . $initial;
                                } else {
                                    $reviewerNameFormatted = $reviewerName;
                                }


                                $itemId = (int) $rev['item_id'];
                                $itemName = htmlspecialchars($rev['item_name']);
                                ?>
                                <div class="single-review" style="margin-bottom: 1em;">
                                    <div class="review-meta">
                                        <div class="review-author">
                                            <a
                                                href="user_profile.php?id=<?= $reviewerId ?>&ref=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                                                <img src="<?= $reviewerPic ?>" alt="Reviewer Profile"
                                                    style="width: 40px; height: 40px; border-radius:50%; object-fit:cover; margin-right:8px;">
                                                <strong><?= $reviewerNameFormatted ?></strong>
                                            </a>
                                        </div>
                                        <p class="review-comment"><?= $revComment ?></p>
                                        <div class="review-product">
                                            <a href="product.php?id=<?= $itemId ?>&and=user_profile"><?= $itemName ?></a>
                                            <em>(<?= $revDate ?>)</em>
                                        </div>
                                    </div>
                                    <div class="review-stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++):
                                            $starClass = ($i <= $revRating) ? 'star-filled' : '';
                                            echo "<span class='star $starClass'>" . ($starClass == 'star-filled' ? "★" : "☆") . "</span>";
                                        endfor;
                                        ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No reviews yet for this user’s products.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif ?>


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
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../assets/html/footer.php'; ?>
</body>

</html>
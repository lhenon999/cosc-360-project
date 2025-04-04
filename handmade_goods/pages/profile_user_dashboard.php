<?php
// Get reviews
$myReviewsStmt = $conn->prepare("
    SELECT 
        r.id AS review_id,
        r.rating,
        r.comment,
        r.created_at,
        i.id AS item_id,
        i.name AS item_name,
        i.user_id AS seller_id,
        seller.name AS seller_name
    FROM REVIEWS r
    JOIN ITEMS i ON r.item_id = i.id
    JOIN USERS seller ON i.user_id = seller.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$myReviewsStmt->bind_param("i", $user_id);
$myReviewsStmt->execute();
$myReviewsResult = $myReviewsStmt->get_result();
$myReviewsStmt->close();

// get ratings summary
$ratingDistStmt = $conn->prepare("
    SELECT rating, COUNT(*) AS rating_count
    FROM REVIEWS
    WHERE user_id = ?
    GROUP BY rating
");
$ratingDistStmt->bind_param("i", $user_id);
$ratingDistStmt->execute();
$ratingDistResult = $ratingDistStmt->get_result();
$ratingDistStmt->close();

$ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$totalReviews = 0;
$sumRatings = 0;

while ($row = $ratingDistResult->fetch_assoc()) {
    $r = (int) $row['rating'];
    $count = (int) $row['rating_count'];
    $ratingCounts[$r] = $count;
    $totalReviews += $count;
    $sumRatings += ($r * $count);
}

$averageRating = 0;
if ($totalReviews > 0) {
    $averageRating = round($sumRatings / $totalReviews, 1);
}

// Fetch total earnings
$stmt = $conn->prepare("
    SELECT SUM(price * quantity) AS total_earnings 
    FROM SALES 
    WHERE seller_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$totalEarnings = 0;
if ($row = $result->fetch_assoc()) {
    $totalEarnings = $row["total_earnings"] ?? 0;
}
$stmt->close();
?>


<div class="profile-tabs mt-5">
    <nav class="tabs-nav">
        <label>
            <a href="#orders" class="tab-link">My Orders</a>
        </label>
        <label>
            <a href="#reviews" class="tab-link">My Reviews</a>
        </label>
        <label>
            <a href="#sales" class="tab-link">My Sales</a>
        </label>
        <div class="tab-slider"></div>
    </nav>
</div>

<div class="tab-content">
    <div id="orders" class="tab-pane active">
        <h3>My Orders</h3>
        <?php
        $stmt = $conn->prepare("
                SELECT id, total_price, status, created_at
                FROM ORDERS
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $order["id"] ?></td>
                            <td>$<?= number_format($order["total_price"], 2) ?></td>
                            <td>
                                <span class="status <?= strtolower($order["status"]) ?>">
                                    <?= htmlspecialchars($order["status"]) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($order["created_at"])) ?></td>
                            <td>
                                <a href="order_details.php?order_id=<?= $order["id"] ?>" class="view-btn">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no orders yet.</p>
        <?php endif;
        $stmt->close();
        ?>
    </div>
    <div id="reviews" class="tab-pane">
        <div class="reviews-containers">
            <div class="rating-summary">
                <h3>Review Summary</h3>

                <div class="rating-overall">
                    <span class="rating-score"><?= $averageRating ?></span>
                    <?php
                    $filledStars = floor($averageRating);
                    $emptyStars = 5 - $filledStars;
                    $starOutput = str_repeat('★', $filledStars) . str_repeat('☆', $emptyStars);
                    ?>
                    <span class="stars"><?= $starOutput ?></span>

                    <span class="rating-count"><?= $totalReviews ?> reviews</span>
                </div>

                <div class="rating-bars">
                    <?php
                    for ($r = 5; $r >= 1; $r--):
                        $percent = ($totalReviews > 0)
                            ? round(($ratingCounts[$r] / $totalReviews) * 100)
                            : 0;
                        ?>
                        <div class="rating-row">
                            <span><?= $r ?></span>
                            <div class="bar">
                                <div class="filled" style="width: <?= $percent ?>%;"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <a class="m-btn g" href="my_activity.php">
                    <span class="material-symbols-outlined">favorite</span>My Activity
                </a>
            </div>

            <div class="reviews-summary-outer">
                <div class="reviews-summary">
                    <h3>Reviews</h3>
                    <?php if ($myReviewsResult->num_rows > 0): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Seller</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $myReviewsResult->fetch_assoc()): ?>
                                    <?php
                                    // Protect special chars, format date, etc.
                                    $itemId = (int) $row['item_id'];
                                    $itemName = htmlspecialchars($row['item_name']);
                                    $sellerName = htmlspecialchars($row['seller_name']);
                                    $rating = (int) $row['rating'];
                                    $comment = htmlspecialchars($row['comment']);
                                    $date = date('M j, Y', strtotime($row['created_at']));
                                    ?>
                                    <tr>
                                        <td>
                                            <!-- Link to product page by item ID -->
                                            <a href="product.php?id=<?= $itemId ?>">
                                                <?= $itemName ?>
                                            </a>
                                        </td>
                                        <td><?= $sellerName ?></td>
                                        <td><?= $rating ?></td>
                                        <td><?= $comment ?></td>
                                        <td><?= $date ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>You haven't left any reviews yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div id="sales" class="tab-pane">
        <div class="sales-container">
            <?php if ($totalEarnings > 0): ?>
                <div class="earnings-summary">
                    <div class="chart-container" style="width: 300px; height: 300px;">
                        <h3>Total Earnings</h3>
                        <canvas id="earningsChart"></canvas>
                        <p>Total Earnings: $<span id="totalEarnings"><?= number_format($totalEarnings, 2) ?></span></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="sales-summary">
                <h3>Sales History</h3>

                <?php
                $stmt = $conn->prepare("
        SELECT s.id, s.order_id, s.buyer_id, s.item_id, s.quantity, s.price, s.sale_date,
            u.name AS buyer_name, u.profile_picture,
            i.name AS item_name
        FROM SALES s
        JOIN USERS u ON s.buyer_id = u.id
        JOIN ITEMS i ON s.item_id = i.id
        WHERE s.seller_id = ?
        ORDER BY s.sale_date DESC
    ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Buyer</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Sale Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sale = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $sale["id"] ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars($sale["profile_picture"]) ?>" alt="Profile Picture">
                                        <?= htmlspecialchars($sale["buyer_name"]) ?>
                                    </td>
                                    <td><?= htmlspecialchars($sale["item_name"]) ?></td>
                                    <td><?= htmlspecialchars($sale["quantity"]) ?></td>
                                    <td>$<?= number_format($sale["price"], 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($sale["sale_date"])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>You have no sales history yet.</p>
                <?php endif;
                $stmt->close();
                ?>
            </div>


        </div>
    </div>


</div>
</div>
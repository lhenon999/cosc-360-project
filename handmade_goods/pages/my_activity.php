<?php
include '../config.php';
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

$myReviewsStmt = $conn->prepare("
    SELECT 
        r.id AS review_id,
        r.rating,
        r.comment,
        r.created_at,
        i.id AS item_id,
        i.name AS item_name,
        i.user_id AS seller_id,
        s.name AS seller_name
    FROM REVIEWS r
    INNER JOIN ITEMS i ON r.item_id = i.id
    INNER JOIN USERS s ON i.user_id = s.id
    WHERE r.user_id = ?
    AND i.status = 'active'
    ORDER BY r.created_at DESC
");
$myReviewsStmt->bind_param("i", $user_id);
$myReviewsStmt->execute();
$myReviewsResult = $myReviewsStmt->get_result();
$myReviewsStmt->close();
?>

<div class="activity-container">
    <h3>My Activity</h3>
    <?php if ($myReviewsResult->num_rows > 0): ?>
        <table class="orders-table" id="activity-table">
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
                    $itemId = (int)$row['item_id'];
                    $itemName = htmlspecialchars($row['item_name']);
                    $sellerName = htmlspecialchars($row['seller_name']);
                    $rating = (int)$row['rating'];
                    $comment = htmlspecialchars($row['comment']);
                    $date = date('M j, Y', strtotime($row['created_at']));
                    ?>
                    <tr>
                        <td>
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

<button class="m-btn" onclick="switchToReviews()">Back</button>
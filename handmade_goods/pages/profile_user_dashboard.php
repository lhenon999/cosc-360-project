<?php
// Fetch total earnings
$stmt = $conn->prepare("
    SELECT SUM(price * quantity) AS total_earnings 
    FROM sales 
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
        <a href="#orders" class="active">My Orders</a>
        <a href="#reviews">My Reviews</a>
        <a href="#sales">Sales</a>
    </nav>

    <div class="tab-content">
        <div id="orders" class="tab-pane active">
            <h3>My Orders</h3>
            <?php
            $stmt = $conn->prepare("
                SELECT id, total_price, status, created_at
                FROM orders
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
                    <h3>Reviews</h3>

                    <?php if (!empty($all_users)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>User Type</th>
                                    <th>Total Orders</th>
                                    <th>Total Listings</th>
                                    <th>Joined</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user["name"]) ?></td>
                                        <td><?= htmlspecialchars($user["email"]) ?></td>
                                        <td><span
                                                class="user-type <?= htmlspecialchars($user["user_type"]) ?>"><?= ucfirst(htmlspecialchars($user["user_type"])) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($user["total_orders"]) ?></td>
                                        <td><?= htmlspecialchars($user["total_listings"]) ?></td>
                                        <td><?= date('M j, Y', strtotime($user["created_at"])) ?></td>
                                        <td>
                                            <a href="user_profile.php?id=<?= htmlspecialchars($user["id"]) ?>"
                                                class="view-btn">View Profile</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No users found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div id="sales" class="tab-pane">
            <div class="sales-container">
                <div class="earnings-summary">
                    <div class="chart-container" style="width: 300px; height: 300px;">
                        <h3>Total Earnings</h3>
                        <canvas id="earningsChart"></canvas>
                        <p>Total Earnings: $<span id="totalEarnings"><?= number_format($totalEarnings ?? 0, 2) ?></span>
                        </p>
                    </div>
                </div>

                <div class="sales-summary">
                    <h3>Sales History</h3>

                    <?php
                    $stmt = $conn->prepare("
        SELECT s.id, s.order_id, s.buyer_id, s.item_id, s.quantity, s.price, s.sale_date,
               u.name AS buyer_name, u.profile_picture,
               i.name AS item_name
        FROM sales s
        JOIN users u ON s.buyer_id = u.id
        JOIN items i ON s.item_id = i.id
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
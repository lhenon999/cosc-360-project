<div class="profile-tabs mt-5">
    <nav class="tabs-nav">
        <?php if ($user_type === 'admin'): ?>
            <label>
                <a href="#users" class="tab-link active">Users</a>
            </label>
            <label>
                <a href="#listings" class="tab-link">Listings</a>
            </label>
            <div class="tab-slider-admin"></div>
        <?php endif; ?>
    </nav>
</div>

    <div class="tab-content">
        <?php if ($user_type === 'admin'): ?>
            <div id="users" class="tab-pane active">
                <h3>User Management</h3>
                <input type="text" id="userSearch" class="form-control mb-3" placeholder="Search users..."
                    onkeyup="filterTable('usersTable', 'userSearch')">
                <?php if (!empty($all_users)): ?>
                    <table class="users-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Profile</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Total Orders</th>
                                <th>Total Listings</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                                <th>Moderate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture"
                                            id="profile-img-users-table">
                                    </td>
                                    <td><?= htmlspecialchars($user["name"]) ?></td>
                                    <td><?= htmlspecialchars($user["email"]) ?></td>
                                    <td><?= $user["total_orders"] ?></td>
                                    <td><?= $user["total_listings"] ?></td>
                                    <td><?= date('M j, Y', strtotime($user["created_at"])) ?></td>
                                    <td>
                                        <a href="user_profile.php?id=<?= $user["id"] ?><?= ($user_type === 'admin') ? '&from=admin' : '' ?>"
                                            class="view-btn">
                                            View Profile
                                        </a>
                                    </td>
                                    <td>
                                        <button type="button" class="manage-btn"
                                            onclick="showManageModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')">Moderate</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            </div>

            <div id="listings" class="tab-pane">
                <h3>Product Inventory Management</h3>
                <input type="text" id="listingsSearch" class="form-control mb-3" placeholder="Search listings..."
                    onkeyup="filterTable('listingsTable', 'listingsSearch')">

                <?php
                $stmt = $conn->prepare("
                    SELECT i.*, u.name as seller_name, u.email as seller_email,
                           (SELECT COUNT(*) FROM order_items oi WHERE oi.item_id = i.id) as total_orders
                    FROM items i
                    JOIN users u ON i.user_id = u.id
                    ORDER BY i.stock ASC, i.name ASC
                ");
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0): ?>
                    <table class="inventory-table" id="listingsTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Stock</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Total Orders</th>
                                <th>Actions</th>
                                <th>Moderate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $result->fetch_assoc()): ?>
                                <tr class="<?= $item['stock'] < 5 ? 'low-stock' : '' ?>">
                                    <td><?= htmlspecialchars($item["name"]) ?></td>
                                    <td>
                                        <span
                                            class="stock-level <?= $item['stock'] < 5 ? 'critical' : ($item['stock'] < 10 ? 'warning' : 'good') ?>">
                                            <?= $item["stock"] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($item["category"]) ?></td>
                                    <td>$<?= number_format($item["price"], 2) ?></td>
                                    <td><?= $item["total_orders"] ?></td>
                                    <td>
                                        <a href="product.php?id=<?= $item["id"] ?>&from=profile_listings" class="view-btn">
                                            View Listing
                                        </a>

                                    </td>

                                    <td>
                                        <a href="user_profile.php?id=<?= $item["user_id"] ?>&from=admin"></a>
                                        <button type="button" class="delete-btn"
                                            onclick="showDeleteListingModal(<?= $item['id'] ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No products found in the inventory.</p>
                <?php endif;
                $stmt->close();
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="manageModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-account-name">Manage Account: <span id="accountName"></span></h3>
        <br>
        <p>Freezing an account blocks all listings and orders.</p>
        <div>
            <input type="hidden" name="user_id" id="manageUserId">
            <input type="hidden" name="user_id" id="deleteUserId">
        </div>

        <div class="modal-buttons">
            <div class="modal-buttons">
                <form id="freezeForm" method="POST" action="freeze_account.php">
                    <input type="hidden" name="user_id" id="manageUserId">
                    <button type="submit" class="freeze-btn">Freeze Account</button>
                </form>


                <button type="button" class="confirm-btn" id="deleteFromManage">Delete Account</button>
                <button type="button" class="cancel-btn" onclick="closeModal('manageModal')">Cancel</button>
            </div>

        </div>
    </div>
</div>

<div id="deleteUserModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete this account? This action cannot be undone.</p>
        <form id="deleteUserForm" method="POST" action="delete_user.php">
            <input type="hidden" name="user_id" id="deleteUserId">
            <div class="modal-buttons">
                <button type="submit" class="confirm-btn">Delete</button>
                <button type="button" class="cancel-btn" onclick="closeModal('deleteUserModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteListingModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete this Listing? This action cannot be undone.</p>
        <form method="POST" action="delete_listing.php">
            <input type="hidden" name="item_id" id="deleteListingItemId">
            <div class="modal-buttons">
                <button type="submit" class="confirm-btn">Delete</button>
                <button type="button" class="cancel-btn" onclick="closeModal('deleteListingModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>
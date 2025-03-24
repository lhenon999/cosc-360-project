<div class="listing-item-container">
    <?php
    $from = '';
    $and = ''; 
    if (isset($from_profile_users) && $from_profile_users) {
        $from = '&from=profile_users';
    } elseif (isset($from_profile_listings) && $from_profile_listings) {
        $from = '&from=profile_listing_users';
    }
    if (isset($isFromProfile) && $isFromProfile) {
        $and = '&and=user_profile';
    } 
    ?>
    <a class="listing-item" href="../pages/product.php?id=<?= $id . $from . $and ?>">

        <div class="product-image-container">
            <img src="<?= $image ?>" alt="<?= htmlspecialchars($name) ?>" class="product-image">
            <?php if (isset($stock)): ?>
                <div class="stock-badge <?= $stock_class ?>">
                    <?php if ($stock <= 0): ?>
                        Out of Stock
                    <?php elseif ($stock <= 5): ?>
                        Only <?= $stock ?> left!
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="product-info">
            <h4 class="product-title"><?= htmlspecialchars($name) ?></h4>
            <p class="product-price">$<?= number_format($price, 2) ?></p>
        </div>
    </a>
</div>
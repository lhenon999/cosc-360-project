<link rel="stylesheet" href="../css/product_card_styles.css">

<div class="listing-item-container">
    <div class="listing-item">
        <img src="<?= $image ?>" alt="<?= htmlspecialchars($name) ?>" class="product-image full-size">
        <div class="button-container hidden-buttons">
            <button class="view-btn">View</button>
            <button class="cart-btn">Add to Cart</button>
        </div>
    </div>
    <div class="product-info">
        <h4 class="product-title"><?= htmlspecialchars($name) ?></h4>
        <p class="product-price">$<?= number_format($price, 2) ?></p>
    </div>
</div>



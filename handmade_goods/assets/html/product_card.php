<link rel="stylesheet" href="../css/product_card_styles.css">

<a class="listing-item-container" href="../pages/product.php?id=<?= $id ?>">
    <div class="listing-item">
        <img src="<?= $image ?>" alt="<?= htmlspecialchars($name) ?>" class="product-image">
    </div>
    <div class="product-info">
        <h4 class="product-title"><?= htmlspecialchars($name) ?></h4>
        <p class="product-price">$<?= number_format($price, 2) ?></p>
    </div>
</a>



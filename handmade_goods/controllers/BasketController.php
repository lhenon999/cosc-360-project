<?php
require_once '../app/models/Basket.php';

class BasketController {
    public function addToBasket($productId, $quantity) {
        $basket = new Basket();
        return $basket->addItem($productId, $quantity);
    }

    public function updateBasket($productId, $quantity) {
        $basket = new Basket();
        return $basket->updateItem($productId, $quantity);
    }

    public function removeFromBasket($productId) {
        $basket = new Basket();
        return $basket->removeItem($productId);
    }
}
?>

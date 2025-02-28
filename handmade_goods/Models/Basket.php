<?php
require_once '../../config/db.php';

class Basket {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function addItem($productId, $quantity) {
        $stmt = $this->db->prepare("INSERT INTO basket (product_id, quantity) VALUES (?, ?)");
        return $stmt->execute([$productId, $quantity]);
    }

    public function updateItem($productId, $quantity) {
        $stmt = $this->db->prepare("UPDATE basket SET quantity = ? WHERE product_id = ?");
        return $stmt->execute([$quantity, $productId]);
    }

    public function removeItem($productId) {
        $stmt = $this->db->prepare("DELETE FROM basket WHERE product_id = ?");
        return $stmt->execute([$productId]);
    }
}
?>

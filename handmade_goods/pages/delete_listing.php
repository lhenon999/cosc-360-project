<?php
session_start();
include __DIR__ . '/../config.php';

if (isset($_GET['id'])) {
    $listingId = $_GET['id'];

    $sql = "DELETE FROM ITEMS WHERE id = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param('i', $listingId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<script>
                    alert('Listing has been deleted successfully.');
                    window.location.href = 'my_shop.php';
                  </script>";
            exit;
        } else {
            echo "<script>
                    alert('Error');
                    window.location.href = 'my_shop.php';
                  </script>";
            exit;
        }
    $stmt->close();
    }
}

$conn->close();
header("Location: my_shop.php");
exit;

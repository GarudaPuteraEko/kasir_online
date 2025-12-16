<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);

if ($product_id <= 0) {
    exit();
}

// Cek stok
$stock_result = $conn->query("SELECT stock FROM products WHERE id = $product_id");
if ($stock_result->num_rows == 0) {
    exit();
}
$stock = $stock_result->fetch_assoc()['stock'];

if ($quantity > $stock) {
    $quantity = $stock;  // Batasi sesuai stok
}

if ($quantity <= 0) {
    $conn->query("DELETE FROM cart WHERE user_id = $user_id AND product_id = $product_id");
} else {
    $check = $conn->query("SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = $quantity WHERE user_id = $user_id AND product_id = $product_id");
    } else {
        $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
    }
}
?>
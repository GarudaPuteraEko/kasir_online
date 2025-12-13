<?php
include 'config.php';
session_start();
require_login();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Proses tambah ke cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['add_to_cart'];
    $quantity = (int)($_POST['quantity'][$product_id] ?? 1);
    if ($quantity < 1) $quantity = 1;

    $product_result = $conn->query("SELECT stock FROM products WHERE id = $product_id");
    $product = $product_result->fetch_assoc();

    if ($product && $product['stock'] >= $quantity) {
        $check = $conn->query("SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE cart SET quantity = quantity + $quantity WHERE user_id = $user_id AND product_id = $product_id");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
        }
        $message = "Produk berhasil ditambahkan ke keranjang!";
    } else {
        $message = "Stok tidak cukup!";
    }
}

// Ambil produk stok > 0
$products = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY name");

// Hitung item di cart
$cart_count_result = $conn->query("SELECT SUM(quantity) AS total FROM cart WHERE user_id = $user_id");
$cart_count = $cart_count_result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pilih Produk</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h3 { color: #854442; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #854442; color: white; }
        img { width: 80px; height: 80px; object-fit: cover; }
        input { width: 60px; padding: 5px; }
        button { padding: 5px 10px; background: #854442; color: white; border: none; cursor: pointer; }
        a { color: #854442; }
        .message { text-align: center; padding: 10px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h3>Pilih Produk</h3>
    <?php if (is_admin()): ?>
        <a href="dashboard.php">Kembali ke Dashboard</a> | 
    <?php endif; ?>
    <a href="cart.php">Keranjang (<?= $cart_count ?> item)</a>
    <?php if (is_kasir()): ?>
        | <a href="confirm_orders.php">Konfirmasi pembelian User</a>
    <?php endif; ?>
    <?php if (is_kasir() || is_user()): ?>
        | <a href="history.php">Riwayat Transaksi</a> | 
        <a href="logout.php">Logout</a>
    <?php endif; ?>
    <hr>

    <form method="POST">
        <table>
            <tr>
                <th>Gambar</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Jumlah</th>
                <th>Aksi</th>
            </tr>
            <?php while($row = $products->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if ($row['image']): ?>
                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                    <?php else: ?>
                        <img src="https://thumbs.dreamstime.com/b/simple-coffee-cup-logo-steam-company-name-placeholder-minimalist-featuring-hand-drawn-style-saucer-rising-above-411426423.jpg" alt="No image">
                    <?php endif; ?>
                </td>
                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                <td><?= $row['price'] ?></td>
                <td><?= $row['stock'] ?></td>
                <td>
                    <input type="number" name="quantity[<?= $row['id'] ?>]" value="1" min="1" max="<?= $row['stock'] ?>">
                </td>
                <td>
                    <button type="submit" name="add_to_cart" value="<?= $row['id'] ?>">Tambah</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </form>

    <?php if ($products->num_rows == 0): ?>
        <p>Tidak ada produk yang tersedia saat ini.</p>
    <?php endif; ?>

    <hr>
    <?php if (isset($message)): ?>
        <div class="message" style="color: <?= strpos($message, 'berhasil') ? 'green' : 'red' ?>;">
            <?= $message ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
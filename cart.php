<?php
include 'config.php';
session_start();
require_login();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Hapus item dari cart
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    $conn->query("DELETE FROM cart WHERE user_id = $user_id AND product_id = $product_id");
    header("Location: cart.php");
    exit();
}

// Kosongkan cart
if (isset($_GET['clear'])) {
    $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    header("Location: cart.php");
    exit();
}

// Checkout: Proses pembayaran semua isi cart
if (isset($_POST['checkout'])) {
    $payment_method = $_POST['payment_method']; // Ambil pilihan pembayaran

    $cart_items = $conn->query("SELECT c.*, p.name, p.price, p.stock, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");

    while ($item = $cart_items->fetch_assoc()) {
        $qty = $item['quantity'];
        $total_price = $item['price'] * $qty;

        if ($item['stock'] >= $qty) {
            // Kurangi stok
            $new_stock = $item['stock'] - $qty;
            $conn->query("UPDATE products SET stock = $new_stock WHERE id = " . $item['product_id']);

            // Catat transaksi dengan metode pembayaran
            $conn->query("INSERT INTO transactions (user_id, product_id, quantity, total_price, payment_method) 
                          VALUES ($user_id, " . $item['product_id'] . ", $qty, $total_price, '$payment_method')");
        }
    }

    // Kosongkan cart setelah sukses
    $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    header("Location: cart.php?success=1");
    exit();
}

// Ambil isi cart dengan gambar
$cart_items = $conn->query("SELECT c.*, p.name, p.price, p.stock, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Keranjang Belanja</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h3 { color: #854442; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #854442; color: white; }
        button { padding: 8px 15px; background: #854442; color: white; border: none; cursor: pointer; }
        a { color: #854442; }
        .total { font-size: 18px; font-weight: bold; text-align: right; margin: 20px 0; }
        img { object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h3>Keranjang Belanja</h3>
    <a href="transaction.php">← Tambah Produk Lagi</a> | 
    <a href="history.php">Riwayat Transaksi</a>
    <hr>

    <?php if (isset($_GET['success'])): ?>
        <p style="color:green; font-weight:bold;">
            Transaksi berhasil! Keranjang telah dikosongkan. 
            <a href="history.php">Lihat Riwayat Transaksi</a>
        </p>
    <?php endif; ?>

    <?php if ($cart_items->num_rows == 0): ?>
        <p>Keranjang kosong.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Gambar</th>
                <th>Produk</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
                <th>Aksi</th>
            </tr>
            <?php 
            $grand_total = 0;
            while($item = $cart_items->fetch_assoc()): 
                $subtotal = $item['price'] * $item['quantity'];
                $grand_total += $subtotal;
            ?>
            <tr>
                <td>
                    <?php if ($item['image']): ?>
                        <img src="<?= $item['image'] ?>" width="80" height="80" alt="<?= $item['name'] ?>">
                    <?php else: ?>
                        <img src="https://thumbs.dreamstime.com/b/steaming-coffee-cup-logo-company-name-placeholder-simple-elegant-featuring-white-steam-rising-placed-saucer-411426360.jpg" width="60" height="60" alt="No image">
                    <?php endif; ?>
                </td>
                <td><?= $item['name'] ?></td>
                <td><?= $item['price'] ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= $subtotal ?></td>
                <td><a href="cart.php?remove=<?= $item['product_id'] ?>" onclick="return confirm('Hapus dari keranjang?')">Hapus</a></td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="4"><strong>Total</strong></td>
                <td colspan="2"><strong><?= $grand_total ?></strong></td>
            </tr>
        </table>

        <div class="total">
            Total Bayar: <?= $grand_total ?>
        </div>

        <form method="POST" onsubmit="return confirm('Yakin checkout semua item?');">
            <strong>Metode Pembayaran:</strong><br>
            <select name="payment_method" required style="padding:5px; margin:10px 0; width:300px;">
                <option value="Tunai">Tunai (Cash)</option>
                <option value="QRIS/E-Wallet">QRIS / E-Wallet (GoPay, OVO, DANA, dll.)</option>
                <option value="Kartu Debit/Kredit">Kartu Debit / Kredit</option>
            </select>
            <br><br>
            <button type="submit" name="checkout">Checkout & Bayar</button>
            <a href="cart.php?clear=1" onclick="return confirm('Kosongkan keranjang?')">Kosongkan Keranjang</a>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
<?php
include 'config.php';
session_start();
require_login();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Ambil semua transaksi user, urut dari terbaru
$transactions = $conn->query("
    SELECT t.id, t.quantity, t.total_price, t.transaction_date, t.payment_method, p.name, p.price, u.username
    FROM transactions t 
    JOIN products p ON t.product_id = p.id 
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.transaction_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Riwayat Transaksi</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h3 { color: #854442; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #854442; color: white; }
        a { color: #854442; }
        .no-data { text-align: center; padding: 20px; font-style: italic; }
    </style>
</head>
<body>
<div class="container">
    <h3>Riwayat Transaksi</h3> 
    <a href="cart.php">Keranjang</a>
    <?php if (is_admin()): ?>
        | <a href="dashboard.php">Dashboard</a>
    <?php endif; ?> 
    <?php if (is_kasir()): ?>
        | <a href="dashboard.php">Kembali ke Halaman Awal</a>
    <?php endif; ?> 
    <hr>

    <?php if ($transactions->num_rows == 0): ?>
        <p class="no-data">Belum ada transaksi.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>No</th>
                <th>Tanggal & Waktu</th>
                <th>Produk</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Total</th>
                <th>Metode Pembayaran</th>
                <th>Dibuat Oleh</th>
            </tr>
            <?php 
            $no = 1;
            while($row = $transactions->fetch_assoc()): 
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= date('d-m-Y H:i:s', strtotime($row['transaction_date'])) ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= $row['price'] ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $row['total_price'] ?></td>
                <td><?= $row['payment_method'] ?></td>
                <td><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
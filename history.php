<?php
include 'config.php';
session_start();
require_login();  // Pastikan login

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Query riwayat transaksi
$transactions = $conn->query("
    SELECT t.*, p.name, p.price, u.username 
    FROM transactions t 
    JOIN products p ON t.product_id = p.id 
    LEFT JOIN users u ON t.user_id = u.id
    " . (is_user() ? "WHERE t.user_id = $user_id" : "") . "
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
        a { color: #854442; text-decoration: none; }
        .btn { padding: 5px 10px; background: #854442; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 13px; }
        .btn:hover { background: #4b3832; }
        .no-data { text-align: center; padding: 20px; font-style: italic; }
    </style>
</head>
<body>
<div class="container">
    <h3>Riwayat Transaksi</h3>

    <!-- Tombol Kembali hanya untuk Admin -->
    <?php if (is_admin()): ?>
        <a href="dashboard.php" class="btn">← Kembali ke Dashboard</a>
    <?php else: ?>
        <a href="transaction.php" class="btn">← Kembali ke Menu</a>
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
                <?php if (is_admin()): ?>
                    <th>Dibuat Oleh</th>
                <?php endif; ?>
            </tr>
            <?php 
            $no = 1;
            while($row = $transactions->fetch_assoc()): 
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= date('d-m-Y H:i:s', strtotime($row['transaction_date'])) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= number_format($row['price']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= number_format($row['total_price']) ?></td>
                <?php if (is_admin()): ?>
                    <td><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
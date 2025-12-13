<?php
include 'config.php';
session_start();
require_login();
require_kasir_or_admin();  // Hanya admin/kasir

// Konfirmasi atau reject
if (isset($_GET['confirm'])) {
    $trans_id = $_GET['confirm'];
    $trans = $conn->query("SELECT t.*, p.stock FROM transactions t JOIN products p ON t.product_id = p.id WHERE t.id = $trans_id AND t.status = 'pending'")->fetch_assoc();
    if ($trans) {
        $new_stock = $trans['stock'] - $trans['quantity'];
        $conn->query("UPDATE products SET stock = $new_stock WHERE id = " . $trans['product_id']);
        $conn->query("UPDATE transactions SET status = 'confirmed' WHERE id = $trans_id");
    }
    header("Location: confirm_orders.php");
    exit();
}

if (isset($_GET['reject'])) {
    $trans_id = $_GET['reject'];
    $conn->query("UPDATE transactions SET status = 'rejected' WHERE id = $trans_id AND status = 'pending'");
    header("Location: confirm_orders.php");
    exit();
}

// Ambil pending orders
$pendings = $conn->query("SELECT t.*, p.name, u.username FROM transactions t JOIN products p ON t.product_id = p.id JOIN users u ON t.user_id = u.id WHERE t.status = 'pending' ORDER BY t.transaction_date DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Pembelian User</title>
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
    <h3>Konfirmasi Pembelian User</h3>
    <a href="transaction.php">Kembali</a>
    <hr>

    <table>
        <tr><th>ID</th><th>User</th><th>Produk</th><th>Jumlah</th><th>Total</th><th>Metode</th><th>Tanggal</th><th>Aksi</th></tr>
        <?php while($row = $pendings->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['username'] ?></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= $row['total_price'] ?></td>
            <td><?= $row['payment_method'] ?></td>
            <td><?= $row['transaction_date'] ?></td>
            <td>
                <a href="?confirm=<?= $row['id'] ?>" onclick="return confirm('Konfirmasi? Stok akan berkurang.')">Konfirmasi</a> |
                <a href="?reject=<?= $row['id'] ?>" onclick="return confirm('Reject?')">Reject</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
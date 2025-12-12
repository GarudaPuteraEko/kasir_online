<?php
include 'config.php';
session_start();
require_login();
require_admin();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Hapus produk
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: dashboard.php");
    exit();
}

// Ambil semua produk
$products = $conn->query("SELECT * FROM products ORDER BY name");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Coffee Shop Kasir</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 1000px; margin: auto; padding: 20px; }
        h2 { color: #854442; text-align: center; }
        h3 { color: #854442; }
        a { color: #854442; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #854442; color: white; }
        img { max-width: 120px; max-height: 120px; object-fit: cover; border-radius: 8px; }
        .placeholder { opacity: 0.5; }
        .add-btn:hover { background: #6d2f2a; }
        .nav-links { margin: 20px 0; }
    </style>
</head>
<body>
<div class="container">
    <h2>Selamat Datang di Dashboard Kasir Coffee Shop</h2>

    <div class="nav-links">
        <a href="manage_users.php">Kelola Kasir</a> | 
        <a href="add_product.php">+ Tambah Produk Baru</a> |
        <a href="transaction.php">Buat Transaksi</a> | 
        <a href="history.php">Riwayat Transaksi</a> | 
        <a href="logout.php">Logout</a>
    </div>

    <hr>

    <h3>Daftar Produk</h3>

    <?php if ($products->num_rows == 0): ?>
        <p>Belum ada produk. <a href="add_product.php">Tambah produk pertama</a></p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Gambar</th>
                <th>Nama</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Deskripsi</th>
                <th>Aksi</th>
            </tr>
            <?php while($row = $products->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td>
                    <?php if ($row['image']): ?>
                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                    <?php else: ?>
                        <img src="https://media.istockphoto.com/id/1262293120/vector/coffee-cup-symbol-icon.jpg?s=612x612&w=0&k=20&c=C5VHghz8P7qraOslQk13-_ArDWHzjhm5ARZ8o7CrO6Y=" 
                             alt="No image" class="placeholder">
                    <?php endif; ?>
                </td>
                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                <td><?= $row['price'] ?></td>
                <td><?= $row['stock'] ?></td>
                <td><?= htmlspecialchars($row['description']) ?: '-' ?></td>
                <td>
                    <a href="edit_product.php?id=<?= $row['id'] ?>">Edit</a> | 
                    <a href="dashboard.php?delete=<?= $row['id'] ?>" 
                       onclick="return confirm('Yakin hapus produk ini?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
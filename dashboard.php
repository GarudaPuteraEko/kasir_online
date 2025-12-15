<?php
include 'config.php';
session_start();
require_login();
require_admin();

// Hapus produk
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: dashboard.php" . (isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit();
}

$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? '';

$search_param = "%$search%";

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= " WHERE p.name LIKE ?";
    $params[] = $search_param;
    $types .= 's';
}

if ($category_id !== '') {
    $sql .= ($search !== '' ? " AND" : " WHERE") . " p.category_id = ?";
    $params[] = $category_id;
    $types .= 'i';
}

$sql .= " ORDER BY p.id ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();
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
        a { color: #854442;}
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #854442; color: white; }
        img { max-width: 120px; max-height: 120px; object-fit: cover; border-radius: 8px; }
        .placeholder { opacity: 0.5; }
        .nav-links { margin: 20px 0; font-size: 16px; }
        button { padding: 5px 10px; background: #854442; color: white; border: none; cursor: pointer; }
        select, input[type="text"] { padding: 5px; margin: 5px 0; }
    </style>
</head>
<body>
<div class="container">
    <h2>Selamat Datang di Dashboard Kasir Coffee Shop</h2>

    <div class="nav-links">
        <a href="add_product.php">+ Tambah Produk Baru</a> |
        <a href="manage_categories.php">Kelola Kategori</a> |
        <a href="history.php">Riwayat Transaksi</a> |
        <a href="logout.php">Logout</a>
    </div>

    <hr>

    <h3>Daftar Produk</h3>

    <!-- Form Pencarian -->
    <form method="GET">
        Cari nama: <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ketik nama produk">
        
        Kategori: 
        <select name="category">
            <option value="">-- Semua --</option>
            <?php 
            $cats = $conn->query("SELECT * FROM categories ORDER BY name");
            while($cat = $cats->fetch_assoc()): ?>
            <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; ?>
        </select>
        
        <button type="submit">Filter</button>
        <?php if ($search || $category_id): ?>
            <a href="dashboard.php">Reset</a>
        <?php endif; ?>
    </form>
    <hr>

    <!-- Hasil Produk -->
    <?php if ($products->num_rows == 0): ?>
        <p>
            <?php if ($search !== ''): ?>
                Tidak ada produk ditemukan untuk pencarian "<strong><?= htmlspecialchars($search) ?></strong>".
                <a href="dashboard.php">Tampilkan semua produk</a>
            <?php else: ?>
                Belum ada produk. <a href="add_product.php">Tambah produk pertama</a>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <?php if ($search !== ''): ?>
            <p>Menampilkan hasil pencarian untuk "<strong><?= htmlspecialchars($search) ?></strong>" (<?= $products->num_rows ?> produk ditemukan)</p>
        <?php endif; ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Gambar</th>
                <th>Nama</th>
                <th>Kategori</th>
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
                <td><?= $row['category_name'] ?? '-' ?></td>
                <td><?= $row['price'] ?></td>
                <td><?= $row['stock'] ?></td>
                <td><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                <td>
                    <a href="edit_product.php?id=<?= $row['id'] ?>&search=<?= urlencode($search) ?>">Edit</a> |
                    <a href="dashboard.php?delete=<?= $row['id'] ?>&search=<?= urlencode($search) ?>" 
                       onclick="return confirm('Yakin hapus produk ini?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
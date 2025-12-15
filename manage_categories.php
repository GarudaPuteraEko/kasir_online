<?php
include 'config.php';
session_start();
require_login();
require_admin();

// Hapus kategori
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("UPDATE products SET category_id = NULL WHERE category_id = $id");
    $conn->query("DELETE FROM categories WHERE id = $id");
    header("Location: manage_categories.php");
    exit();
}

// Ambil semua kategori
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daftar Kategori</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h2 { color: #854442; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #854442; color: white; }
        a { color: #854442; text-decoration: none; }
        .btn { padding: 5px 10px; background: #854442; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 13px; }
        .btn:hover { background: #4b3832; }
        .btn-edit { padding: 5px 10px; background: #ffca28; color: black; cursor: pointer; font-size: 13px; border: 1px solid black; border-radius: 3px; margin-right: 3px; display: inline-block; }
        .btn-edit:hover { background: #f9a825; }
        .btn-delete { padding: 5px 10px; background: #d32f2f; color: white; cursor: pointer; font-size: 13px; border: 1px solid black; border-radius: 3px; display: inline-block; }
        .btn-delete:hover { background: #b71c1c; }
    </style>
</head>
<body>
<div class="container">
    <h2>Daftar Kategori</h2>
    <a href="dashboard.php" class="btn">← Kembali ke Dashboard</a>
    <a href="add_category.php" class="btn">+ Tambah Kategori Baru</a>
    <hr>

    <?php if ($categories->num_rows == 0): ?>
        <p>Belum ada kategori. <a href="add_category.php">Tambah kategori pertama</a></p>
    <?php else: ?>
        <table>
            <tr><th>ID</th><th>Nama Kategori</th><th>Aksi</th></tr>
            <?php while($row = $categories->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>
                    <a href="edit_category.php?id=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus? Semua produk akan kehilangan kategori ini.')" class="btn-delete">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
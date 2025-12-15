<?php
include 'config.php';
session_start();
require_login();
require_admin();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$id = $_GET['id'] ?? 0;
$result = $conn->query("SELECT * FROM products WHERE id = $id");
$product = $result->fetch_assoc();

if (isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $image = $_POST['image'] ?: NULL;
    $category_id = $_POST['category_id'] ?: NULL;

    $conn->query("UPDATE products SET name='$name', price=$price, stock=$stock, description='$description', image='$image', category_id=" . ($category_id ? $category_id : 'NULL') . " WHERE id=$id");
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Produk</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h3 { color: #854442; }
        input, textarea { width: 100%; padding: 5px; margin: 5px 0; }
        button { padding: 5px 10px; background: #854442; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #4b3832; }
        .btn { padding: 5px 10px; background: #854442; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 13px; }
        .btn:hover { background: #4b3832; }
        a { color: #854442; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h3>Edit Produk</h3>
    <hr>

    <form method="POST">
        Nama Produk<br>
        <input type="text" name="name" value="<?= $product['name'] ?>" required><br><br>

        Harga<br>
        <input type="number" name="price" value="<?= $product['price'] ?>" required><br><br>

        Stok<br>
        <input type="number" name="stock" value="<?= $product['stock'] ?>" required><br><br>

        Deskripsi<br>
        <textarea name="description"><?= $product['description'] ?></textarea><br><br>

        Gambar (URL Saat Ini: <?= $product['image'] ? $product['image'] : 'Tidak ada' ?>)<br>
        <input type="text" name="image" value="<?= htmlspecialchars($product['image']) ?>" placeholder="https://example.com/gambar.jpg"><br><br>

        Kategori<br>
        <select name="category_id">
            <option value="">-- Tidak Ada Kategori --</option>
            <?php 
            $cats = $conn->query("SELECT * FROM categories ORDER BY name");
            while($cat = $cats->fetch_assoc()): ?>
            <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= $cat['name'] ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit" name="update_product">Update Produk</button>
        <a href="dashboard.php" class="btn">Batal</a>
    </form>
</div>
</body>
</html>
<?php
include 'config.php';
session_start();
require_login();
require_admin();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['add_product'])) {
    $name        = $_POST['name'];
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];
    $description = $_POST['description'];
    $image       = $_POST['image'] ?: NULL;
    $category_id = $_POST['category_id'] ?: NULL;

    $sql = "INSERT INTO products (name, price, stock, description, image, category_id) 
        VALUES ('$name', $price, $stock, '$description', '$image', " . ($category_id ? $category_id : 'NULL') . ")";

    if ($conn->query($sql) === TRUE) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h3 { color: #854442; }
        input, textarea { width: 100%; padding: 5px; margin: 5px 0; }
        button { padding: 5px 10px; background: #854442; color: white; border: none; cursor: pointer; }
        .btn { padding: 5px 10px; background: #854442; color: white; border: none; cursor: pointer; font-size: 13px; }
        a { color: #854442; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h3>Tambah Produk Baru</h3>
    <a href="dashboard.php" class="btn">Kembali ke Dashboard</a>
    <hr>

    <form method="POST">
        Nama Produk<br>
        <input type="text" name="name" required><br><br>
        
        Harga<br>
        <input type="number" name="price" required><br><br>
        
        Stok<br>
        <input type="number" name="stock" required><br><br>
        
        Deskripsi<br>
        <textarea name="description"></textarea><br><br>
        
        Gambar (URL)<br>
        <input type="text" name="image" placeholder="https://example.com/gambar.jpg"><br><br>
        
        Kategori<br>
        <select name="category_id">
            <option value="">-- Tidak Ada Kategori --</option>
            <?php 
            $cats = $conn->query("SELECT * FROM categories ORDER BY name");
            while($cat = $cats->fetch_assoc()): ?>
            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit" name="add_product">Tambah Produk</button>
    </form>
</div>
</body>
</html>
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

    $sql = "INSERT INTO products (name, price, stock, description, image) 
            VALUES ('$name', '$price', '$stock', '$description', '$image')";

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
        a { color: #854442; }
    </style>
</head>
<body>
<div class="container">
    <h3>Tambah Produk Baru</h3>
    <a href="dashboard.php">Kembali ke Dashboard</a>
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

        <button type="submit" name="add_product">Tambah Produk</button>
    </form>
</div>
</body>
</html>
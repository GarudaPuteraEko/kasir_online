<?php
include 'config.php';
session_start();
require_login();
require_admin();

if (!isset($_GET['id'])) {
    header("Location: manage_categories.php");
    exit();
}

$id = $_GET['id'];
$cat = $conn->query("SELECT * FROM categories WHERE id = $id")->fetch_assoc();

if (isset($_POST['update_category'])) {
    $name = $_POST['name'];
    $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    header("Location: manage_categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 600px; margin: auto; padding: 20px; }
        h2 { color: #854442; }
        input { width: 100%; padding: 8px; margin: 10px 0; }
        button { padding: 5px 10px; background: #854442; color: white; border: none; cursor: pointer; }
        a { color: #854442; text-decoration: none; }
        button { padding: 5px 10px; background: #854442; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #4b3832; }
        .btn { padding: 5px 10px; background: #854442; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 13px; }
        .btn:hover { background: #4b3832; }
    </style>
</head>
<body>
<div class="container">
    <h2>Edit Kategori</h2>
    <hr>

    <form method="POST">
        Nama Kategori<br>
        <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
        <br><br>
        <button type="submit" name="update_category">Update Kategori</button>
        <a href="manage_categories.php" class="btn">Batal</a>
    </form>
</div>
</body>
</html>
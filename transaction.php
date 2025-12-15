<?php
include 'config.php';
session_start();
require_login();
require_user();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Parameter filter
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? '';

$search_param = "%$search%";

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.stock > 0";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND p.name LIKE ?";
    $params[] = $search_param;
    $types .= 's';
}

if ($category_id !== '') {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= 'i';
}

$sql .= " ORDER BY p.name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Ambil kategori untuk dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Hitung cart
$cart_count_result = $conn->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = $user_id");
$cart_count = $cart_count_result->fetch_assoc()['total'];

// Proses tambah cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['add_to_cart'];
    $quantity = (int)($_POST['quantity'][$product_id] ?? 1);
    if ($quantity < 1) $quantity = 1;

    $product = $conn->query("SELECT stock FROM products WHERE id = $product_id")->fetch_assoc();

    if ($product && $product['stock'] >= $quantity) {
        $check = $conn->query("SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE cart SET quantity = quantity + $quantity WHERE user_id = $user_id AND product_id = $product_id");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
        }
        $message = "Produk berhasil ditambahkan ke keranjang!";
    } else {
        $message = "Stok tidak cukup!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pilih Produk</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h3 { color: #854442; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #854442; color: white; }
        img { width: 80px; height: 80px; object-fit: cover; }
        input[type="number"] { width: 60px; padding: 5px; }
        button { padding: 5px 10px; background: #854442; color: white; border: none; cursor: pointer; }
        a { color: #854442; }
        select, input[type="text"] { padding: 5px; margin: 5px 0; }
        .message { text-align: center; padding: 10px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h3>Pilih Produk</h3>
    <a href="cart.php">Keranjang (<?= $cart_count ?> item)</a> |
    <a href="logout.php">Logout</a>
    <hr>

    <!-- Filter Simple -->
    <form method="GET">
        Cari nama: <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ketik nama produk">
        
        Kategori: 
        <select name="category">
            <option value="">-- Semua --</option>
            <?php while($cat = $categories->fetch_assoc()): ?>
            <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; ?>
        </select>
        
        <button type="submit">Filter</button>
        <?php if ($search || $category_id): ?>
            <a href="transaction.php">Reset</a>
        <?php endif; ?>
    </form>
    <hr>

    <?php if (isset($message)): ?>
        <div class="message" style="color: <?= strpos($message, 'berhasil') ? 'green' : 'red' ?>;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?php if ($products->num_rows == 0): ?>
            Tidak ada produk ditemukan untuk pencarian "<strong><?= htmlspecialchars($search) ?></strong>".
                <a href="dashboard.php">Tampilkan semua produk</a>
        <?php else: ?>
            <table>
                <tr>
                    <th>Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                </tr>
                <?php while($row = $products->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($row['image']): ?>
                            <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                        <?php else: ?>
                            <img src="https://thumbs.dreamstime.com/b/simple-coffee-cup-logo-steam-company-name-placeholder-minimalist-featuring-hand-drawn-style-saucer-rising-above-411426423.jpg" alt="No image">
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td><?= $row['category_name'] ?? '-' ?></td>
                    <td><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                    <td><?= $row['price'] ?></td>
                    <td><?= $row['stock'] ?></td>
                    <td><input type="number" name="quantity[<?= $row['id'] ?>]" value="1" min="1" max="<?= $row['stock'] ?>"></td>
                    <td><button type="submit" name="add_to_cart" value="<?= $row['id'] ?>">Tambah</button></td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
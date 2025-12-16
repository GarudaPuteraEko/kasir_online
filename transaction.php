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

// Ambil kategori
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Ambil jumlah di cart untuk setiap produk
$cart_qty = [];
$cart_result = $conn->query("SELECT product_id, quantity FROM cart WHERE user_id = $user_id");
while ($c = $cart_result->fetch_assoc()) {
    $cart_qty[$c['product_id']] = $c['quantity'];
}

// Hitung grand total
$total_result = $conn->query("SELECT SUM(c.quantity * p.price) AS total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");
$grand_total = $total_result->fetch_assoc()['total'] ?? 0;

// Checkout
if (isset($_POST['checkout'])) {
    $paid_amount = (int)($_POST['paid_amount'] ?? 0);

    if ($paid_amount < $grand_total) {
        $message = "Uang pembayaran kurang! Total Rp " . number_format($grand_total) . ", dibayar Rp " . number_format($paid_amount);
    } else {
        // Generate session unik
        $checkout_session = uniqid('sess_', true);

        $cart_items = $conn->query("SELECT c.*, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");

        $all_success = true;

        while ($item = $cart_items->fetch_assoc()) {
            $qty = $item['quantity'];
            $total_price = $item['price'] * $qty;

            if ($item['stock'] >= $qty) {
                $new_stock = $item['stock'] - $qty;
                $conn->query("UPDATE products SET stock = $new_stock WHERE id = " . $item['product_id']);

                $conn->query("INSERT INTO transactions (user_id, product_id, quantity, total_price, payment_method, checkout_session) 
                              VALUES ($user_id, " . $item['product_id'] . ", $qty, $total_price, 'Tunai', '$checkout_session')");
            } else {
                $all_success = false;
            }
        }

        if ($all_success && $grand_total > 0) {
            $conn->query("DELETE FROM cart WHERE user_id = $user_id");
            // Kirim paid_amount & kembalian ke receipt
            $change = $paid_amount - $grand_total;
            header("Location: receipt.php?session=$checkout_session&paid=$paid_amount&change=$change");
            exit();
        } else {
            $message = "Checkout gagal: Stok tidak cukup.";
        }
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
        .container { display: flex; gap: 20px; max-width: 1200px; margin: auto; padding: 20px; }
        .left { flex: 2; }
        .right { flex: 1; background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 8px; position: sticky; top: 20px; height: fit-content; }
        h3 { color: #854442; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #854442; color: white; }
        img { width: 80px; height: 80px; object-fit: cover; }
        input[type="number"] { width: 80px; padding: 5px; font-size: 16px; }
        button { padding: 5px 10px; background: #854442; color: white; border: none; cursor: pointer; border-radius: 3px; }
        button:hover { background: #4b3832; }
        .btn { padding: 5px 10px; background: #854442; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 13px; }
        .btn:hover { background: #4b3832; }
        a { color: #854442; text-decoration: none; }
        select, input[type="text"] { padding: 5px; margin: 5px 0; }
        .message { text-align: center; padding: 10px; font-weight: bold; }
        .total { font-size: 24px; font-weight: bold; text-align: center; margin: 30px 0; color: #854442; }
        .checkout-btn { width: 100%; padding: 15px; font-size: 18px; background: #2e7d32; color: white; border: none; cursor: pointer; }
        .checkout-btn:hover { background: #1b5e20; }
    </style>
    <script>
        function updateCart(productId, qty) {
            if (qty < 0) qty = 0;
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "update_cart.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("product_id=" + productId + "&quantity=" + qty);
            xhr.onload = function() {
                location.reload();  // Refresh untuk update total
            };
        }
    </script>
</head>
<body>
<div class="container">
    <!-- Kiri: Daftar Produk -->
    <div class="left">
        <h3>Pilih Produk</h3>
        <a href="logout.php" class="btn">Logout</a>
        <hr>

        <!-- Filter -->
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
                <a href="transaction.php" class="btn">Reset</a>
            <?php endif; ?>
        </form>
        <hr>

        <?php if (isset($message)): ?>
            <div class="message" style="color: red;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($products->num_rows == 0): ?>
            <p>Tidak ada produk yang tersedia.</p>
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
                </tr>
                <?php while($row = $products->fetch_assoc()): 
                    $current_qty = $cart_qty[$row['id']] ?? 0;
                ?>
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
                    <td>
                        <input type="number" min="0" max="<?= $row['stock'] ?>" value="<?= $current_qty ?>" 
                               onchange="updateCart(<?= $row['id'] ?>, this.value)">
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="right">
        <h3>Ringkasan</h3>
        <hr>

        <?php if ($grand_total == 0): ?>
            <p style="text-align:center; font-size:18px; color:#888; margin:50px 0;">
                Keranjang kosong<br>
                Ubah jumlah produk di sebelah kiri
            </p>
        <?php else: ?>
            <div class="total">
                Total Bayar<br>
                Rp <?= number_format($grand_total) ?>
            </div>

            <form method="POST">
                <strong>Metode Pembayaran: Tunai</strong><br><br>

                <strong>Masukkan Nominal Pembayaran:</strong><br>
                <input type="number" name="paid_amount" min="<?= $grand_total ?>" value="<?= $grand_total ?>" 
                    style="width:100%; padding:15px; margin:15px 0; font-size:18px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box;" 
                    placeholder="Min Rp <?= number_format($grand_total) ?>" 
                    required>

                <?php if (isset($_POST['checkout'])): 
                    $paid = (int)($_POST['paid_amount'] ?? 0);
                    $change = $paid - $grand_total;
                ?>
                    <?php if ($change >= 0): ?>
                        <div style="font-size:20px; font-weight:bold; color:green; text-align:center; margin:15px 0;">
                            Kembalian: Rp <?= number_format($change) ?>
                        </div>
                    <?php else: ?>
                        <div style="font-size:18px; font-weight:bold; color:red; text-align:center; margin:15px 0;">
                            Uang tidak cukup! Kurang Rp <?= number_format(abs($change)) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <button type="submit" name="checkout" class="checkout-btn">
                    Checkout & Cetak Struk
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
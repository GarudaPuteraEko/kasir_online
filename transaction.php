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

                $conn->query("INSERT INTO transactions (user_id, product_id, quantity, total_price, checkout_session) 
                    VALUES ($user_id, " . $item['product_id'] . ", $qty, $total_price, '$checkout_session')");
            } else {
                $all_success = false;
            }
        }

        if ($all_success && $grand_total > 0) {
            $conn->query("DELETE FROM cart WHERE user_id = $user_id");
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Kopi - Pilih Produk</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f5e6d3;
            --primary: #854442;
            --dark: #4b3832;
            --light: #ffffff;
            --accent: #2e7d32;
            --accent-dark: #1b5e20;
            --border: #d9c2b0;
            --shadow: rgba(75, 56, 50, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg);
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1600px;
            margin: 20px auto;
            padding: 0 20px;
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }

        .main {
            flex: 3;
            min-width: 300px;
        }

        .sidebar {
            flex: 1;
            min-width: 350px;
            background: var(--light);
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 8px 25px var(--shadow);
            position: sticky;
            top: 20px;
            align-self: start;
        }

        header {
            text-align: center;
            margin-bottom: 25px;
        }

        header h1 {
            color: var(--primary);
            font-size: 2.2rem;
        }

        .nav {
            margin: 15px 0;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 9px 18px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin: 0 8px;
            transition: all 0.3s;
        }

        .btn:hover { background: var(--dark); }

        .filter {
            background: var(--light);
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow);
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .filter input, .filter select {
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            flex: 1;
            min-width: 180px;
        }

        .filter button {
            padding: 10px 28px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .filter button:hover { background: var(--dark); }

        .reset {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .reset:hover {
            background: var(--primary);
            color: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
            gap: 20px;
        }

        .product-card {
            background: var(--light);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px var(--shadow);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px var(--shadow);
        }

        .product-img {
            width: 100%;
            height: 140px; 
            object-fit: cover;
        }

        .product-info {
            padding: 15px; 
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1.1rem; 
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .product-category {
            font-size: 0.8rem;
            color: #888;
            margin-bottom: 8px;
        }

        .product-desc {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 12px;
            flex-grow: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-weight: 500;
        }

        .price {
            font-size: 1.15rem;
            color: var(--primary);
        }

        .stock {
            color: #888;
            font-size: 0.85rem;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
        }

        .quantity-input label {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .quantity-input input {
            width: 70px; 
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 8px;
            text-align: center;
            font-size: 1rem;
        }

        .total-price {
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--primary);
            text-align: center;
            padding: 20px;
            background: #fdf3e9;
            border-radius: 12px;
            margin: 20px 0;
        }

        .payment-form label {
            display: block;
            margin: 12px 0 6px;
            font-weight: 500;
        }

        .payment-form input {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 1.3rem;
            text-align: right;
        }

        .change {
            text-align: center;
            padding: 14px;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 15px 0;
            border-radius: 10px;
        }

        .change.positive { background: #e8f5e9; color: #2e7d32; }
        .change.negative { background: #ffebee; color: red; }

        .checkout-btn {
            width: 100%;
            padding: 16px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .checkout-btn:hover {
            background: var(--accent-dark);
        }

        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #888;
            font-size: 1.2rem;
            background: var(--light);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow);
        }

        .message {
            background: #ffebee;
            color: red;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
        }

        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                position: static;
            }
        }
    </style>
    <script>
        function updateCart(productId, qty) {
            if (qty < 0) qty = 0;
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "update_cart.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("product_id=" + productId + "&quantity=" + qty);
            xhr.onload = function() {
                location.reload();
            };
        }
    </script>
</head>
<body>

<div class="container">
    <div class="main">
        <header>
            <h1>Kasir Kopi</h1>
            <div class="nav">
                <a href="history.php" class="btn">Riwayat Transaksi</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </header>

        <?php if (isset($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="filter">
            <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; width:100%; align-items:center;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama produk...">
                <select name="category">
                    <option value="">-- Semua Kategori --</option>
                    <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Cari</button>
                <?php if ($search || $category_id): ?>
                    <a href="transaction.php" class="btn reset">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="products-grid">
            <?php if ($products->num_rows == 0): ?>
                <div class="empty">
                    Tidak ada produk yang tersedia atau sesuai dengan filter.
                </div>
            <?php else: ?>
                <?php while($row = $products->fetch_assoc()): 
                    $current_qty = $cart_qty[$row['id']] ?? 0;
                ?>
                    <div class="product-card">
                        <?php if ($row['image']): ?>
                            <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="product-img">
                        <?php else: ?>
                            <img src="https://thumbs.dreamstime.com/b/simple-coffee-cup-logo-steam-company-name-placeholder-minimalist-featuring-hand-drawn-style-saucer-rising-above-411426423.jpg" alt="No image" class="product-img">
                        <?php endif; ?>

                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="product-category"><?= htmlspecialchars($row['category_name'] ?? 'Tanpa Kategori') ?></div>
                            <div class="product-desc"><?= htmlspecialchars($row['description'] ?: 'Tidak ada deskripsi') ?></div>

                            <div class="product-details">
                                <span class="price">Rp <?= number_format($row['price']) ?></span>
                                <span class="stock">Stok: <?= $row['stock'] ?></span>
                            </div>

                            <div class="quantity-input">
                                <label>Jumlah</label>
                                <input type="number" min="0" max="<?= $row['stock'] ?>" value="<?= $current_qty ?>" 
                                       onchange="updateCart(<?= $row['id'] ?>, this.value)">
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="sidebar">
        <h2 style="color:var(--primary); text-align:center; margin-bottom:20px; font-size:1.6rem;">Ringkasan Pesanan</h2>

        <?php if ($grand_total == 0): ?>
            <div class="empty">
                Keranjang masih kosong<br><br>
                Pilih dan atur jumlah produk di sebelah kiri
            </div>
        <?php else: ?>
            <div class="total-price">
                Total: Rp <?= number_format($grand_total) ?>
            </div>

            <form method="POST" class="payment-form">
                <label>Nominal Pembayaran (Rp)</label>
                <input type="number" name="paid_amount" min="<?= $grand_total ?>" value="<?= $grand_total ?>" required>

                <?php if (isset($_POST['checkout'])): 
                    $paid = (int)($_POST['paid_amount'] ?? 0);
                    $change = $paid - $grand_total;
                ?>
                    <div class="change <?= $change >= 0 ? 'positive' : 'negative' ?>">
                        <?= $change >= 0 ? 'Kembalian: Rp ' . number_format($change) : 'Kurang: Rp ' . number_format(abs($change)) ?>
                    </div>
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
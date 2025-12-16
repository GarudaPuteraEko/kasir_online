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

// Ambil transaksi terbaru
$latest = $conn->query("
    SELECT transaction_date 
    FROM transactions 
    WHERE user_id = $user_id 
    ORDER BY id DESC 
    LIMIT 1
");

if ($latest->num_rows == 0) {
    die("Belum ada transaksi.");
}

$latest_date = $latest->fetch_assoc()['transaction_date'];

// Ambil semua item dari checkout terakhir
$items = $conn->query("
    SELECT t.quantity, t.total_price, t.transaction_date, t.payment_method, p.name, p.price 
    FROM transactions t 
    JOIN products p ON t.product_id = p.id 
    WHERE t.user_id = $user_id 
      AND t.transaction_date >= DATE_SUB('$latest_date', INTERVAL 1 MINUTE)
    ORDER BY t.id ASC
");

if ($items->num_rows == 0) {
    die("Transaksi tidak ditemukan.");
}

$total = 0;
$rows = [];
while ($row = $items->fetch_assoc()) {
    $rows[] = $row;
    $total += $row['total_price'];
}

$tanggal = date('d-m-Y H:i:s', strtotime($rows[0]['transaction_date']));
$payment_method = $rows[0]['payment_method'] ?? 'Tunai';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Struk - Coffee Shop</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            width: 80mm; 
            margin: 0 auto; 
            padding: 15px 10px; 
            font-size: 15px; 
            line-height: 1.5; 
        }
        h1 { 
            text-align: center; 
            font-size: 22px; 
            margin: 0 0 8px; 
            font-weight: bold; 
        }
        h2 { 
            text-align: center; 
            font-size: 18px; 
            margin: 5px 0 15px; 
            font-weight: bold; 
        }
        .center { text-align: center; }
        .info { 
            margin: 8px 0; 
            font-size: 14px; 
        }
        hr { 
            border: none; 
            border-top: 2px dashed #000; 
            margin: 15px 0; 
        }
        .item { 
            margin: 12px 0; 
        }
        .item-name { 
            font-weight: bold; 
            font-size: 16px; 
        }
        .item-detail { 
            margin-left: 10px; 
            font-size: 15px; 
        }
        .total { 
            font-size: 20px; 
            font-weight: bold; 
            text-align: right; 
            margin: 20px 0; 
        }
        .thanks { 
            font-size: 18px; 
            font-weight: bold; 
            text-align: center; 
            margin: 25px 0 10px; 
        }
        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
        .btn { padding: 5px 10px; background: #854442; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 13px; }
        .btn:hover { background: #4b3832; }
        a { color: #854442; text-decoration: none;}
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div>
        <h1>COFFEE SHOP</h1>
        <h2>STRUK PEMBELIAN</h2>
        <hr>

        <div class="info center"><strong><?= $tanggal ?></strong></div>
        <div class="info center">Metode: <strong><?= htmlspecialchars($payment_method) ?></strong></div>
        <hr>

        <?php foreach ($rows as $row): ?>
        <div class="item">
            <div class="item-name"><?= htmlspecialchars($row['name']) ?></div>
            <div class="item-detail">
                <?= $row['quantity'] ?> x Rp <?= number_format($row['price']) ?> 
                <span style="float:right;"><strong>Rp <?= number_format($row['total_price']) ?></strong></span>
            </div>
        </div>
        <?php endforeach; ?>

        <hr>
        <div class="total">
            TOTAL: Rp <?= number_format($total) ?>
        </div>
        <hr>

        <div class="thanks">
            TERIMA KASIH!
        </div>
        <div class="center" style="font-size:16px;">
            <strong>Selamat menikmati pesanan Anda :)</strong>
        </div>

        <div class="no-print" style="margin-top:40px; text-align:center; font-size:13px;">
            <a href="transaction.php" class="btn">← Kembali</a>
        </div>
    </div>
</body>
</html>
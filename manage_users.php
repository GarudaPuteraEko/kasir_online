<?php
include 'config.php';
session_start();
require_login();
require_admin();  // Hanya admin

// Hapus user (kecuali diri sendiri & admin lain kalau ada)
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($delete_id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $delete_id AND role = 'kasir'");  // Hanya boleh hapus kasir
    }
    header("Location: manage_users.php");
    exit();
}

// Ambil semua user
$users = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengguna (Kasir)</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h2 { color: #854442; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #854442; color: white; }
        a { color: #854442; }
    </style>
</head>
<body>
<div class="container">
    <h2>Kelola Kasir</h2>
    <a href="dashboard.php">← Kembali ke Dashboard</a> | 
    <a href="register.php">+ Tambah Kasir Baru</a> 
    <hr>

    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Tanggal Dibuat</th>
            <th>Aksi</th>
        </tr>
        <?php while($row = $users->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><strong><?= $row['role'] == 'admin' ? 'Admin' : 'Kasir' ?></strong></td>
            <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
            <td>
                <?php if ($row['role'] == 'kasir' && $row['id'] != $_SESSION['user_id']): ?>
                    <a href="manage_users.php?delete=<?= $row['id'] ?>" 
                       onclick="return confirm('Yakin hapus kasir ini?')">Hapus</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
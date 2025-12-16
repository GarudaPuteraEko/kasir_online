<?php
include 'config.php';
session_start();

// Kalau sudah login, langsung redirect sesuai role
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: transaction.php");
    }
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['username']  = $row['username'];
            $_SESSION['role']      = $row['role'];  

            if ($row['role'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: transaction.php");  // User langsung ke halaman belanja
            }
            exit();
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Username tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Coffee Shop Kasir</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { 
            max-width: 400px; 
            margin: auto; 
            padding: 40px;  
            background: #fff; 
            border-radius: 10px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
        h2 { text-align: center; color: #854442; }
        input { 
            width: 100%; 
            padding: 10px; 
            margin: 10px 0; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            box-sizing: border-box;  
        }
        button { 
            width: 100%; 
            padding: 10px; 
            background: #854442; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
        }
        button:hover { background: #4b3832; }
        .coffee-theme { background-image: url('https://example.com/coffee-bg.jpg'); background-size: cover; }
        .error { color: red; text-align: center; margin: 10px 0; }
    </style>
</head>
<body class="coffee-theme">
    <div class="container">
        <h2>Login Akun</h2>

        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Belum punya akun? <a href="register.php">Register</a></p>
    </div>
</body>
</html>
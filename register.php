<?php
include 'config.php';
session_start();
require_login();
require_admin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
        header("Location: manage_users.php");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Coffee Shop Kasir</title>
    <style>
        body { background-color: #f5e6d3; font-family: Arial, sans-serif; color: #4b3832; }
        .container { 
            max-width: 400px; 
            margin: auto; 
            padding: 40px;  /* Naikkan dari 20px jadi 40px biar ada ruang kiri-kanan */
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
            box-sizing: border-box;  /* Tambahin ini! Penting banget */
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
        .coffee-theme { background-image: url('https://example.com/coffee-bg.jpg'); /* Ganti dengan gambar kopi jika ada */ background-size: cover; }
    </style>
</head>
<body class="coffee-theme">
    <div class="container">
        <h2>Register Akun</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>
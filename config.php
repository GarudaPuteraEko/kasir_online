<?php
// Koneksi ke database (config.php)
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = "kasir_db";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_kasir() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'kasir';
}

function require_admin() {
    if (!is_admin()) {
        header("Location: transaction.php");  // Kasir diarahkan ke halaman jualan
        exit();
    }
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>
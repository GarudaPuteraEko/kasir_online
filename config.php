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

function is_user() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function is_kasir_or_admin() {
    return is_admin() || is_kasir();
}

function require_admin() {
    if (!is_admin()) {
        header("Location: transaction.php");  // Kasir diarahkan ke halaman jualan
        exit();
    }
}

function require_kasir_or_admin() {
    if (!is_kasir_or_admin()) {
        header("Location: transaction.php");
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
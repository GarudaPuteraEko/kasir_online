<?php
// Koneksi ke database (config.php)
$servername = "localhost";
$username = "root"; 
$password = ""; 
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

function is_user() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function require_admin() {
    if (!is_admin()) {
        header("Location: transaction.php");  
        exit();
    }
}

function require_user() {
    if (is_admin()) {
        header("Location: dashboard.php");
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
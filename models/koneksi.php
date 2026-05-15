<?php
$host     = "localhost";
$username = "root";
$password = "";
$database = "pemweb";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

function bersihkan($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim(mysqli_real_escape_string($conn, $data))));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function cekLoginUser() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
        redirect('/padleplay3/views/login.php');
    }
}

function cekLoginAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        redirect('/padleplay3/views/login.php');
    }
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
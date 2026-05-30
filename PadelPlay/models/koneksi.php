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

function cekInactivity() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $batas_inactivity = 120; 
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['terakhir_aktif'])) {
            $durasi_diam = time() - $_SESSION['terakhir_aktif'];
            if ($durasi_diam > $batas_inactivity) {
                session_unset();
                session_destroy();
                redirect('/PadelPlay/views/login.php?error=Sesi Anda telah berakhir karena tidak ada aktivitas selama 2 menit.');
            }
        }
        $_SESSION['terakhir_aktif'] = time();
    }
}

function cekLoginUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    cekInactivity();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
        redirect('/PadelPlay/views/login.php');
    }
}

function cekLoginAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    cekInactivity();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        redirect('/PadelPlay/views/login.php');
    }
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
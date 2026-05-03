<?php
session_start();
include 'koneksi.php';

$lapangan = $_POST['lapangan'];
$tanggal  = $_POST['tanggal'];
$waktu    = $_POST['waktu'];
$email    = $_SESSION['email'];

$cek = $conn->prepare("SELECT * FROM reservations WHERE lapangan = ? AND tanggal = ? AND waktu_mulai = ?");
$cek->bind_param("sss", $lapangan, $tanggal, $waktu);
$cek->execute();
$hasil = $cek->get_result();

if ($hasil->num_rows > 0) {
    echo "<script>alert('Maaf, jadwal ini sudah di-booking orang lain. Silahkan pilih waktu atau tanggal lain!'); window.history.back();</script>";
} else {
    $stmt = $conn->prepare("INSERT INTO reservations (user_email, lapangan, tanggal, waktu_mulai) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $lapangan, $tanggal, $waktu);
    
    if($stmt->execute()){
        echo "<script>alert('Booking Berhasil! Silahkan cek riwayat sewa anda.'); window.location='user.html';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
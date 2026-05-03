<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id']);
$email = $_SESSION['email'];

$stmt = $conn->prepare("UPDATE reservations SET status='Dibatalkan' WHERE id=? AND user_email=?");
$stmt->bind_param("is", $id, $email);
$stmt->execute();

header("Location: riwayat_booking.php");
exit;
?>
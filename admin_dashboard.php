<?php
include 'koneksi.php';

$data = [];

$lapangan = $conn->query("SELECT COUNT(*) as total FROM lapangan");
$booking = $conn->query("SELECT COUNT(*) as total FROM reservations");
$user = $conn->query("SELECT COUNT(*) as total FROM users");

$data['lapangan'] = $lapangan->fetch_assoc()['total'];
$data['booking'] = $booking->fetch_assoc()['total'];
$data['user'] = $user->fetch_assoc()['total'];

echo json_encode($data);
?>
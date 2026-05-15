<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(["error" => "unauthorized"]);
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS lapangan (
id INT AUTO_INCREMENT PRIMARY KEY,
nama VARCHAR(100),
harga INT,
status VARCHAR(20)
)");

$conn->query("CREATE TABLE IF NOT EXISTS reservations (
id INT AUTO_INCREMENT PRIMARY KEY,
user_email VARCHAR(255),
user_name VARCHAR(255),
lapangan VARCHAR(100),
tanggal DATE,
waktu_mulai TIME,
durasi INT,
status VARCHAR(20),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$lapangan = [];
$q1 = $conn->query("SELECT * FROM lapangan");
while ($r = $q1->fetch_assoc()) {
    $lapangan[] = $r;
}

$users = [];
$q2 = $conn->query("SELECT id,name,email FROM users");
while ($r = $q2->fetch_assoc()) {
    $users[] = $r;
}

$bookings = [];
$q3 = $conn->query("SELECT * FROM reservations ORDER BY tanggal DESC, waktu_mulai DESC");
while ($r = $q3->fetch_assoc()) {
    $bookings[] = $r;
}

$bookingTerbaru = [];
$q4 = $conn->query("SELECT * FROM reservations ORDER BY created_at DESC LIMIT 5");
while ($r = $q4->fetch_assoc()) {
    $bookingTerbaru[] = $r;
}

echo json_encode([
"total_lapangan" => count($lapangan),
"total_booking" => count($bookings),
"total_user" => count($users),
"lapangan" => $lapangan,
"bookings" => $bookings,
"booking_terbaru" => $bookingTerbaru,
"users" => $users
]);
?>
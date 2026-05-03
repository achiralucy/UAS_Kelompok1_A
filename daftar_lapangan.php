<?php
// Aktifkan laporan error biar kita tau rusaknya di mana
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'koneksi.php';

// Pastikan variabel koneksi kamu namanya $conn di file koneksi.php
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$query = "SELECT * FROM lapangan";
$result = $conn->query($query);

$lapangan = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        $lapangan[] = $row;
    }
} else {
    // Kalau query salah, tampilkan pesan error
    echo "Error pada query: " . $conn->error;
    exit;
}

header('Content-Type: application/json');
echo json_encode($lapangan);
exit;
?>
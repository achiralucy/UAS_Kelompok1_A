<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$email = $_SESSION['email'];

if ($id <= 0) {
    echo "ID tidak valid";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM reservations WHERE id=? AND user_email=?");
$stmt->bind_param("is", $id, $email);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "Data tidak ditemukan";
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $tanggal = $_POST['tanggal'] ?? '';
    $waktu   = $_POST['waktu'] ?? '';
    $durasi  = isset($_POST['durasi']) ? intval($_POST['durasi']) : 0;

    if ($tanggal == '' || $waktu == '' || $durasi <= 0) {
        $message = "Semua field harus diisi dengan benar";
    } else {

        $update = $conn->prepare("UPDATE reservations 
        SET tanggal=?, waktu_mulai=?, durasi=? 
        WHERE id=? AND user_email=?");

        $update->bind_param("ssiis", $tanggal, $waktu, $durasi, $id, $email);

        if ($update->execute()) {
            header("Location: riwayat_booking.php");
            exit;
        } else {
            $message = "Gagal update";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Booking</title>
    <link rel="stylesheet" href="user.css">
</head>
<body>

<div class="container">
    <h2>Edit Booking</h2>

    <?php if ($message != ""): ?>
        <p><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">

    <label>Tanggal</label>
    <input type="date" name="tanggal" value="<?= $data['tanggal'] ?>" required>

    <label>Waktu</label>
    <input type="time" name="waktu" value="<?= $data['waktu_mulai'] ?>" required>

    <label>Durasi</label>
    <input type="number" name="durasi" value="<?= $data['durasi'] ?>" min="1" required>

    <div class="form-action">
        <button type="submit" class="btn-simpan">Simpan Perubahan</button>
    </div>

</form>

<script>
document.querySelector("form").onsubmit = function(e){
    var tanggal = document.querySelector("[name='tanggal']").value;
    var waktu = document.querySelector("[name='waktu']").value;
    var durasi = document.querySelector("[name='durasi']").value;

    if (tanggal === "" || waktu === "" || durasi === "" || durasi <= 0) {
        alert("Isi semua data dengan benar");
        e.preventDefault();
    }
};
</script>

</body>
</html>
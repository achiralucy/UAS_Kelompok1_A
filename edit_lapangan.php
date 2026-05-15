<?php
include 'koneksi.php';

$id = $_POST['id'];
$nama = $_POST['nama'];
$harga = $_POST['harga'];
$status = $_POST['status'];

$conn->query("UPDATE lapangan SET nama='$nama', harga='$harga', status='$status' WHERE id=$id");

echo "OK";
?>
<?php
include 'koneksi.php';

$nama=$_POST['nama'];
$harga=$_POST['harga'];
$status=$_POST['status'];

$stmt=$conn->prepare("INSERT INTO lapangan(nama,harga,status) VALUES(?,?,?)");
$stmt->bind_param("sis",$nama,$harga,$status);
$stmt->execute();

header("Location: admin.html");
exit;
?>
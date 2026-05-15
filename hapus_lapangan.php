<?php
include 'koneksi.php';

$id = $_GET['id'];

$conn->query("DELETE FROM lapangan WHERE id=$id");

echo "OK";
?>
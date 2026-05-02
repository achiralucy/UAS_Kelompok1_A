<?php
include 'koneksi.php';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)");
$role = 'user';
$stmt->bind_param("ssss",$name,$email,$password,$role);
$stmt->execute();

header("Location: login.html");
?>
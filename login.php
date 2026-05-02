<?php
session_start();
include 'koneksi.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$query = $conn->prepare("SELECT * FROM users WHERE email=?");
$query->bind_param("s",$email);
$query->execute();
$result = $query->get_result();

if($result->num_rows>0){
    $user=$result->fetch_assoc();

    if($password == $user['password']){
        $_SESSION['nama']=$user['name'];
        $_SESSION['email']=$user['email'];
        $_SESSION['role']=$user['role'] ?? 'user';

        if($_SESSION['role']==='admin'){
            header("Location: admin.html");
        }else{
            header("Location: dashboard.php");
        }
        exit;
    }
}

header("Location: login.html");
?>
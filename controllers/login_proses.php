<?php
session_start();
require_once '../models/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = bersihkan($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: ../views/login.php?error=Email dan password wajib diisi.");
        exit;
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['name']; 
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: ../views/admin/dashboard.php");
            } else {
                header("Location: ../views/user/index.php");
            }
            exit;
        } else {
            header("Location: ../views/login.php?error=Email atau password salah.");
            exit;
        }
    }
} else {
    header("Location: ../views/login.php");
    exit;
}
<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        redirect('/padelplay/index_admin.php');
    } else {
        redirect('/padelplay/index_user.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = bersihkan($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
  
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                redirect('/padelplay/index_admin.php');
            } else {
                redirect('/padelplay/index_user.php');
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - PadelPlay</title>
    <link rel="stylesheet" href="user.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-icon">P</div>
            <div class="auth-logo-text">Padel<span>Play</span></div>
        </div>

        <div class="auth-title">Masuk ke akun</div>
        <div class="auth-subtitle">Lanjutkan booking lapangan padel.</div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@kamu.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-pink" style="width:100%; justify-content:center;">Masuk</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="register_user.php">Daftar</a>
        </div>
    </div>
</div>
<script src="user.js"></script>
</body>
</html>
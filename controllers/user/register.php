<?php
session_start();
require_once '../../models/koneksi.php';

if (!function_exists('bersihkan')) {
    function bersihkan($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }
}

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user') {
    header("Location: ../../views/user/index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = bersihkan($_POST['nama'] ?? '');
    $email = bersihkan($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $cek = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $nama, $email, $hash);

            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat! Silakan masuk.';
            } else {
                $error = 'Gagal membuat akun. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/user.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-icon">P</div>
            <div class="auth-logo-text">Padel<span>Play</span></div>
        </div>

        <div class="auth-title">Buat akun baru</div>
        <div class="auth-subtitle">Mulai booking lapangan padel favoritmu.</div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" placeholder="Nama kamu" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@kamu.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="konfirmasi" class="form-control" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn-pink" style="width:100%; justify-content:center;">Daftar Sekarang</button>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a href="../../views/login.php">Masuk</a>
        </div>
    </div>
</div>
<script src="../../assets/js/user.js"></script>
</body>
</html>
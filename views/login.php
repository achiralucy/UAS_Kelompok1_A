<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: /PadelPlay/views/admin/dashboard.php"); 
    } else {
        header("Location: /PadelPlay/views/user/index.php");      
    }
    exit;
}
$error = $_GET['error'] ?? ''; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - PadelPlay</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="../controllers/login_proses.php" method="POST">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@kamu.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit">Masuk</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="/PadelPlay4/controllers/user/register.php">Daftar</a>
        </div>
    </div>
</div>
<script src="../assets/js/user.js"></script>
</body>
</html>
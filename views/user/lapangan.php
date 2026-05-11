<?php
session_start();
require_once '../../models/koneksi.php';

$sudahLogin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'user';

$res = $conn->query("SELECT * FROM lapangan WHERE status = 'aktif' ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapangan - PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/user.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <div class="navbar-logo">P</div>
        <span class="navbar-brand-text">Padel<span>Play</span></span>
    </a>
    <ul class="navbar-nav">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="lapangan.php" class="active">Lapangan</a></li>
        <?php if ($sudahLogin): ?>
            <li><a href="../../controllers/user/booking.php">Booking</a></li>
            <li><a href="riwayat.php">Riwayat</a></li>
        <?php endif; ?>
    </ul>
    <div class="navbar-actions">
        <?php if ($sudahLogin): ?>
            <span style="color:#888;font-size:14px;">Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></span>
            <a href="../../controllers/logout.php" class="btn-keluar">⎋ Keluar</a>
        <?php else: ?>
            <a href="../login.php" class="btn-masuk">Masuk</a>
            <a href="../../controllers/user/register.php" class="btn-daftar">Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Pilih <span>Lapangan</span></h1>
        <p class="page-subtitle">Lapangan padel berkualitas, semua sudah verified.</p>
    </div>

    <div class="grid-lapangan">
        <?php if ($res && $res->num_rows > 0): ?>
            <?php while ($l = $res->fetch_assoc()): ?>
            <div class="card-lapangan">
                <div class="card-lapangan-foto">
                    <img 
                        src="<?= !empty($l['foto']) ? '../../assets/imges/' . htmlspecialchars($l['foto']) : '../../assets/images/Padel.jpeg' ?>" 
                        alt="<?= htmlspecialchars($l['nama']) ?>"
                    >
                </div>
                <div class="card-lapangan-body">
                    <div class="card-lapangan-nama"><?= htmlspecialchars($l['nama']) ?></div>
                    <div class="card-lapangan-lokasi">📍 <?= htmlspecialchars($l['lokasi']) ?></div>
                    <div class="card-lapangan-deskripsi"><?= htmlspecialchars($l['deskripsi']) ?></div>
                    <div class="card-lapangan-footer">
                        <div class="harga"><?= formatRupiah($l['harga']) ?> <span>/jam</span></div>
                        <?php if ($sudahLogin): ?>
                            <a href="../../controllers/user/booking.php?lapangan=<?= $l['id'] ?>" class="btn-booking">Booking</a>
                        <?php else: ?>
                            <a href="../login.php" class="btn-booking">Login dulu</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="color:#666; padding:40px 0;">Belum ada lapangan tersedia.</div>
        <?php endif; ?>
    </div>
</div>

<footer class="footer">
    <p>© 2026 <span>PadelPlay</span> · Lampung Padel Center</p>
</footer>

<script src="../../assets/js/user.js"></script>
</body>
</html>
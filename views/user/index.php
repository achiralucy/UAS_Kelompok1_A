<?php
session_start();
require_once __DIR__ . '/../../models/koneksi.php';

$sudahLogin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'user';
$namaUser = $sudahLogin ? $_SESSION['user_nama'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/user.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <div class="navbar-logo">P</div>
        <span class="navbar-brand-text">Padel<span>Play</span></span>
    </a>

    <ul class="navbar-nav">
        <li><a href="index.php" class="active">Beranda</a></li>
        <li><a href="lapangan.php">Lapangan</a></li>
        <?php if ($sudahLogin): ?>
            <li><a href="../../controllers/user/booking.php">Booking</a></li>
            <li><a href="riwayat.php">Riwayat</a></li>
        <?php endif; ?>
    </ul>

    <div class="navbar-actions">
        <?php if ($sudahLogin): ?>
            <span style="color:#888;font-size:14px;">Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></span>
            <a href="../../controllers/logout.php" class="btn-keluar">Keluar</a>
        <?php else: ?>
            <a href="../login.php" class="btn-masuk">Masuk</a>
            <a href="../../controllers/user/register.php" class="btn-daftar">Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content">
        <div class="hero-badge">⚡ Booking Lapangan Padel #1 di Lampung</div>
        <h1 class="hero-title">Main Padel <span>Tanpa Ribet.</span></h1>
        <p class="hero-subtitle">
            Pesan lapangan padel favoritmu kapan saja, lihat ketersediaan real-time, dan langsung main. Tidak perlu chat admin lagi.
        </p>
        <div class="hero-actions">
            <?php if ($sudahLogin): ?>
                <a href="../../controllers/user/booking.php" class="btn-pink">Booking Sekarang</a>
                <a href="lapangan.php" class="btn-outline">Lihat Lapangan</a>
            <?php else: ?>
                <a href="../../controllers/user/register.php" class="btn-pink">Mulai Sekarang</a>
                <a href="lapangan.php" class="btn-outline">Lihat Lapangan</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section style="padding: 70px 0; background: #0f0f0f;">
    <div class="container">
        <h2 style="font-family:'Montserrat',sans-serif; font-weight:800; font-size:28px; color:#fff; margin-bottom:8px;">
            Kenapa <span style="color:#e91e8c;">PadelPlay?</span>
        </h2>
        <p style="color:#888; margin-bottom:40px;">Sistem booking lapangan yang simpel dan efisien.</p>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
            <div style="background:#111; border:1px solid #222; border-radius:14px; padding:24px;">
                <div style="font-size:32px; margin-bottom:12px;">📅</div>
                <div style="font-family:'Montserrat',sans-serif; font-weight:700; color:#fff; margin-bottom:6px;">Booking Online 24/7</div>
                <div style="color:#888; font-size:13px;">Pesan kapan saja tanpa perlu telepon atau datang langsung.</div>
            </div>
            <div style="background:#111; border:1px solid #222; border-radius:14px; padding:24px;">
                <div style="font-size:32px; margin-bottom:12px;">🎯</div>
                <div style="font-family:'Montserrat',sans-serif; font-weight:700; color:#fff; margin-bottom:6px;">Jadwal Real-Time</div>
                <div style="color:#888; font-size:13px;">Lihat slot yang tersedia secara langsung, tidak ada double booking.</div>
            </div>
            <div style="background:#111; border:1px solid #222; border-radius:14px; padding:24px;">
                <div style="font-size:32px; margin-bottom:12px;">💳</div>
                <div style="font-family:'Montserrat',sans-serif; font-weight:700; color:#fff; margin-bottom:6px;">Bayar di Lokasi</div>
                <div style="color:#888; font-size:13px;">Tidak perlu transfer dulu. Bayar langsung saat tiba di lapangan.</div>
            </div>
            <div style="background:#111; border:1px solid #222; border-radius:14px; padding:24px;">
                <div style="font-size:32px; margin-bottom:12px;">📋</div>
                <div style="font-family:'Montserrat',sans-serif; font-weight:700; color:#fff; margin-bottom:6px;">Riwayat Booking</div>
                <div style="color:#888; font-size:13px;">Semua pemesananmu tercatat rapi, bisa dibatalkan jika perlu.</div>
            </div>
        </div>
    </div>
</section>

<section style="padding: 60px 0;">
    <div class="container">
        <h2 style="font-family:'Montserrat',sans-serif; font-weight:800; font-size:28px; color:#fff; margin-bottom:8px;">
            Lapangan <span style="color:#e91e8c;">Tersedia</span>
        </h2>
        <p style="color:#888; margin-bottom:36px;">Pilih lapangan padel terbaik untukmu.</p>

        <?php
        $res = $conn->query("SELECT * FROM lapangan WHERE status = 'aktif' LIMIT 3");
        if ($res && $res->num_rows > 0):
        ?>
        <div class="grid-lapangan">
            <?php while ($l = $res->fetch_assoc()): ?>
            <div class="card-lapangan">
                <div class="card-lapangan-foto">
                    <img 
                        src="<?= !empty($l['foto']) ? htmlspecialchars($l['foto']) : '../../assets/images/Padel.jpeg' ?>" 
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
                            <a href="../login.php" class="btn-booking">Booking</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <div style="text-align:center; margin-top:30px;">
            <a href="lapangan.php" class="btn-outline">Lihat Semua Lapangan</a>
        </div>
    </div>
</section>

<footer class="footer">
    <p>© 2026 <span>PadelPlay</span> · Lampung Padel Center · Dibuat oleh Kelompok 1 Ilmu Komputer A Unila</p>
</footer>

<script src="../../assets/js/user.js"></script>
</body>
</html>
<?php
session_start();
require_once __DIR__ . '/../../models/koneksi.php';

$sudahLogin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'user';
$namaUser   = $sudahLogin ? htmlspecialchars($_SESSION['user_nama']) : '';
cekInactivity();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=1.6">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <div class="navbar-logo">P</div>
        <span class="navbar-brand-text">Padel<span>Play</span></span>
    </a>
    <button class="menu-toggle" onclick="toggleMenu()">☰ </button>

    <ul class="navbar-nav" id="navbarNav">
        <li><a href="index.php" class="active">Beranda</a></li>
        <li><a href="lapangan.php">Lapangan</a></li>
        <?php if ($sudahLogin): ?>
            <li><a href="../../controllers/user/booking.php">Booking</a></li>
            <li><a href="riwayat.php">Riwayat</a></li>
            <li class="mobile-only"><a href="profil.php">Profil</a></li>
            <li class="mobile-only"><a href="#" onclick="tampilModalLogout(event)">Keluar</a></li>
        <?php else: ?>
            <li class="mobile-only"><a href="../login.php">Masuk</a></li>
            <li class="mobile-only"><a href="../../controllers/user/register.php">Daftar</a></li>
        <?php endif; ?>
    </ul>

    <div class="navbar-actions">
        <?php if ($sudahLogin): ?>
            <span class="topbar-name">Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></span>
            <a href="profil.php" class="btn-profil-nav">Profil</a>
            <a href="#" class="btn-keluar" onclick="tampilModalLogout(event)">⎋ Keluar</a>
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
            Pesan lapangan padel favoritmu kapan saja, lihat ketersediaan real-time, dan langsung main.
            Tidak perlu chat admin lagi.
        </p>
        <div class="hero-actions">
            <?php if ($sudahLogin): ?>
                <a href="../../controllers/user/booking.php" class="btn btn-pink">Booking Sekarang</a>
                <a href="lapangan.php" class="btn btn-outline">Lihat Lapangan</a>
            <?php else: ?>
                <a href="../../controllers/user/register.php" class="btn btn-pink">Mulai Sekarang</a>
                <a href="lapangan.php" class="btn btn-outline">Lihat Lapangan</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="home-section-features">
    <div class="container">
        <h2 class="home-section-title">Kenapa <span>PadelPlay?</span></h2>
        <p class="home-section-subtitle">Sistem booking lapangan yang simpel dan efisien.</p>

        <div class="home-features-grid">
            <?php
            $fitur = [
                ['icon'=>'📅','judul'=>'Booking Online 24/7','desc'=>'Pesan kapan saja tanpa perlu telepon atau datang langsung.'],
                ['icon'=>'🎯','judul'=>'Jadwal Real-Time','desc'=>'Lihat slot yang tersedia secara langsung, tidak ada double booking.'],
                ['icon'=>'💳','judul'=>'Bayar di Lokasi','desc'=>'Tidak perlu transfer dahulu. Bayar langsung saat tiba di lapangan.'],
                ['icon'=>'📋','judul'=>'Riwayat Booking','desc'=>'Semua pemesananmu tercatat rapi, bisa dibatalkan jika perlu.'],
            ];
            foreach ($fitur as $f):
            ?>
            <div class="home-feature-card">
                <div class="home-feature-icon"><?= $f['icon'] ?></div>
                <div class="home-feature-label"><?= $f['judul'] ?></div>
                <div class="home-feature-desc"><?= $f['desc'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="home-section-fields">
    <div class="container">
        <h2 class="home-section-title">Lapangan <span>Tersedia</span></h2>
        <p class="home-section-subtitle-fields">Pilih lapangan padel terbaik untukmu.</p>

        <?php
        $res = $conn->query("SELECT * FROM lapangan WHERE status = 'aktif' LIMIT 3");
        if ($res && $res->num_rows > 0):
        ?>
        <div class="grid-lapangan">
            <?php while ($l = $res->fetch_assoc()): ?>
            <div class="card-lapangan">
                <div class="card-lapangan-foto">
                    <?php
                    $fotoSrc = !empty($l['foto'])
                        ? '../../assets/images/' . htmlspecialchars($l['foto'])
                        : '../../assets/images/Padel.jpeg';
                    ?>
                    <img src="<?= $fotoSrc ?>" alt="<?= htmlspecialchars($l['nama']) ?>">
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

        <div class="home-fields-action-wrap">
            <a href="lapangan.php" class="btn btn-outline">Lihat Semua Lapangan</a>
        </div>
    </div>
</section>

<footer class="footer">
    <p>© 2026 <span>PadelPlay</span> · Lampung Padel Center · Dibuat oleh Kelompok 1 Ilmu Komputer A Unila</p>
</footer>

<div class="logout-overlay" id="modal-logout">
    <div class="logout-box">
        <div class="logout-icon">⎋</div>
        <h3>Konfirmasi Keluar</h3>
        <p>Apakah Anda yakin ingin keluar dari akun PadelPlay?</p>
        <div class="logout-btns">
            <button class="lbtn-no" onclick="tutupModalLogout()">Tidak</button>
            <a href="../../controllers/logout.php" class="lbtn-yes">Ya, Keluar</a>
        </div>
    </div>
</div>

<script src="../../assets/js/user.js"></script>
<script>
function tampilModalLogout(e) {
    e.preventDefault();
    document.getElementById('modal-logout').classList.add('active');
}
function tutupModalLogout() {
    document.getElementById('modal-logout').classList.remove('active');
}
document.getElementById('modal-logout').addEventListener('click', function(e) {
    if (e.target === this) tutupModalLogout();
});
function toggleMenu() {
    document.getElementById('navbarNav').classList.toggle('show');
    document.body.classList.toggle('menu-open');
}
</script>
</body>
</html>
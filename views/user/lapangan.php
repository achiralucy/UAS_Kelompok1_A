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
    <link rel="stylesheet" href="../../assets/css/additions.css">
    <style>
        .logout-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.75); z-index:9999;
            align-items:center; justify-content:center;
        }
        .logout-overlay.active { display:flex; }
        .logout-box {
            background:#111; border:1px solid #2a2a2a; border-radius:16px;
            width:90%; max-width:380px; padding:36px 28px; text-align:center;
            box-shadow:0 0 40px rgba(233,30,140,.12);
        }
        .logout-box .logout-icon { font-size:48px; margin-bottom:14px; }
        .logout-box h3 { font-family:'Montserrat',sans-serif; font-weight:700; color:#fff; font-size:18px; margin-bottom:10px; }
        .logout-box p { color:#888; font-size:14px; margin-bottom:26px; }
        .logout-btns { display:flex; gap:12px; justify-content:center; }
        .lbtn-no { padding:10px 26px; border:1px solid #333; background:transparent; color:#ccc; border-radius:8px; cursor:pointer; font-size:14px; }
        .lbtn-no:hover { border-color:#e91e8c; color:#e91e8c; }
        .lbtn-yes { padding:10px 26px; background:#e91e8c; border:none; color:#fff; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; text-decoration:none; display:inline-block; }
    </style>
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
            <a href="profil.php" class="btn-profil-nav">Profil</a>
            <a href="../../controllers/logout.php" class="btn-keluar">⎋ Keluar</a>
            <a href="#" class="btn-keluar" onclick="tampilModalLogout(event)">⎋ Keluar</a>
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
<!-- Modal Logout -->
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
</script>
</body>
</html>

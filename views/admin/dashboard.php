<?php
session_start();
require_once '../../models/koneksi.php';
cekLoginAdmin();

$totalLapangan = $conn->query("SELECT COUNT(*) as c FROM lapangan")->fetch_assoc()['c'] ?? 0;
$totalUser     = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'user'")->fetch_assoc()['c'] ?? 0;
$totalBooking  = $conn->query("SELECT COUNT(*) as c FROM booking")->fetch_assoc()['c'] ?? 0;
$bookingPending= $conn->query("SELECT COUNT(*) as c FROM booking WHERE status='pending'")->fetch_assoc()['c'] ?? 0;

$pendapatanRes = $conn->query("SELECT SUM(total_harga) as total FROM booking WHERE status='confirmed'");
$pendapatan    = $pendapatanRes->fetch_assoc()['total'] ?? 0;

$bookingTerbaru = $conn->query("
    SELECT b.*, u.name AS user_nama, l.nama AS lapangan_nama
    FROM booking b
    JOIN users u ON b.user_id = u.id
    JOIN lapangan l ON b.lapangan_id = l.id
    ORDER BY b.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="admin-wrapper">

    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">P</div>
            <span class="sidebar-brand-text">Padel<span>Play</span></span>
        </a>
        <span class="sidebar-badge">Admin Padel</span>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-label">Menu</li>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="../../controllers/admin/lapangan.php">Lapangan</a></li>
            <li><a href="../../controllers/admin/booking.php">Booking</a></li>
            <li><a href="../../controllers/admin/kelola.php">Pengguna</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="#" onclick="tampilModalLogout(event)">
                <span class="sidebar-menu-icon">⎋</span><span>Keluar</span>
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <div class="topbar">
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-right">
                <div class="topbar-admin-info">
                    <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?></div>
                    <span class="topbar-name"><?= htmlspecialchars($_SESSION['user_nama']) ?></span>
                </div>
            </div>
        </div>

        <div class="page-content">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Selamat Datang, <span><?= htmlspecialchars(explode(' ', $_SESSION['user_nama'])[0]) ?>!</span></h1>
                    <p>Ringkasan aktivitas PadelPlay hari ini.</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🏟️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalLapangan ?></div>
                        <div class="stat-label">Total Lapangan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalUser ?></div>
                        <div class="stat-label">Total Pengguna</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalBooking ?></div>
                        <div class="stat-label">Total Booking</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $bookingPending ?></div>
                        <div class="stat-label">Booking Pending</div>
                    </div>
                </div>
            </div>

            <div class="admin-omzet-card">
                <div class="admin-omzet-icon">💰</div>
                <div>
                    <div class="admin-omzet-title">Total Pendapatan (Confirmed)</div>
                    <div class="admin-omzet-value"><?= formatRupiah($pendapatan) ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-header-title">Booking Terbaru</span>
                    <a href="../../controllers/admin/booking.php" class="kode-link">Lihat Semua →</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pengguna</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookingTerbaru->num_rows > 0): ?>
                                <?php while ($b = $bookingTerbaru->fetch_assoc()): ?>
                                <tr>
                                    <td class="kode-link">
                                        <?= htmlspecialchars($b['kode_booking'] ?? '-') ?>
                                    </td>
                                    <td><?= htmlspecialchars($b['user_nama']) ?></td>
                                    <td><?= htmlspecialchars($b['lapangan_nama']) ?></td>
                                    <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                                    <td class="resi-total-value"><?= formatRupiah($b['total_harga']) ?></td>
                                    <td>
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <span class="badge badge-pending">Pending</span>
                                        <?php elseif ($b['status'] === 'confirmed'): ?>
                                            <span class="badge badge-confirmed">Confirmed</span>
                                        <?php else: ?>
                                            <span class="badge badge-cancelled">Dibatalkan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📅</div><p>Belum ada booking.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="logout-overlay" id="modal-logout">
    <div class="logout-box">
        <div class="logout-icon">⎋</div>
        <h3>Konfirmasi Keluar</h3>
        <p>Apakah Anda yakin ingin keluar dari padel admin?</p>
        <div class="logout-btns">
            <button class="lbtn-no" onclick="tutupModal('modal-logout')">Tidak</button>
            <a href="../../controllers/logout.php" class="lbtn-yes">Ya, Keluar</a>
        </div>
    </div>
</div>

<script>
function bukaModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function tutupModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
function tampilModalLogout(e) {
    e.preventDefault();
    bukaModal('modal-logout');
}
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('logout-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});
</script>
<script src="../../assets/js/admin.js"></script>
</body>
</html>
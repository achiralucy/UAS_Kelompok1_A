<?php
session_start();
require_once '../../models/koneksi.php';
cekLoginAdmin();

$totalLapangan = $conn->query("SELECT COUNT(*) as n FROM lapangan WHERE status = 'aktif'")->fetch_assoc()['n'];
$totalUser = $conn->query("SELECT COUNT(*) as n FROM users WHERE role = 'user'")->fetch_assoc()['n'];
$totalBooking = $conn->query("SELECT COUNT(*) as n FROM booking")->fetch_assoc()['n'];
$totalPending = $conn->query("SELECT COUNT(*) as n FROM booking WHERE status = 'pending'")->fetch_assoc()['n'];
$totalPendapatan = $conn->query("SELECT COALESCE(SUM(total_harga),0) as n FROM booking WHERE status != 'cancelled'")->fetch_assoc()['n'];

$recentBookings = $conn->query("
    SELECT b.*, u.name as user_nama, l.nama as lapangan_nama
    FROM booking b
    JOIN users u ON b.user_id = u.id
    JOIN lapangan l ON b.lapangan_id = l.id
    ORDER BY b.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">

    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">P</div>
            <span class="sidebar-brand-text">Padel<span>Play</span></span>
        </a>
        <span class="sidebar-badge">Admin Panel</span>

        <ul class="sidebar-menu">
            <li class="sidebar-menu-label">Menu</li>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="../../controllers/admin/lapangan.php">Lapangan</a></li>
            <li><a href="../../controllers/admin/booking.php">Booking</a></li>
            <li><a href="../../controllers/admin/kelola.php">Pengguna</a></li>
        </ul>

        <div class="sidebar-footer">
            <a href="../../controllers/logout.php"><span class="sidebar-menu-icon">⎋</span><span>Keluar</span></a>
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
                    <h1>Selamat datang, <span><?= htmlspecialchars($_SESSION['user_nama']) ?></span> 👋</h1>
                    <p>Ringkasan data sistem hari ini — <?= date('d F Y') ?></p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-accent"></div>
                    <div class="stat-card-icon">🏓</div>
                    <div class="stat-card-value"><?= $totalLapangan ?></div>
                    <div class="stat-card-label">Lapangan Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-accent"></div>
                    <div class="stat-card-icon">👤</div>
                    <div class="stat-card-value"><?= $totalUser ?></div>
                    <div class="stat-card-label">Total Pengguna</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-accent"></div>
                    <div class="stat-card-icon">📅</div>
                    <div class="stat-card-value"><?= $totalBooking ?></div>
                    <div class="stat-card-label">Total Booking</div>
                </div>
                <div class="stat-card stat-card-pink">
                    <div class="stat-card-accent"></div>
                    <div class="stat-card-icon">⏳</div>
                    <div class="stat-card-value"><?= $totalPending ?></div>
                    <div class="stat-card-label">Booking Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-accent"></div>
                    <div class="stat-card-icon">💰</div>
                    <div class="stat-card-value" style="font-size:20px;"><?= formatRupiah($totalPendapatan) ?></div>
                    <div class="stat-card-label">Estimasi Pendapatan</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-header-title">Booking Terbaru</span>
                    <a href="../../controllers/admin/booking.php" class="btn btn-outline btn-sm">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentBookings->num_rows > 0): ?>
                                <?php while ($b = $recentBookings->fetch_assoc()): ?>
                                <tr>
                                    <td><strong style="color:#fff;"><?= htmlspecialchars($b['user_nama']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['lapangan_nama']) ?></td>
                                    <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                                    <td><?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?></td>
                                    <td style="color:#e91e8c; font-weight:700;"><?= formatRupiah($b['total_harga']) ?></td>
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
                                <tr><td colspan="6" style="text-align:center;color:#555;padding:30px;">Belum ada booking.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/admin.js"></script>
</body>
</html>
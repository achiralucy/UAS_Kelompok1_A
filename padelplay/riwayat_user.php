<?php
session_start();
require_once 'koneksi.php';
cekLoginUser();

$user_id = $_SESSION['user_id'];
$success = '';

if (isset($_GET['batal'])) {
    $bookId = (int)$_GET['batal'];
    $stmtBatal = $conn->prepare("UPDATE booking SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmtBatal->bind_param("ii", $bookId, $user_id);
    $stmtBatal->execute();
    $success = 'Booking berhasil dibatalkan.';
}

if (isset($_GET['sukses'])) {
    $success = 'Booking berhasil dibuat! Sampai jumpa di lapangan. 🎾';
}

$stmt = $conn->prepare("
    SELECT b.*, l.nama as lapangan_nama, l.lokasi 
    FROM booking b 
    JOIN lapangan l ON b.lapangan_id = l.id 
    WHERE b.user_id = ? 
    ORDER BY b.tanggal DESC, b.jam_mulai DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking - PadelPlay</title>
    <link rel="stylesheet" href="user.css">
</head>
<body>

<nav class="navbar">
    <a href="index_user.php" class="navbar-brand">
        <div class="navbar-logo">P</div>
        <span class="navbar-brand-text">Padel<span>Play</span></span>
    </a>
    <ul class="navbar-nav">
        <li><a href="index_user.php">Beranda</a></li>
        <li><a href="lapangan_user.php">Lapangan</a></li>
        <li><a href="booking_user.php">Booking</a></li>
        <li><a href="riwayat_user.php" class="active">Riwayat</a></li>
    </ul>
    <div class="navbar-actions">
        <span style="color:#888;font-size:14px;">Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?></span>
        <a href="logout.php" class="btn-keluar">⎋ Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Riwayat <span>Booking</span></h1>
        <p class="page-subtitle">Semua pemesananmu di satu tempat.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= $success ?></div>
    <?php endif; ?>

    <?php if ($bookings->num_rows === 0): ?>
        <div class="riwayat-empty">
            <div class="riwayat-empty-icon">📋</div>
            <p>Belum ada booking.</p>
            <a href="booking_user.php" class="btn-pink">Booking Sekarang</a>
        </div>
    <?php else: ?>
        <div class="tabel-riwayat">
            <table>
                <thead>
                    <tr>
                        <th>Lapangan</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Durasi</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($b = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong style="color:#fff;"><?= htmlspecialchars($b['lapangan_nama']) ?></strong><br>
                            <small style="color:#666;"><?= htmlspecialchars($b['lokasi']) ?></small>
                        </td>
                        <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                        <td><?= substr($b['jam_mulai'], 0, 5) ?> - <?= substr($b['jam_selesai'], 0, 5) ?></td>
                        <td><?= $b['durasi'] ?> jam</td>
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
                        <td>
                            <?php if ($b['status'] === 'pending' && $b['tanggal'] >= date('Y-m-d')): ?>
                                <button class="btn-batal" onclick="konfirmaBatal(<?= $b['id'] ?>)">Batalkan</button>
                            <?php else: ?>
                                <span style="color:#444;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <p>© 2026 <span>PadelPlay</span> · Lampung Padel Center</p>
</footer>

<script src="user.js"></script>
</body>
</html>
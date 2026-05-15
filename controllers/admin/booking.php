<?php
session_start();
require_once '../../models/koneksi.php';

$success = '';
$error = '';

if (isset($_GET['update_status']) && isset($_GET['status'])) {
    $bookId = (int)$_GET['update_status'];
    $status = bersihkan($_GET['status']);

    if (in_array($status, ['pending', 'confirmed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE booking SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $bookId);
        if ($stmt->execute()) {
            $success = 'Status booking berhasil diperbarui.';
        } else {
            $error = 'Gagal memperbarui status.';
        }
    }
}

$filterStatus = bersihkan($_GET['filter_status'] ?? '');
$filterTanggal = bersihkan($_GET['filter_tanggal'] ?? '');

$where = "WHERE 1=1";
if ($filterStatus) $where .= " AND b.status = '$filterStatus'";
if ($filterTanggal) $where .= " AND b.tanggal = '$filterTanggal'";

$bookings = $conn->query("
    SELECT b.*, u.name as user_nama, u.email as user_email, l.nama as lapangan_nama
    FROM booking b
    JOIN users u ON b.user_id = u.id
    JOIN lapangan l ON b.lapangan_id = l.id
    $where
    ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Admin PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">

    <aside class="sidebar">
        <a href="../../views/admin/dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">P</div>
            <span class="sidebar-brand-text">Padel<span>Play</span></span>
        </a>
        <span class="sidebar-badge">Admin Panel</span>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-label">Menu</li>
            <li><a href="../../views/admin/dashboard.php">Dashboard</a></li>
            <li><a href="lapangan.php">Lapangan</a></li>
            <li><a href="booking.php" class="active">Booking</a></li>
            <li><a href="kelola.php">Pengguna</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../logout.php"><span class="sidebar-menu-icon">⎋</span><span>Keluar</span></a>
        </div>
    </aside>

    <div class="admin-main">
        <div class="topbar">
            <div class="topbar-title">Kelola Booking</div>
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
                    <h1>Kelola <span>Booking</span></h1>
                    <p>Monitor dan kelola semua pemesanan lapangan.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <div class="card" style="margin-bottom:20px;">
                <div class="card-body" style="padding:16px 22px;">
                    <form method="GET" style="display:flex; gap:14px; align-items:flex-end; flex-wrap:wrap;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:160px;">
                            <label class="form-label">Filter Status</label>
                            <select name="filter_status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:160px;">
                            <label class="form-label">Filter Tanggal</label>
                            <input type="date" name="filter_tanggal" class="form-control" value="<?= $filterTanggal ?>">
                        </div>
                        <button type="submit" class="btn btn-pink" style="height:42px;">Filter</button>
                        <a href="booking.php" class="btn btn-outline" style="height:42px;">Reset</a>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-header-title">Semua Booking</span>
                    <span style="color:#666;font-size:13px;"><?= $bookings->num_rows ?> data</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pengguna</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Durasi</th>
                                <th>Total</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings->num_rows > 0): $no = 1; ?>
                                <?php while ($b = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td style="color:#555;"><?= $no++ ?></td>
                                    <td>
                                        <strong style="color:#fff;"><?= htmlspecialchars($b['user_nama']) ?></strong><br>
                                        <small style="color:#555;"><?= htmlspecialchars($b['user_email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($b['lapangan_nama']) ?></td>
                                    <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                                    <td><?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?></td>
                                    <td><?= $b['durasi'] ?> jam</td>
                                    <td style="color:#e91e8c; font-weight:700;"><?= formatRupiah($b['total_harga']) ?></td>
                                    <td style="color:#666; max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= $b['catatan'] ? htmlspecialchars($b['catatan']) : '-' ?>
                                    </td>
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
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <button class="btn btn-success btn-sm" onclick="updateStatus(<?= $b['id'] ?>, 'confirmed')">Konfirmasi</button>
                                            <button class="btn btn-danger btn-sm" onclick="updateStatus(<?= $b['id'] ?>, 'cancelled')">Batalkan</button>
                                        <?php elseif ($b['status'] === 'confirmed'): ?>
                                            <button class="btn btn-danger btn-sm" onclick="updateStatus(<?= $b['id'] ?>, 'cancelled')">Batalkan</button>
                                        <?php else: ?>
                                            <span style="color:#444;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="10"><div class="empty-state"><div class="empty-state-icon">📅</div><p>Belum ada data booking.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(id, status) {
    if(confirm('Apakah Anda yakin ingin mengubah status booking ini menjadi ' + status + '?')) {
        window.location.href = 'booking.php?update_status=' + id + '&status=' + status;
    }
}
</script>
<script src="../../assets/js/admin.js"></script>
</body>
</html>
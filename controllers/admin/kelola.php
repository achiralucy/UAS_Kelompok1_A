<?php
session_start();
require_once '../../models/koneksi.php';

$success = '';
$error = '';

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id === $_SESSION['user_id']) {
        $error = 'Tidak bisa menghapus akun sendiri.';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = 'Pengguna berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus pengguna.';
        }
    }
}

$users = $conn->query("SELECT u.*, COUNT(b.id) as total_booking FROM users u LEFT JOIN booking b ON u.id = b.user_id WHERE u.role = 'user' GROUP BY u.id ORDER BY u.id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/additions.css">
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
            <li><a href="booking.php">Booking</a></li>
            <li><a href="kelola.php" class="active">Pengguna</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../logout.php"><span class="sidebar-menu-icon">⎋</span><span>Keluar</span></a>
        </div>
    </aside>

    <div class="admin-main">
        <div class="topbar">
            <div class="topbar-title">Kelola Pengguna</div>
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
                    <h1>Data <span>Pengguna</span></h1>
                    <p>Lihat semua akun pengguna yang terdaftar.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <span class="card-header-title">Daftar Pengguna</span>
                    <span style="color:#666;font-size:13px;"><?= $users->num_rows ?> pengguna</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Total Booking</th>
                                <th>ID</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): $no = 1; ?>
                                <?php while ($u = $users->fetch_assoc()): ?>
                                <tr>
                                    <td style="color:#555;"><?= $no++ ?></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:34px; height:34px; background:linear-gradient(135deg,#e91e8c,#c0166e); border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; color:#fff; flex-shrink:0;">
                                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                            </div>
                                            <strong style="color:#fff;"><?= htmlspecialchars($u['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td style="color:#888;"><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <span style="font-family:'Montserrat',sans-serif; font-weight:700; color:#e91e8c;"><?= $u['total_booking'] ?></span>
                                        <span style="color:#555;"> booking</span>
                                    </td>
                                    <td style="color:#666;"><?= $u['id'] ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="konfirmasiHapus('kelola.php?hapus=<?= $u['id'] ?>', '<?= htmlspecialchars($u['name']) ?>')">Hapus</button>
                                    </td>   
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6"><div class="empty-state"><p>Belum ada pengguna terdaftar.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function konfirmasiHapus(url, nama) {
    if (confirm('Apakah Anda yakin ingin menghapus pengguna "' + nama + '"? Semua data booking pengguna ini mungkin akan terdampak.')) {
        window.location.href = url;
    }
}
</script>
<script src="../../assets/js/admin.js"></script>
</body>
</html>
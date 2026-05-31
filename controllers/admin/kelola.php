<?php
session_start();
require_once '../../models/koneksi.php';

$success = '';
$error   = '';

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
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleAdminMenu()"></div>

<div class="admin-wrapper">

    <aside class="sidebar">
        <a href="../../views/admin/dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">P</div>
            <span class="sidebar-brand-text">Padel<span>Play</span></span>
        </a>
        <span class="sidebar-badge">Admin Padel</span>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-label">Menu</li>
            <li><a href="../../views/admin/dashboard.php">Dashboard</a></li>
            <li><a href="lapangan.php">Lapangan</a></li>
            <li><a href="booking.php">Booking</a></li>
            <li><a href="kelola.php" class="active">Pengguna</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="#" onclick="tampilModalLogout(event)">Keluar</a>
        </div>
    </aside>

    <div class="admin-main">
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-admin" onclick="toggleAdminMenu()">☰</button>
                <div class="topbar-title">Kelola Pengguna</div>
            </div>
            <div class="topbar-right">
                <div class="topbar-admin-info">
                    <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'] ?? 'A', 0, 1)) ?></div>
                    <span class="topbar-name"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'Admin') ?></span>
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
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <span class="card-header-title">Daftar Pengguna</span>
                    <span class="badge-count-data"><?= $users->num_rows ?> pengguna</span>
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
                                    <td class="td-number"><?= $no++ ?></td>
                                    <td>
                                        <div class="user-avatar-wrapper">
                                            <div class="user-avatar-box">
                                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                            </div>
                                            <strong class="td-user-name"><?= htmlspecialchars($u['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td class="td-user-email-alt"><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <span class="td-booking-count"><?= $u['total_booking'] ?></span>
                                        <span class="td-booking-label"> booking</span>
                                    </td>
                                    <td class="td-user-id"><?= $u['id'] ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="modalHapusUser('kelola.php?hapus=<?= $u['id'] ?>', '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">Hapus</button>
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

<div class="modal-overlay" id="modal-hapus-user">
    <div class="modal modal-box-admin-medium">
        <div class="modal-header">
            <span class="modal-title">Konfirmasi Hapus</span>
            <button class="modal-close" onclick="tutupModal('modal-hapus-user')">✕</button>
        </div>
        <div class="modal-body modal-body-admin-center">
            <div class="modal-large-icon">⚠️</div>
            <p>Apakah Anda yakin ingin menghapus pengguna <strong id="hapus-nama-user" class="highlight-pink"></strong>?</p>
            <p class="modal-text-desc-simple">Semua data booking pengguna ini mungkin akan terdampak dan terhapus permanen.</p>
        </div>
        <div class="modal-footer modal-footer-admin-center">
            <button type="button" class="btn btn-outline" onclick="tutupModal('modal-hapus-user')">Batal</button>
            <a href="#" id="hapus-url-user" class="btn btn-danger">Ya, Hapus</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-logout">
    <div class="modal modal-box-admin-small">
        <div class="modal-header">
            <span class="modal-title">Konfirmasi Keluar</span>
            <button class="modal-close" onclick="tutupModal('modal-logout')">✕</button>
        </div>
        <div class="modal-body modal-body-admin-center">
            <p class="modal-text-desc-simple">Apakah Anda yakin ingin keluar dari padel admin?</p>
        </div>
        <div class="modal-footer modal-footer-admin-center">
            <button class="btn btn-outline" onclick="tutupModal('modal-logout')">Tidak</button>
            <a href="../logout.php" class="btn btn-pink">Ya, Keluar</a>
        </div>
    </div>
</div>

<script>
function toggleAdminMenu() {
    document.body.classList.toggle('admin-menu-open');
}

function bukaModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function tutupModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

function tampilModalLogout(e) {
    e.preventDefault();
    bukaModal('modal-logout');
}

function modalHapusUser(url, nama) {
    document.getElementById('hapus-nama-user').textContent = nama;
    document.getElementById('hapus-url-user').href = url;
    bukaModal('modal-hapus-user');
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity .5s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 4500);
    });
});
</script>
<script src="../../assets/js/admin.js"></script>
</body>
</html>
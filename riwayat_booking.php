<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];

$data = [];
$query = $conn->prepare("SELECT * FROM reservations WHERE user_email=? ORDER BY tanggal DESC");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Riwayat Booking</title>
<link rel="stylesheet" href="user.css">
</head>
<script src="script.js"></script>
<body>

<div id="sidebar">
    <div id="sidebar-logo">
        <h1>PadelPlay</h1>
        <p>User Dashboard</p>
    </div>

    <div id="sidebar-menu">
        <div class="menu-label">Menu Utama</div>
        <a href="user.html" class="menu-item">Pilih Lapangan</a>
        <a href="riwayat_booking.php" class="menu-item active">Riwayat Booking</a>
        <a href="profile.php" class="menu-item">Profil Saya</a>
    </div>

    <div id="sidebar-footer">
        <a href="logout.php" id="btn-logout">Logout</a>
    </div>
</div>

<div id="main">
    <div id="navbar">
        <div style="display:flex;align-items:center;gap:15px;">
            <button id="menu">☰</button>
            <div style="font-weight:bold;color:#333;">Riwayat Booking</div>
        </div>
        <div style="background-color:#ffe4f5;color:#cc2e97;padding:6px 14px;border-radius:20px;font-weight:bold;font-size:13px;">Member</div>
    </div>

    <div id="content">

        <h2 style="margin-bottom:20px;">Riwayat Booking</h2>

        <?php if (count($data) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Lapangan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($data as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['tanggal']) ?></td>
                    <td><?= htmlspecialchars($d['waktu_mulai']) ?></td>
                    <td><?= htmlspecialchars($d['lapangan']) ?></td>

                    <td>
                        <?php
                        $status = $d['status'] ?? 'Menunggu';
                        if ($status == 'Selesai') {
                            echo '<span class="badge selesai">Selesai</span>';
                        } elseif ($status == 'Dibatalkan') {
                            echo '<span class="badge batal">Dibatalkan</span>';
                        } else {
                            echo '<span class="badge pending">Menunggu</span>';
                        }
                        ?>
                    </td>

                    <td>
                        <?php if ($status != 'Selesai' && $status != 'Dibatalkan'): ?>
                            <div class="aksi">
                                <a class="btn-aksi btn-edit" href="edit_booking.php?id=<?= (int)$d['id'] ?>">Ubah</a>
                                <a class="btn-aksi btn-batal" href="batal_booking.php?id=<?= (int)$d['id'] ?>">Batal</a>
                            </div>
                        <?php else: ?>
                            <span class="no-aksi">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state">Belum ada riwayat booking</div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
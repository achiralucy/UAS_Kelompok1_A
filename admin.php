<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit;
}

$tableSql = "CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    lapangan VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    waktu_mulai TIME NOT NULL,
    durasi INT NOT NULL,
    peserta INT NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($tableSql);

// Ambil data pemesanan lengkap
$bookings = [];
$bookingStmt = $conn->prepare("SELECT r.*, u.name AS user_name FROM reservations r LEFT JOIN users u ON r.user_email = u.email ORDER BY r.tanggal ASC, r.waktu_mulai ASC");
$bookingStmt->execute();
$result = $bookingStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$bookingStmt->close();

// Ambil data pengguna
$users = [];
$userStmt = $conn->prepare("SELECT id, name, email, role FROM users ORDER BY id ASC");
$userStmt->execute();
$userResult = $userStmt->get_result();
while ($userRow = $userResult->fetch_assoc()) {
    $users[] = $userRow;
}
$userStmt->close();

$totalUsers = count($users);
$totalBookings = count($bookings);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PadelPlay</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard">
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div>
                <p class="eyebrow">Admin Panel</p>
                <h1>Halo, Admin <?php echo htmlspecialchars($_SESSION['nama']); ?> 🎾</h1>
                <p class="subtext">Kelola pengguna dan pemesanan lapangan.</p>
            </div>
            <div class="header-actions">
                <a class="button button-secondary" href="logout.php">Logout</a>
                <a class="button button-primary" href="dashboard.php">Halaman User</a>
            </div>
        </header>

        <section class="stats-grid">
            <div class="panel">
                <p class="panel-label">Total Pengguna</p>
                <h2><?php echo $totalUsers; ?></h2>
                <p class="panel-footnote">Termasuk admin dan user.</p>
            </div>
            <div class="panel">
                <p class="panel-label">Total Pemesanan</p>
                <h2><?php echo $totalBookings; ?></h2>
                <p class="panel-footnote">Semua pemesanan yang tersimpan.</p>
            </div>
            <div class="panel">
                <p class="panel-label">Waktu Sekarang</p>
                <h2><?php echo date('H:i'); ?></h2>
                <p class="panel-footnote">Tanggal <?php echo date('d M Y'); ?>.</p>
            </div>
        </section>

        <div class="grid-layout">
            <section class="panel booking-panel">
                <h2>Daftar Pengguna</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role'] ?: 'user'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel schedule-panel">
                <div class="panel-heading">
                    <h2>Riwayat Pemesanan Semua User</h2>
                    <p>Review semua booking lapangan yang telah dibuat.</p>
                </div>
                <?php if ($totalBookings > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Lapangan</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Durasi</th>
                                    <th>Peserta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['user_name'] ?: $booking['user_email']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['lapangan']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($booking['tanggal'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($booking['waktu_mulai'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['durasi']); ?> jam</td>
                                        <td><?php echo htmlspecialchars($booking['peserta']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Belum ada pemesanan yang tercatat.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</body>
</html>

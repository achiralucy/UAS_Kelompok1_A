<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}

$lapanganList = ['Lapangan 1', 'Lapangan 2', 'Lapangan 3'];

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

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lapangan   = isset($_POST['lapangan']) ? trim($_POST['lapangan']) : '';
    $tanggal    = isset($_POST['tanggal']) ? $_POST['tanggal'] : '';
    $waktu      = isset($_POST['waktu']) ? $_POST['waktu'] : '';
    $durasi     = isset($_POST['durasi']) ? (int) $_POST['durasi'] : 0;
    $peserta    = isset($_POST['peserta']) ? (int) $_POST['peserta'] : 0;
    $catatan    = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';

    if (empty($lapangan) || empty($tanggal) || empty($waktu) || $durasi < 1 || $peserta < 1) {
        $error = 'Lengkapi semua data pemesanan dengan benar.';
    } elseif (!in_array($lapangan, $lapanganList)) {
        $error = 'Lapangan tidak valid.';
    } else {
        $insertSql = $conn->prepare("INSERT INTO reservations (user_email, user_name, lapangan, tanggal, waktu_mulai, durasi, peserta, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insertSql->bind_param("sssssiis", $_SESSION['email'], $_SESSION['nama'], $lapangan, $tanggal, $waktu, $durasi, $peserta, $catatan);
        if ($insertSql->execute()) {
            $success = 'Pemesanan berhasil disimpan. Silakan cek riwayat di bawah.';
        } else {
            $error = 'Gagal menyimpan pemesanan. Coba lagi nanti.';
        }
        $insertSql->close();
    }
}

$bookings = [];
$bookingStmt = $conn->prepare("SELECT * FROM reservations WHERE user_email = ? ORDER BY tanggal ASC, waktu_mulai ASC");
$bookingStmt->bind_param("s", $_SESSION['email']);
$bookingStmt->execute();
$result = $bookingStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$bookingStmt->close();

$today = date('Y-m-d');
$todayCount = 0;
foreach ($bookings as $item) {
    if ($item['tanggal'] === $today) {
        $todayCount++;
    }
}
$totalBookings = count($bookings);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard PadelPlay</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard">
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div>
                <p class="eyebrow">Dashboard Penyewaan</p>
                <h1>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?> 🎾</h1>
                <p class="subtext">Kelola pemesanan lapangan padel kamu dengan cepat dan mudah.</p>
            </div>
            <div class="header-actions">
                <a class="button button-secondary" href="logout.php">Logout</a>
            </div>
        </header>

        <section class="stats-grid">
            <div class="panel">
                <p class="panel-label">Lapangan Tersedia</p>
                <h2><?php echo count($lapanganList); ?></h2>
                <p class="panel-footnote">3 lapangan siap disewa setiap hari.</p>
            </div>
            <div class="panel">
                <p class="panel-label">Pemesanan Hari Ini</p>
                <h2><?php echo $todayCount; ?></h2>
                <p class="panel-footnote">Pemesanan untuk tanggal <?php echo date('d M Y'); ?>.</p>
            </div>
            <div class="panel">
                <p class="panel-label">Total Pemesanan</p>
                <h2><?php echo $totalBookings; ?></h2>
                <p class="panel-footnote">Riwayat pemesanan sesuai akun kamu.</p>
            </div>
        </section>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="grid-layout">
            <section class="panel booking-panel">
                <h2>Pesan Lapangan</h2>
                <form action="dashboard.php" method="post">
                    <label for="lapangan">Pilih Lapangan</label>
                    <select id="lapangan" name="lapangan" required>
                        <option value="">-- Pilih Lapangan --</option>
                        <?php foreach ($lapanganList as $lapangan): ?>
                            <option value="<?php echo htmlspecialchars($lapangan); ?>"><?php echo htmlspecialchars($lapangan); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="tanggal">Tanggal</label>
                    <input type="date" id="tanggal" name="tanggal" min="<?php echo date('Y-m-d'); ?>" required>

                    <label for="waktu">Waktu Mulai</label>
                    <input type="time" id="waktu" name="waktu" required>

                    <label for="durasi">Durasi (jam)</label>
                    <input type="number" id="durasi" name="durasi" min="1" max="5" value="1" required>

                    <label for="peserta">Jumlah Peserta</label>
                    <input type="number" id="peserta" name="peserta" min="1" max="8" value="2" required>

                    <label for="catatan">Catatan</label>
                    <textarea id="catatan" name="catatan" placeholder="Contoh: Butuh bola tambahan atau pelatih..." rows="3"></textarea>

                    <button type="submit">Booking Sekarang</button>
                </form>
            </section>

            <section class="panel schedule-panel">
                <div class="panel-heading">
                    <h2>Riwayat Pemesanan</h2>
                    <p><?php echo $totalBookings > 0 ? 'Lihat ringkasan pemesanan terbaru Anda.' : 'Belum ada pemesanan. Segera lakukan booking.'; ?></p>
                </div>
                <?php if ($totalBookings > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
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
                    <p class="empty-state">Belum ada pemesanan. Gunakan form di sebelah kiri untuk melakukan booking lapangan padel.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</body>
</html>
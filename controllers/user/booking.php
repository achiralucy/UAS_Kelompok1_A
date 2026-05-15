<?php
session_start();
require_once '../../models/koneksi.php';
cekLoginUser();

ini_set('display_errors', 0);
error_reporting(0);

date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$today = date('Y-m-d');

$resLapangan = $conn->query("SELECT * FROM lapangan WHERE status = 'aktif' ORDER BY nama ASC");
$lapanganList = [];

while ($l = $resLapangan->fetch_assoc()) {
    $lapanganList[] = $l;
}

if (empty($lapanganList)) {
    die("Belum ada data lapangan.");
}

$lapanganDipilih = isset($_GET['lapangan']) ? (int)$_GET['lapangan'] : $lapanganList[0]['id'];
$tanggalDipilih = (isset($_GET['tanggal']) && $_GET['tanggal'] !== '') ? $_GET['tanggal'] : $today;

if (strtotime($tanggalDipilih) < strtotime($today)) {
    $tanggalDipilih = $today;
}

$lapanganInfo = null;

foreach ($lapanganList as $l) {
    if ($l['id'] == $lapanganDipilih) {
        $lapanganInfo = $l;
        break;
    }
}

$now = new DateTime();

$slotBooked = [];

$stmtSlot = $conn->prepare("SELECT jam_mulai, jam_selesai FROM booking WHERE lapangan_id = ? AND tanggal = ? AND status != 'cancelled'");
$stmtSlot->bind_param("is", $lapanganDipilih, $tanggalDipilih);
$stmtSlot->execute();
$resSlot = $stmtSlot->get_result();

while ($s = $resSlot->fetch_assoc()) {
    $mulai = (int)substr($s['jam_mulai'], 0, 2);
    $selesai = (int)substr($s['jam_selesai'], 0, 2);

    for ($j = $mulai; $j < $selesai; $j++) {
        $slotBooked[] = str_pad($j, 2, '0', STR_PAD_LEFT) . ':00';
    }
}

$semuaSlot = [];

for ($h = 7; $h <= 21; $h++) {
    $semuaSlot[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lapID = (int)($_POST['lapangan_id'] ?? 0);
    $tgl = bersihkan($_POST['tanggal'] ?? '');
    $jamMulai = bersihkan($_POST['jam_mulai'] ?? '');
    $jamSelesai = bersihkan($_POST['jam_selesai'] ?? '');
    $durasi = (int)($_POST['durasi'] ?? 1);
    $catatan = bersihkan($_POST['catatan'] ?? '');

    $startBooking = new DateTime($tgl . ' ' . $jamMulai);

    if (!$lapID || !$tgl || !$jamMulai || !$jamSelesai || !$durasi) {
        $error = 'Mohon lengkapi semua pilihan booking.';
    } elseif (strtotime($tgl) < strtotime($today)) {
        $error = 'Tanggal tidak boleh di masa lalu.';
    } elseif ($startBooking <= new DateTime()) {
        $error = 'Jam sudah lewat tidak bisa dibooking.';
    } else {

        $cekKonflik = $conn->prepare("
            SELECT id FROM booking 
            WHERE lapangan_id = ? AND tanggal = ? AND status != 'cancelled'
            AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
        ");
        $cekKonflik->bind_param("isss", $lapID, $tgl, $jamMulai, $jamSelesai);
        $cekKonflik->execute();
        $cekKonflik->store_result();

        if ($cekKonflik->num_rows > 0) {
            $error = 'Slot waktu sudah dipesan.';
        } else {

            $stmtHarga = $conn->prepare("SELECT harga FROM lapangan WHERE id = ?");
            $stmtHarga->bind_param("i", $lapID);
            $stmtHarga->execute();
            $resHarga = $stmtHarga->get_result()->fetch_assoc();

            $harga = $resHarga['harga'] ?? 0;
            $total = $harga * $durasi;

            $stmtBook = $conn->prepare("
                INSERT INTO booking 
                (user_id, lapangan_id, tanggal, jam_mulai, jam_selesai, durasi, total_harga, catatan, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmtBook->bind_param("iissssis", $user_id, $lapID, $tgl, $jamMulai, $jamSelesai, $durasi, $total, $catatan);

            if ($stmtBook->execute()) {
                header("Location: ../../views/user/riwayat.php?sukses=1");
                exit;
            } else {
                $error = 'Gagal menyimpan booking.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Lapangan - PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/user.css">
</head>
<body>

<nav class="navbar">
    <a href="../../views/user/index_user.php" class="navbar-brand">
        <div class="navbar-logo">P</div>
        <span class="navbar-brand-text">Padel<span>Play</span></span>
    </a>
    <ul class="navbar-nav">
        <li><a href="../../views/user/index.php">Beranda</a></li>
        <li><a href="../../views/user/lapangan.php">Lapangan</a></li>
        <li><a href="booking.php" class="active">Booking</a></li>
        <li><a href="../../views/user/riwayat.php">Riwayat</a></li>
    </ul>
    <div class="navbar-actions">
        <span style="color:#888;font-size:14px;">
            Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?>
        </span>
        <a href="../../views/user/profil.php" class="btn-profil-nav">Profil</a>
        <a href="../logout.php" class="btn-keluar">⎋ Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Booking <span>Lapangan</span></h1>
        <p class="page-subtitle">Pilih lapangan, tanggal, dan slot waktu yang tersedia.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <div class="booking-layout">
        <div class="booking-form-card">
            <form method="GET" action="booking.php">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:22px;">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Lapangan</label>
                        <select name="lapangan" id="lapangan_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($lapanganList as $l): ?>
                                <option value="<?= $l['id'] ?>" 
                                    data-harga="<?= $l['harga'] ?>"
                                    <?= ($l['id'] == $lapanganDipilih) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control"
                            value="<?= $tanggalDipilih ?>" min="<?= date('Y-m-d') ?>"
                            onchange="this.form.submit()">
                    </div>
                </div>
            </form>

            <div class="slot-label">🕐 Pilih jam mulai</div>
            <div class="slot-grid">
                <?php foreach ($semuaSlot as $slot):
                    $isBooked = in_array($slot, $slotBooked);
                    $slotTime = new DateTime($tanggalDipilih . ' ' . $slot);
                    $isPast = ($tanggalDipilih === $today && $slotTime <= $now);
                    $class = ($isBooked || $isPast) ? 'slot-btn booked' : 'slot-btn';
                ?>
                    <button type="button" class="<?= $class ?>" data-jam="<?= $slot ?>"
                        <?= ($isBooked || $isPast) ? 'disabled' : '' ?>
                        onclick="pilihSlot('<?= $slot ?>')">
                        <?= $slot ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Durasi (jam)</label>
                <select id="durasi_select" class="form-control" onchange="document.getElementById('durasi').value=this.value; updateRingkasan();">
                    <option value="1">1 jam</option>
                    <option value="2">2 jam</option>
                    <option value="3">3 jam</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Catatan (opsional)</label>
                <textarea id="catatan_input" class="form-control" placeholder="Misal: bawa raket sendiri, perlu sewa, dll."></textarea>
            </div>
        </div>

        <div class="ringkasan-card">
            <div class="ringkasan-title">Ringkasan</div>
            <div class="ringkasan-row">
                <span class="label">Lapangan</span>
                <span class="value" id="ringkasan_lapangan"><?= htmlspecialchars($lapanganInfo['nama'] ?? '-') ?></span>
            </div>
            <div class="ringkasan-row">
                <span class="label">Tanggal</span>
                <span class="value" id="ringkasan_tanggal"><?= $tanggalDipilih ?></span>
            </div>
            <div class="ringkasan-row">
                <span class="label">Mulai</span>
                <span class="value" id="ringkasan_mulai">-</span>
            </div>
            <div class="ringkasan-row">
                <span class="label">Selesai</span>
                <span class="value" id="ringkasan_selesai">-</span>
            </div>

            <hr class="ringkasan-divider">

            <div class="ringkasan-total">
                <span class="label">Total</span>
                <span class="value" id="ringkasan_total"><?= formatRupiah($lapanganInfo['harga'] ?? 0) ?></span>
            </div>
            <div class="ringkasan-note">Pembayaran dilakukan langsung di lokasi.</div>

            <form method="POST" action="booking.php">
                <input type="hidden" name="lapangan_id" value="<?= $lapanganDipilih ?>">
                <input type="hidden" name="tanggal" value="<?= $tanggalDipilih ?>">
                <input type="hidden" name="jam_mulai" id="jam_mulai" value="">
                <input type="hidden" name="jam_selesai" id="jam_selesai" value="">
                <input type="hidden" name="durasi" id="durasi" value="1">
                <input type="hidden" name="catatan" id="catatan_hidden" value="">
                <button type="submit" class="btn-konfirmasi"
                    onclick="document.getElementById('catatan_hidden').value=document.getElementById('catatan_input').value; document.getElementById('durasi').value=document.getElementById('durasi_select').value;">
                    Konfirmasi Booking
                </button>
            </form>
        </div>
    </div>
</div>

<footer class="footer">
    <p>© 2026 <span>PadelPlay</span> · Lampung Padel Center</p>
</footer>

<script src="../../assets/js/user.js"></script>
<script>
const hargaLapangan = <?= isset($lapanganInfo['harga']) ? $lapanganInfo['harga'] : 0 ?>;
</script>
</body>
</html>
<?php
session_start();
require_once '../../models/koneksi.php';

date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../../views/login.php");
    exit;
}

$error = '';
$success = '';

$today = date('Y-m-d');
$now = new DateTime();

$resiData = null;

$resLapangan  = $conn->query("SELECT * FROM lapangan WHERE status = 'aktif' ORDER BY nama ASC");
$lapanganList = [];
while ($l = $resLapangan->fetch_assoc()) {
    $lapanganList[] = $l;
}

if (empty($lapanganList)) {
    die("<p class='die-message'>Belum ada lapangan tersedia.</p>");
}

$lapanganDipilih = isset($_GET['lapangan']) ? (int)$_GET['lapangan'] : $lapanganList[0]['id'];
$tanggalDipilih = (isset($_GET['tanggal']) && $_GET['tanggal'] !== '')
    ? $_GET['tanggal']
    : $today;

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

$slotBooked = [];
$stmtSlot   = $conn->prepare("SELECT jam_mulai, jam_selesai FROM booking WHERE lapangan_id = ? AND tanggal = ? AND status != 'cancelled'");
$stmtSlot->bind_param("is", $lapanganDipilih, $tanggalDipilih);
$stmtSlot->execute();
$resSlot = $stmtSlot->get_result();
while ($s = $resSlot->fetch_assoc()) {
    $mulai   = (int)substr($s['jam_mulai'],  0, 2);
    $selesai = (int)substr($s['jam_selesai'], 0, 2);
    for ($j = $mulai; $j < $selesai; $j++) {
        $slotBooked[] = str_pad($j, 2, '0', STR_PAD_LEFT) . ':00';
    }
}

$semuaSlot = [];
for ($h = 7; $h <= 21; $h++) {
    $semuaSlot[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
}

function generateKodeBooking($conn) {
    do {
        $kode = 'PDL-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $cek  = $conn->prepare("SELECT id FROM booking WHERE kode_booking = ?");
        $cek->bind_param("s", $kode);
        $cek->execute();
        $cek->store_result();
    } while ($cek->num_rows > 0);
    return $kode;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lapID      = (int)($_POST['lapangan_id'] ?? 0);
    $tgl        = bersihkan($_POST['tanggal']    ?? '');
    $jamMulai   = bersihkan($_POST['jam_mulai']  ?? '');
    $jamSelesai = bersihkan($_POST['jam_selesai'] ?? '');
    $durasi     = (int)($_POST['durasi']          ?? 1);
    $catatan    = bersihkan($_POST['catatan']     ?? '');

    if (!$lapID || !$tgl || !$jamMulai || !$jamSelesai || !$durasi) {
        $error = 'Mohon lengkapi semua pilihan booking.';
    } elseif ($tgl < date('Y-m-d')) {
        $error = 'Tanggal tidak boleh di masa lalu.';
    } elseif (new DateTime($tgl . ' ' . $jamMulai) <= new DateTime()) {
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
            $error = 'Slot waktu sudah dipesan orang lain. Silakan pilih jam lain.';
        } else {
            $stmtHarga = $conn->prepare("SELECT harga, nama FROM lapangan WHERE id = ?");
            $stmtHarga->bind_param("i", $lapID);
            $stmtHarga->execute();
            $resHarga    = $stmtHarga->get_result()->fetch_assoc();
            $harga       = $resHarga['harga'] ?? 0;
            $namaLapangan = $resHarga['nama']  ?? '-';
            $total       = $harga * $durasi;

            $kodeBooking = generateKodeBooking($conn);

            $stmtBook = $conn->prepare("
                INSERT INTO booking
                    (kode_booking, user_id, lapangan_id, tanggal, jam_mulai, jam_selesai, durasi, total_harga, catatan, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmtBook->bind_param("siissssis", $kodeBooking, $user_id, $lapID, $tgl, $jamMulai, $jamSelesai, $durasi, $total, $catatan);

            if ($stmtBook->execute()) {
                $resiData = [
                    'kode_booking' => $kodeBooking,
                    'lapangan'     => $namaLapangan,
                    'tanggal'      => date('d M Y', strtotime($tgl)),
                    'jam_mulai'    => substr($jamMulai,   0, 5),
                    'jam_selesai'  => substr($jamSelesai, 0, 5),
                    'durasi'       => $durasi,
                    'total'        => $total,
                    'status'       => 'Pending',
                ];
                $success = 'Booking berhasil!';
            } else {
                $error = 'Gagal menyimpan booking: ' . $conn->error;
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
    <link rel="stylesheet" href="../../assets/css/style.css?v=2.2">
</head>
<body>

<nav class="navbar">
    <a href="../../views/user/index.php" class="navbar-brand">
        <div class="navbar-logo">P</div>
        <span class="navbar-brand-text">Padel<span>Play</span></span>
    </a>

    <button class="menu-toggle" onclick="toggleMenu()">☰</button>

    <ul class="navbar-nav" id="navbarNav">
        <li><a href="../../views/user/index.php">Beranda</a></li>
        <li><a href="../../views/user/lapangan.php">Lapangan</a></li>
        <li><a href="booking.php" class="active">Booking</a></li>
        <li><a href="../../views/user/riwayat.php">Riwayat</a></li>

        <li class="mobile-only">
            <a href="../../views/user/profil.php">Profil</a>
        </li>

        <li class="mobile-only">
            <a href="#" onclick="tampilModalLogout(event)">Keluar</a>
        </li>
    </ul>

    <div class="navbar-actions">
        <span class="navbar-user-greeting">
            Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?>
        </span>

        <a href="../../views/user/profil.php" class="btn-profil-nav">Profil</a>
        <a href="#" class="btn-keluar" onclick="tampilModalLogout(event)">⎋ Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Booking <span>Lapangan</span></h1>
        <p class="page-subtitle">Pilih lapangan, tanggal, dan slot waktu yang tersedia.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="booking-layout">
        <div class="booking-form-card">
            <form method="GET" action="booking.php" id="filter-form">
                <div class="booking-filter-wrapper">
                    <div class="form-group form-group-inline">
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
                    <div class="form-group form-group-inline">
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
                    <button
                        type="button"
                        class="<?= $class ?>"
                        data-jam="<?= $slot ?>"
                        <?= ($isBooked || $isPast) ? 'disabled' : '' ?>
                        onclick="pilihSlot('<?= $slot ?>')"
                    >
                        <?= $slot ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Durasi (jam)</label>
                <select id="durasi_select" class="form-control"
                    onchange="document.getElementById('durasi').value=this.value; updateRingkasan();">
                    <option value="1">1 jam</option>
                    <option value="2">2 jam</option>
                    <option value="3">3 jam</option>
                </select>
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
                <span class="value" id="ringkasan_tanggal"><?= date('d M Y', strtotime($tanggalDipilih)) ?></span>
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
                <span class="value" id="ringkasan_total">Rp 0</span>
            </div>
            <div class="ringkasan-note">Pembayaran dilakukan langsung di lokasi.</div>

            <form method="POST" action="booking.php" id="form-booking">
                <input type="hidden" name="lapangan_id" value="<?= $lapanganDipilih ?>">
                <input type="hidden" name="tanggal"     value="<?= $tanggalDipilih ?>">
                <input type="hidden" name="jam_mulai"   id="jam_mulai"   value="">
                <input type="hidden" name="jam_selesai" id="jam_selesai" value="">
                <input type="hidden" name="durasi"      id="durasi"      value="1">
                <button type="button" class="btn-konfirmasi" onclick="submitBooking()">
                    Konfirmasi Booking
                </button>
            </form>
        </div>
    </div>
</div>

<footer class="footer">
    <p>© 2026 <span>PadelPlay</span> · Lampung Padel Center</p>
</footer>

<?php if ($resiData): ?>
<div class="resi-overlay active" id="popup-resi">
    <div class="resi-box">
        <div class="resi-header">
            <div class="resi-logo">Padel<span>Play</span></div>
            <div class="resi-tagline">Booking Lapangan Padel #1 di Lampung</div>
            <div class="resi-kode"><?= htmlspecialchars($resiData['kode_booking']) ?></div>
            <div class="resi-caption-text">Kode Booking</div>
        </div>
        <div class="resi-body">
            <div class="resi-row">
                <span class="rl">Lapangan</span>
                <span class="rv"><?= htmlspecialchars($resiData['lapangan']) ?></span>
            </div>
            <div class="resi-row">
                <span class="rl">Tanggal</span>
                <span class="rv"><?= htmlspecialchars($resiData['tanggal']) ?></span>
            </div>
            <div class="resi-row">
                <span class="rl">Jam Mulai</span>
                <span class="rv"><?= htmlspecialchars($resiData['jam_mulai']) ?></span>
            </div>
            <div class="resi-row">
                <span class="rl">Jam Selesai</span>
                <span class="rv"><?= htmlspecialchars($resiData['jam_selesai']) ?></span>
            </div>
            <div class="resi-row">
                <span class="rl">Durasi</span>
                <span class="rv"><?= $resiData['durasi'] ?> jam</span>
            </div>
            <div class="resi-row">
                <span class="rl">Status</span>
                <span class="rv"><span class="badge-resi-status">⏳ <?= htmlspecialchars($resiData['status']) ?></span></span>
            </div>
            <hr class="resi-divider">
            <div class="resi-total-row">
                <span class="resi-total-label">Total Harga</span>
                <span class="resi-total-value"><?= 'Rp ' . number_format($resiData['total'], 0, ',', '.') ?></span>
            </div>
            <div class="resi-note">💡 Simpan kode booking ini. Bayar langsung di lokasi saat tiba.</div>
        </div>
        <div class="resi-actions">
            <button class="btn-print" onclick="lihatRiwayat()">📋 Lihat Riwayat</button>
            <button class="btn-tutup" onclick="tutupResi()">✓ Selesai</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="logout-overlay" id="modal-logout">
    <div class="logout-box">
        <div class="logout-icon">⎋</div>
        <h3>Konfirmasi Keluar</h3>
        <p>Apakah Anda yakin ingin keluar dari akun PadelPlay?</p>
        <div class="logout-btns">
            <button class="lbtn-no" onclick="tutupModalLogout()">Tidak</button>
            <a href="../../controllers/logout.php" class="lbtn-yes">Ya, Keluar</a>
        </div>
    </div>
</div>

<script src="../../assets/js/user.js"></script>
<script>
const hargaLapangan = <?= (int)($lapanganInfo['harga'] ?? 0) ?>;
let selectedJamMulai = '';

function pilihSlot(jam) {
    document.querySelectorAll('.slot-btn').forEach(btn => {
        if (!btn.classList.contains('booked')) {
            btn.classList.remove('active');
        }
    });

    const targetBtn = document.querySelector(`.slot-btn[data-jam="${jam}"]`);
    if (targetBtn) targetBtn.classList.add('active');

    selectedJamMulai = jam;
    document.getElementById('jam_mulai').value = jam;
    
    updateRingkasan();
}

function updateRingkasan() {
    if (!selectedJamMulai) return;

    const durasi = parseInt(document.getElementById('durasi_select').value);
    const [jam] = selectedJamMulai.split(':').map(Number);
    const jamSelesaiHitung = String(jam + durasi).padStart(2, '0') + ':00';

    document.getElementById('jam_selesai').value = jamSelesaiHitung;
    document.getElementById('ringkasan_mulai').textContent = selectedJamMulai;
    document.getElementById('ringkasan_selesai').textContent = jamSelesaiHitung;

    const totalHarga = hargaLapangan * durasi;
    document.getElementById('ringkasan_total').textContent = 'Rp ' + totalHarga.toLocaleString('id-ID');
}

function tutupResi() {
    document.getElementById('popup-resi').classList.remove('active');
    window.location.href = '../../views/user/riwayat.php';
}

function lihatRiwayat() {
    window.location.href = '../../views/user/riwayat.php?sukses=1';
}

function slotTidakValid(jamMulai, durasi) {
    const semuaBooked = [];
    document.querySelectorAll('.slot-btn.booked').forEach(btn => {
        semuaBooked.push(btn.dataset.jam);
    });

    const [jam] = jamMulai.split(':').map(Number);
    for (let i = 0; i < durasi; i++) {
        const cekJam = String(jam + i).padStart(2, '0') + ':00';
        if (semuaBooked.includes(cekJam)) {
            return true;
        }
    }
    return false;
}

function submitBooking() {
    const durasi = parseInt(document.getElementById('durasi_select').value);
    const jamMulai = document.getElementById('jam_mulai').value;

    if (!jamMulai) {
        alert('Pilih jam terlebih dahulu.');
        return;
    }

    const [jam] = jamMulai.split(':').map(Number);
    if (jam + durasi > 22) {
        alert('Melewati jam operasional.');
        return;
    }

    if (slotTidakValid(jamMulai, durasi)) {
        alert('Durasi melewati slot yang sudah dibooking.');
        return;
    }

    document.getElementById('durasi').value = durasi;
    document.getElementById('form-booking').submit();
}

function tampilModalLogout(e) {
    e.preventDefault();
    document.getElementById('modal-logout').classList.add('active');
}

function tutupModalLogout() {
    document.getElementById('modal-logout').classList.remove('active');
}
function toggleMenu() {
    document.getElementById('navbarNav').classList.toggle('show');
    document.body.classList.toggle('menu-open');
}
</script>
</body>
</html>
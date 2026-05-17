<?php
session_start();
require_once '../../models/koneksi.php';
cekLoginUser();

ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
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
    die("<p style='color:#fff;padding:40px;'>Belum ada lapangan tersedia.</p>");
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
    <link rel="stylesheet" href="../../assets/css/user.css">
    <link rel="stylesheet" href="../../assets/css/additions.css">
    <style>
        .resi-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.75);
            z-index: 9999;
            align-items: center; justify-content: center;
        }
        .resi-overlay.active { display: flex; }

        .resi-box {
            background: #111;
            border: 1px solid #2a2a2a;
            border-radius: 16px;
            width: 90%; max-width: 440px;
            padding: 0; overflow: hidden;
            box-shadow: 0 0 40px rgba(233,30,140,.15);
        }
        .resi-header {
            background: linear-gradient(135deg,#1a0a12,#2d0a1e);
            border-bottom: 1px solid #2a2a2a;
            padding: 20px 24px 16px;
            text-align: center;
        }
        .resi-logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800; font-size: 22px; color: #fff;
            margin-bottom: 4px;
        }
        .resi-logo span { color: #e91e8c; }
        .resi-tagline { color: #666; font-size: 12px; }
        .resi-kode {
            font-family: monospace; font-size: 22px; font-weight: 700;
            color: #e91e8c; letter-spacing: 2px;
            margin: 12px 0 4px;
        }
        .resi-body { padding: 20px 24px; }
        .resi-divider {
            border: none; border-top: 1px dashed #2a2a2a;
            margin: 14px 0;
        }
        .resi-row {
            display: flex; justify-content: space-between;
            align-items: flex-start; margin-bottom: 10px;
        }
        .resi-row .rl { color: #666; font-size: 13px; }
        .resi-row .rv { color: #ccc; font-size: 13px; font-weight: 600; text-align: right; max-width: 60%; }
        .resi-total-row {
            display: flex; justify-content: space-between;
            align-items: center; margin-top: 4px;
        }
        .resi-total-label { color: #fff; font-weight: 700; font-size: 15px; }
        .resi-total-value { color: #e91e8c; font-weight: 800; font-size: 18px; }
        .badge-resi-status {
            display: inline-block; padding: 3px 12px;
            background: rgba(255,165,0,.15); color: #ffa500;
            border: 1px solid #ffa500; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .resi-note {
            background: #1a1a1a; border-radius: 8px;
            padding: 10px 14px; color: #666; font-size: 12px;
            text-align: center; margin-top: 12px;
        }
        .resi-actions {
            padding: 16px 24px 20px;
            display: flex; gap: 12px;
        }
        .resi-actions .btn-print {
            flex: 1; background: transparent; border: 1px solid #e91e8c;
            color: #e91e8c; border-radius: 8px; padding: 11px;
            cursor: pointer; font-weight: 600; font-size: 14px;
            transition: background .2s;
        }
        .resi-actions .btn-print:hover { background: rgba(233,30,140,.1); }
        .resi-actions .btn-tutup {
            flex: 1; background: #e91e8c; border: none;
            color: #fff; border-radius: 8px; padding: 11px;
            cursor: pointer; font-weight: 600; font-size: 14px;
            transition: opacity .2s;
        }
        .resi-actions .btn-tutup:hover { opacity: .85; }
        .modal-overlay-custom {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.75); z-index: 9998;
            align-items: center; justify-content: center;
        }
        .modal-overlay-custom.active { display: flex; }
        .modal-custom {
            background: #111; border: 1px solid #2a2a2a;
            border-radius: 16px; width: 90%; max-width: 380px;
            padding: 32px 28px; text-align: center;
        }
        .modal-custom h3 { color: #fff; font-family:'Montserrat',sans-serif; margin-bottom:12px; }
        .modal-custom p { color: #888; font-size: 14px; margin-bottom: 24px; }
        .modal-custom .modal-btns { display: flex; gap: 12px; justify-content: center; }
        .modal-custom .mbtn-no {
            padding: 10px 24px; border: 1px solid #333;
            background: transparent; color: #ccc; border-radius: 8px;
            cursor: pointer; font-size: 14px; transition: border-color .2s;
        }
        .modal-custom .mbtn-no:hover { border-color: #e91e8c; color: #e91e8c; }
        .modal-custom .mbtn-yes {
            padding: 10px 24px; background: #e91e8c; border: none;
            color: #fff; border-radius: 8px; cursor: pointer;
            font-size: 14px; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center;
        }

        @media print {
            body * { visibility: hidden; }
            .resi-print-area, .resi-print-area * { visibility: visible; }
            .resi-print-area {
                position: fixed; inset: 0; background: #fff !important;
                color: #000 !important; padding: 30px;
            }
            .resi-actions { display: none !important; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="../../views/user/index.php" class="navbar-brand">
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
        <span style="color:#888;font-size:14px;">Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></span>
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
                    
                    $class = ($isBooked || $isPast)
                        ? 'slot-btn booked'
                        : 'slot-btn';
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
        <div class="resi-print-area">
            <div class="resi-header">
                <div class="resi-logo">Padel<span>Play</span></div>
                <div class="resi-tagline">Booking Lapangan Padel #1 di Lampung</div>
                <div class="resi-kode"><?= htmlspecialchars($resiData['kode_booking']) ?></div>
                <div style="color:#888;font-size:12px;">Kode Booking</div>
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
                    <span class="resi-total-value"><?= formatRupiah($resiData['total']) ?></span>
                </div>
                <div class="resi-note">💡 Simpan kode booking ini. Bayar langsung di lokasi saat tiba.</div>
            </div>
        </div>
        <div class="resi-actions">
            <button class="btn-print" onclick="lihatRiwayat()">📋 Lihat Riwayat</button>
            <button class="btn-tutup" onclick="tutupResi()">✓ Selesai</button>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="modal-overlay-custom" id="modal-logout">
    <div class="modal-custom">
        <div style="font-size:40px;margin-bottom:12px;">⎋</div>
        <h3>Konfirmasi Keluar</h3>
        <p>Apakah Anda yakin ingin keluar dari akun?</p>
        <div class="modal-btns">
            <button class="mbtn-no" onclick="tutupModalLogout()">Tidak</button>
            <a href="../../controllers/logout.php" class="mbtn-yes">Ya, Keluar</a>
        </div>
    </div>
</div>

<script src="../../assets/js/user.js"></script>
<script>
const hargaLapangan = <?= (int)($lapanganInfo['harga'] ?? 0) ?>;

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

    updateRingkasan();

    document.getElementById('form-booking').submit();
}

function tampilModalLogout(e) {
    e.preventDefault();
    document.getElementById('modal-logout').classList.add('active');
}
function tutupModalLogout() {
    document.getElementById('modal-logout').classList.remove('active');
}
</script>
</body>
</html>
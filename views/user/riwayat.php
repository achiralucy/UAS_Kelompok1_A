<?php
session_start();
require_once '../../models/koneksi.php';
cekLoginUser();

$user_id = $_SESSION['user_id'];
$success = '';

if (isset($_GET['batal'])) {
    $bookId = (int)$_GET['batal'];
    $stmtBatal = $conn->prepare("UPDATE booking SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmtBatal->bind_param("ii", $bookId, $user_id);
    if ($stmtBatal->execute() && $stmtBatal->affected_rows > 0) {
        $success = 'Booking berhasil dibatalkan.';
    }
}

if (isset($_GET['sukses'])) {
    $success = 'Booking berhasil dibuat! Sampai jumpa di lapangan. 🎾';
}

$stmt = $conn->prepare("
    SELECT b.*, l.nama AS lapangan_nama, l.lokasi
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
    <link rel="stylesheet" href="../../assets/css/style.css?v=1.9">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <div class="navbar-logo">P</div>
        <span class="navbar-brand-text">Padel<span>Play</span></span>
    </a>
    <ul class="navbar-nav">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="lapangan.php">Lapangan</a></li>
        <li><a href="../../controllers/user/booking.php">Booking</a></li>
        <li><a href="riwayat.php" class="active">Riwayat</a></li>
    </ul>
    <div class="navbar-actions">
        <span class="navbar-user-greeting">Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?></span>
        <a href="profil.php" class="btn-profil-nav">Profil</a>
        <a href="#" class="btn-keluar" onclick="tampilModalLogout(event)">⎋ Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Riwayat <span>Booking</span></h1>
        <p class="page-subtitle">Semua pemesananmu di satu tempat.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($bookings->num_rows === 0): ?>
        <div class="riwayat-empty">
            <div class="riwayat-empty-icon">📋</div>
            <p>Belum ada booking.</p>
            <a href="../../controllers/user/booking.php" class="btn btn-pink">Booking Sekarang</a>
        </div>
    <?php else: ?>
        <div class="tabel-riwayat">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
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
                            <button class="kode-link"
                                onclick="tampilResi(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)">
                                <?= htmlspecialchars($b['kode_booking'] ?? 'PDL-????') ?>
                            </button>
                        </td>
                        <td>
                            <strong class="table-field-name"><?= htmlspecialchars($b['lapangan_nama']) ?></strong><br>
                            <small class="table-field-location"><?= htmlspecialchars($b['lokasi']) ?></small>
                        </td>
                        <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                        <td><?= substr($b['jam_mulai'], 0, 5) ?> - <?= substr($b['jam_selesai'], 0, 5) ?></td>
                        <td><?= $b['durasi'] ?> jam</td>
                        <td class="table-field-total"><?= formatRupiah($b['total_harga']) ?></td>
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
                                <button class="btn-batal"
                                    onclick="tampilModalBatal(<?= $b['id'] ?>)">Batalkan</button>
                            <?php else: ?>
                                <span class="table-field-location">-</span>
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

<div class="popup-overlay" id="modal-batal">
    <div class="modal-konfirm">
        <div class="mk-icon">⚠️</div>
        <h3>Konfirmasi Pembatalan</h3>
        <p>Yakin ingin membatalkan booking ini?<br>Status akan berubah menjadi <strong class="teks-batal">Dibatalkan</strong>.</p>
        <div class="mk-btns">
            <button class="mbtn-no" onclick="tutupModal('modal-batal')">Tidak</button>
            <a href="#" id="batal-url" class="mbtn-yes">Ya, Batalkan</a>
        </div>
    </div>
</div>

<div class="popup-overlay" id="popup-resi">
    <div class="resi-box">
        <div class="resi-print-area">
            <div class="resi-header">
                <div class="resi-logo">Padel<span>Play</span></div>
                <div class="resi-subtitle-text">Booking Lapangan Padel #1 di Lampung</div>
                <div class="resi-kode" id="r-kode">PDL-XXXXXXXX</div>
                <div class="resi-caption-text">Kode Booking</div>
            </div>
            <div class="resi-body">
                <div class="resi-row">
                    <span class="rl">Lapangan</span>
                    <span class="rv" id="r-lapangan">-</span>
                </div>
                <div class="resi-row">
                    <span class="rl">Tanggal</span>
                    <span class="rv" id="r-tanggal">-</span>
                </div>
                <div class="resi-row">
                    <span class="rl">Jam Mulai</span>
                    <span class="rv" id="r-mulai">-</span>
                </div>
                <div class="resi-row">
                    <span class="rl">Jam Selesai</span>
                    <span class="rv" id="r-selesai">-</span>
                </div>
                <div class="resi-row">
                    <span class="rl">Durasi</span>
                    <span class="rv" id="r-durasi">-</span>
                </div>
                <div class="resi-row">
                    <span class="rl">Status</span>
                    <span class="rv"><span id="r-status" class="badge-resi-status">-</span></span>
                </div>
                <hr class="resi-divider">
                <div class="resi-total-row">
                    <span class="resi-total-label">Total Harga</span>
                    <span class="resi-total-value" id="r-total">-</span>
                </div>
                <div class="resi-note">💡 Tunjukkan kode booking ini kepada petugas saat tiba di lokasi.</div>
            </div>
        </div>
        <div class="resi-actions">
            <button class="btn-tutup" onclick="tutupModal('popup-resi')">✕ Tutup</button>
        </div>
    </div>
</div>

<div class="popup-overlay" id="modal-logout">
    <div class="modal-konfirm">
        <div class="mk-icon">⎋</div>
        <h3>Konfirmasi Keluar</h3>
        <p>Apakah Anda yakin ingin keluar dari akun?</p>
        <div class="mk-btns">
            <button class="mbtn-no" onclick="tutupModal('modal-logout')">Tidak</button>
            <a href="../../controllers/logout.php" class="mbtn-yes">Ya, Keluar</a>
        </div>
    </div>
</div>

<script src="../../assets/js/user.js"></script>
<script>
function bukaModal(id) {
    document.getElementById(id).classList.add('active');
}
function tutupModal(id) {
    document.getElementById(id).classList.remove('active');
}

document.querySelectorAll('.popup-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});

function tampilModalBatal(id) {
    document.getElementById('batal-url').href = 'riwayat.php?batal=' + id;
    bukaModal('modal-batal');
}

function tampilModalLogout(e) {
    e.preventDefault();
    bukaModal('modal-logout');
}

function tampilResi(b) {
    const statusMap = {
        'pending':   { teks: '⏳ Pending',      cls: 'badge-resi-status' },
        'confirmed': { teks: '✅ Confirmed',     cls: 'badge-resi-status badge-resi-confirmed' },
        'cancelled': { teks: '❌ Dibatalkan',    cls: 'badge-resi-status' },
    };
    const st = statusMap[b.status] || { teks: b.status, cls: 'badge-resi-status' };

    const tglFormatted = b.tanggal
        ? new Date(b.tanggal + 'T00:00:00').toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'})
        : '-';

    document.getElementById('r-kode').textContent   = b.kode_booking || 'PDL-????';
    document.getElementById('r-lapangan').textContent = b.lapangan_nama || '-';
    document.getElementById('r-tanggal').textContent  = tglFormatted;
    document.getElementById('r-mulai').textContent    = (b.jam_mulai   || '').slice(0,5);
    document.getElementById('r-selesai').textContent  = (b.jam_selesai || '').slice(0,5);
    document.getElementById('r-durasi').textContent   = (b.durasi || '-') + ' jam';
    document.getElementById('r-total').textContent    =
        'Rp ' + parseInt(b.total_harga || 0).toLocaleString('id-ID');

    const elStatus = document.getElementById('r-status');
    elStatus.textContent  = st.teks;
    elStatus.className    = st.cls;

    bukaModal('popup-resi');
}

document.querySelectorAll('.alert').forEach(function(el) {
    setTimeout(function() {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(function() { el.remove(); }, 500);
    }, 4500);
});
</script>
</body>
</html>
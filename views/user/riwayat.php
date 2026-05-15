<?php
/**
 * views/user/riwayat.php
 * Riwayat booking user + modal konfirmasi pembatalan
 */
session_start();
require_once '../../models/koneksi.php';
cekLoginUser();

$user_id = $_SESSION['user_id'];
$success = '';

// ─── Proses pembatalan ────────────────────────────────────────
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

// ─── Ambil riwayat booking ────────────────────────────────────
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
    <link rel="stylesheet" href="../../assets/css/user.css">
    <link rel="stylesheet" href="../../assets/css/additions.css">
    <style>
        /* ── Shared Modal Overlay ────────────────────────────────── */
        .popup-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.78); z-index: 9999;
            align-items: center; justify-content: center;
        }
        .popup-overlay.active { display: flex; }

        /* ── Modal Konfirmasi Batal ───────────────────────────────── */
        .modal-konfirm {
            background: #111; border: 1px solid #2a2a2a;
            border-radius: 16px; width: 90%; max-width: 390px;
            padding: 36px 28px; text-align: center;
            box-shadow: 0 0 40px rgba(233,30,140,.1);
        }
        .modal-konfirm .mk-icon { font-size: 48px; margin-bottom: 14px; }
        .modal-konfirm h3 {
            font-family: 'Montserrat', sans-serif; font-weight: 700;
            color: #fff; font-size: 18px; margin-bottom: 10px;
        }
        .modal-konfirm p  { color: #888; font-size: 14px; margin-bottom: 24px; }
        .modal-konfirm .mk-btns { display: flex; gap: 12px; justify-content: center; }
        .mk-btns .mbtn-no {
            padding: 10px 26px; border: 1px solid #333; background: transparent;
            color: #ccc; border-radius: 8px; cursor: pointer; font-size: 14px;
            transition: border-color .2s, color .2s;
        }
        .mk-btns .mbtn-no:hover { border-color: #e91e8c; color: #e91e8c; }
        .mk-btns .mbtn-yes {
            padding: 10px 26px; background: #e91e8c; border: none;
            color: #fff; border-radius: 8px; cursor: pointer; font-size: 14px;
            font-weight: 600; text-decoration: none; display: inline-block;
            transition: opacity .2s;
        }
        .mk-btns .mbtn-yes:hover { opacity: .85; }

        /* ── Popup Resi ──────────────────────────────────────────── */
        .resi-box {
            background: #111; border: 1px solid #2a2a2a;
            border-radius: 16px; width: 90%; max-width: 440px;
            overflow: hidden; box-shadow: 0 0 40px rgba(233,30,140,.15);
        }
        .resi-header {
            background: linear-gradient(135deg,#1a0a12,#2d0a1e);
            border-bottom: 1px solid #2a2a2a;
            padding: 20px 24px 16px; text-align: center;
        }
        .resi-logo {
            font-family:'Montserrat',sans-serif; font-weight:800;
            font-size:22px; color:#fff; margin-bottom:4px;
        }
        .resi-logo span { color:#e91e8c; }
        .resi-kode {
            font-family:monospace; font-size:22px; font-weight:700;
            color:#e91e8c; letter-spacing:2px; margin:12px 0 4px;
        }
        .resi-body { padding:20px 24px; }
        .resi-divider { border:none; border-top:1px dashed #2a2a2a; margin:14px 0; }
        .resi-row { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
        .resi-row .rl { color:#666; font-size:13px; }
        .resi-row .rv { color:#ccc; font-size:13px; font-weight:600; text-align:right; max-width:60%; }
        .resi-total-row { display:flex; justify-content:space-between; align-items:center; margin-top:4px; }
        .resi-total-label { color:#fff; font-weight:700; font-size:15px; }
        .resi-total-value { color:#e91e8c; font-weight:800; font-size:18px; }
        .badge-resi-status {
            display:inline-block; padding:3px 12px;
            background:rgba(255,165,0,.15); color:#ffa500;
            border:1px solid #ffa500; border-radius:20px;
            font-size:12px; font-weight:600;
        }
        .badge-resi-confirmed {
            background:rgba(0,200,100,.15); color:#00c864;
            border-color:#00c864;
        }
        .resi-note {
            background:#1a1a1a; border-radius:8px; padding:10px 14px;
            color:#666; font-size:12px; text-align:center; margin-top:12px;
        }
        .resi-actions {
            padding:16px 24px 20px; display:flex; gap:12px;
        }
        .resi-actions .btn-print {
            flex:1; background:transparent; border:1px solid #e91e8c;
            color:#e91e8c; border-radius:8px; padding:11px;
            cursor:pointer; font-weight:600; font-size:14px;
            transition:background .2s;
        }
        .resi-actions .btn-print:hover { background:rgba(233,30,140,.1); }
        .resi-actions .btn-tutup {
            flex:1; background:#e91e8c; border:none; color:#fff;
            border-radius:8px; padding:11px; cursor:pointer;
            font-weight:600; font-size:14px; transition:opacity .2s;
        }
        .resi-actions .btn-tutup:hover { opacity:.85; }

        /* ── Kode booking di tabel ───────────────────────────────── */
        .kode-link {
            font-family: monospace; font-size: 12px; color: #e91e8c;
            cursor: pointer; background: none; border: none;
            padding: 0; text-decoration: underline dotted;
            transition: opacity .2s;
        }
        .kode-link:hover { opacity: .7; }

        @media print {
            body * { visibility: hidden; }
            .resi-print-area, .resi-print-area * { visibility: visible; }
            .resi-print-area { position: fixed; inset: 0; background: #fff !important; padding: 30px; }
            .resi-actions { display: none !important; }
        }
    </style>
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
        <span style="color:#888;font-size:14px;">Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?></span>
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
            <a href="../../controllers/user/booking.php" class="btn-pink">Booking Sekarang</a>
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
                            <strong style="color:#fff;"><?= htmlspecialchars($b['lapangan_nama']) ?></strong><br>
                            <small style="color:#666;"><?= htmlspecialchars($b['lokasi']) ?></small>
                        </td>
                        <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                        <td><?= substr($b['jam_mulai'], 0, 5) ?> - <?= substr($b['jam_selesai'], 0, 5) ?></td>
                        <td><?= $b['durasi'] ?> jam</td>
                        <td style="color:#e91e8c; font-weight:700;"><?= formatRupiah($b['total_harga']) ?></td>
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
                                <span style="color:#444;">-</span>
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

<!-- ══════════════ MODAL KONFIRMASI BATAL ══════════════ -->
<div class="popup-overlay" id="modal-batal">
    <div class="modal-konfirm">
        <div class="mk-icon">⚠️</div>
        <h3>Konfirmasi Pembatalan</h3>
        <p>Yakin ingin membatalkan booking ini?<br>Status akan berubah menjadi <strong style="color:#e91e8c;">Dibatalkan</strong>.</p>
        <div class="mk-btns">
            <button class="mbtn-no" onclick="tutupModal('modal-batal')">Tidak</button>
            <a href="#" id="batal-url" class="mbtn-yes">Ya, Batalkan</a>
        </div>
    </div>
</div>

<!-- ══════════════ POPUP RESI ══════════════ -->
<div class="popup-overlay" id="popup-resi">
    <div class="resi-box">
        <div class="resi-print-area">
            <div class="resi-header">
                <div class="resi-logo">Padel<span>Play</span></div>
                <div style="color:#666;font-size:12px;">Booking Lapangan Padel #1 di Lampung</div>
                <div class="resi-kode" id="r-kode">PDL-XXXXXXXX</div>
                <div style="color:#888;font-size:12px;">Kode Booking</div>
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

<!-- ══════════════ MODAL LOGOUT ══════════════ -->
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

// Tutup klik di luar
document.querySelectorAll('.popup-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});

// Modal konfirmasi batal
function tampilModalBatal(id) {
    document.getElementById('batal-url').href = 'riwayat.php?batal=' + id;
    bukaModal('modal-batal');
}

// Modal logout
function tampilModalLogout(e) {
    e.preventDefault();
    bukaModal('modal-logout');
}

// Tampil resi dari data booking
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

// Auto-hide alert
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

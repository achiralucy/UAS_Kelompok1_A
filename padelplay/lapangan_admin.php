<?php
session_start();
require_once 'koneksi.php';
cekLoginAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $nama = bersihkan($_POST['nama'] ?? '');
    $lokasi = bersihkan($_POST['lokasi'] ?? '');
    $deskripsi = bersihkan($_POST['deskripsi'] ?? '');
    $harga = (int)($_POST['harga'] ?? 0);
    $status = bersihkan($_POST['status'] ?? 'aktif');

    if (!$nama || !$lokasi || !$harga) {
        $error = 'Nama, lokasi, dan harga wajib diisi.';
    } else {
        $stmt = $conn->prepare("INSERT INTO lapangan (nama, lokasi, deskripsi, harga, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $nama, $lokasi, $deskripsi, $harga, $status);
        if ($stmt->execute()) {
            $success = 'Lapangan berhasil ditambahkan.';
        } else {
            $error = 'Gagal menambahkan lapangan.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
    $id = (int)$_POST['id'];
    $nama = bersihkan($_POST['nama'] ?? '');
    $lokasi = bersihkan($_POST['lokasi'] ?? '');
    $deskripsi = bersihkan($_POST['deskripsi'] ?? '');
    $harga = (int)($_POST['harga'] ?? 0);
    $status = bersihkan($_POST['status'] ?? 'aktif');

    if (!$nama || !$lokasi || !$harga) {
        $error = 'Nama, lokasi, dan harga wajib diisi.';
    } else {
        $stmt = $conn->prepare("UPDATE lapangan SET nama=?, lokasi=?, deskripsi=?, harga=?, status=? WHERE id=?");
        $stmt->bind_param("sssisi", $nama, $lokasi, $deskripsi, $harga, $status, $id);
        if ($stmt->execute()) {
            $success = 'Lapangan berhasil diperbarui.';
        } else {
            $error = 'Gagal memperbarui lapangan.';
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->prepare("DELETE FROM lapangan WHERE id = ?")->execute() || true;
    $stmt = $conn->prepare("DELETE FROM lapangan WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = 'Lapangan berhasil dihapus.';
    } else {
        $error = 'Gagal menghapus. Lapangan mungkin masih punya booking aktif.';
    }
}

$lapanganList = $conn->query("SELECT * FROM lapangan ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan - Admin PadelPlay</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-wrapper">

    <aside class="sidebar">
        <a href="index_admin.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">P</div>
            <span class="sidebar-brand-text">Padel<span>Play</span></span>
        </a>
        <span class="sidebar-badge">Admin Panel</span>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-label">Menu</li>
            <li><a href="index_admin.php"><span class="sidebar-menu-icon">📊</span><span>Dashboard</span></a></li>
            <li><a href="lapangan_admin.php" class="active"><span class="sidebar-menu-icon">🏓</span><span>Lapangan</span></a></li>
            <li><a href="booking_admin.php"><span class="sidebar-menu-icon">📅</span><span>Booking</span></a></li>
            <li><a href="kelola_admin.php"><span class="sidebar-menu-icon">👤</span><span>Pengguna</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php"><span class="sidebar-menu-icon">⎋</span><span>Keluar</span></a>
        </div>
    </aside>

    <div class="admin-main">
        <div class="topbar">
            <div class="topbar-title">Kelola Lapangan</div>
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
                    <h1>Kelola <span>Lapangan</span></h1>
                    <p>Tambah, edit, atau hapus data lapangan padel.</p>
                </div>
                <button class="btn btn-pink" onclick="bukaModal('modal-tambah')">+ Tambah Lapangan</button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <span class="card-header-title">Data Lapangan</span>
                    <span style="color:#666;font-size:13px;"><?= $lapanganList->num_rows ?> lapangan</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Lapangan</th>
                                <th>Lokasi</th>
                                <th>Harga/Jam</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lapanganList->num_rows > 0): $no = 1; ?>
                                <?php while ($l = $lapanganList->fetch_assoc()): ?>
                                <tr>
                                    <td style="color:#555;"><?= $no++ ?></td>
                                    <td>
                                    <strong style="color:#fff;"><?= htmlspecialchars($l['nama'] ?? '') ?></strong><br>
                                    <small style="color:#555;">
                                    <?= htmlspecialchars(substr($l['deskripsi'] ?? '', 0, 50)) ?>...
                                    </small>
                                    </td>
                                    <td><?= htmlspecialchars($l['lokasi'] ?? '') ?></td>
                                    <td style="color:#e91e8c; font-weight:700;"><?= formatRupiah($l['harga'] ?? 0) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline btn-sm" onclick='editLapangan(<?= json_encode($l) ?>)'>✏️ Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="konfirmasiHapus('lapangan_admin.php?hapus=<?= $l['id'] ?>', '<?= htmlspecialchars($l['nama']) ?>')">🗑️ Hapus</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">🏓</div><p>Belum ada data lapangan.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-tambah">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Tambah Lapangan Baru</span>
            <button class="modal-close" onclick="tutupModal('modal-tambah')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="aksi" value="tambah">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Lapangan</label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Court Pro Arena" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Lampung Padel Center - Lt. 2" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" placeholder="Deskripsi singkat lapangan..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Harga per Jam (Rp)</label>
                        <input type="number" name="harga" class="form-control" placeholder="100000" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupModal('modal-tambah')">Batal</button>
                <button type="submit" class="btn btn-pink">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-edit-lapangan">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Edit Lapangan</span>
            <button class="modal-close" onclick="tutupModal('modal-edit-lapangan')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Lapangan</label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" id="edit_lokasi" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" class="form-control"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Harga per Jam (Rp)</label>
                        <input type="number" name="harga" id="edit_harga" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupModal('modal-edit-lapangan')">Batal</button>
                <button type="submit" class="btn btn-pink">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="admin.js"></script>
</body>
</html>
<?php
session_start();
require_once '../../models/koneksi.php';
cekLoginAdmin();

$success = '';
$error   = '';

function uploadFoto($fileInput, $conn) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'nama' => null]; 
    }

    $file     = $_FILES[$fileInput];
    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png'];

    if (!in_array($ekstensi, $allowed)) {
        return ['ok' => false, 'pesan' => 'Format gambar harus JPG, JPEG, atau PNG.'];
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        return ['ok' => false, 'pesan' => 'Ukuran gambar maksimal 3 MB.'];
    }

    $namaFile = 'lapangan_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ekstensi;
    $tujuan   = __DIR__ . '/../../assets/images/' . $namaFile;

    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        return ['ok' => false, 'pesan' => 'Gagal menyimpan gambar ke server.'];
    }

    return ['ok' => true, 'nama' => $namaFile];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    $nama      = bersihkan($_POST['nama']      ?? '');
    $lokasi    = bersihkan($_POST['lokasi']    ?? '');
    $deskripsi = bersihkan($_POST['deskripsi'] ?? '');
    $harga     = (int)($_POST['harga']         ?? 0);
    $status    = bersihkan($_POST['status']    ?? 'aktif');

    if (!$nama || !$lokasi || !$harga) {
        $error = 'Nama, lokasi, dan harga wajib diisi.';
    } else {
        $upload = uploadFoto('foto', $conn);
        if (!$upload['ok']) {
            $error = $upload['pesan'];
        } else {
            $foto  = $upload['nama'];
            $stmt  = $conn->prepare(
                "INSERT INTO lapangan (nama, lokasi, deskripsi, harga, status, foto) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssiss", $nama, $lokasi, $deskripsi, $harga, $status, $foto);
            if ($stmt->execute()) {
                $success = 'Lapangan berhasil ditambahkan.';
            } else {
                $error = 'Gagal menambahkan lapangan: ' . $conn->error;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
    $id        = (int)($_POST['id']            ?? 0);
    $nama      = bersihkan($_POST['nama']      ?? '');
    $lokasi    = bersihkan($_POST['lokasi']    ?? '');
    $deskripsi = bersihkan($_POST['deskripsi'] ?? '');
    $harga     = (int)($_POST['harga']         ?? 0);
    $status    = bersihkan($_POST['status']    ?? 'aktif');
    $fotoLama  = bersihkan($_POST['foto_lama'] ?? '');

    if (!$nama || !$lokasi || !$harga) {
        $error = 'Nama, lokasi, dan harga wajib diisi.';
    } else {
        $upload = uploadFoto('foto', $conn);
        if (!$upload['ok']) {
            $error = $upload['pesan'];
        } else {
            $foto = $upload['nama'] ?? $fotoLama;
            if ($upload['nama'] && $fotoLama) {
                $pathLama = __DIR__ . '/../../assets/images/' . $fotoLama;
                if (file_exists($pathLama) && $fotoLama !== 'Padel.jpeg') {
                    @unlink($pathLama);
                }
            }

            $stmt = $conn->prepare(
                "UPDATE lapangan SET nama=?, lokasi=?, deskripsi=?, harga=?, status=?, foto=? WHERE id=?"
            );
            $stmt->bind_param("sssissi", $nama, $lokasi, $deskripsi, $harga, $status, $foto, $id);
            if ($stmt->execute()) {
                $success = 'Lapangan berhasil diperbarui.';
            } else {
                $error = 'Gagal memperbarui lapangan: ' . $conn->error;
            }
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $resF = $conn->prepare("SELECT foto FROM lapangan WHERE id = ?");
    $resF->bind_param("i", $id);
    $resF->execute();
    $rowF = $resF->get_result()->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM lapangan WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        if (!empty($rowF['foto']) && $rowF['foto'] !== 'Padel.jpeg') {
            $pathFoto = __DIR__ . '/../../assets/images/' . $rowF['foto'];
            if (file_exists($pathFoto)) @unlink($pathFoto);
        }
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
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/additions.css">
    <style>
        .foto-thumb {
            width: 60px; height: 45px; object-fit: cover;
            border-radius: 6px; border: 1px solid #2a2a2a;
        }
        .foto-preview-wrap { margin-top: 10px; display: none; }
        .foto-preview-wrap img {
            max-width: 100%; max-height: 160px;
            border-radius: 8px; border: 1px solid #333;
        }
        .upload-area {
            border: 2px dashed #333; border-radius: 10px;
            padding: 16px; text-align: center;
            cursor: pointer; transition: border-color .2s;
        }
        .upload-area:hover { border-color: #e91e8c; }
        .upload-area input[type=file] { display: none; }
        .upload-area label {
            cursor: pointer; color: #888; font-size: 13px;
        }
        .upload-area label span { color: #e91e8c; }
        .foto-saat-ini {
            width: 80px; height: 60px; object-fit: cover;
            border-radius: 8px; border: 1px solid #2a2a2a;
            margin-bottom: 8px; display: block;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">

    <aside class="sidebar">
        <a href="../../views/admin/dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">P</div>
            <span class="sidebar-brand-text">Padel<span>Play</span></span>
        </a>
        <span class="sidebar-badge">Admin Panel</span>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-label">Menu</li>
            <li><a href="../../views/admin/dashboard.php">Dashboard</a></li>
            <li><a href="lapangan.php" class="active">Lapangan</a></li>
            <li><a href="booking.php">Booking</a></li>
            <li><a href="kelola.php">Pengguna</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="#" onclick="tampilModalLogout(event)">
                <span class="sidebar-menu-icon">⎋</span><span>Keluar</span>
            </a>
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
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
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
                                <th>Foto</th>
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
                                        <?php
                                        $fotoSrc = !empty($l['foto'])
                                            ? '../../assets/images/' . htmlspecialchars($l['foto'])
                                            : '../../assets/images/Padel.jpeg';
                                        ?>
                                        <img src="<?= $fotoSrc ?>" alt="<?= htmlspecialchars($l['nama']) ?>" class="foto-thumb">
                                    </td>
                                    <td>
                                        <strong style="color:#fff;"><?= htmlspecialchars($l['nama'] ?? '') ?></strong><br>
                                        <small style="color:#555;"><?= htmlspecialchars(substr($l['deskripsi'] ?? '', 0, 50)) ?>...</small>
                                    </td>
                                    <td><?= htmlspecialchars($l['lokasi'] ?? '') ?></td>
                                    <td style="color:#e91e8c; font-weight:700;"><?= formatRupiah($l['harga'] ?? 0) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline btn-sm" onclick='editLapangan(<?= json_encode($l) ?>)'>Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="modalHapusLapangan('lapangan.php?hapus=<?= $l['id'] ?>', '<?= htmlspecialchars($l['nama'], ENT_QUOTES) ?>')">Hapus</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">🏓</div><p>Belum ada data lapangan.</p></div></td></tr>
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
        <form method="POST" enctype="multipart/form-data">
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
                <div class="form-group">
                    <label class="form-label">Foto Lapangan <small style="color:#666;">(jpg/jpeg/png, maks 3MB)</small></label>
                    <div class="upload-area" onclick="document.getElementById('foto-tambah').click()">
                        <input type="file" name="foto" id="foto-tambah" accept=".jpg,.jpeg,.png"
                               onchange="previewFoto(this, 'preview-tambah')">
                        <label>📷 Klik untuk pilih gambar (<span>jpg, jpeg, png</span>)</label>
                    </div>
                    <div class="foto-preview-wrap" id="preview-tambah">
                        <img src="" alt="Preview">
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
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="foto_lama" id="edit_foto_lama">
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

                <div class="form-group">
                    <label class="form-label">Foto Saat Ini</label>
                    <img id="edit_foto_preview_lama" src="" alt="Foto lapangan" class="foto-saat-ini">
                    <label class="form-label" style="margin-top:8px;">Ganti Foto <small style="color:#666;">(opsional, jpg/jpeg/png, maks 3MB)</small></label>
                    <div class="upload-area" onclick="document.getElementById('foto-edit').click()">
                        <input type="file" name="foto" id="foto-edit" accept=".jpg,.jpeg,.png"
                               onchange="previewFoto(this, 'preview-edit')">
                        <label>📷 Klik untuk ganti gambar (<span>jpg, jpeg, png</span>)</label>
                    </div>
                    <div class="foto-preview-wrap" id="preview-edit">
                        <img src="" alt="Preview baru">
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

<div class="modal-overlay" id="modal-hapus-lapangan">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <span class="modal-title" style="color:#e91e8c;">⚠️ Konfirmasi Hapus</span>
            <button class="modal-close" onclick="tutupModal('modal-hapus-lapangan')">✕</button>
        </div>
        <div class="modal-body" style="text-align:center; padding:30px 24px;">
            <div style="font-size:48px; margin-bottom:16px;">🗑️</div>
            <p style="color:#ccc; font-size:15px; margin-bottom:6px;">Yakin ingin menghapus lapangan:</p>
            <p style="color:#fff; font-weight:700; font-size:17px; margin-bottom:16px;" id="hapus-nama-lapangan">-</p>
            <p style="color:#888; font-size:13px;">Data yang dihapus tidak bisa dikembalikan.<br>Pastikan lapangan tidak punya booking aktif.</p>
        </div>
        <div class="modal-footer" style="justify-content:center; gap:16px;">
            <button class="btn btn-outline" onclick="tutupModal('modal-hapus-lapangan')">Tidak</button>
            <a href="#" id="hapus-url-lapangan" class="btn btn-danger">Ya, Hapus</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-logout">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <span class="modal-title">Konfirmasi Keluar</span>
            <button class="modal-close" onclick="tutupModal('modal-logout')">✕</button>
        </div>
        <div class="modal-body" style="text-align:center; padding:30px 24px;">
            <div style="font-size:48px; margin-bottom:16px;">⎋</div>
            <p style="color:#ccc; font-size:15px;">Apakah Anda yakin ingin keluar dari panel admin?</p>
        </div>
        <div class="modal-footer" style="justify-content:center; gap:16px;">
            <button class="btn btn-outline" onclick="tutupModal('modal-logout')">Tidak</button>
            <a href="../logout.php" class="btn btn-pink">Ya, Keluar</a>
        </div>
    </div>
</div>

<script>

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

function previewFoto(input, previewId) {
    const wrap = document.getElementById(previewId);
    const img  = wrap.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            wrap.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function editLapangan(data) {
    document.getElementById('edit_id').value        = data.id;
    document.getElementById('edit_nama').value      = data.nama;
    document.getElementById('edit_lokasi').value    = data.lokasi;
    document.getElementById('edit_deskripsi').value = data.deskripsi || '';
    document.getElementById('edit_harga').value     = data.harga;
    document.getElementById('edit_status').value    = data.status;
    document.getElementById('edit_foto_lama').value = data.foto || '';

    const fotoSrc = data.foto
        ? '../../assets/images/' + data.foto
        : '../../assets/images/Padel.jpeg';
    document.getElementById('edit_foto_preview_lama').src = fotoSrc;

    const previewEdit = document.getElementById('preview-edit');
    previewEdit.style.display = 'none';
    previewEdit.querySelector('img').src = '';

    bukaModal('modal-edit-lapangan');
}

function modalHapusLapangan(url, nama) {
    document.getElementById('hapus-nama-lapangan').textContent = nama;
    document.getElementById('hapus-url-lapangan').href          = url;
    bukaModal('modal-hapus-lapangan');
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity .5s';
            alert.style.opacity    = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 4500);
    });
});
</script>
<script src="../../assets/js/admin.js"></script>
</body>
</html>

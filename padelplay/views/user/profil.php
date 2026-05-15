<?php
session_start();
require_once '../../models/koneksi.php';
cekLoginUser();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = bersihkan($_POST['nama'] ?? '');
    $email = bersihkan($_POST['email'] ?? '');
    $passwordLama = $_POST['password_lama'] ?? '';
    $passwordBaru = $_POST['password_baru'] ?? '';

    if (empty($nama) || empty($email)) {

        $error = 'Nama dan email wajib diisi.';

    } else {

        if (!empty($passwordLama) || !empty($passwordBaru)) {

            if (empty($passwordLama) || empty($passwordBaru)) {

                $error = 'Password lama dan password baru wajib diisi.';

            } elseif (!password_verify($passwordLama, $user['password'])) {

                $error = 'Password lama tidak sesuai.';

            } elseif (strlen($passwordBaru) < 6) {

                $error = 'Password baru minimal 6 karakter.';

            } else {

                $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

                $update = $conn->prepare(
                    "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?"
                );

                $update->bind_param(
                    "sssi",
                    $nama,
                    $email,
                    $hash,
                    $user_id
                );
            }

        } else {

            $update = $conn->prepare(
                "UPDATE users SET name = ?, email = ? WHERE id = ?"
            );

            $update->bind_param(
                "ssi",
                $nama,
                $email,
                $user_id
            );
        }

        if (empty($error)) {

            if ($update->execute()) {

                $_SESSION['user_nama'] = $nama;

                $success = 'Profil berhasil diperbarui.';

                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

            } else {

                $error = 'Gagal memperbarui profil.';
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
    <title>Profil Saya - PadelPlay</title>
    <link rel="stylesheet" href="../../assets/css/user.css">
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
        <li><a href="riwayat.php">Riwayat</a></li>
    </ul>

    <div class="navbar-actions">

        <span style="color:#888;font-size:14px;">
            Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?>
        </span>

        <a href="profil.php" class="btn-profil-nav">
            Profil
        </a>

        <a href="../../controllers/logout.php" class="btn-keluar">
            ⎋ Keluar
        </a>

    </div>

</nav>

<div class="container">

    <div class="page-header">

        <h1 class="page-title">
            Kelola <span>Profil</span>
        </h1>

        <p class="page-subtitle">
            Edit data akun kamu.
        </p>

    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="profil-card">

        <div class="profil-header">

            <div class="profil-avatar">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>

            <div>

                <div class="profil-nama">
                    <?= htmlspecialchars($user['name']) ?>
                </div>

                <div class="profil-email">
                    <?= htmlspecialchars($user['email']) ?>
                </div>

            </div>

        </div>

        <form method="POST" autocomplete="off" spellcheck="false">

            <div class="input-group">

                <input
                    type="text"
                    name="nama"
                    class="input-profil"
                    placeholder=" "
                    value="<?= htmlspecialchars($user['name']) ?>"
                    required
                >

                <label>Nama Lengkap</label>

            </div>

            <div class="input-group">

                <input
                    type="email"
                    name="email"
                    class="input-profil"
                    placeholder=" "
                    value="<?= htmlspecialchars($user['email']) ?>"
                    required
                >

                <label>Email</label>

            </div>

            <div class="input-group">

                <input
                    type="password"
                    name="password_lama"
                    class="input-profil"
                    placeholder=" "
                    autocomplete="new-password"
                >

                <label>Masukkan kata sandi lama</label>

            </div>

            <div class="input-group">

                <input
                    type="password"
                    name="password_baru"
                    class="input-profil"
                    placeholder=" "
                    autocomplete="new-password"
                >

                <label>Masukkan kata sandi baru</label>

            </div>

            <button type="submit" class="btn-pink btn-profil">
                Simpan Perubahan
            </button>

        </form>

    </div>

</div>

<footer class="footer">
    <p>© 2026 <span>PadelPlay</span> · Lampung Padel Center</p>
</footer>

</body>
</html>
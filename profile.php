<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$message = "";

$query = $conn->prepare("SELECT * FROM users WHERE email=?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo "User tidak ditemukan";
    exit;
}

$user = $result->fetch_assoc();

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);

    if ($name == "") {
        $message = "Nama tidak boleh kosong";
    } else {
        $update = $conn->prepare("UPDATE users SET name=? WHERE email=?");
        $update->bind_param("ss", $name, $email);

        if ($update->execute()) {
            $_SESSION['nama'] = $name;
            $message = "Profil berhasil diupdate";
        } else {
            $message = "Gagal update profil";
        }
    }
}

if (isset($_POST['update_password'])) {
    $old = trim($_POST['old_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if ($old == "" || $new == "" || $confirm == "") {
        $message = "Semua field harus diisi";
    } elseif ($old != $user['password']) {
        $message = "Password lama salah";
    } elseif ($new != $confirm) {
        $message = "Konfirmasi password tidak cocok";
    } else {
        $update = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $update->bind_param("ss", $new, $email);

        if ($update->execute()) {
            $message = "Password berhasil diganti";
        } else {
            $message = "Gagal ganti password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Profil</title>
<link rel="stylesheet" href="user.css">
</head>
<script src="script.js"></script>
<body>

<div id="sidebar">
    <div id="sidebar-logo">
        <h1>PadelPlay</h1>
        <p>User Dashboard</p>
    </div>

    <div id="sidebar-menu">
        <div class="menu-label">Menu Utama</div>
        <a href="user.html" class="menu-item">Pilih Lapangan</a>
        <a href="riwayat_booking.php" class="menu-item">Riwayat Booking</a>
        <a href="profile.php" class="menu-item active">Profil Saya</a>
    </div>

    <div id="sidebar-footer">
        <a href="logout.php" id="btn-logout">Logout</a>
    </div>
</div>

<div id="main">
    <div id="navbar">
        <div style="display:flex;align-items:center;gap:15px;">
            <button id="menu">☰</button>
            <div style="font-weight:bold;color:#333;">Profil</div>
        </div>
        <div style="background-color:#ffe4f5;color:#cc2e97;padding:6px 14px;border-radius:20px;font-weight:bold;font-size:13px;">Member</div>
    </div>

    <div id="content">

        <h2>Profil</h2>

        <?php if ($message != ""): ?>
            <p style="margin-bottom:15px;"><?= $message ?></p>
        <?php endif; ?>

        <div class="content">

            <div class="sidebar">
                <p id="menuProfile" class="active">Data Diri</p>
                <p id="menuPassword">Ubah Kata Sandi</p>
            </div>

            <div id="profileForm" class="form-section">
                <form method="POST">
                    <div>
                        <label>Nama Lengkap *</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div>
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>

                    <button class="btn" name="update_profile">Simpan Perubahan</button>
                </form>
            </div>

            <div id="passwordForm" class="form-section" style="display:none;">
                <form method="POST">
                    <div>
                        <label>Password Lama</label>
                        <input type="password" name="old_password" required>
                    </div>

                    <div>
                        <label>Password Baru</label>
                        <input type="password" name="new_password" required>
                    </div>

                    <div>
                        <label>Konfirmasi Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>

                    <button class="btn" name="update_password">Simpan Perubahan</button>
                </form>
            </div>

        </div>

    </div>
</div>
</body>
</html>
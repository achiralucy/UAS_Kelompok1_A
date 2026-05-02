<?php
include 'koneksi.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name     = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validasi input
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Gunakan prepared statement untuk keamanan
        $check_query = $conn->prepare("SELECT * FROM users WHERE email=?");
        $check_query->bind_param("s", $email);
        $check_query->execute();
        $result = $check_query->get_result();

        if ($result->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            // Insert data
            $insert_query = $conn->prepare("INSERT INTO users(name, email, password, role) VALUES(?, ?, ?, ?)");
            $insert_query->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($insert_query->execute()) {
                $success = 'Registrasi berhasil! Silakan login.';
                // Reset form
                $name = '';
                $email = '';
                $password = '';
            } else {
                $error = 'Registrasi gagal! Coba lagi.';
            }

            $insert_query->close();
        }

        $check_query->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PadelPlay - Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class = "card">
        <h2>PadelPlay</h2>
        <?php 
        if ($error != '') {
            echo '<div style="color: red; margin-bottom: 15px;">' . htmlspecialchars($error) . '</div>';
        }
        if ($success != '') {
            echo '<div style="color: green; margin-bottom: 15px;">' . htmlspecialchars($success) . '</div>';
        }
        ?>
        <form action="register.php" method="post">
            <label for="name">Nama</label>
            <input type="text" id="name" name="name" placeholder="Nama Lengkap" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="nama@email.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="password" required>
            <button type="submit">Daftar</button>
        </form>
    <p class="footer">Sudah punya akun? <a href="login.php">Masuk</a></p>
    </div>
</body>
</html>
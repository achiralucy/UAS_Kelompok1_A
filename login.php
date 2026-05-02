<?php
session_start();
include 'koneksi.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Gunakan prepared statement untuk keamanan
    $query = $conn->prepare("SELECT * FROM users WHERE email=?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        // cek password
        if (password_verify($password, $user['password'])) {

            $_SESSION['nama'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'user';

            // Redirect ke halaman yang sesuai berdasarkan peran
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();

        } else {
            $error = 'Password salah!';
        }

    } else {
        $error = 'Email tidak ditemukan!';
    }

    $query->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PadelPlay - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class = "card">
        <h2>PadelPlay</h2>
        <?php 
        if ($error != '') {
            echo '<div style="color: red; margin-bottom: 15px;">' . htmlspecialchars($error) . '</div>';
        }
        ?>
        <form action="login.php" method="post">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="nama@email.com" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="password" required>
            <button type="submit">Masuk</button>
        </form>
    <p class="footer">Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>
</body>
</html>
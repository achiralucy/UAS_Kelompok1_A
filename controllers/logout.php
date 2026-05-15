<?php
/**
 * controllers/logout.php
 * Hancurkan sesi dan arahkan ke halaman awal user
 */
session_start();
session_unset();
session_destroy();
// Setelah logout → kembali ke halaman utama user
header("Location: ../views/user/index.php");
exit;

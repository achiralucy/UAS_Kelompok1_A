<?php
session_start();
session_destroy();
header("Location: ../views/login.php");
exit();
?>
session_unset();
session_destroy();

header("Location: ../views/user/index.php");
exit;

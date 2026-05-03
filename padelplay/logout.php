<?php
session_start();
session_destroy();
header("Location: /padelplay/login.php");
exit();
?>
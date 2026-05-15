<?php
session_start();
session_destroy();
header('Location: /hotel/index.php');
exit;
?>

<?php
require_once 'connect.php';

session_start();
session_destroy();
header("Location: " . SITE_URL . "index.php");
exit;
?>
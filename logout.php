<?php
require_once 'assets/templates/includes/config.php';
session_start();
session_destroy();
header("Location: index.php");
exit();
?>
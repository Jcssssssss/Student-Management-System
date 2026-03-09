<?php
// ============================================================
// Logout — Destroy session and redirect to login
// ============================================================
require_once 'config.php';

$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
?>

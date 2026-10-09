<?php
require_once __DIR__ . '/../includes/config.php';

// پاک کردن سشن
$_SESSION = array();
session_destroy();

// پاک کردن کوکی سشن
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

header('Location: index.php');
exit;
?>
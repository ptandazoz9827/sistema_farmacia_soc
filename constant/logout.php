<?php
require_once __DIR__ . '/session.php';
$_SESSION = [];
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/farmagest/', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: /farmagest/login.php');
exit;

<?php
// Guardar como constant/check.php (hacer copia de seguridad del original antes de reemplazarlo)
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/security.php';

if (empty($_SESSION['userId'])) {
    header('Location: /farmagest/login.php');
    exit;
}

$timeout = 15 * 60; // 15 minutos
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    $uid = (int)$_SESSION['userId'];
    security_log('SESSION_EXPIRED', 'WARNING', 'Sesion cerrada por inactividad.', $uid, null);
    session_unset();
    session_destroy();
    header('Location: /farmagest/login.php?expired=1');
    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();

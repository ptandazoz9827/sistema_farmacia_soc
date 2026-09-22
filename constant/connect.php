<?php
$username = 'farmagest';
$dbname = 'farmacia';
$store_url = '/farmagest/';
$configFile = '/etc/farmagest/database.php';
if (!is_readable($configFile)) {
    http_response_code(503);
    exit('FarmaGest: configuración de base de datos pendiente.');
}
$config = require $configFile;
$localhost = $config['host'] ?? 'localhost';
$port = (int) ($config['port'] ?? 3306);
$password = $config['password'];
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connect = new mysqli($localhost, $username, $password, $dbname, $port);
    $connect->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('FarmaGest database connection failed: ' . $e->getCode());
    http_response_code(503);
    exit('FarmaGest: base de datos no disponible.');
}

<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../constant/order_service.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
try {
    $id = order_save($connect, $_POST, null);
    header('Location: ../Order.php');
    exit;
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
} catch (Throwable $e) {
    error_log('FarmaGest order: ' . $e->getMessage());
    http_response_code(500);
    echo 'No se pudo guardar la factura.';
}

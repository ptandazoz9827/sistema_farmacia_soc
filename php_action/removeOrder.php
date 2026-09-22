<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../constant/order_service.php';
$id = (int)($_GET['id'] ?? $_POST['orderId'] ?? 0);
$connect->begin_transaction();
try {
    $row = order_execute($connect, 'SELECT id FROM orders WHERE id=? AND delete_status=0 FOR UPDATE', [$id])->get_result()->fetch_assoc();
    if ($row) {
        order_restore_stock($connect, $id);
        order_execute($connect, 'UPDATE orders SET delete_status=1 WHERE id=?', [$id]);
    }
    $connect->commit();
    header('Location: ../Order.php');
} catch (Throwable $e) {
    $connect->rollback();
    http_response_code(500);
    echo 'No se pudo anular la factura.';
}

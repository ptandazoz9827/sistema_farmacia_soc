<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../constant/product_service.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
try {
    $id = (int)($_GET['id'] ?? 0);
    $row = order_execute($connect, 'SELECT product_id FROM product WHERE product_id=? AND status=1', [$id])->get_result()->fetch_assoc();
    if (!$row) throw new InvalidArgumentException('Medicamento no encontrado.');
    $values = product_values($_POST, true);
    order_execute($connect, 'UPDATE product SET product_name=?,brand_id=?,categories_id=?,quantity=?,rate=?,mrp=?,bno=?,expdate=?,active=? WHERE product_id=?', [...$values, $id]);
    security_log('PRODUCT_UPDATED', 'WARNING', 'Medicamento ID: ' . $id, (int)$_SESSION['userId'], $_SESSION['email'] ?? null);
    header('Location: ../product.php');
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
} catch (Throwable $e) {
    error_log('FarmaGest product: ' . $e->getMessage());
    http_response_code(500);
    echo 'No se pudo guardar el medicamento.';
}

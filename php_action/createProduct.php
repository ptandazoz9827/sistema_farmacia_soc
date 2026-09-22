<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../constant/product_service.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
try {
    $values = product_values($_POST, false);
    $image = product_upload();
    order_execute($connect, 'INSERT INTO product(product_name,brand_id,categories_id,quantity,rate,mrp,bno,expdate,active,product_image) VALUES(?,?,?,?,?,?,?,?,?,?)', [...$values, $image]);
    $id = $connect->insert_id;
    security_log('PRODUCT_CREATED', 'INFO', 'Medicamento ID: ' . $id, (int)$_SESSION['userId'], $_SESSION['email'] ?? null);
    header('Location: ../product.php');
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
} catch (Throwable $e) {
    error_log('FarmaGest product: ' . $e->getMessage());
    http_response_code(500);
    echo 'No se pudo guardar el medicamento.';
}

<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../constant/order_service.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = order_execute($connect, 'UPDATE product SET active=2,status=2 WHERE product_id=? AND status=1', [$id]);
if ($stmt->affected_rows) security_log('PRODUCT_REMOVED', 'WARNING', 'Medicamento ID: ' . $id, (int)$_SESSION['userId'], $_SESSION['email'] ?? null);
header('Location: ../product.php');

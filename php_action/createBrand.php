<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../constant/order_service.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
order_execute($connect, 'INSERT INTO brands(brand_name,brand_active) VALUES(?,?)', [trim((string)($_POST['brandName'] ?? '')), (int)($_POST['brandStatus'] ?? 1)]);
header('Location: ../brand.php');

<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../constant/order_service.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
order_execute($connect, 'UPDATE brands SET brand_name=?,brand_active=? WHERE brand_id=?', [trim((string)($_POST['brandName'] ?? '')), (int)($_POST['brandStatus'] ?? 1), (int)($_GET['id'] ?? 0)]);
header('Location: ../brand.php');

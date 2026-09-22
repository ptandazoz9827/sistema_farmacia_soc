<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../constant/order_service.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
order_execute($connect, 'INSERT INTO categories(categories_name,categories_active) VALUES(?,?)', [trim((string)($_POST['categoriesName'] ?? '')), (int)($_POST['categoriesStatus'] ?? 1)]);
header('Location: ../categories.php');

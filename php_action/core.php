<?php
require_once __DIR__ . '/../constant/check.php';

$adminActions = ['createUser.php','editUser.php','removeUser.php','createBrand.php','editBrand.php','removeBrand.php','createBrandImport.php','createCategories.php','editCategories.php','removeCategories.php','createProduct.php','editProduct.php','removeProduct.php','editProductImage.php'];
if (in_array(basename($_SERVER['SCRIPT_FILENAME'] ?? ''), $adminActions, true) && (int)$_SESSION['userId'] !== 1) {
    http_response_code(403);
    exit('Acceso restringido al administrador.');
}

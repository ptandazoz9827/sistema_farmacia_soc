<?php
require_once __DIR__ . '/order_service.php';
function product_values(array $input, bool $edit): array {
    $name = trim((string)($input[$edit ? 'editProductName' : 'productName'] ?? ''));
    $qty = filter_var($input[$edit ? 'editQuantity' : 'quantity'] ?? null, FILTER_VALIDATE_INT);
    $rate = filter_var($input[$edit ? 'editRate' : 'rate'] ?? null, FILTER_VALIDATE_FLOAT);
    $mrp = filter_var($input['mrp'] ?? 0, FILTER_VALIDATE_FLOAT);
    $date = (string)($input['expdate'] ?? '');
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($name === '' || $qty === false || $qty < 0 || $rate === false || $rate < 0 || $mrp === false || $mrp < 0 || !$parsed || $parsed->format('Y-m-d') !== $date) throw new InvalidArgumentException('Revise nombre, cantidad, precio y vencimiento.');
    return [$name, (int)($input[$edit ? 'editBrandName' : 'brandName'] ?? 0), (int)($input[$edit ? 'editCategoryName' : 'categoryName'] ?? 0), $qty, $rate, $mrp, $input['bno'] ?? '', $date, (int)($input[$edit ? 'editProductStatus' : 'productStatus'] ?? 1)];
}
function product_upload(): string {
    $file = $_FILES['Medicine'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) throw new InvalidArgumentException('Imagen inválida o mayor de 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? null;
    if (!$ext) throw new InvalidArgumentException('Use una imagen JPG, PNG o WebP.');
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../assets/myimages/' . $name)) throw new RuntimeException('No se pudo guardar la imagen.');
    return $name;
}

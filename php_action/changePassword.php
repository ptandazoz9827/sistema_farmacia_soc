<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');
$valid = ['success' => false, 'messages' => 'Solicitud inválida'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_SESSION['userId'];
    $current = (string)($_POST['password'] ?? '');
    $new = (string)($_POST['npassword'] ?? '');
    $confirm = (string)($_POST['cpassword'] ?? '');
    $stmt = $connect->prepare('SELECT password FROM users WHERE user_id=?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || !security_verify_password_and_migrate($userId, $current, $user['password'])) {
        $valid['messages'] = 'Contraseña actual incorrecta';
    } elseif ($new === '' || $new !== $confirm) {
        $valid['messages'] = 'La nueva contraseña y su confirmación deben coincidir';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $connect->prepare('UPDATE users SET password=? WHERE user_id=?');
        $stmt->bind_param('si', $hash, $userId);
        $stmt->execute();
        security_log('PASSWORD_CHANGED', 'INFO', 'Contraseña actualizada.', $userId, $_SESSION['email'] ?? null);
        session_regenerate_id(true);
        $valid = ['success' => true, 'messages' => 'Contraseña actualizada'];
    }
}
echo json_encode($valid);

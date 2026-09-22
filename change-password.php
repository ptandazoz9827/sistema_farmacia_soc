<?php
require_once __DIR__ . '/constant/check.php';
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) { http_response_code(403); exit('Solicitud inválida.'); }
    $stmt = $connect->prepare('SELECT password FROM users WHERE user_id=?');
    $stmt->bind_param('i', $_SESSION['userId']);
    $stmt->execute();
    $stored = $stmt->get_result()->fetch_assoc()['password'];
    $new = (string)($_POST['new_password'] ?? '');
    if (strlen($new) < 10 || $new !== ($_POST['confirm_password'] ?? '')) {
        $message = 'Use al menos 10 caracteres y confirme la misma contraseña.';
    } elseif (!security_verify_password_and_migrate((int)$_SESSION['userId'], (string)($_POST['current_password'] ?? ''), $stored)) {
        $message = 'La contraseña actual es incorrecta.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $connect->prepare('UPDATE users SET password=? WHERE user_id=?');
        $stmt->bind_param('si', $hash, $_SESSION['userId']);
        $stmt->execute();
        session_regenerate_id(true);
        security_log('PASSWORD_CHANGED', 'INFO', 'Contraseña actualizada.', (int)$_SESSION['userId'], $_SESSION['email'] ?? null);
        $message = 'Contraseña actualizada.';
    }
}
include './constant/layout/head.php';
include './constant/layout/header.php';
include './constant/layout/sidebar.php';
?>
<div class="page-wrapper"><div class="container-fluid"><div class="card"><div class="card-body">
<h3>Cambiar contraseña</h3><p role="status"><?= htmlspecialchars($message) ?></p>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<label>Contraseña actual</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required>
<label>Nueva contraseña</label><input class="form-control" type="password" name="new_password" autocomplete="new-password" minlength="10" required>
<label>Confirmar contraseña</label><input class="form-control" type="password" name="confirm_password" autocomplete="new-password" minlength="10" required>
<button class="btn btn-primary mt-3">Guardar</button>
</form></div></div></div></div>
<?php include './constant/layout/footer.php'; ?>

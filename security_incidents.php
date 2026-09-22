<?php
require_once __DIR__ . '/constant/check.php';
if ((int)$_SESSION['userId'] !== 1) { http_response_code(403); exit('Acceso restringido al administrador.'); }
?>
<?php
require_once __DIR__ . '/constant/check.php';
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Solicitud inválida');
}
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/check.php');
require_once './constant/connect.php';
require_once './constant/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['incident_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['OPEN','INVESTIGATING','RESOLVED'];
    if ($id > 0 && in_array($status, $allowed, true)) {
        if ($status === 'RESOLVED') {
            $stmt = $connect->prepare("UPDATE security_incidents SET status=?, resolved_at=NOW() WHERE id=?");
        } else {
            $stmt = $connect->prepare("UPDATE security_incidents SET status=?, resolved_at=NULL WHERE id=?");
        }
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();
        security_log('INCIDENT_STATUS_CHANGED', 'INFO', "Incidente {$id} actualizado a {$status}.", (int)$_SESSION['userId'], null);
    }
}

$incidents = $connect->query("SELECT * FROM security_incidents ORDER BY id DESC");
?>
<div class="page-wrapper"><div class="container-fluid">
<div class="row page-titles"><div class="col-md-12"><h3 class="text-primary">Incidentes de Seguridad</h3></div></div>
<div class="card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Apertura</th><th>Tipo</th><th>Severidad</th><th>Email</th><th>IP</th><th>Estado</th><th>Accion</th></tr></thead><tbody>
<?php while($i=$incidents->fetch_assoc()): ?>
<tr><td><?= (int)$i['id'] ?></td><td><?= htmlspecialchars($i['opened_at']) ?></td><td><?= htmlspecialchars($i['incident_type']) ?></td><td><?= htmlspecialchars($i['severity']) ?></td><td><?= htmlspecialchars($i['related_email'] ?? '') ?></td><td><?= htmlspecialchars($i['ip_address'] ?? '') ?></td><td><?= htmlspecialchars($i['status']) ?></td>
<td><form method="post" class="form-inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="incident_id" value="<?= (int)$i['id'] ?>"><select name="status" class="form-control mr-2"><option>OPEN</option><option>INVESTIGATING</option><option>RESOLVED</option></select><button class="btn btn-primary" type="submit">Actualizar</button></form></td></tr>
<?php endwhile; ?>
</tbody></table></div></div></div></div></div>
<?php include('./constant/layout/footer.php'); ?>

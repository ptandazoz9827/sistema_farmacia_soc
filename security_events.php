<?php
require_once __DIR__ . '/constant/check.php';
if ((int)$_SESSION['userId'] !== 1) { http_response_code(403); exit('Acceso restringido al administrador.'); }
?>
<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/check.php');
require_once './constant/connect.php';
$events = $connect->query("SELECT * FROM security_events ORDER BY id DESC LIMIT 200");
?>
<div class="page-wrapper"><div class="container-fluid">
<div class="row page-titles"><div class="col-md-12"><h3 class="text-primary">Eventos de Seguridad</h3></div></div>
<div class="card"><div class="card-body"><div class="table-responsive">
<table class="table table-striped table-bordered"><thead><tr><th>ID</th><th>Fecha</th><th>Email</th><th>Evento</th><th>Severidad</th><th>IP</th><th>Descripcion</th></tr></thead><tbody>
<?php while($e=$events->fetch_assoc()): ?>
<tr><td><?= (int)$e['id'] ?></td><td><?= htmlspecialchars($e['created_at']) ?></td><td><?= htmlspecialchars($e['email'] ?? '') ?></td><td><?= htmlspecialchars($e['event_type']) ?></td><td><?= htmlspecialchars($e['severity']) ?></td><td><?= htmlspecialchars($e['ip_address'] ?? '') ?></td><td><?= htmlspecialchars($e['description'] ?? '') ?></td></tr>
<?php endwhile; ?>
</tbody></table></div></div></div></div></div>
<?php include('./constant/layout/footer.php'); ?>

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

function scalar_count($connect, $sql) {
    $r = $connect->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_row();
    return (int)($row[0] ?? 0);
}

$success = scalar_count($connect, "SELECT COUNT(*) FROM security_events WHERE event_type='LOGIN_SUCCESS' AND created_at >= CURDATE()");
$failed = scalar_count($connect, "SELECT COUNT(*) FROM security_events WHERE event_type='LOGIN_FAILED' AND created_at >= CURDATE()");
$critical = scalar_count($connect, "SELECT COUNT(*) FROM security_events WHERE severity='CRITICAL' AND created_at >= CURDATE()");
$openIncidents = scalar_count($connect, "SELECT COUNT(*) FROM security_incidents WHERE status <> 'RESOLVED'");
$events = $connect->query("SELECT * FROM security_events ORDER BY id DESC LIMIT 15");
?>
<div class="page-wrapper">
  <div class="container-fluid">
    <div class="row page-titles"><div class="col-md-12"><h3 class="text-primary">FarmaGest Secure - Dashboard</h3></div></div>
    <div class="row">
      <div class="col-md-3"><div class="card p-3"><h5>Accesos exitosos hoy</h5><h2><?= $success ?></h2></div></div>
      <div class="col-md-3"><div class="card p-3"><h5>Accesos fallidos hoy</h5><h2><?= $failed ?></h2></div></div>
      <div class="col-md-3"><div class="card p-3"><h5>Eventos criticos hoy</h5><h2><?= $critical ?></h2></div></div>
      <div class="col-md-3"><div class="card p-3"><h5>Incidentes abiertos</h5><h2><?= $openIncidents ?></h2></div></div>
    </div>
    <div class="card"><div class="card-body">
      <h4>Eventos recientes</h4>
      <div class="table-responsive"><table class="table table-striped table-bordered">
        <thead><tr><th>Fecha</th><th>Email</th><th>Evento</th><th>Severidad</th><th>IP</th><th>Descripcion</th></tr></thead>
        <tbody>
        <?php while($e = $events->fetch_assoc()): ?>
          <tr><td><?= htmlspecialchars($e['created_at']) ?></td><td><?= htmlspecialchars($e['email'] ?? '') ?></td><td><?= htmlspecialchars($e['event_type']) ?></td><td><?= htmlspecialchars($e['severity']) ?></td><td><?= htmlspecialchars($e['ip_address'] ?? '') ?></td><td><?= htmlspecialchars($e['description'] ?? '') ?></td></tr>
        <?php endwhile; ?>
        </tbody>
      </table></div>
    </div></div>
  </div>
</div>
<?php include('./constant/layout/footer.php'); ?>

<?php
require_once __DIR__ . '/constant/check.php';
if ((int)$_SESSION['userId'] !== 1) { http_response_code(403); exit('Acceso restringido al administrador.'); }
?>
<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/check.php');
?>
<div class="page-wrapper"><div class="container-fluid">
<div class="row page-titles"><div class="col-md-12"><h3 class="text-primary">Inteligencia OSINT Tecnica</h3></div></div>
<div class="card"><div class="card-body">
<p>Este modulo documenta fuentes publicas para revisar vulnerabilidades de la superficie tecnologica de FarmaGest. No ejecuta escaneos ni ataques.</p>
<table class="table table-bordered"><thead><tr><th>Tecnologia</th><th>Dato a verificar</th><th>Fuente publica</th><th>Uso defensivo</th></tr></thead><tbody>
<tr><td>PHP</td><td>Version instalada: <?= htmlspecialchars(PHP_VERSION) ?></td><td><a href="https://nvd.nist.gov/" target="_blank">NVD</a></td><td>Revisar CVE asociados a la version.</td></tr>
<tr><td>Apache HTTP Server</td><td>Version del servidor</td><td><a href="https://nvd.nist.gov/" target="_blank">NVD</a></td><td>Priorizar actualizaciones de seguridad.</td></tr>
<tr><td>MySQL</td><td>Version del motor</td><td><a href="https://nvd.nist.gov/" target="_blank">NVD</a></td><td>Contrastar CVE con la version instalada.</td></tr>
<tr><td>AdminLTE / Bootstrap / jQuery</td><td>Versiones de dependencias</td><td>Repositorios y avisos oficiales</td><td>Identificar componentes desactualizados.</td></tr>
</tbody></table>
</div></div></div></div>
<?php include('./constant/layout/footer.php'); ?>

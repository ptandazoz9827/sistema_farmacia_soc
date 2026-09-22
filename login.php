<?php
// Fragmento de referencia para reemplazar la logica insegura de login.php.
// Mantener el HTML existente del formulario y adaptar el mensaje $loginError.

require_once __DIR__ . '/constant/session.php';
require_once __DIR__ . '/constant/connect.php';
require_once __DIR__ . '/constant/security.php';

$loginError = isset($_GET['expired']) ? 'Sesión cerrada por inactividad.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $loginError = 'Email y contrasena son obligatorios.';
    } else {
        $failuresBefore = security_failed_count($email, 15);
        if ($failuresBefore >= 5 || security_is_locked($email)) {
            security_log('LOGIN_BLOCKED', 'CRITICAL', 'Acceso temporalmente bloqueado por multiples intentos fallidos.', null, $email);
            $loginError = 'Acceso temporalmente bloqueado. Intente nuevamente en 15 minutos.';
        } else {
            $stmt = $connect->prepare("SELECT user_id, name, email, password FROM users WHERE email=? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $valid = false;
            if ($user) {
                $valid = security_verify_password_and_migrate((int)$user['user_id'], $password, $user['password']);
            }

            if ($valid) {
                security_record_attempt($email, true);
                security_clear_failed_attempts($email);
                session_regenerate_id(true);
                $_SESSION['userId'] = (int)$user['user_id'];
                $_SESSION['userName'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['LAST_ACTIVITY'] = time();
                security_log('LOGIN_SUCCESS', 'INFO', 'Inicio de sesion correcto.', (int)$user['user_id'], $email);
                header('Location: dashboard.php');
                exit;
            }

            security_record_attempt($email, false);
            $failures = security_failed_count($email, 15);
            $severity = $failures >= 5 ? 'CRITICAL' : ($failures >= 3 ? 'WARNING' : 'INFO');
            security_log('LOGIN_FAILED', $severity, "Intento fallido {$failures}/5.", null, $email);

            if ($failures >= 5) {
                security_log('BRUTE_FORCE_DETECTED', 'CRITICAL', 'Umbral de cinco intentos fallidos alcanzado.', null, $email);
                security_open_bruteforce_incident($email, $failures);
                $loginError = 'Demasiados intentos fallidos. Acceso bloqueado durante 15 minutos.';
            } else {
                $loginError = "Credenciales incorrectas. Intento {$failures}/5.";
            }
        }
    }
}
?>

<?php include __DIR__ . '/constant/layout/head.php'; ?>
<link rel="stylesheet" href="assets/css/popup_style.css">
<style>
  .footer1 {
    position: fixed;
    bottom: 0;
    width: 100%;
    color: #5c4ac7;
    text-align: center;
  }
</style>
<div id="main-wrapper">
  <div class="unix-login">

    <div class="container-fluid" style="background-image: url('assets/uploadImage/Logo/banner3.jpg');
 background-color: #ffffff;background-size:cover">
      <div class="row ">
        <div class="col-md-4">
          <div class="login-content ">
            <div class="login-form">
              <center><img src="./assets/uploadImage/Logo/logo.png" style="width: 300px;"></center><br>
              <p role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p>
              <form action="login.php" method="post" id="loginForm" class="row">
                <div class="form-group col-md-12">
                  <label lass="col-sm-3 control-label">Correo</label>
                  <input type="text" name="email" id="email" class="form-control" placeholder="correo" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Invalid email address" required="">

                </div>
                <div class="form-group col-md-12">
                  <label>Contraseña</label>
                  <input type="password" id="password" name="password" class="form-control" placeholder="contraseña" required="">
                </div>



                <div class="col-md-12">
                  <button style="background-color: #102b49; border-radius: 50px;" type="submit" name="login" class=" f-w-600 text-white btn  btn-flat m-b-30 m-t-30">Ingresar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>




<script src="./assets/js/lib/jquery/jquery.min.js"></script>

<script src="./assets/js/lib/bootstrap/js/popper.min.js"></script>
<script src="./assets/js/lib/bootstrap/js/bootstrap.min.js"></script>

<script src="./assets/js/jquery.slimscroll.js"></script>

<script src="./assets/js/sidebarmenu.js"></script>

<script src="./assets/js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>

<script src="./assets/js/custom.min.js"></script>
<footer class="bg-primary text-white text-center text-lg-start fixed-bottom">
  <!-- Grid container -->

  <!-- Grid container -->

  <!-- Copyright -->
  <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2)">
    Para más desarrollos accede a
    <a class="text-white" href="https://www.configuroweb.com/">ConfiguroWeb</a>
  </div>
  <!-- Copyright -->
</footer>
</div>
</body>

</html>
<?php
// admin/configuracion.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

if (isset($_POST['guardar'])) {
    foreach (['nombre_sitio', 'email_contacto', 'texto_pie'] as $clave) {
        $valor = mysqli_real_escape_string($link, trim($_POST[$clave] ?? ''));
        mysqli_query($link, "UPDATE config SET valor='$valor' WHERE clave='$clave'");
    }
    alerta('Configuración', 'Guardada correctamente', 2);
    header('Location: configuracion.php');
    exit;
}

$res = mysqli_query($link, "SELECT clave, valor FROM config");
$config = [];
while ($row = mysqli_fetch_assoc($res)) {
    $config[$row['clave']] = $row['valor'];
}

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <form method="post">
    <div class="card">
      <div class="card-header pb-0"><h4 class="card-title">Configuración del sitio</h4></div>
      <div class="card-body table-responsive">
        <table class="table text-md-nowrap">
          <tr><td class="iz"><b>Nombre del sitio:</b></td><td><input class="form-control" name="nombre_sitio" value="<?= htmlspecialchars($config['nombre_sitio'] ?? '') ?>"></td></tr>
          <tr><td class="iz"><b>Email de contacto:</b></td><td><input class="form-control" type="email" name="email_contacto" value="<?= htmlspecialchars($config['email_contacto'] ?? '') ?>"></td></tr>
          <tr><td class="iz"><b>Texto del pie de la landing:</b></td><td><input class="form-control" name="texto_pie" value="<?= htmlspecialchars($config['texto_pie'] ?? '') ?>"></td></tr>
          <tr><td colspan="2"><input type="submit" name="guardar" value="Guardar" class="btn btn-primary"></td></tr>
        </table>
      </div>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>

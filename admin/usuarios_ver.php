<?php
// admin/usuarios_ver.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$ide = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if (isset($_POST['guardar'])) {
    $usu_usuario = mysqli_real_escape_string($link, trim($_POST['usu_usuario']));
    $clave_nueva = $_POST['usu_clave'] ?? '';

    if ($usu_usuario === '') {
        $error = 'El nombre de usuario es obligatorio.';
    } elseif ($ide === 0 && $clave_nueva === '') {
        $error = 'La clave es obligatoria para un usuario nuevo.';
    } else {
        if ($ide > 0) {
            $sql = "UPDATE usuario SET usu_usuario='$usu_usuario'";
            if ($clave_nueva !== '') {
                $sql .= ", usu_clave='" . md5($clave_nueva) . "'";
            }
            $sql .= " WHERE id=$ide";
            mysqli_query($link, $sql);
            alerta('Usuario', 'Actualizado correctamente', 2);
        } else {
            mysqli_query($link, "INSERT INTO usuario (usu_usuario, usu_clave, usu_derechos)
                VALUES ('$usu_usuario', '" . md5($clave_nueva) . "', '1')");
            $ide = mysqli_insert_id($link);
            alerta('Usuario', 'Creado correctamente', 2);
        }
        header("Location: usuarios_ver.php?id=$ide");
        exit;
    }
}

if (isset($_POST['eliminar'])) {
    $total = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS n FROM usuario"))['n'];
    if ((int)$total <= 1) {
        alerta('Usuario', 'No se puede eliminar el único usuario administrador', 1);
    } elseif ($ide === (int)$_SESSION['id']) {
        alerta('Usuario', 'No puedes eliminar tu propio usuario mientras tienes sesión abierta', 1);
    } else {
        mysqli_query($link, "DELETE FROM usuario WHERE id=$ide");
        alerta('Usuario', 'Eliminado correctamente', 2);
        header('Location: usuarios_index.php');
        exit;
    }
    header("Location: usuarios_ver.php?id=$ide");
    exit;
}

$u = ['usu_usuario' => ''];
if ($ide > 0) {
    $res = mysqli_query($link, "SELECT * FROM usuario WHERE id=$ide");
    $u = mysqli_fetch_assoc($res);
}

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <a href="usuarios_index.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-chevron-circle-left"></i> Regresar</a>
</div>
<?php if ($error): ?>
  <div class="col-xl-12"><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>
<div class="col-xl-12">
  <form method="post">
    <div class="card">
      <div class="card-header pb-0"><h4 class="card-title"><?= $ide > 0 ? 'Editar' : 'Nuevo' ?> usuario</h4></div>
      <div class="card-body table-responsive">
        <table class="table text-md-nowrap">
          <tr><td class="iz"><b>Usuario:</b></td><td><input class="form-control" name="usu_usuario" value="<?= htmlspecialchars($u['usu_usuario']) ?>" required></td></tr>
          <tr><td class="iz"><b>Clave:</b></td><td>
            <input class="form-control" type="password" name="usu_clave" placeholder="<?= $ide > 0 ? 'Dejar en blanco para no cambiarla' : '' ?>">
          </td></tr>
          <tr><td colspan="2"><input type="submit" name="guardar" value="Guardar" class="btn btn-primary"></td></tr>
        </table>
      </div>
    </div>
  </form>
</div>
<?php if ($ide > 0): ?>
<div class="col-xl-12">
  <div class="card">
    <div class="card-header pb-3">
      <div class="d-flex justify-content-between">
        <form method="post" onsubmit="return confirm('¿Seguro que quieres eliminar este usuario?');">
          <input type="hidden" name="ide" value="<?= $ide ?>">
          <button type="submit" name="eliminar" value="1" class="btn btn-outline-primary">
            <i class="fa fa-trash"></i> Eliminar
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>

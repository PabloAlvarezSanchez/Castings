<?php
// admin/usuarios_index.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$res = mysqli_query($link, "SELECT id, usu_usuario, usu_reintento FROM usuario ORDER BY usu_usuario");

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <div class="card">
    <div class="card-header pb-0 d-flex justify-content-between">
      <h4 class="card-title">Usuarios administradores</h4>
      <a href="usuarios_ver.php" class="btn btn-outline-primary"><i class="fa fa-plus"></i> Nuevo usuario</a>
    </div>
    <div class="card-body table-responsive">
      <table class="table text-md-nowrap">
        <thead><tr><th>Usuario</th><th>Intentos fallidos</th><th></th></tr></thead>
        <tbody>
        <?php while ($u = mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?= htmlspecialchars($u['usu_usuario']) ?></td>
            <td><?= (int)$u['usu_reintento'] ?></td>
            <td><a href="usuarios_ver.php?id=<?= (int)$u['id'] ?>">Editar</a></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>

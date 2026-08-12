<?php
// admin/castings_ver.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$ide = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (isset($_POST['guardar'])) {
    $titulo = mysqli_real_escape_string($link, $_POST['titulo']);
    $tipo = mysqli_real_escape_string($link, $_POST['tipo']);
    $descripcion = mysqli_real_escape_string($link, $_POST['descripcion']);
    $fecha_apertura = mysqli_real_escape_string($link, $_POST['fecha_apertura']);
    $fecha_cierre = mysqli_real_escape_string($link, $_POST['fecha_cierre']);
    $estado = mysqli_real_escape_string($link, $_POST['estado']);

    if ($ide > 0) {
        mysqli_query($link, "UPDATE casting SET titulo='$titulo', tipo='$tipo', descripcion='$descripcion',
            fecha_apertura='$fecha_apertura', fecha_cierre='$fecha_cierre', estado='$estado' WHERE id=$ide");
        alerta('Casting', 'Actualizado correctamente', 2);
    } else {
        mysqli_query($link, "INSERT INTO casting (titulo, tipo, descripcion, fecha_apertura, fecha_cierre, estado)
            VALUES ('$titulo', '$tipo', '$descripcion', '$fecha_apertura', '$fecha_cierre', '$estado')");
        $ide = mysqli_insert_id($link);
        alerta('Casting', 'Creado correctamente', 2);
    }
    header("Location: castings_ver.php?id=$ide");
    exit;
}

if (isset($_POST['eliminar'])) {
    mysqli_query($link, "DELETE FROM casting WHERE id=$ide");
    alerta('Casting', 'Eliminado correctamente', 2);
    header('Location: castings_index.php');
    exit;
}

$c = ['titulo' => '', 'tipo' => '', 'descripcion' => '', 'fecha_apertura' => '', 'fecha_cierre' => '', 'estado' => 'abierto'];
if ($ide > 0) {
    $res = mysqli_query($link, "SELECT * FROM casting WHERE id=$ide");
    $c = mysqli_fetch_assoc($res);
}

$inscritos = [];
if ($ide > 0) {
    $inscritos_res = mysqli_query($link, "
      SELECT i.id, i.estado, i.fecha_inscripcion, act.id AS actor_id, act.nombre, act.email
      FROM inscripcion i
      JOIN actor act ON act.id = i.actor_id
      WHERE i.casting_id = $ide
      ORDER BY i.fecha_inscripcion DESC
    ");
    while ($row = mysqli_fetch_assoc($inscritos_res)) {
        $inscritos[] = $row;
    }
}

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <a href="castings_index.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-chevron-circle-left"></i> Regresar</a>
</div>
<div class="col-xl-8">
  <form method="post">
    <div class="card">
      <div class="card-header pb-0"><h4 class="card-title"><?= $ide > 0 ? 'Editar' : 'Nuevo' ?> casting</h4></div>
      <div class="card-body table-responsive">
        <table class="table text-md-nowrap">
          <tr><td class="iz"><b>Título:</b></td><td><input class="form-control" name="titulo" value="<?= htmlspecialchars($c['titulo']) ?>" required></td></tr>
          <tr><td class="iz"><b>Tipo:</b></td><td><input class="form-control" name="tipo" value="<?= htmlspecialchars($c['tipo']) ?>" placeholder="Película, corto..." required></td></tr>
          <tr><td class="iz"><b>Descripción:</b></td><td><textarea class="form-control" name="descripcion"><?= htmlspecialchars($c['descripcion']) ?></textarea></td></tr>
          <tr><td class="iz"><b>Fecha apertura:</b></td><td><input class="form-control" type="date" name="fecha_apertura" value="<?= htmlspecialchars($c['fecha_apertura']) ?>" required></td></tr>
          <tr><td class="iz"><b>Fecha cierre:</b></td><td><input class="form-control" type="date" name="fecha_cierre" value="<?= htmlspecialchars($c['fecha_cierre']) ?>" required></td></tr>
          <tr><td class="iz"><b>Estado:</b></td><td>
            <select class="form-control" name="estado">
              <option value="abierto" <?= $c['estado'] == 'abierto' ? 'selected' : '' ?>>Abierto</option>
              <option value="cerrado" <?= $c['estado'] == 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
            </select>
          </td></tr>
          <tr><td colspan="2"><input type="submit" name="guardar" value="Guardar" class="btn btn-primary"></td></tr>
        </table>
      </div>
    </div>
  </form>
</div>

<?php if ($ide > 0): ?>
<div class="col-xl-4">
  <div class="card">
    <div class="card-header pb-0">
      <h4 class="card-title">Inscritos en este casting</h4>
    </div>
    <div class="card-body">
      <?php if (empty($inscritos)): ?>
        <p class="lista-vacia">Nadie se ha inscrito todavía.</p>
      <?php else: ?>
        <ul class="lista-panel">
          <?php foreach ($inscritos as $i => $insc): ?>
            <li style="--i:<?= $i ?>">
              <a href="actores_ver.php?id=<?= (int)$insc['actor_id'] ?>">
                <i class="fa fa-user lista-icono"></i>
                <span class="lista-texto"><?= htmlspecialchars($insc['nombre']) ?></span>
                <span class="lista-fecha"><?= htmlspecialchars($insc['estado']) ?></span>
                <span class="lista-flecha">&rarr;</span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="col-xl-12 mt-3">
  <div class="card">
    <div class="card-header pb-3">
      <div class="d-flex justify-content-between">
        <form method="post" onsubmit="return confirm('¿Seguro que quieres eliminar este casting?');">
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

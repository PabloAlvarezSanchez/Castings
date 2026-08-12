<?php
// admin/actores_ver.php — ficha de UNA PERSONA ($ide = actor_id), con todas sus inscripciones
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$ide = (int)$_GET['id'];

if (isset($_POST['guardar_estado'])) {
    $inscripcion_id = (int)$_POST['inscripcion_id'];
    $estado = mysqli_real_escape_string($link, $_POST['estado']);
    mysqli_query($link, "UPDATE inscripcion SET estado='$estado' WHERE id=$inscripcion_id AND actor_id=$ide");
    alerta('Inscripción', 'Estado actualizado', 2);
    header("Location: actores_ver.php?id=$ide");
    exit;
}

$res = mysqli_query($link, "SELECT * FROM actor WHERE id=$ide");
$actor = mysqli_fetch_assoc($res);

if (!$actor) {
    http_response_code(404);
    die('Actor no encontrado.');
}

$inscripciones_res = mysqli_query($link, "
  SELECT i.id, i.estado, i.fecha_inscripcion, c.id AS casting_id, c.titulo AS casting_titulo
  FROM inscripcion i
  JOIN casting c ON c.id = i.casting_id
  WHERE i.actor_id = $ide
  ORDER BY i.fecha_inscripcion DESC
");
$inscripciones = [];
while ($row = mysqli_fetch_assoc($inscripciones_res)) {
    $row['media'] = [];
    $media_res = mysqli_query($link, "SELECT tipo, ruta_fichero FROM actor_media WHERE inscripcion_id=" . (int)$row['id']);
    while ($m = mysqli_fetch_assoc($media_res)) {
        $row['media'][] = $m;
    }
    $inscripciones[] = $row;
}

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <a href="actores_index.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-chevron-circle-left"></i> Regresar</a>
</div>
<div class="col-xl-8">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Ficha de <?= htmlspecialchars($actor['nombre']) ?></h4></div>
    <div class="card-body table-responsive">
      <table class="table text-md-nowrap">
        <tr><td class="iz"><b>Email:</b></td><td><a href="mailto:<?= htmlspecialchars($actor['email']) ?>"><?= htmlspecialchars($actor['email']) ?></a></td></tr>
        <tr><td class="iz"><b>Teléfono:</b></td><td><a href="<?= htmlspecialchars(whatsapp_link($actor['telefono'])) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($actor['telefono']) ?></a></td></tr>
        <tr><td class="iz"><b>Fecha de nacimiento:</b></td><td><?= fecha_es($actor['fecha_nacimiento']) ?></td></tr>
        <tr><td class="iz"><b>Edad:</b></td><td><?= calcular_edad($actor['fecha_nacimiento']) ?> años</td></tr>
        <tr><td class="iz"><b>Altura:</b></td><td><?= htmlspecialchars($actor['altura']) ?> cm</td></tr>
        <tr><td class="iz"><b>Medidas:</b></td><td><?= htmlspecialchars($actor['medidas']) ?></td></tr>
      </table>
    </div>
  </div>
</div>

<div class="col-xl-4">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Castings</h4></div>
    <div class="card-body">
      <?php if (empty($inscripciones)): ?>
        <p class="lista-vacia">Sin inscripciones.</p>
      <?php else: ?>
        <ul class="lista-panel">
          <?php foreach ($inscripciones as $i => $insc): ?>
            <li style="--i:<?= $i ?>">
              <a href="#" onclick="document.querySelectorAll('.inscripcion-bloque').forEach(function(el){ el.style.display = 'none'; }); document.getElementById('insc-<?= (int)$insc['id'] ?>').style.display = 'block'; return false;">
                <i class="fa fa-film lista-icono"></i>
                <span class="lista-texto"><?= htmlspecialchars($insc['casting_titulo']) ?></span>
                <span class="lista-fecha"><?= htmlspecialchars($insc['estado']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php foreach ($inscripciones as $insc): ?>
<div class="col-xl-12 mt-3">
  <div class="card inscripcion-bloque" id="insc-<?= (int)$insc['id'] ?>" style="display:none;">
    <div class="card-header pb-0">
      <h4 class="card-title"><?= htmlspecialchars($insc['casting_titulo']) ?></h4>
    </div>
    <div class="card-body">
      <div class="inscripcion-cabecera">
        <span class="inscripcion-fecha">Inscrito el <?= fecha_es($insc['fecha_inscripcion'], true) ?></span>
      </div>
      <form method="post" class="inscripcion-form">
        <input type="hidden" name="inscripcion_id" value="<?= (int)$insc['id'] ?>">
        <select class="form-control form-control-sm" name="estado" style="width:auto;display:inline-block;">
          <option value="pendiente" <?= $insc['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
          <option value="aceptado" <?= $insc['estado'] == 'aceptado' ? 'selected' : '' ?>>Aceptado</option>
          <option value="rechazado" <?= $insc['estado'] == 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
        </select>
        <button type="submit" name="guardar_estado" value="1" class="btn btn-sm btn-primary">Guardar</button>
      </form>
      <?php if (!empty($insc['media'])): ?>
        <div class="inscripcion-media">
          <?php foreach ($insc['media'] as $m): ?>
            <?php if ($m['tipo'] === 'foto'): ?>
              <img src="../<?= htmlspecialchars($m['ruta_fichero']) ?>" style="max-width:160px;margin:5px;">
            <?php else: ?>
              <video src="../<?= htmlspecialchars($m['ruta_fichero']) ?>" controls style="max-width:320px;margin:5px;"></video>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="lista-vacia">Sin material adjunto para este casting.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>

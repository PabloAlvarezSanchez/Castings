<?php
// admin/index.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$castings_abiertos_res = mysqli_query($link, "SELECT id, titulo FROM casting WHERE estado = 'abierto' ORDER BY fecha_apertura DESC");
$castings_abiertos = [];
while ($row = mysqli_fetch_assoc($castings_abiertos_res)) {
    $castings_abiertos[] = $row;
}

$pendientes_res = mysqli_query($link, "
  SELECT c.id, c.titulo, COUNT(i.id) AS pendientes
  FROM casting c
  JOIN inscripcion i ON i.casting_id = c.id AND i.estado = 'pendiente'
  GROUP BY c.id, c.titulo
  ORDER BY pendientes DESC
");
$pendientes_por_casting = [];
while ($row = mysqli_fetch_assoc($pendientes_res)) {
    $pendientes_por_casting[] = $row;
}

$ultimas_res = mysqli_query($link, "
  SELECT i.id, act.id AS actor_id, act.nombre, i.fecha_inscripcion, c.titulo AS casting_titulo
  FROM inscripcion i
  JOIN actor act ON act.id = i.actor_id
  JOIN casting c ON c.id = i.casting_id
  ORDER BY i.fecha_inscripcion DESC
  LIMIT 10
");
$ultimas = [];
while ($row = mysqli_fetch_assoc($ultimas_res)) {
    $ultimas[] = $row;
}

$total_pendientes = array_sum(array_column($pendientes_por_casting, 'pendientes'));
$total_inscripciones = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS n FROM inscripcion"))['n'];

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <div class="stat-fila">
    <div class="stat-tile" style="--i:0">
      <span class="stat-num"><?= count($castings_abiertos) ?></span>
      <span class="stat-label">Castings abiertos</span>
    </div>
    <div class="stat-tile" style="--i:1">
      <span class="stat-num"><?= (int)$total_pendientes ?></span>
      <span class="stat-label">Inscripciones pendientes</span>
    </div>
    <div class="stat-tile" style="--i:2">
      <span class="stat-num"><?= (int)$total_inscripciones ?></span>
      <span class="stat-label">Inscripciones totales</span>
    </div>
  </div>
</div>

<div class="col-xl-6">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Castings abiertos</h4></div>
    <div class="card-body">
      <?php if (empty($castings_abiertos)): ?>
        <p class="lista-vacia">No hay castings abiertos ahora mismo.</p>
      <?php else: ?>
        <ul class="lista-panel">
          <?php foreach ($castings_abiertos as $i => $c): ?>
            <li style="--i:<?= $i ?>">
              <a href="castings_ver.php?id=<?= (int)$c['id'] ?>">
                <i class="fa fa-film lista-icono"></i>
                <span class="lista-texto"><?= htmlspecialchars($c['titulo']) ?></span>
                <span class="lista-flecha">&rarr;</span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="col-xl-6">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Pendientes por casting</h4></div>
    <div class="card-body">
      <?php if (empty($pendientes_por_casting)): ?>
        <p class="lista-vacia">No hay inscripciones pendientes.</p>
      <?php else: ?>
        <ul class="lista-panel">
          <?php foreach ($pendientes_por_casting as $i => $p): ?>
            <li style="--i:<?= $i ?>">
              <a href="actores_index.php?estado=pendiente">
                <i class="fa fa-hourglass-half lista-icono"></i>
                <span class="lista-texto"><?= htmlspecialchars($p['titulo']) ?></span>
                <span class="lista-badge"><?= (int)$p['pendientes'] ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="col-xl-12">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Últimas inscripciones</h4></div>
    <div class="card-body">
      <?php if (empty($ultimas)): ?>
        <p class="lista-vacia">Todavía no hay inscripciones.</p>
      <?php else: ?>
        <ul class="lista-panel lista-panel-ancha">
          <?php foreach ($ultimas as $i => $u): ?>
            <li style="--i:<?= $i ?>">
              <a href="actores_ver.php?id=<?= (int)$u['actor_id'] ?>">
                <i class="fa fa-user lista-icono"></i>
                <span class="lista-texto"><?= htmlspecialchars($u['nombre']) ?></span>
                <span class="lista-secundario"><?= htmlspecialchars($u['casting_titulo']) ?></span>
                <span class="lista-fecha"><?= fecha_es($u['fecha_inscripcion'], true) ?></span>
                <span class="lista-flecha">&rarr;</span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>

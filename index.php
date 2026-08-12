<?php
// index.php
require_once __DIR__ . '/comun/conecta.php';
require_once __DIR__ . '/comun/general.php';

$res = mysqli_query($link, "SELECT id, titulo, tipo, descripcion, fecha_cierre FROM casting WHERE estado='abierto' ORDER BY fecha_apertura DESC");
$castings = [];
while ($row = mysqli_fetch_assoc($res)) {
    $castings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Castings abiertos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..900;1,9..144,300..900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="assets/css/landing.css" rel="stylesheet">
</head>
<body>

<div class="grain"></div>

<header class="hero">
  <div class="hero-frame">
    <span class="hero-tag">CONVOCATORIA ABIERTA</span>
    <h1 class="hero-title">Castings<br>en cartel</h1>
    <p class="hero-sub">Películas, cortos y proyectos que buscan rostros nuevos.<br>Elige el tuyo.</p>
  </div>
  <div class="hero-counter">
    <span class="hero-counter-num"><?= count($castings) ?></span>
    <span class="hero-counter-label"><?= count($castings) === 1 ? 'convocatoria abierta' : 'convocatorias abiertas' ?></span>
  </div>
</header>

<main class="listado">
  <?php if (empty($castings)): ?>
    <p class="vacio">No hay castings abiertos en este momento.<br>Vuelve a pasarte pronto.</p>
  <?php else: ?>
    <?php foreach ($castings as $i => $c): ?>
      <a href="casting_ver.php?id=<?= (int)$c['id'] ?>" class="ficha" style="--i: <?= $i ?>">
        <span class="ficha-num"><?= sprintf('%02d', $i + 1) ?></span>
        <div class="ficha-cuerpo">
          <span class="ficha-tipo"><?= htmlspecialchars($c['tipo']) ?></span>
          <h2 class="ficha-titulo"><?= htmlspecialchars($c['titulo']) ?></h2>
          <p class="ficha-desc"><?= htmlspecialchars($c['descripcion']) ?></p>
        </div>
        <div class="ficha-cierre">
          <span class="ficha-cierre-label">Cierra</span>
          <span class="ficha-cierre-fecha"><?= fecha_es($c['fecha_cierre']) ?></span>
        </div>
        <span class="ficha-cta">Inscribirme <span class="ficha-flecha">&rarr;</span></span>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

<footer class="pie">
  <span>&copy; <?= date('Y') ?> Castings</span>
  <a href="admin/login.php">Acceso equipo</a>
</footer>

</body>
</html>

<?php
// comun/admin_sidebar.php — requiere $pagina_actual definida en interfaz_cabeza.php
$activo_castings = in_array($pagina_actual, ['castings_index.php', 'castings_ver.php']);
$activo_actores = in_array($pagina_actual, ['actores_index.php', 'actores_ver.php']);
$activo_usuarios = in_array($pagina_actual, ['usuarios_index.php', 'usuarios_ver.php']);
$activo_config = $pagina_actual === 'configuracion.php';
$activo_dashboard = $pagina_actual === 'index.php';
?>
<nav class="admin-sidebar">
  <div class="admin-sidebar-marca">
    <i class="fa fa-clapperboard"></i>
    <span>Castings</span>
  </div>
  <ul class="admin-sidebar-menu">
    <li>
      <a href="index.php" class="<?= $activo_dashboard ? 'activo' : '' ?>">
        <i class="fa fa-gauge"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="castings_index.php" class="<?= $activo_castings ? 'activo' : '' ?>">
        <i class="fa fa-film"></i> Castings
      </a>
    </li>
    <li>
      <a href="actores_index.php" class="<?= $activo_actores ? 'activo' : '' ?>">
        <i class="fa fa-users"></i> Inscripciones
      </a>
    </li>
    <li>
      <a href="usuarios_index.php" class="<?= $activo_usuarios ? 'activo' : '' ?>">
        <i class="fa fa-user-shield"></i> Usuarios
      </a>
    </li>
    <li>
      <a href="configuracion.php" class="<?= $activo_config ? 'activo' : '' ?>">
        <i class="fa fa-sliders"></i> Configuración
      </a>
    </li>
  </ul>
  <div class="admin-sidebar-pie">
    <a href="logout.php"><i class="fa fa-right-from-bracket"></i> Salir</a>
  </div>
</nav>

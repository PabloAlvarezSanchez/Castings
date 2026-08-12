<?php
// admin/castings_index.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$res = mysqli_query($link, "SELECT id, titulo, tipo, estado, fecha_apertura, fecha_cierre FROM casting ORDER BY fecha_apertura DESC");

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <div class="card">
    <div class="card-header pb-0 d-flex justify-content-between">
      <h4 class="card-title">Castings</h4>
      <a href="castings_ver.php" class="btn btn-outline-primary"><i class="fa fa-plus"></i> Nuevo casting</a>
    </div>
    <div class="card-body">
      <div class="row mb-3 g-2">
        <div class="col-md-8">
          <input type="text" id="buscador-castings" class="form-control" placeholder="Buscar por título o tipo...">
        </div>
        <div class="col-md-4">
          <select id="filtro-estado-castings" class="form-control">
            <option value="">Todos los estados</option>
            <option value="abierto">Abierto</option>
            <option value="cerrado">Cerrado</option>
          </select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table text-md-nowrap" id="tabla-castings">
          <thead><tr><th>Título</th><th>Tipo</th><th>Estado</th><th>Apertura</th><th>Cierre</th><th></th></tr></thead>
          <tbody>
          <?php while ($c = mysqli_fetch_assoc($res)): ?>
            <tr data-titulo="<?= htmlspecialchars(mb_strtolower($c['titulo'])) ?>" data-tipo="<?= htmlspecialchars(mb_strtolower($c['tipo'])) ?>" data-estado="<?= htmlspecialchars($c['estado']) ?>">
              <td><?= htmlspecialchars($c['titulo']) ?></td>
              <td><?= htmlspecialchars($c['tipo']) ?></td>
              <td><?= htmlspecialchars($c['estado']) ?></td>
              <td><?= fecha_es($c['fecha_apertura']) ?></td>
              <td><?= fecha_es($c['fecha_cierre']) ?></td>
              <td><a href="castings_ver.php?id=<?= (int)$c['id'] ?>">Editar</a></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        <p id="sin-resultados-castings" class="lista-vacia" style="display:none;">Ningún casting coincide con la búsqueda.</p>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var buscador = document.getElementById('buscador-castings');
  var filtroEstado = document.getElementById('filtro-estado-castings');
  var filas = document.querySelectorAll('#tabla-castings tbody tr');
  var sinResultados = document.getElementById('sin-resultados-castings');

  function filtrar() {
    var texto = buscador.value.trim().toLowerCase();
    var estado = filtroEstado.value;
    var visibles = 0;

    filas.forEach(function (fila) {
      var coincideTexto = fila.dataset.titulo.includes(texto) || fila.dataset.tipo.includes(texto);
      var coincideEstado = !estado || fila.dataset.estado === estado;
      var mostrar = coincideTexto && coincideEstado;
      fila.style.display = mostrar ? '' : 'none';
      if (mostrar) visibles++;
    });

    sinResultados.style.display = visibles === 0 ? 'block' : 'none';
  }

  buscador.addEventListener('input', filtrar);
  filtroEstado.addEventListener('change', filtrar);
})();
</script>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>

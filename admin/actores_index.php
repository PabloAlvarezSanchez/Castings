<?php
// admin/actores_index.php — listado de PERSONAS inscritas (una fila por actor)
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$sql = "SELECT act.id, act.nombre, act.email, act.telefono,
               COUNT(i.id) AS total_castings,
               MAX(i.fecha_inscripcion) AS ultima_inscripcion,
               GROUP_CONCAT(DISTINCT i.estado) AS estados,
               GROUP_CONCAT(DISTINCT i.casting_id) AS castings_ids
        FROM actor act
        JOIN inscripcion i ON i.actor_id = act.id
        GROUP BY act.id, act.nombre, act.email, act.telefono
        ORDER BY ultima_inscripcion DESC";
$res = mysqli_query($link, $sql);

$castings_res = mysqli_query($link, "SELECT id, titulo FROM casting ORDER BY titulo");
$castings_lista = [];
while ($row = mysqli_fetch_assoc($castings_res)) {
    $castings_lista[] = $row;
}

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <div class="card">
    <div class="card-header pb-0">
      <h4 class="card-title">Inscripciones</h4>
    </div>
    <div class="card-body">
      <div class="row mb-3 g-2">
        <div class="col-md-5">
          <input type="text" id="buscador-actores" class="form-control" placeholder="Buscar por nombre, email o teléfono...">
        </div>
        <div class="col-md-4">
          <select id="filtro-casting-actores" class="form-control">
            <option value="">Todos los castings</option>
            <?php foreach ($castings_lista as $cst): ?>
              <option value="<?= (int)$cst['id'] ?>"><?= htmlspecialchars($cst['titulo']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select id="filtro-estado-actores" class="form-control">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="aceptado">Aceptado</option>
            <option value="rechazado">Rechazado</option>
          </select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table text-md-nowrap" id="tabla-actores">
          <thead><tr><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Castings</th><th>Última inscripción</th><th></th></tr></thead>
          <tbody>
          <?php while ($a = mysqli_fetch_assoc($res)): ?>
            <tr data-texto="<?= htmlspecialchars(mb_strtolower($a['nombre'] . ' ' . $a['email'] . ' ' . $a['telefono'])) ?>" data-estados="<?= htmlspecialchars($a['estados']) ?>" data-castings="<?= htmlspecialchars($a['castings_ids']) ?>">
              <td><?= htmlspecialchars($a['nombre']) ?></td>
              <td><a href="mailto:<?= htmlspecialchars($a['email']) ?>"><?= htmlspecialchars($a['email']) ?></a></td>
              <td><a href="<?= htmlspecialchars(whatsapp_link($a['telefono'])) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($a['telefono']) ?></a></td>
              <td>
                <span class="badge bg-warning text-dark">
                  <i class="fa fa-layer-group"></i> <?= (int)$a['total_castings'] ?>
                </span>
              </td>
              <td><?= fecha_es($a['ultima_inscripcion'], true) ?></td>
              <td><a href="actores_ver.php?id=<?= (int)$a['id'] ?>">Ver ficha</a></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        <p id="sin-resultados-actores" class="lista-vacia" style="display:none;">Nadie coincide con la búsqueda.</p>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var buscador = document.getElementById('buscador-actores');
  var filtroEstado = document.getElementById('filtro-estado-actores');
  var filtroCasting = document.getElementById('filtro-casting-actores');
  var filas = document.querySelectorAll('#tabla-actores tbody tr');
  var sinResultados = document.getElementById('sin-resultados-actores');

  function filtrar() {
    var texto = buscador.value.trim().toLowerCase();
    var estado = filtroEstado.value;
    var casting = filtroCasting.value;
    var visibles = 0;

    filas.forEach(function (fila) {
      var coincideTexto = fila.dataset.texto.includes(texto);
      var coincideEstado = !estado || fila.dataset.estados.split(',').includes(estado);
      var coincideCasting = !casting || fila.dataset.castings.split(',').includes(casting);
      var mostrar = coincideTexto && coincideEstado && coincideCasting;
      fila.style.display = mostrar ? '' : 'none';
      if (mostrar) visibles++;
    });

    sinResultados.style.display = visibles === 0 ? 'block' : 'none';
  }

  buscador.addEventListener('input', filtrar);
  filtroEstado.addEventListener('change', filtrar);
  filtroCasting.addEventListener('change', filtrar);

  // preselecciona filtros si se llega con ?estado=... o ?casting_id=... (enlaces desde dashboard/casting)
  var params = new URLSearchParams(window.location.search);
  if (params.get('estado')) {
    filtroEstado.value = params.get('estado');
  }
  if (params.get('casting_id')) {
    filtroCasting.value = params.get('casting_id');
  }
  filtrar();
})();
</script>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>

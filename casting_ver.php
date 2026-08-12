<?php
// casting_ver.php
require_once __DIR__ . '/comun/conecta.php';
require_once __DIR__ . '/comun/general.php';

$casting_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$res = mysqli_query($link, "SELECT * FROM casting WHERE id=$casting_id AND estado='abierto'");
$casting = mysqli_fetch_assoc($res);

if (!$casting) {
    http_response_code(404);
    die('Casting no encontrado o cerrado.');
}

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $altura = (float)($_POST['altura'] ?? 0);
    $medidas = trim($_POST['medidas'] ?? '');

    $fecha_valida = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email no válido.';
    if ($telefono === '') $errores[] = 'El teléfono es obligatorio.';
    if (!$fecha_valida || $fecha_valida > new DateTime()) $errores[] = 'Fecha de nacimiento no válida.';
    if ($altura <= 0) $errores[] = 'Altura no válida.';

    if (empty($errores)) {
        $nombre_e = mysqli_real_escape_string($link, $nombre);
        $email_e = mysqli_real_escape_string($link, $email);
        $telefono_e = mysqli_real_escape_string($link, $telefono);
        $fecha_nacimiento_e = mysqli_real_escape_string($link, $fecha_nacimiento);
        $medidas_e = mysqli_real_escape_string($link, $medidas);

        // actor = persona, identificada por email. Si ya existe, actualiza sus datos (son los mas recientes).
        $actor_existente = mysqli_fetch_assoc(mysqli_query($link, "SELECT id FROM actor WHERE email='$email_e'"));
        if ($actor_existente) {
            $actor_id = (int)$actor_existente['id'];
            mysqli_query($link, "UPDATE actor SET nombre='$nombre_e', telefono='$telefono_e', fecha_nacimiento='$fecha_nacimiento_e', altura=$altura, medidas='$medidas_e' WHERE id=$actor_id");
        } else {
            mysqli_query($link, "INSERT INTO actor (nombre, email, telefono, fecha_nacimiento, altura, medidas)
                VALUES ('$nombre_e', '$email_e', '$telefono_e', '$fecha_nacimiento_e', $altura, '$medidas_e')");
            $actor_id = mysqli_insert_id($link);
        }

        // ¿ya está inscrito en este casting concreto? evita duplicar la inscripcion.
        $inscripcion_existente = mysqli_fetch_assoc(mysqli_query($link, "SELECT id FROM inscripcion WHERE actor_id=$actor_id AND casting_id=$casting_id"));
        if ($inscripcion_existente) {
            $errores[] = 'Ya estás inscrito/a en este casting con este email.';
        } else {
            mysqli_query($link, "INSERT INTO inscripcion (actor_id, casting_id) VALUES ($actor_id, $casting_id)");
            $inscripcion_id = mysqli_insert_id($link);

            $destino = __DIR__ . "/uploads/castings/$casting_id/$inscripcion_id";

            if (!empty($_FILES['fotos']['name'][0])) {
                foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp) {
                    $file = [
                        'tmp_name' => $tmp,
                        'name' => $_FILES['fotos']['name'][$i],
                        'size' => $_FILES['fotos']['size'][$i],
                        'error' => $_FILES['fotos']['error'][$i],
                    ];
                    $ruta = subir_fichero($file, $destino, ['image/jpeg', 'image/png'], 5 * 1024 * 1024);
                    if ($ruta) {
                        $ruta_rel = 'uploads/castings/' . $casting_id . '/' . $inscripcion_id . '/' . basename($ruta);
                        mysqli_query($link, "INSERT INTO actor_media (inscripcion_id, tipo, ruta_fichero) VALUES ($inscripcion_id, 'foto', '" . mysqli_real_escape_string($link, $ruta_rel) . "')");
                    }
                }
            }

            if (!empty($_FILES['video']['name'])) {
                $ruta = subir_fichero($_FILES['video'], $destino, ['video/mp4'], 100 * 1024 * 1024);
                if ($ruta) {
                    $ruta_rel = 'uploads/castings/' . $casting_id . '/' . $inscripcion_id . '/' . basename($ruta);
                    mysqli_query($link, "INSERT INTO actor_media (inscripcion_id, tipo, ruta_fichero) VALUES ($inscripcion_id, 'video', '" . mysqli_real_escape_string($link, $ruta_rel) . "')");
                }
            }

            $exito = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($casting['titulo']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..900;1,9..144,300..900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="assets/css/landing.css" rel="stylesheet">
  <link href="assets/css/ficha-form.css" rel="stylesheet">
</head>
<body>

<div class="grain"></div>

<header class="ficha-hero">
  <a href="index.php" class="volver">&larr; Todos los castings</a>
  <span class="hero-tag"><?= htmlspecialchars($casting['tipo']) ?></span>
  <h1 class="hero-title"><?= htmlspecialchars($casting['titulo']) ?></h1>
  <p class="hero-sub"><?= htmlspecialchars($casting['descripcion']) ?></p>
  <p class="ficha-cierre-info">Cierra el <?= fecha_es($casting['fecha_cierre']) ?></p>
</header>

<main class="formulario-wrap">
  <?php if ($exito): ?>
    <div class="aviso aviso-ok">
      <span class="aviso-num">&#10003;</span>
      <div>
        <h2>Inscripción recibida</h2>
        <p>Gracias por presentarte. Te contactaremos si eres seleccionado/a.</p>
      </div>
    </div>
  <?php else: ?>

    <?php if (!empty($errores)): ?>
      <div class="aviso aviso-error">
        <ul>
          <?php foreach ($errores as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="ficha-form">

      <fieldset>
        <legend>Datos personales</legend>
        <div class="campo">
          <label for="nombre">Nombre completo</label>
          <input id="nombre" class="input" name="nombre" required>
        </div>
        <div class="fila">
          <div class="campo">
            <label for="email">Email</label>
            <input id="email" class="input" type="email" name="email" required>
          </div>
          <div class="campo">
            <label for="telefono">Teléfono</label>
            <input id="telefono" class="input" name="telefono" required>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Ficha física</legend>
        <div class="fila fila-3">
          <div class="campo">
            <label for="fecha_nacimiento">Fecha de nacimiento</label>
            <input id="fecha_nacimiento" class="input" type="date" name="fecha_nacimiento" required>
          </div>
          <div class="campo">
            <label for="altura">Altura (cm)</label>
            <input id="altura" class="input" type="number" step="0.1" name="altura" required>
          </div>
          <div class="campo">
            <label for="medidas">Medidas</label>
            <input id="medidas" class="input" name="medidas" placeholder="Opcional">
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Material</legend>
        <div class="campo">
          <label for="fotos">Fotos (jpg / png, máx. 5 MB c/u)</label>
          <input id="fotos" class="input input-file" type="file" name="fotos[]" accept="image/jpeg,image/png" multiple>
        </div>
        <div class="campo">
          <label for="video">Vídeo de presentación (mp4, máx. 100 MB)</label>
          <input id="video" class="input input-file" type="file" name="video" accept="video/mp4">
        </div>
      </fieldset>

      <button class="btn-enviar" type="submit">Enviar inscripción <span class="ficha-flecha">&rarr;</span></button>
    </form>

  <?php endif; ?>
</main>

<footer class="pie">
  <span>&copy; <?= date('Y') ?> Castings</span>
  <a href="admin/login.php">Acceso equipo</a>
</footer>

</body>
</html>

<?php
// admin/login.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = mysqli_real_escape_string($link, $_POST['usuario']);
    $clave_md5 = md5($_POST['clave']);
    $res = mysqli_query($link, "SELECT id, usu_usuario, usu_reintento FROM usuario WHERE usu_usuario = '$usuario' AND usu_clave = '$clave_md5'");
    $row = mysqli_fetch_assoc($res);
    if ($row) {
        mysqli_query($link, "UPDATE usuario SET usu_reintento = 0 WHERE id = " . (int)$row['id']);
        $_SESSION['id'] = $row['id'];
        $_SESSION['usuario'] = $row['usu_usuario'];
        header('Location: index.php');
        exit;
    } else {
        mysqli_query($link, "UPDATE usuario SET usu_reintento = usu_reintento + 1 WHERE usu_usuario = '$usuario'");
        $error = 'Usuario o clave incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso administración - Castings</title>
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500..700;1,9..144,500..700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
</head>
<body>

<div class="grain"></div>

<div class="login-wrap">
  <div class="login-marca">
    <i class="fa fa-clapperboard"></i>
    <span>Castings</span>
  </div>

  <form method="post" class="login-card">
    <h1>Acceso equipo</h1>
    <p class="login-sub">Panel de administración de castings</p>

    <?php if ($error): ?>
      <div class="login-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="campo">
      <label for="usuario">Usuario</label>
      <input id="usuario" class="input" name="usuario" required autofocus>
    </div>
    <div class="campo">
      <label for="clave">Clave</label>
      <input id="clave" class="input" type="password" name="clave" required>
    </div>

    <button class="btn-entrar" type="submit">Entrar <span class="flecha">&rarr;</span></button>
  </form>

  <a href="../index.php" class="login-volver">&larr; Volver a la landing</a>
</div>

</body>
</html>

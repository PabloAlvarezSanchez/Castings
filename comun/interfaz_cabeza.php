<?php
// comun/interfaz_cabeza.php
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Castings - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500..700;1,9..144,500..700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="../assets/css/estilo.css" rel="stylesheet">
</head>
<body>
<div class="admin-layout">
  <?php require __DIR__ . '/admin_sidebar.php'; ?>
  <div class="admin-contenido">
    <div class="container-fluid py-4">
    <?php
    if (isset($_SESSION['alerta'])) {
        $a = $_SESSION['alerta'];
        $clase = $a['tipo'] == 1 ? 'danger' : ($a['tipo'] == 2 ? 'success' : 'info');
        echo "<div class='alert alert-$clase'><b>{$a['titulo']}</b>: {$a['mensaje']}</div>";
        unset($_SESSION['alerta']);
    }
    ?>
    <div class="row">

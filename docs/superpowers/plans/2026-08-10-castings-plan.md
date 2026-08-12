# Plataforma de castings — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Landing pública de castings abiertos con formulario de inscripción de actores (ficha completa + fotos/vídeo), y panel admin (login único) para gestionar castings e inscripciones.

**Architecture:** PHP plano + mysqli, sin framework, sin build step. Carpeta `comun/` con includes compartidos (conexión, helpers, cabecera/pie), carpeta `admin/` con vistas de gestión (patrón `_index`/`_ver` estilo haboob), raíz con landing pública. Subida de ficheros a disco en `uploads/`.

**Tech Stack:** PHP 7+/8, MySQL/MariaDB (mysqli), Bootstrap (CDN o `assets/`), sin Composer salvo que ya exista `libs/` reutilizable.

## Global Constraints

- No usar PDO — API mysqli clásica, variable global `$link` (spec: Conexión BD).
- Login admin: `usu_usuario` + MD5(`usu_clave`) contra tabla `usuario`, sesión con `$_SESSION['id']`, permisos vía `comprueba()` (spec: Admin).
- Un solo administrador — sin roles múltiples (spec: Decisiones deliberadas).
- Ficheros de actor en disco local, ruta `uploads/castings/{casting_id}/{actor_id}/` (spec: Landing pública).
- Validar tipo MIME y tamaño máximo en toda subida de fichero — frontera de seguridad, no se omite (spec: Landing pública).
- CSS: Bootstrap antes que CSS propio; si hace falta CSS custom va en `assets/css/`, nunca inline (skill php-legacy).
- Vistas admin siguen el molde de columna+card+tabla+botón Guardar y el patrón de borrado con caja roja + clave de confirmación (skill php-legacy).
- No hay test runner en este stack: cada tarea se verifica con pasos manuales concretos (SQL a ejecutar / URL a visitar / resultado esperado en pantalla), no con asserts automatizados.

---

## File Structure

```
comun/
  conecta.php          -- conexión mysqli, variable global $link
  general.php           -- helpers: comprueba(), alerta(), utilidades de subida de ficheros
  interfaz_cabeza.php   -- cabecera HTML común (admin)
  interfaz_pie.php      -- pie HTML común (admin)
  config.php            -- credenciales BD (no versionar valores reales)
schema.sql               -- script de creación de tablas
index.php                 -- landing: lista castings abiertos
casting_ver.php           -- landing: detalle casting + formulario inscripción
admin/
  login.php
  logout.php
  index.php               -- dashboard
  castings_index.php
  castings_ver.php
  actores_index.php
  actores_ver.php
assets/
  css/estilo.css
uploads/
  castings/               -- ficheros subidos (gitignore)
```

---

### Task 1: Esquema de base de datos

**Files:**
- Create: `schema.sql`

**Interfaces:**
- Produces: tablas `usuario`, `casting`, `actor`, `actor_media` que consumen todas las tareas siguientes.

- [ ] **Step 1: Escribir el script de esquema**

```sql
CREATE TABLE usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usu_usuario VARCHAR(50) NOT NULL UNIQUE,
  usu_clave VARCHAR(32) NOT NULL,       -- MD5
  usu_derechos VARCHAR(50) NOT NULL DEFAULT '1',
  usu_reintento INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE casting (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  descripcion TEXT,
  fecha_apertura DATE NOT NULL,
  fecha_cierre DATE NOT NULL,
  estado ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE actor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  casting_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NOT NULL,
  edad INT NOT NULL,
  altura DECIMAL(4,1) NOT NULL,
  medidas VARCHAR(60),
  fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
  CONSTRAINT fk_actor_casting FOREIGN KEY (casting_id) REFERENCES casting(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE actor_media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_id INT NOT NULL,
  tipo ENUM('foto','video') NOT NULL,
  ruta_fichero VARCHAR(255) NOT NULL,
  CONSTRAINT fk_media_actor FOREIGN KEY (actor_id) REFERENCES actor(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Ejecutar el script contra la BD de pruebas**

Run: conectar con el cliente mysql (ver skill php-legacy para credenciales del servidor de pruebas) y ejecutar `source schema.sql;`
Expected: 4 tablas creadas sin error. Verificar con `SHOW TABLES;`

- [ ] **Step 3: Insertar el usuario admin inicial**

```sql
INSERT INTO usuario (usu_usuario, usu_clave, usu_derechos)
VALUES ('admin', MD5('cambiar-esta-clave'), '1');
```

Run: ejecutar el INSERT, luego `SELECT * FROM usuario;`
Expected: una fila con `usu_usuario = 'admin'`.

- [ ] **Step 4: Commit**

```bash
git add schema.sql
git commit -m "feat: esquema inicial de BD (usuario, casting, actor, actor_media)"
```

---

### Task 2: Includes comunes (conexión, helpers, cabecera/pie)

**Files:**
- Create: `comun/config.php`
- Create: `comun/conecta.php`
- Create: `comun/general.php`
- Create: `comun/interfaz_cabeza.php`
- Create: `comun/interfaz_pie.php`

**Interfaces:**
- Produces:
  - `$link` (mysqli global, definida en `conecta.php`)
  - `comprueba(int $usuario_id, int $zona): void` — corta ejecución con redirect si no hay permiso
  - `alerta(string $titulo, string $mensaje, int $tipo): void` — tipo 1=error,2=exito,3=info; encola mensaje en sesión
  - `subir_fichero(array $file, string $destino_dir, array $mimes_permitidos, int $max_bytes): string|false` — mueve el fichero subido, devuelve ruta relativa guardada o `false` si falla validación

- [ ] **Step 1: Crear `comun/config.php` con credenciales de conexión**

```php
<?php
// comun/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'web');
define('DB_PASS', 'CAMBIAR');
define('DB_NAME', 'castings');
```

- [ ] **Step 2: Crear `comun/conecta.php`**

```php
<?php
// comun/conecta.php
require_once __DIR__ . '/config.php';

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Error de conexión: ' . mysqli_connect_error());
}
mysqli_set_charset($link, 'utf8mb4');
```

- [ ] **Step 3: Crear `comun/general.php` con `comprueba()`, `alerta()` y `subir_fichero()`**

```php
<?php
// comun/general.php

function comprueba($usuario_id, $zona) {
    global $link;
    $res = mysqli_query($link, "SELECT usu_derechos FROM usuario WHERE id = " . (int)$usuario_id);
    $row = mysqli_fetch_assoc($res);
    if (!$row || $row['usu_derechos'][$zona] !== '1') {
        header('Location: denegado.php');
        exit;
    }
}

function alerta($titulo, $mensaje, $tipo) {
    if (!isset($_SESSION)) session_start();
    $_SESSION['alerta'] = ['titulo' => $titulo, 'mensaje' => $mensaje, 'tipo' => $tipo];
}

function subir_fichero($file, $destino_dir, $mimes_permitidos, $max_bytes) {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > $max_bytes) {
        return false;
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $mimes_permitidos, true)) {
        return false;
    }
    if (!is_dir($destino_dir)) {
        mkdir($destino_dir, 0755, true);
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre_final = uniqid('m_', true) . '.' . $ext;
    $ruta = rtrim($destino_dir, '/') . '/' . $nombre_final;
    if (!move_uploaded_file($file['tmp_name'], $ruta)) {
        return false;
    }
    return $ruta;
}
```

- [ ] **Step 4: Crear `comun/interfaz_cabeza.php`**

```php
<?php
// comun/interfaz_cabeza.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Castings - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" rel="stylesheet">
  <link href="/assets/css/estilo.css" rel="stylesheet">
</head>
<body>
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
```

- [ ] **Step 5: Crear `comun/interfaz_pie.php`**

```php
<?php
// comun/interfaz_pie.php
?>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

- [ ] **Step 6: Crear `assets/css/estilo.css` vacío (placeholder de estilos propios futuros)**

```css
/* estilos propios del proyecto, Bootstrap cubre la base */
```

- [ ] **Step 7: Verificar manualmente**

Crear un fichero temporal `comun/_prueba.php` con `require 'conecta.php'; echo 'ok';`, visitarlo por navegador o `php -f`.
Expected: imprime `ok` sin errores de conexión. Borrar el fichero de prueba después.

- [ ] **Step 8: Commit**

```bash
git add comun/ assets/
git commit -m "feat: includes comunes (conexion, helpers, cabecera/pie)"
```

---

### Task 3: Login y logout admin

**Files:**
- Create: `admin/login.php`
- Create: `admin/logout.php`
- Create: `denegado.php`

**Interfaces:**
- Consumes: `$link` (Task 2), tabla `usuario` (Task 1)
- Produces: sesión con `$_SESSION['id']`, `$_SESSION['usuario']` — consumida por todas las vistas de `admin/`

- [ ] **Step 1: Crear `admin/login.php`**

```php
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
<head><meta charset="UTF-8"><title>Login admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<div class="container" style="max-width:400px;margin-top:100px;">
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="mb-3"><label>Usuario</label><input class="form-control" name="usuario" required></div>
    <div class="mb-3"><label>Clave</label><input class="form-control" type="password" name="clave" required></div>
    <button class="btn btn-primary" type="submit">Entrar</button>
  </form>
</div>
</body>
</html>
```

- [ ] **Step 2: Crear `admin/logout.php`**

```php
<?php
// admin/logout.php
session_start();
session_destroy();
header('Location: login.php');
exit;
```

- [ ] **Step 3: Crear `denegado.php` en raíz**

```php
<?php
// denegado.php
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Acceso denegado</title></head>
<body><h1>Acceso denegado</h1><p>No tienes permiso para ver esta página.</p></body>
</html>
```

- [ ] **Step 4: Verificar manualmente**

Visitar `admin/login.php`, probar clave incorrecta (debe mostrar error y no crear sesión) y luego la clave correcta creada en Task 1 (debe redirigir a `admin/index.php`, aunque ese fichero no exista todavía dará 404 — es esperado hasta Task 4).
Expected: clave incorrecta → mensaje de error visible. Clave correcta → intento de redirect a `index.php`.

- [ ] **Step 5: Commit**

```bash
git add admin/login.php admin/logout.php denegado.php
git commit -m "feat: login y logout de administrador"
```

---

### Task 4: Dashboard admin

**Files:**
- Create: `admin/index.php`

**Interfaces:**
- Consumes: `$link`, `comprueba()` (Task 2), sesión (Task 3), tablas `casting`/`actor` (Task 1)
- Produces: página de entrada del admin tras login

- [ ] **Step 1: Crear `admin/index.php`**

```php
<?php
// admin/index.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$castings_abiertos = mysqli_query($link, "SELECT id, titulo FROM casting WHERE estado = 'abierto' ORDER BY fecha_apertura DESC");

$pendientes_por_casting = mysqli_query($link, "
  SELECT c.id, c.titulo, COUNT(a.id) AS pendientes
  FROM casting c
  JOIN actor a ON a.casting_id = c.id AND a.estado = 'pendiente'
  GROUP BY c.id, c.titulo
");

$ultimas = mysqli_query($link, "
  SELECT a.id, a.nombre, a.fecha_inscripcion, c.titulo AS casting_titulo
  FROM actor a
  JOIN casting c ON c.id = a.casting_id
  ORDER BY a.fecha_inscripcion DESC
  LIMIT 10
");

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-6">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Castings abiertos</h4></div>
    <div class="card-body table-responsive">
      <table class="table text-md-nowrap">
        <?php while ($c = mysqli_fetch_assoc($castings_abiertos)): ?>
          <tr><td><?= htmlspecialchars($c['titulo']) ?></td>
              <td><a href="castings_ver.php?id=<?= (int)$c['id'] ?>">Ver</a></td></tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>
</div>
<div class="col-xl-6">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Inscripciones pendientes por casting</h4></div>
    <div class="card-body table-responsive">
      <table class="table text-md-nowrap">
        <?php while ($p = mysqli_fetch_assoc($pendientes_por_casting)): ?>
          <tr><td><?= htmlspecialchars($p['titulo']) ?></td>
              <td><?= (int)$p['pendientes'] ?> pendientes</td>
              <td><a href="actores_index.php?casting_id=<?= (int)$p['id'] ?>">Ver</a></td></tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>
</div>
<div class="col-xl-12">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Últimas inscripciones</h4></div>
    <div class="card-body table-responsive">
      <table class="table text-md-nowrap">
        <?php while ($u = mysqli_fetch_assoc($ultimas)): ?>
          <tr>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= htmlspecialchars($u['casting_titulo']) ?></td>
            <td><?= htmlspecialchars($u['fecha_inscripcion']) ?></td>
            <td><a href="actores_ver.php?id=<?= (int)$u['id'] ?>">Ver ficha</a></td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>
```

- [ ] **Step 2: Verificar manualmente**

Login como admin, comprobar redirect a `admin/index.php`.
Expected: dashboard carga sin error PHP (tablas vacías = listados vacíos, es correcto ya que aún no hay castings).

- [ ] **Step 3: Commit**

```bash
git add admin/index.php
git commit -m "feat: dashboard admin con resumen de castings e inscripciones"
```

---

### Task 5: CRUD de castings (admin)

**Files:**
- Create: `admin/castings_index.php`
- Create: `admin/castings_ver.php`

**Interfaces:**
- Consumes: `$link`, `comprueba()`, `alerta()` (Task 2), sesión (Task 3)
- Produces: tabla `casting` gestionable — consumida por landing (Task 7) y por `actores_index.php` (Task 6)

- [ ] **Step 1: Crear `admin/castings_index.php`**

```php
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
    <div class="card-body table-responsive">
      <table class="table text-md-nowrap">
        <thead><tr><th>Título</th><th>Tipo</th><th>Estado</th><th>Apertura</th><th>Cierre</th><th></th></tr></thead>
        <tbody>
        <?php while ($c = mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?= htmlspecialchars($c['titulo']) ?></td>
            <td><?= htmlspecialchars($c['tipo']) ?></td>
            <td><?= htmlspecialchars($c['estado']) ?></td>
            <td><?= htmlspecialchars($c['fecha_apertura']) ?></td>
            <td><?= htmlspecialchars($c['fecha_cierre']) ?></td>
            <td><a href="castings_ver.php?id=<?= (int)$c['id'] ?>">Editar</a></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>
```

- [ ] **Step 2: Crear `admin/castings_ver.php` (alta/edición/borrado)**

```php
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

if (isset($_POST['eliminar']) && $_POST['aborrar'] == $ide) {
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

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <a href="castings_index.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-chevron-circle-left"></i> Regresar</a>
</div>
<div class="col-xl-12">
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
<div class="col-xl-12">
  <div class="card">
    <div class="card-body bg-danger" id="cajaborrar" style="display:none;padding:20px;">
      <form method="post">
        <input type="hidden" name="ide" value="<?= $ide ?>">
        Clave de borrado: <span><?= $ide ?></span><br>
        Repetir clave de borrado: <input name="aborrar" type="text" class="form-control d-inline-block" style="width:120px;">
        <input type="submit" name="eliminar" value="Confirmar" class="btn btn-light">
      </form>
    </div>
    <div class="card-header pb-3">
      <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('cajaborrar').style.display = document.getElementById('cajaborrar').style.display === 'none' ? 'block' : 'none';">
          <i class="fa fa-trash"></i> Eliminar
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>
```

- [ ] **Step 3: Verificar manualmente**

Crear un casting nuevo desde `castings_ver.php`, comprobar que aparece en `castings_index.php`. Editarlo, comprobar cambio. Borrarlo con la clave correcta.
Expected: `SELECT * FROM casting;` refleja cada operación (alta/edición/borrado).

- [ ] **Step 4: Commit**

```bash
git add admin/castings_index.php admin/castings_ver.php
git commit -m "feat: CRUD de castings en admin"
```

---

### Task 6: Listado y ficha de inscripciones (admin)

**Files:**
- Create: `admin/actores_index.php`
- Create: `admin/actores_ver.php`

**Interfaces:**
- Consumes: `$link`, `comprueba()`, `alerta()` (Task 2), tablas `actor`/`actor_media`/`casting` (Task 1)
- Produces: gestión de estado de inscripción (pendiente/aceptado/rechazado)

- [ ] **Step 1: Crear `admin/actores_index.php`**

```php
<?php
// admin/actores_index.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$casting_id = isset($_GET['casting_id']) ? (int)$_GET['casting_id'] : 0;
$filtro_estado = isset($_GET['estado']) ? mysqli_real_escape_string($link, $_GET['estado']) : '';

$casting_res = mysqli_query($link, "SELECT titulo FROM casting WHERE id=$casting_id");
$casting = mysqli_fetch_assoc($casting_res);

$sql = "SELECT id, nombre, email, telefono, estado, fecha_inscripcion FROM actor WHERE casting_id=$casting_id";
if ($filtro_estado !== '') {
    $sql .= " AND estado='$filtro_estado'";
}
$sql .= " ORDER BY fecha_inscripcion DESC";
$res = mysqli_query($link, $sql);

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <a href="castings_index.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-chevron-circle-left"></i> Regresar</a>
</div>
<div class="col-xl-12">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Inscripciones: <?= htmlspecialchars($casting['titulo'] ?? '') ?></h4></div>
    <div class="card-body">
      <div class="mb-3">
        <a href="?casting_id=<?= $casting_id ?>" class="btn btn-sm btn-outline-secondary">Todos</a>
        <a href="?casting_id=<?= $casting_id ?>&estado=pendiente" class="btn btn-sm btn-outline-warning">Pendientes</a>
        <a href="?casting_id=<?= $casting_id ?>&estado=aceptado" class="btn btn-sm btn-outline-success">Aceptados</a>
        <a href="?casting_id=<?= $casting_id ?>&estado=rechazado" class="btn btn-sm btn-outline-danger">Rechazados</a>
      </div>
      <div class="table-responsive">
        <table class="table text-md-nowrap">
          <thead><tr><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
          <tbody>
          <?php while ($a = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><?= htmlspecialchars($a['nombre']) ?></td>
              <td><?= htmlspecialchars($a['email']) ?></td>
              <td><?= htmlspecialchars($a['telefono']) ?></td>
              <td><?= htmlspecialchars($a['estado']) ?></td>
              <td><?= htmlspecialchars($a['fecha_inscripcion']) ?></td>
              <td><a href="actores_ver.php?id=<?= (int)$a['id'] ?>">Ver ficha</a></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>
```

- [ ] **Step 2: Crear `admin/actores_ver.php`**

```php
<?php
// admin/actores_ver.php
session_start();
require_once __DIR__ . '/../comun/conecta.php';
require_once __DIR__ . '/../comun/general.php';
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
$zona = 0;
comprueba($_SESSION['id'], $zona);

$ide = (int)$_GET['id'];

if (isset($_POST['guardar'])) {
    $estado = mysqli_real_escape_string($link, $_POST['estado']);
    mysqli_query($link, "UPDATE actor SET estado='$estado' WHERE id=$ide");
    alerta('Inscripción', 'Estado actualizado', 2);
    header("Location: actores_ver.php?id=$ide");
    exit;
}

$res = mysqli_query($link, "
  SELECT a.*, c.titulo AS casting_titulo, c.id AS casting_id
  FROM actor a JOIN casting c ON c.id = a.casting_id
  WHERE a.id=$ide
");
$actor = mysqli_fetch_assoc($res);

$media_res = mysqli_query($link, "SELECT tipo, ruta_fichero FROM actor_media WHERE actor_id=$ide");

require __DIR__ . '/../comun/interfaz_cabeza.php';
?>
<div class="col-xl-12">
  <a href="actores_index.php?casting_id=<?= (int)$actor['casting_id'] ?>" class="btn btn-outline-secondary mb-3"><i class="fa fa-chevron-circle-left"></i> Regresar</a>
</div>
<div class="col-xl-12">
  <form method="post">
    <div class="card">
      <div class="card-header pb-0"><h4 class="card-title">Ficha de <?= htmlspecialchars($actor['nombre']) ?></h4></div>
      <div class="card-body table-responsive">
        <table class="table text-md-nowrap">
          <tr><td class="iz"><b>Casting:</b></td><td><?= htmlspecialchars($actor['casting_titulo']) ?></td></tr>
          <tr><td class="iz"><b>Email:</b></td><td><?= htmlspecialchars($actor['email']) ?></td></tr>
          <tr><td class="iz"><b>Teléfono:</b></td><td><?= htmlspecialchars($actor['telefono']) ?></td></tr>
          <tr><td class="iz"><b>Edad:</b></td><td><?= (int)$actor['edad'] ?></td></tr>
          <tr><td class="iz"><b>Altura:</b></td><td><?= htmlspecialchars($actor['altura']) ?> cm</td></tr>
          <tr><td class="iz"><b>Medidas:</b></td><td><?= htmlspecialchars($actor['medidas']) ?></td></tr>
          <tr><td class="iz"><b>Fecha inscripción:</b></td><td><?= htmlspecialchars($actor['fecha_inscripcion']) ?></td></tr>
          <tr><td class="iz"><b>Estado:</b></td><td>
            <select class="form-control" name="estado">
              <option value="pendiente" <?= $actor['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
              <option value="aceptado" <?= $actor['estado'] == 'aceptado' ? 'selected' : '' ?>>Aceptado</option>
              <option value="rechazado" <?= $actor['estado'] == 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
            </select>
          </td></tr>
          <tr><td colspan="2"><input type="submit" name="guardar" value="Guardar" class="btn btn-primary"></td></tr>
        </table>
      </div>
    </div>
  </form>
</div>
<div class="col-xl-12">
  <div class="card">
    <div class="card-header pb-0"><h4 class="card-title">Material</h4></div>
    <div class="card-body">
      <?php while ($m = mysqli_fetch_assoc($media_res)): ?>
        <?php if ($m['tipo'] === 'foto'): ?>
          <img src="/<?= htmlspecialchars($m['ruta_fichero']) ?>" style="max-width:200px;margin:5px;">
        <?php else: ?>
          <video src="/<?= htmlspecialchars($m['ruta_fichero']) ?>" controls style="max-width:400px;margin:5px;"></video>
        <?php endif; ?>
      <?php endwhile; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../comun/interfaz_pie.php'; ?>
```

- [ ] **Step 3: Verificar manualmente**

Sobre un actor insertado a mano por SQL (una fila mínima en `actor` con `casting_id` existente), abrir `actores_ver.php?id=X`, cambiar estado y guardar.
Expected: `SELECT estado FROM actor WHERE id=X;` refleja el nuevo valor.

- [ ] **Step 4: Commit**

```bash
git add admin/actores_index.php admin/actores_ver.php
git commit -m "feat: listado y ficha de inscripciones con cambio de estado"
```

---

### Task 7: Landing pública — listado de castings

**Files:**
- Create: `index.php`
- Create: `assets/css/landing.css`

**Interfaces:**
- Consumes: `$link` (Task 2), tabla `casting` (Task 1)
- Produces: enlaces a `casting_ver.php?id=X` (Task 8)

- [ ] **Step 1: Crear `index.php` en raíz**

```php
<?php
// index.php
require_once __DIR__ . '/comun/conecta.php';

$res = mysqli_query($link, "SELECT id, titulo, tipo, descripcion, fecha_cierre FROM casting WHERE estado='abierto' ORDER BY fecha_apertura DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Castings abiertos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/landing.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
  <h1 class="mb-4">Castings abiertos</h1>
  <div class="row">
    <?php while ($c = mysqli_fetch_assoc($res)): ?>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($c['titulo']) ?></h5>
            <p class="card-subtitle text-muted mb-2"><?= htmlspecialchars($c['tipo']) ?></p>
            <p class="card-text"><?= htmlspecialchars($c['descripcion']) ?></p>
            <p class="card-text"><small>Cierra: <?= htmlspecialchars($c['fecha_cierre']) ?></small></p>
            <a href="casting_ver.php?id=<?= (int)$c['id'] ?>" class="btn btn-primary">Inscribirme</a>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
    <?php if (mysqli_num_rows($res) === 0): ?>
      <p>No hay castings abiertos en este momento.</p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
```

- [ ] **Step 2: Crear `assets/css/landing.css` vacío**

```css
/* estilos propios de la landing pública */
```

- [ ] **Step 3: Verificar manualmente**

Marcar un casting como `abierto` (desde admin o SQL), visitar `index.php`.
Expected: la card del casting aparece con enlace a `casting_ver.php?id=X`. Con todos los castings cerrados, muestra el mensaje "No hay castings abiertos".

- [ ] **Step 4: Commit**

```bash
git add index.php assets/css/landing.css
git commit -m "feat: landing publica con listado de castings abiertos"
```

---

### Task 8: Landing pública — formulario de inscripción

**Files:**
- Create: `casting_ver.php`

**Interfaces:**
- Consumes: `$link` (Task 2), `subir_fichero()` (Task 2), tablas `casting`/`actor`/`actor_media` (Task 1)
- Produces: filas nuevas en `actor` + `actor_media`, consumidas por el dashboard (Task 4) y las vistas de admin (Task 6)

- [ ] **Step 1: Crear `casting_ver.php`**

```php
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
    $edad = (int)($_POST['edad'] ?? 0);
    $altura = (float)($_POST['altura'] ?? 0);
    $medidas = trim($_POST['medidas'] ?? '');

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email no válido.';
    if ($telefono === '') $errores[] = 'El teléfono es obligatorio.';
    if ($edad <= 0 || $edad > 120) $errores[] = 'Edad no válida.';
    if ($altura <= 0) $errores[] = 'Altura no válida.';

    if (empty($errores)) {
        $nombre_e = mysqli_real_escape_string($link, $nombre);
        $email_e = mysqli_real_escape_string($link, $email);
        $telefono_e = mysqli_real_escape_string($link, $telefono);
        $medidas_e = mysqli_real_escape_string($link, $medidas);

        mysqli_query($link, "INSERT INTO actor (casting_id, nombre, email, telefono, edad, altura, medidas)
            VALUES ($casting_id, '$nombre_e', '$email_e', '$telefono_e', $edad, $altura, '$medidas_e')");
        $actor_id = mysqli_insert_id($link);

        $destino = __DIR__ . "/uploads/castings/$casting_id/$actor_id";

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
                    $ruta_rel = 'uploads/castings/' . $casting_id . '/' . $actor_id . '/' . basename($ruta);
                    mysqli_query($link, "INSERT INTO actor_media (actor_id, tipo, ruta_fichero) VALUES ($actor_id, 'foto', '" . mysqli_real_escape_string($link, $ruta_rel) . "')");
                }
            }
        }

        if (!empty($_FILES['video']['name'])) {
            $ruta = subir_fichero($_FILES['video'], $destino, ['video/mp4'], 100 * 1024 * 1024);
            if ($ruta) {
                $ruta_rel = 'uploads/castings/' . $casting_id . '/' . $actor_id . '/' . basename($ruta);
                mysqli_query($link, "INSERT INTO actor_media (actor_id, tipo, ruta_fichero) VALUES ($actor_id, 'video', '" . mysqli_real_escape_string($link, $ruta_rel) . "')");
            }
        }

        $exito = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($casting['titulo']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/landing.css" rel="stylesheet">
</head>
<body>
<div class="container py-5" style="max-width:700px;">
  <a href="index.php">&larr; Volver a castings</a>
  <h1 class="mt-3"><?= htmlspecialchars($casting['titulo']) ?></h1>
  <p><?= htmlspecialchars($casting['descripcion']) ?></p>

  <?php if ($exito): ?>
    <div class="alert alert-success">Inscripción enviada correctamente. Te contactaremos si eres seleccionado/a.</div>
  <?php else: ?>
    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errores as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <div class="mb-3"><label>Nombre</label><input class="form-control" name="nombre" required></div>
      <div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div>
      <div class="mb-3"><label>Teléfono</label><input class="form-control" name="telefono" required></div>
      <div class="mb-3"><label>Edad</label><input class="form-control" type="number" name="edad" required></div>
      <div class="mb-3"><label>Altura (cm)</label><input class="form-control" type="number" step="0.1" name="altura" required></div>
      <div class="mb-3"><label>Medidas</label><input class="form-control" name="medidas"></div>
      <div class="mb-3"><label>Fotos (jpg/png, máx 5MB cada una)</label><input class="form-control" type="file" name="fotos[]" accept="image/jpeg,image/png" multiple></div>
      <div class="mb-3"><label>Vídeo (mp4, máx 100MB)</label><input class="form-control" type="file" name="video" accept="video/mp4"></div>
      <button class="btn btn-primary" type="submit">Enviar inscripción</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
```

- [ ] **Step 2: Verificar manualmente**

Con un casting abierto, visitar `casting_ver.php?id=X`, enviar el formulario con datos válidos y una foto jpg pequeña.
Expected: mensaje de éxito en pantalla; `SELECT * FROM actor WHERE casting_id=X;` muestra la fila nueva; `SELECT * FROM actor_media WHERE actor_id=<nuevo_id>;` muestra la foto; el fichero existe en `uploads/castings/X/<actor_id>/`.

Repetir enviando un fichero no permitido (ej. .txt como foto).
Expected: la inscripción se guarda (los datos son válidos) pero no se crea fila en `actor_media` para ese fichero — `subir_fichero()` devuelve `false` y el bloque lo ignora silenciosamente.

- [ ] **Step 3: Commit**

```bash
git add casting_ver.php
git commit -m "feat: formulario de inscripcion de actores con subida de fotos y video"
```

---

### Task 9: .gitignore y arranque del repo

**Files:**
- Create: `.gitignore`
- Create: `comun/config.php.example`

**Interfaces:** ninguna — tarea de housekeeping, no afecta a otras tareas.

- [ ] **Step 1: Crear `.gitignore`**

```
uploads/
comun/config.php
```

- [ ] **Step 2: Crear `comun/config.php.example`**

```php
<?php
// comun/config.php.example -- copiar a comun/config.php y rellenar credenciales reales
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');
```

- [ ] **Step 3: Verificar manualmente**

`git status` tras crear un fichero dentro de `uploads/` no debe listarlo.
Expected: `uploads/` y `comun/config.php` ignorados; `comun/config.php.example` sí versionado.

- [ ] **Step 4: Commit**

```bash
git add .gitignore comun/config.php.example
git commit -m "chore: gitignore y plantilla de configuracion"
```

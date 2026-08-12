# Castings — estado y convenciones del proyecto

PHP legacy plano + mysqli, sin framework. Ver specs/plan en `docs/superpowers/`.

## Entorno local
- XAMPP en `C:\xampp`, junction `C:\xampp\htdocs\castings` -> esta carpeta.
- BD `castings` en MySQL local (root sin clave). `comun/config.php` (fuera de git) tiene las credenciales.
- Landing: http://localhost/castings/ — Admin: http://localhost/castings/admin/login.php

## Convenciones propias de ESTE proyecto (se desvían del molde php-legacy estándar)
- **Borrado en vistas admin:** NO se usa el patrón "caja roja + repetir clave de borrado".
  Se usa un botón `Eliminar` con `onsubmit="return confirm('...');"` en el propio form.
  Ver `admin/castings_ver.php` y `admin/usuarios_ver.php` como molde.
- **Sidebar admin:** `comun/admin_sidebar.php`, incluido desde `comun/interfaz_cabeza.php`.
  Requiere `$pagina_actual` (definida en `interfaz_cabeza.php` vía `basename($_SERVER['PHP_SELF'])`).
  Al añadir una vista nueva al menú, añadir su entrada activa en `admin_sidebar.php`.
- **Landing pública** (`index.php`, `casting_ver.php`) usa CSS propio (`assets/css/landing.css`,
  `assets/css/ficha-form.css`), no Bootstrap — dirección visual "cartel de festival de cine",
  verde bosque + crema + dorado, tipografía Fraunces + Space Mono. El admin sigue Bootstrap
  con acento de la misma paleta (`assets/css/estilo.css`).
- Rutas de assets/uploads son siempre RELATIVAS (nunca empiezan por `/`) — el proyecto puede
  vivir en una subcarpeta (`/castings/`), no en la raíz del dominio.

## Modelo de datos: actor vs inscripcion
- `actor` = la PERSONA, identificada de forma única por `email` (UNIQUE). Guarda nombre,
  telefono, `fecha_nacimiento` (DATE, no edad en años), altura, medidas — los datos MÁS
  RECIENTES de esa persona (se sobrescriben si vuelve a inscribirse con el mismo email).
  La edad se calcula al vuelo con `calcular_edad()` (`comun/general.php`), nunca se guarda.
- `inscripcion` = una persona presentándose a UN casting concreto (`actor_id` + `casting_id`,
  UNIQUE juntos — no se puede duplicar inscripción al mismo casting). Aquí vive `estado`
  (pendiente/aceptado/rechazado) y `fecha_inscripcion`.
- `actor_media` cuelga de `inscripcion_id`, no de `actor_id` — el material es por inscripción
  (cada casting puede pedir fotos/vídeo distintos).
- En `casting_ver.php`, al llegar un email ya existente se actualiza `actor` y se reutiliza su
  id; si ya existe inscripción a ESE casting, se avisa en vez de duplicar.
- `admin/actores_index.php` lista PERSONAS (una fila por actor, agrupado), no inscripciones —
  el filtro de estado es "tiene alguna inscripción con ese estado". El parámetro `id` en
  `admin/actores_ver.php` es `actor_id`; la ficha muestra TODAS sus inscripciones (una por
  casting) con su propio selector de estado y su propio material adjunto.

## Módulos existentes
- Landing + inscripción de actores (con subida de fotos/vídeo a `uploads/`)
- Admin: dashboard, CRUD castings, listado+ficha inscripciones (con estado, marca visual si
  la persona está en más de un casting), CRUD usuarios administradores, configuración del
  sitio (tabla `config` clave-valor)

## Pendiente / próximos pasos
- Cambiar la clave del admin inicial (`admin` / `cambiar-esta-clave` en `schema.sql`).
- Sin repo git todavía (decisión explícita del usuario).

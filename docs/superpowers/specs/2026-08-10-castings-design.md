# Plataforma de castings — landing + admin

## Contexto
Aplicación PHP legacy (sin framework, estilo haboob) para publicar castings de cine/cortos
y recibir inscripciones de actores. Proyecto nuevo, carpeta vacía.

## Alcance
- Landing pública: lista de castings abiertos + formulario de inscripción con ficha completa
  (datos + fotos + vídeo subidos al servidor).
- Admin (un solo usuario administrador): CRUD de castings, listado de inscripciones por
  casting, ficha de actor con cambio de estado (pendiente/aceptado/rechazado).
- Fuera de alcance: roles múltiples de admin, notificaciones por email, CDN/almacenamiento
  externo, exportación a Excel/PDF (se puede añadir después si se pide).

## Stack
PHP plano + mysqli + Bootstrap, sin framework, sin build step. Mismas convenciones que
proyectos haboob existentes, adaptando nombres:

| Carpeta | Rol |
|---|---|
| `comun/` | `conecta.php`, `general.php`, `interfaz_cabeza.php`, `interfaz_pie.php` |
| `admin/` | Vistas del panel de administración (equivalente a `haboob/`, mismo patrón interno) |
| raíz / `public/` | Landing pública (sin login) |
| `uploads/castings/{casting_id}/{actor_id}/` | Fotos y vídeo subidos por los actores |

## Modelo de datos

```sql
casting
  id, titulo, tipo, descripcion, fecha_apertura, fecha_cierre,
  estado ENUM('abierto','cerrado')

actor
  id, casting_id FK -> casting.id,
  nombre, email, telefono, edad, altura, medidas,
  fecha_inscripcion, estado ENUM('pendiente','aceptado','rechazado')

actor_media
  id, actor_id FK -> actor.id,
  tipo ENUM('foto','video'), ruta_fichero

usuario
  (tabla estándar legacy: usu_usuario, usu_clave MD5, usu_derechos, usu_reintento, ...)
```

## Landing pública
- `index.php` — lista `casting` con `estado = 'abierto'`.
- `casting_ver.php?id=X` — detalle del casting + formulario de inscripción.
  - Campos: nombre, email, teléfono, edad, altura, medidas, fotos (varias), vídeo.
  - Validación de subida: tipo MIME permitido (jpg/png para foto, mp4 para vídeo) y tamaño
    máximo por fichero — frontera de seguridad, no se omite.
  - Inserta en `actor` + una fila por fichero en `actor_media`.
  - Guarda ficheros en `uploads/castings/{casting_id}/{actor_id}/`.

## Admin (login legacy: usuario/MD5, sesión, `comprueba()`)
- `admin/index.php` — dashboard: castings abiertos, nº de inscripciones pendientes por
  casting, últimas inscripciones recibidas (enlace directo a la ficha del actor). Pantalla
  de entrada tras login.
- `admin/castings_index.php` — listado de castings (molde tipo `productos_index.php`).
- `admin/castings_ver.php` — alta/edición/borrado de casting (molde tipo `productos_ver.php`:
  card + tabla + botón Guardar + caja de borrado roja con clave de confirmación).
- `admin/actores_index.php?casting_id=X` — listado de inscripciones de un casting, filtro por
  estado.
- `admin/actores_ver.php?id=X` — ficha de actor: datos, galería de fotos, reproductor de
  vídeo, selector de estado (pendiente/aceptado/rechazado) que se guarda con botón Guardar.

## Decisiones deliberadas (YAGNI)
- Un solo admin, sin roles ni permisos granulares más allá del login estándar.
- Sin cola de subida ni procesamiento de vídeo (transcoding): se guarda tal cual se sube.
- Almacenamiento en disco local del servidor, no CDN — volumen esperado bajo.
- Sin notificaciones por email al cambiar estado — se puede añadir luego si se pide.

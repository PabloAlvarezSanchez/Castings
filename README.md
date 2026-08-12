# Castings

Proyecto PHP + MySQL para una landing de castings con panel de administración.

## Descripción

- Landing pública con inscripción de actores.
- Administración de castings, usuarios y fichas de actores.
- Subida de fotos y vídeos por inscripción.
- Arquitectura PHP legacy plana usando `mysqli`.

## Requisitos

- Windows
- XAMPP instalado en `C:\xampp`
- Apache y MySQL activos
- Base de datos MySQL `castings`

## Instalación local

1. Copia `comun/config.php.example` a `comun/config.php`.
2. Crea la base de datos `castings` en MySQL.
3. Importa `schema.sql` en la base de datos.
4. Asegúrate de que el proyecto esté en `C:\xampp\htdocs\castings` o que Apache apunte a la carpeta del proyecto.

## Rutas principales

- Landing pública: `http://localhost/castings/`
- Página de casting: `http://localhost/castings/casting_ver.php`
- Admin login: `http://localhost/castings/admin/login.php`

## Convenciones del proyecto

- El admin usa Bootstrap y archivos CSS en `assets/css/estilo.css`.
- La landing pública usa CSS propio en `assets/css/landing.css` y `assets/css/ficha-form.css`.
- Las rutas de subida en `uploads/` son relativas para que el proyecto pueda vivir en una subcarpeta.
- La configuración sensible (`comun/config.php`) no está versionada.

## Datos y modelo

- `actor` guarda los datos personales de la persona.
- `inscripcion` guarda su participación en un casting.
- `actor_media` está relacionado con la inscripción, no con el actor.
- El correo electrónico del actor es único y se utiliza para actualizar registros existentes.

## Notas importantes

- Revisa `schema.sql` para la contraseña inicial del admin y cámbiala si es necesario.
- Si no puedes acceder al admin, asegúrate de haber importado correctamente la base de datos y de haber copiado `comun/config.php`.

## Repositorio GitHub

https://github.com/PabloAlvarezSanchez/Castings.git

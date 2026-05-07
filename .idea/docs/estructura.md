# Estructura del Proyecto

Este proyecto quedo organizado para crecer sin mezclar toda la logica en los HTML.

## Carpetas principales

- `assets/css/`
  Aqui vive el CSS compartido del sistema.

- `assets/js/limpieza/`
  Aqui esta la logica del modulo de limpieza.
  - `index.js`: control de habitaciones, inventario y registro de limpieza.
  - `asignacion.js`: asignacion de tareas de limpieza.

- `assets/js/empleados/`
  Aqui esta la logica del modulo de empleados.
  - `empleados.js`: registro, edicion y estadisticas del personal.

- `imagenes_empleados/`
  Fotos o avatares del personal de limpieza.

- `php/`
  Endpoints y servicios del backend.
  - `conexion.php`: conexion a MySQL.
  - `empleados_schema.php`: asegura la estructura base de empleados.
  - `guardar_*.php`: guardado de datos.
  - `obtener_*.php`: lectura de datos.

## Pantallas

- `index.html`
  Pantalla principal del modulo de limpieza.

- `asignacion_limpieza.html`
  Pantalla para asignar habitaciones a empleados.

- `empleados_limpieza.html`
  Pantalla para administrar empleados y ver sus estadisticas.

## Regla para crecer el proyecto

Si se agrega un modulo nuevo, seguir esta idea:

1. Crear la pantalla HTML del modulo.
2. Crear su JS dentro de `assets/js/<modulo>/`.
3. Reusar `assets/css/estilos_limpieza.css` o dividirlo despues por componentes.
4. Crear endpoints PHP propios si el modulo necesita guardar datos.

## Siguiente paso recomendado

La proxima mejora natural es separar `php/` por carpetas:

- `php/config/`
- `php/limpieza/`
- `php/empleados/`
- `php/helpers/`

Eso se puede hacer en una segunda fase para no romper rutas ahora.

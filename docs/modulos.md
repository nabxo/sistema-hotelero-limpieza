# Modulos Actuales

## Limpieza

Responsable de:

- estado de habitaciones
- cambio de estados
- inventario de limpieza
- registro de limpieza
- asignacion de tareas

Archivos principales:

- `index.html`
- `asignacion_limpieza.html`
- `assets/js/limpieza/index.js`
- `assets/js/limpieza/asignacion.js`

## Empleados

Responsable de:

- registrar empleados
- editar datos del personal
- guardar fotos
- ver estadisticas individuales

Archivos principales:

- `empleados_limpieza.html`
- `assets/js/empleados/empleados.js`
- `php/obtener_empleados.php`
- `php/guardar_empleado.php`

## Base de datos

Tablas principales:

- `habitaciones`
- `inventario`
- `empleados_limpieza`
- `asignaciones_limpieza`
- `registros_limpieza`

Relacion importante:

- `asignaciones_limpieza.empleado_id`
- `registros_limpieza.empleado_id`

Eso permite que las estadisticas futuras se calculen por empleado real y no solo por nombre escrito.

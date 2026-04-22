# Sistema Hotelero de Limpieza

Proyecto para manejar habitaciones, inventario, asignaciones y registros de limpieza.

## Tecnologias
- HTML
- CSS
- JavaScript
- PHP
- MySQL
- XAMPP

## Archivos mas importantes
- `index.html`: habitaciones, inventario y registro de limpieza
- `asignacion_limpieza.html`: asignar personal de limpieza
- `php/conexion.php`: conexion con MySQL
- `php/guardar_asignacion.php`: guarda asignaciones
- `php/guardar_registro.php`: guarda registros
- `php/obtener_datos.php`: trae datos de la base
- `php/sincronizar_datos.php`: pasa datos viejos de localStorage a MySQL si hace falta
- `base_datos_sistema_hotelero_limpieza.sql`: crea la base y las tablas

## Como funciona
1. El usuario llena un formulario.
2. JavaScript valida y guarda localmente.
3. Luego manda los datos a PHP con `fetch`.
4. PHP recibe eso y lo guarda en MySQL.

## Por que hay localStorage y MySQL
Se dejo `localStorage` porque asi trabajaba el proyecto al principio.  
MySQL se agrego para que los datos no se pierdan al cerrar el navegador.

## Como abrirlo
1. Enciende Apache y MySQL en XAMPP.
2. Importa `base_datos_sistema_hotelero_limpieza.sql` en phpMyAdmin.
3. Abre:
   - `http://localhost/sistema-hotelero-limpieza/index.html`
   - `http://localhost/sistema-hotelero-limpieza/asignacion_limpieza.html`

## Si algo falla
- Revisa que la pagina este abierta desde `localhost`
- Revisa que MySQL este encendido
- Revisa `php/conexion.php`
- Si sale `Failed to fetch`, casi siempre es porque se abrio el HTML como archivo local

## Idea rapida para exponer
“El sistema ya funcionaba en JavaScript con localStorage. Lo que hice fue conectarlo a MySQL con PHP para que tambien guardara los datos en la base. No cambie el diseño, solo agregue la parte de persistencia.”

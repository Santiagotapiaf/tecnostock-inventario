# TecnoStock — Sistema de inventario

Proyecto del Taller de Sistemas de Información (Caso 1). Aplicación web para gestionar el catálogo de productos y los movimientos de inventario de TecnoStock.

**Autor:** Santiago Tapia — UTEM

---

## 1. Problema

TecnoStock comercializa accesorios tecnológicos y registra sus productos en una planilla de cálculo. Esto ha provocado tres problemas concretos:

- Códigos de producto duplicados.
- Errores en las cantidades disponibles.
- Poca claridad sobre el stock real en cada momento.

Se requiere una aplicación web sencilla que centralice la información, mantenga actualizado el catálogo y conserve la integridad de las existencias.

## 2. Alcance del MVP

**Incluye**

- Inicio de sesión de un usuario previamente registrado.
- Registro, consulta, búsqueda, edición y desactivación de productos.
- Registro de entradas y salidas de existencias.
- Actualización automática del stock tras cada movimiento válido.
- Alerta visual cuando el stock actual es menor que el stock mínimo.

**No incluye**

- Registro público de usuarios ni múltiples roles o permisos.
- Reportes avanzados, facturación o integración con ventas.
- Frameworks de frontend o backend (el enunciado pide una solución sin frameworks complejos).

**Justificación:** el MVP se enfoca en el flujo crítico de registro, control y actualización de stock, que es la causa directa de los errores detectados en la planilla, y deja fuera funciones administrativas secundarias que no inciden en el problema central del caso.

## 3. Actor

| Actor | Responsabilidad |
|---|---|
| Encargado de inventario | Administra productos y movimientos de existencias. Es el único usuario del sistema. |

## 4. Requerimientos funcionales

| N° | Requerimiento |
|---|---|
| RF1 | Iniciar sesión con un usuario previamente registrado. |
| RF2 | Registrar productos con código, nombre, categoría, descripción, precio, stock actual y stock mínimo. |
| RF3 | Consultar el listado de productos. |
| RF4 | Buscar productos por nombre, código o categoría. |
| RF5 | Modificar precio, descripción, categoría y cantidades de un producto. |
| RF6 | Desactivar productos que ya no se comercializan. |
| RF7 | Registrar entradas y salidas de existencias. |
| RF8 | Actualizar automáticamente el stock tras cada movimiento válido. |
| RF9 | Mostrar una alerta cuando el stock actual sea menor que el stock mínimo. |

## 5. Reglas de negocio

| N° | Regla |
|---|---|
| RN1 | El código de producto no puede repetirse. |
| RN2 | El precio, el stock actual, el stock mínimo y la cantidad de un movimiento no pueden ser negativos. |
| RN3 | Todo movimiento registra fecha, tipo, cantidad, producto y usuario responsable. |
| RN4 | Una salida no puede dejar el stock bajo cero. |
| RN5 | Un producto con movimientos registrados se desactiva; no se elimina físicamente. |
| RN6 | El stock se actualiza automáticamente después de cada entrada o salida válida. |

## 6. Tecnologías

| Propósito | Herramienta |
|---|---|
| Servidor local | XAMPP (Apache 2.4, PHP 8.2, MariaDB 10.4 compatible con MySQL) |
| Base de datos | MySQL / MariaDB administrada con phpMyAdmin |
| Servidor | PHP 8 con PDO y consultas preparadas |
| Interfaz | HTML5 y Bootstrap 5.3 |
| Validaciones | JavaScript (cliente) y PHP (servidor) |
| Editor y versionamiento | Visual Studio Code, Git y GitHub |

## 7. Modelo de datos

| Tabla | Campos principales |
|---|---|
| `usuario` | id_usuario (PK), nombre, correo (único), clave (hash), estado |
| `categoria` | id_categoria (PK), nombre, descripcion, estado |
| `producto` | id_producto (PK), codigo (único), nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria (FK) |
| `movimiento` | id_movimiento (PK), fecha_hora, tipo (ENTRADA/SALIDA), cantidad, observacion, id_producto (FK), id_usuario (FK) |

**Relaciones**

- Una categoría tiene muchos productos (`producto.id_categoria`).
- Un producto tiene muchos movimientos (`movimiento.id_producto`).
- Un usuario registra muchos movimientos (`movimiento.id_usuario`).

**Restricciones de integridad**

- `codigo` único en producto (RN1) y `correo` único en usuario.
- `CHECK` para que precio, stock actual y stock mínimo no sean negativos, y para que la cantidad de un movimiento sea mayor que cero (RN2).
- `ENUM('ENTRADA','SALIDA')` para el tipo de movimiento.
- Claves foráneas entre producto, categoría, movimiento y usuario.
- Campo `estado` en usuario, categoría y producto para desactivar sin eliminar (RN5).

## 8. Decisiones de diseño

- **Baja lógica:** desactivar un producto cambia `estado` a 0. Nunca se ejecuta `DELETE`, por lo que el historial de movimientos siempre conserva su producto.
- **Movimientos atómicos:** al registrar una entrada o salida, el `INSERT` en `movimiento` y el `UPDATE` del stock ocurren dentro de una transacción PDO. Si algo falla, se hace `rollBack()` y no queda ningún cambio a medias. La fila del producto se bloquea con `SELECT ... FOR UPDATE` para evitar lecturas simultáneas del mismo stock.
- **Doble validación:** JavaScript da respuesta inmediata en el navegador y PHP repite todas las reglas en el servidor, de modo que los datos inválidos se rechazan aunque se desactive JavaScript.
- **Seguridad básica:** consultas preparadas en todas las operaciones, claves con `password_hash()` y `password_verify()`, salida escapada con `htmlspecialchars()` y páginas protegidas por sesión.
- **Edición de cantidades:** el formulario de edición permite corregir el stock directamente, como indica el enunciado ("modificar las cantidades"). Las entradas y salidas del día a día se registran como movimientos.

## 9. Trazabilidad

| Requerimiento / regla | Implementación | Cómo verificarlo |
|---|---|---|
| RF1 | `login.php`, tabla `usuario`, `password_verify()` | Iniciar sesión con el usuario de prueba |
| RF2, RN1, RN2 | `productos/crear.php`, `js/validaciones.js`, restricciones `UNIQUE` y `CHECK` | Crear un producto; probar código repetido y valores negativos |
| RF3, RF4 | `index.php` (listado y buscador) | Buscar por nombre, código o categoría |
| RF5 | `productos/editar.php` | Editar un producto existente |
| RF6, RN5 | `productos/desactivar.php` (`UPDATE estado = 0`) | Desactivar un producto y comprobar que sale del listado |
| RF7, RN3 | `movimientos/registrar.php`, tabla `movimiento` | Registrar una entrada y una salida |
| RF8, RN6 | Transacción PDO en `movimientos/registrar.php` | Ver cómo cambia el stock tras el movimiento |
| RN4 | `movimientos/registrar.php` y `js/validaciones.js` | Intentar una salida mayor al stock disponible |
| RF9 | `index.php` (`stock_actual < stock_minimo`) | Producto CAB-010 resaltado en rojo con la etiqueta "Stock bajo" |

## 10. Pruebas

Datos de prueba: los que carga `database.sql` (1 usuario, 4 categorías y 5 productos, de los cuales CAB-010 tiene stock bajo).

| N° | Caso | Resultado esperado | Resultado obtenido |
|---|---|---|---|
| P1 | Iniciar sesión con `admin@tecnostock.cl` / `123456` | Acceso al listado con el nombre del usuario | OK |
| P2 | Abrir `index.php`, `crear.php`, `editar.php` y `registrar.php` sin sesión | Redirección a `login.php` | OK |
| P3 | Abrir `editar.php?id=999` (producto inexistente) | Vuelve al listado sin errores de PHP | OK |
| P4 | Crear producto con datos válidos | Aparece en el listado con mensaje de confirmación | OK |
| P5 | Crear producto con un código ya existente | Mensaje "Ya existe un producto con ese código." | OK |
| P6 | Producto con stock 2 y mínimo 5 (CAB-010) | Fila roja y etiqueta "Stock bajo" | OK |
| P7 | Editar el stock de 2 a 10 | Se quita la alerta y aparece mensaje de actualización | OK |
| P8 | Desactivar un producto | Desaparece del listado y aparece mensaje de confirmación | OK |
| P9 | Salida de 5 unidades con stock 20 | "Movimiento registrado. Nuevo stock: 15." | OK |
| P10 | Salida de 100 unidades con stock 15 | "No hay stock suficiente. Stock actual: 15." y el stock no cambia | OK |
| P11 | Entrada de 3 unidades con stock 30 | Nuevo stock 33 | OK |
| P12 | Enviar "Nuevo producto" con todos los campos vacíos | Mensajes de error en cada campo, sin recargar la página | OK |
| P13 | Escribir un precio negativo (-5) | Mensaje de error en el campo | OK |
| P14 | Salida mayor al stock desde el formulario de movimientos | Bloqueada en el navegador antes de enviar | OK |
| P15 | Eliminar la base y reconstruirla con `database.sql` | "9 consultas ejecutadas" y el sistema funciona con los datos de prueba | OK |
| P16 | Iniciar sesión con clave incorrecta | Mensaje "Correo o clave incorrectos." | OK |
| P17 | Crear producto con precio negativo con JavaScript desactivado | El servidor (PHP) rechaza el dato con su mensaje de error | OK |

## 11. Cómo ejecutar el proyecto

1. Instalar XAMPP e iniciar **Apache** y **MySQL** desde el panel de control.
2. Copiar la carpeta del proyecto a `C:\xampp\htdocs\tecnostock`.
3. Abrir `http://localhost/phpmyadmin/`, ir a la pestaña **Importar**, seleccionar `database.sql` y presionar **Continuar**. Esto crea la base `tecnostock` con sus tablas y datos de prueba.
4. Revisar `config.php` (por defecto usa el usuario `root` sin clave, que es la configuración estándar de XAMPP).
5. Abrir `http://localhost/tecnostock/login.php`.

**Usuario de prueba:** `admin@tecnostock.cl` — **clave:** `123456`

Bootstrap se carga desde un CDN, por lo que se necesita conexión a internet para ver los estilos.

## 12. Estructura del proyecto

| Archivo | Función |
|---|---|
| `config.php` | Conexión PDO a la base de datos |
| `login.php`, `logout.php` | Inicio y cierre de sesión |
| `index.php` | Listado de productos, búsqueda y alerta de stock bajo |
| `productos/crear.php` | Alta de productos |
| `productos/editar.php` | Edición de productos |
| `productos/desactivar.php` | Desactivación (baja lógica) |
| `movimientos/registrar.php` | Entradas y salidas de stock |
| `js/validaciones.js` | Validaciones del lado del cliente |
| `database.sql` | Script de la base de datos con datos de prueba |

<?php
session_start();
require '../config.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$exito = '';

// Traer productos activos para el selector
$productos = $pdo->query("SELECT id_producto, codigo, nombre, stock_actual FROM producto WHERE estado = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto  = $_POST['id_producto'] ?? '';
    $tipo        = $_POST['tipo'] ?? '';
    $cantidad    = $_POST['cantidad'] ?? '';
    $observacion = trim($_POST['observacion'] ?? '');
    $idUsuario   = $_SESSION['id_usuario'];

    if ($idProducto === '' || $tipo === '' || $cantidad === '') {
        $error = 'Debes seleccionar un producto, un tipo de movimiento y la cantidad.';
    } elseif (!ctype_digit((string)$cantidad) || $cantidad <= 0) {
        $error = 'La cantidad debe ser un número mayor a cero.';
    } elseif (!in_array($tipo, ['ENTRADA', 'SALIDA'])) {
        $error = 'Tipo de movimiento inválido.';
    } else {
        try {
            $pdo->beginTransaction();

            // Bloquea la fila del producto para evitar condiciones de carrera
            $stmtProducto = $pdo->prepare("SELECT stock_actual FROM producto WHERE id_producto = ? FOR UPDATE");
            $stmtProducto->execute([$idProducto]);
            $producto = $stmtProducto->fetch();

            if (!$producto) {
                throw new Exception('El producto seleccionado no existe.');
            }

            $stockActual = $producto['stock_actual'];

            if ($tipo === 'SALIDA' && $cantidad > $stockActual) {
                // RN4: una salida no puede dejar el stock en negativo
                throw new Exception("No hay stock suficiente. Stock actual: $stockActual.");
            }

            $nuevoStock = $tipo === 'ENTRADA'
                ? $stockActual + $cantidad
                : $stockActual - $cantidad;

            // 1. Registrar el movimiento
            $stmtMov = $pdo->prepare("INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario)
                                       VALUES (?, ?, ?, ?, ?)");
            $stmtMov->execute([$tipo, $cantidad, $observacion, $idProducto, $idUsuario]);

            // 2. Actualizar el stock del producto
            $stmtUpdate = $pdo->prepare("UPDATE producto SET stock_actual = ? WHERE id_producto = ?");
            $stmtUpdate->execute([$nuevoStock, $idProducto]);

            $pdo->commit();

            $exito = "Movimiento registrado. Nuevo stock: $nuevoStock.";

            // Refrescar la lista de productos para mostrar el stock actualizado
            $productos = $pdo->query("SELECT id_producto, codigo, nombre, stock_actual FROM producto WHERE estado = 1 ORDER BY nombre")->fetchAll();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar movimiento - TecnoStock</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Registrar movimiento</h3>
        <a href="../index.php" class="btn btn-outline-secondary btn-sm">← Volver al listado</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($exito): ?>
        <div class="alert alert-success"><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="mb-3">
            <label class="form-label">Producto *</label>
            <select name="id_producto" class="form-select" required>
                <option value="">Selecciona un producto</option>
                <?php foreach ($productos as $p): ?>
                    <option value="<?= $p['id_producto'] ?>">
                        <?= htmlspecialchars($p['codigo'] . ' - ' . $p['nombre']) ?> (Stock: <?= $p['stock_actual'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Tipo de movimiento *</label>
            <select name="tipo" class="form-select" required>
                <option value="">Selecciona un tipo</option>
                <option value="ENTRADA">Entrada</option>
                <option value="SALIDA">Salida</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Cantidad *</label>
            <input type="number" name="cantidad" class="form-control" min="1" step="1" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Observación</label>
            <input type="text" name="observacion" class="form-control" placeholder="Opcional">
        </div>

        <button type="submit" class="btn btn-primary">Registrar movimiento</button>
    </form>
</div>
</body>
</html>
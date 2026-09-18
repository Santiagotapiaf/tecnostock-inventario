<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

// Búsqueda (opcional, por nombre, código o categoría)
$busqueda = trim($_GET['busqueda'] ?? '');

$sql = "SELECT p.id_producto, p.codigo, p.nombre, p.precio, p.stock_actual, p.stock_minimo, c.nombre AS categoria
        FROM producto p
        JOIN categoria c ON p.id_categoria = c.id_categoria
        WHERE p.estado = 1";

$params = [];

if ($busqueda !== '') {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)";
    $like = "%$busqueda%";
    $params = [$like, $like, $like];
}

$sql .= " ORDER BY p.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - TecnoStock</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>TecnoStock — Productos</h3>
        <div>
            <span class="me-3">Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="logout.php" class="btn btn-outline-secondary btn-sm">Cerrar sesión</a>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3">
    <form method="GET" class="d-flex" style="max-width: 400px; width: 100%;">
        <input type="text" name="busqueda" class="form-control me-2"
               placeholder="Buscar por nombre, código o categoría"
               value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit" class="btn btn-secondary">Buscar</button>
    </form>
    <div>
        <a href="movimientos/registrar.php" class="btn btn-outline-primary me-2">Registrar movimiento</a>
        <a href="productos/crear.php" class="btn btn-primary">+ Nuevo producto</a>
    </div>
</div>

    <table class="table table-bordered bg-white">
        <thead class="table-light">
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($productos)): ?>
                <tr><td colspan="6" class="text-center text-muted">No se encontraron productos.</td></tr>
            <?php else: foreach ($productos as $p): ?>
                <?php $stockBajo = $p['stock_actual'] < $p['stock_minimo']; ?>
                <tr class="<?= $stockBajo ? 'table-danger' : '' ?>">
                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                    <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
                    <td>
                        <?= $p['stock_actual'] ?>
                        <?php if ($stockBajo): ?>
                            <span class="badge bg-danger">Stock bajo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="productos/editar.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                        <a href="productos/desactivar.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('¿Desactivar este producto?')">Desactivar</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

</div>
</body>
</html>
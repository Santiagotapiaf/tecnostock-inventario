<?php
session_start();
require '../config.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

$error = '';

// Traer categorías para el selector
$categorias = $pdo->query("SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = trim($_POST['codigo'] ?? '');
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = $_POST['precio'] ?? '';
    $stockActual  = $_POST['stock_actual'] ?? '';
    $stockMinimo  = $_POST['stock_minimo'] ?? '';
    $idCategoria  = $_POST['id_categoria'] ?? '';

    // Validación en servidor (nunca confiar solo en el navegador)
    if ($codigo === '' || $nombre === '' || $precio === '' || $stockActual === '' || $stockMinimo === '' || $idCategoria === '') {
        $error = 'Todos los campos obligatorios deben estar completos.';
    } elseif (!is_numeric($precio) || $precio < 0) {
        $error = 'El precio no puede ser negativo.';
    } elseif (!ctype_digit((string)$stockActual) || $stockActual < 0) {
        $error = 'El stock actual no puede ser negativo.';
    } elseif (!ctype_digit((string)$stockMinimo) || $stockMinimo < 0) {
        $error = 'El stock mínimo no puede ser negativo.';
    } else {
        // Verificar que el código no esté repetido (RN1)
        $check = $pdo->prepare("SELECT id_producto FROM producto WHERE codigo = ?");
        $check->execute([$codigo]);

        if ($check->fetch()) {
            $error = 'Ya existe un producto con ese código.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria)
                                    VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stockActual, $stockMinimo, $idCategoria]);

            header('Location: ../index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo producto - TecnoStock</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 600px;">
    <h3 class="mb-4">Nuevo producto</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

       <form method="POST" novalidate data-validar="producto">
        <div class="mb-3">
            <label class="form-label">Código *</label>
            <input type="text" name="codigo" class="form-control" required
                   value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Nombre *</label>
            <input type="text" name="nombre" class="form-control" required
                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Categoría *</label>
            <select name="id_categoria" class="form-select" required>
                <option value="">Selecciona una categoría</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>">
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col mb-3">
                <label class="form-label">Precio *</label>
                <input type="number" name="precio" class="form-control" min="0" step="1" required
                       value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>">
            </div>
            <div class="col mb-3">
                <label class="form-label">Stock actual *</label>
                <input type="number" name="stock_actual" class="form-control" min="0" step="1" required
                       value="<?= htmlspecialchars($_POST['stock_actual'] ?? '0') ?>">
            </div>
            <div class="col mb-3">
                <label class="form-label">Stock mínimo *</label>
                <input type="number" name="stock_minimo" class="form-control" min="0" step="1" required
                       value="<?= htmlspecialchars($_POST['stock_minimo'] ?? '0') ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="../index.php" class="btn btn-outline-secondary">Cancelar</a>
    </form>
</div>
   <script src="../js/validaciones.js"></script>
</body>
</html>
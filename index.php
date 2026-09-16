<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TecnoStock</title>
</head>
<body>
    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
    <p>Tu sesión está activa. Aquí irá el listado de productos.</p>
    <a href="logout.php">Cerrar sesión</a>
</body>
</html>
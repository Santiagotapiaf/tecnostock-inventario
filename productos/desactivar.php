<?php
session_start();
require '../config.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Nunca se borra el registro (DELETE), solo se cambia el estado (RN5)
    $stmt = $pdo->prepare("UPDATE producto SET estado = 0 WHERE id_producto = ?");
    $stmt->execute([$id]);
}

header('Location: ../index.php');
exit;
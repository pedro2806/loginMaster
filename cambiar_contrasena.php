<?php
session_start();
include '../incidencias/conn.php';

$id_usuario = $_COOKIE['id_usuario'];

$contrasena_actual = $_POST['contrasena_actual'] ?? '';
$nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar que la nueva contraseña y confirmación coincidan
    if ($nueva_contrasena !== $confirmar_contrasena) {
        echo "Las contraseñas no coinciden.";
        exit;
    }

    // Consultar la contraseña actual en la BD
    $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id_usuario = $id_usuario");
    $stmt->execute();
    $stmt->bind_result($password_en_bd);
    $stmt->fetch();
    $stmt->close();

    // Verificar la contraseña actual (sin hash)
    if ($contrasena_actual !== $password_en_bd) {
        echo "La contraseña actual es incorrecta.";
        exit;
    }

    // Actualizar la contraseña nueva en la BD (sin hash)
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = $id_usuario");
    $stmt->bind_param("s", $nueva_contrasena);

    if ($stmt->execute()) {
        echo "Contraseña actualizada correctamente.";
    } else {
        echo "Error al actualizar la contraseña.";
    }

    $stmt->close();
    $conn->close();
}
?>

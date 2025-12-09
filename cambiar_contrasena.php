<?php
session_start();
include '../controlVehicular/conn.php';

$id_usuario = $_COOKIE['noEmpleadoL'] ?? '';

$contrasena_actual = $_POST['contrasena_actual'] ?? '';
$nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
$accion = $_POST['accion'] ?? '';


if ($accion === 'CambiarPass') {
    
   if ($accion === 'CambiarPass') {
    
    // --- Configuración de Respuesta ---
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => ''];

    // 1. Verificar que la nueva contraseña y confirmación coincidan
    if ($nueva_contrasena !== $confirmar_contrasena) {
        $response['message'] = 'Las contraseñas nuevas no coinciden.';
        echo json_encode($response);
        exit;
    }
    
    // --- 2. VERIFICACIÓN DE CONTRASEÑA ACTUAL (Texto Plano) ---
    
    // Sentencia preparada para seleccionar la contraseña actual
    $stmt = $conn->prepare("SELECT password FROM usuarios WHERE noEmpleado = ?");
    if (!$stmt) {
        $response['message'] = 'Error al preparar la consulta de lectura: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    // Vincula el ID del usuario como entero ('i')
    $stmt->bind_param('i', $id_usuario); 
    $stmt->execute();
    $stmt->bind_result($password_en_bd); // Contraseña en texto plano
    $stmt->fetch();
    $stmt->close(); // Cierra la primera sentencia preparada

    // 3. Verificar la contraseña actual (Comparación directa)
    // Compara la contraseña PLANA ingresada ($contrasena_actual) con la de la BD ($password_en_bd).
    if (!isset($password_en_bd) || $contrasena_actual !== $password_en_bd) {
        $response['message'] = 'La contraseña actual es incorrecta.'; 
        echo json_encode($response);
        exit;
    }
    
    // --- 4. ACTUALIZACIÓN DE CONTRASEÑA NUEVA (Texto Plano) ---
    
    // Sentencia preparada para la actualización
    $stmt_update = $conn->prepare("UPDATE usuarios SET password = ? WHERE noEmpleado = ?");

    if (!$stmt_update) {
        $response['message'] = 'Error al preparar la consulta de actualización: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    // Vincula la nueva contraseña (string 's') y el ID de usuario (integer 'i')
    $stmt_update->bind_param('si', $nueva_contrasena, $id_usuario);
    
    if ($stmt_update->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Contraseña actualizada con éxito.';
    } else {
        $response['message'] = 'Error al actualizar la contraseña: ' . $stmt_update->error;
    }

    $stmt_update->close(); // Cierra la segunda sentencia
    
    // --- FIN DEL PROCESO ---
    echo json_encode($response);
    $conn->close(); // Cierra la conexión
    exit;
}
}
?>

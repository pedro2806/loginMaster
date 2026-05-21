<?php
session_start();
include '../incidencias/conn.php';

$id_usuario = $_COOKIE['noEmpleadoL'] ?? '';

$contrasena_actual = $_POST['contrasena_actual'] ?? '';
$nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
$accion = $_POST['accion'] ?? '';

if ($accion === 'CambiarPass') {
    
    // --- Configuración de Respuesta ---
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => ''];

    // Validar que tengamos el noEmpleado
    if (empty($id_usuario)) {
        $response['message'] = 'Sesión no válida. Inicia sesión nuevamente.';
        echo json_encode($response);
        exit;
    }

    // 1. Verificar que la nueva contraseña y confirmación coincidan
    if ($nueva_contrasena !== $confirmar_contrasena) {
        $response['message'] = 'Las contraseñas nuevas no coinciden.';
        echo json_encode($response);
        exit;
    }
    
    // --- 2. VERIFICACIÓN DE CONTRASEÑA ACTUAL (Hash + Fallback) ---
    
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
    $stmt->bind_result($password_en_bd); // Puede ser hash o texto plano
    $stmt->fetch();
    $stmt->close(); // Cierra la primera sentencia preparada

    // 3. Verificar la contraseña actual: hash primero, luego fallback a texto plano
    $password_valida = false;
    
    if (isset($password_en_bd)) {
        if (password_verify($contrasena_actual, $password_en_bd)) {
            // Caso 1: Contraseña ya hasheada y coincide
            $password_valida = true;
        } else if ($contrasena_actual === $password_en_bd) {
            // Caso 2: Contraseña vieja en texto plano y coincide
            $password_valida = true;
        }
    }
    
    if (!$password_valida) {
        $response['message'] = 'La contraseña actual es incorrecta.'; 
        echo json_encode($response);
        exit;
    }
    
    // --- 4. ACTUALIZACIÓN DE CONTRASEÑA NUEVA ---
    
    // Hasheamos SOLO el campo password, password_restaurar se queda en texto plano
    $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

    // Sentencia preparada para la actualización
    $stmt_update = $conn->prepare("UPDATE usuarios SET password = ?, password_restaurar = ? WHERE noEmpleado = ?");

    if (!$stmt_update) {
        $response['message'] = 'Error al preparar la consulta de actualización: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    // Vincula: password HASHEADA (string), password_restaurar PLANA (string), noEmpleado (integer)
    $stmt_update->bind_param('ssi', $nueva_contrasena_hash, $nueva_contrasena, $id_usuario);
    
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

?>
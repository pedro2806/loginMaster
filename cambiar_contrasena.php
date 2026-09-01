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

    // 1. Ningún campo puede ir vacío. Sin esta validación, dejar la nueva y la
    // confirmación en blanco pasaba el "no coinciden" ('' === ''), se guardaba
    // password_hash('') y la cuenta quedaba con contraseña vacía: password_verify('')
    // contra ese hash da true, así que cualquiera entraba sin contraseña.
    if ($contrasena_actual === '' || $nueva_contrasena === '' || $confirmar_contrasena === '') {
        $response['message'] = 'Llena los tres campos para cambiar tu contraseña.';
        echo json_encode($response);
        exit;
    }

    // 2. Verificar que la nueva contraseña y confirmación coincidan
    if ($nueva_contrasena !== $confirmar_contrasena) {
        $response['message'] = 'Las contraseñas nuevas no coinciden.';
        echo json_encode($response);
        exit;
    }

    // 3. Largo mínimo
    if (strlen($nueva_contrasena) < 6) {
        $response['message'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        echo json_encode($response);
        exit;
    }

    // 4. Que sea realmente distinta: el aviso de contraseña de fábrica pierde
    // sentido si el usuario "cambia" a la misma que ya traía.
    if ($nueva_contrasena === $contrasena_actual) {
        $response['message'] = 'La nueva contraseña debe ser distinta a la actual.';
        echo json_encode($response);
        exit;
    }

    // --- 5. VERIFICACIÓN DE CONTRASEÑA ACTUAL (Hash + Fallback) ---
    
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

    // 6. Verificar la contraseña actual: hash primero, luego fallback a texto plano
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
    
    // --- 7. ACTUALIZACIÓN DE CONTRASEÑA NUEVA ---
    
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
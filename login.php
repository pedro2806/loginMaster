<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0); 
ini_set('log_errors', 1);

include '../incidencias/conn.php';
$conn->select_db("mess_rrhh");

$email = $_POST['InputEmail'] ?? '';
$password = $_POST['InputPassword'] ?? '';
$accion = $_POST['btningresar'] ?? '';
$accionT = $_POST['accion'] ?? '';
$accionV = $_POST['accionV'] ?? '';

$emailValido = trim(strtolower($email));

// === LOGIN PRINCIPAL ===
if ($accion == 'Ingresar') {
    $datosUsr = [];        

    // Busca por usuario O por correo directo
    $sql = "SELECT u.id_usuario, u.usuario, u.nombre, u.noEmpleado, u.rol, u.correo, u.password, k.Password_KPI
    FROM usuarios u 
    LEFT JOIN accesos_kpis k ON u.correo = k.Correo
    WHERE u.usuario = ? OR u.correo = ?";                
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $emailValido, $emailValido);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Validar hash
        if (password_verify($password, $row['password'])) {
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['rol'] = $row['rol'];
            
            $datosUsr[] = [                
                'id' => $row['id_usuario'],
                'usuario' => $row['usuario'],
                'nombre' => $row['nombre'],
                'noEmpleado' => $row['noEmpleado'],
                'rol' => $row['rol'],
                'email' => $row['correo'],
                'kpis' => $row['Password_KPI']
            ];
            
        } else if ($password === $row['password']) {
            // Fallback para contraseñas viejas sin hash
            $nuevo_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_up = $conn->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
            $stmt_up->bind_param('si', $nuevo_hash, $row['id_usuario']);
            $stmt_up->execute();
            $stmt_up->close();
            
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['rol'] = $row['rol'];
            
            $datosUsr[] = [                
                'id' => $row['id_usuario'],
                'usuario' => $row['usuario'],
                'nombre' => $row['nombre'],
                'noEmpleado' => $row['noEmpleado'],
                'rol' => $row['rol'],
                'email' => $row['correo'],
                'kpis' => $row['Password_KPI']
            ];
            
        } else {
            echo json_encode([]);
            $stmt->close();
            $conn->close();
            exit;
        }
    }
    
    echo json_encode($datosUsr);
    $stmt->close();
    $conn->close();
    exit;
}

// === FUNCIONALIDAD PARA REGISTRAR TALLAS ===
if ($accionT == 'registraTallas') {
    // ... resto igual
}

// === VALIDAR TALLA ===
if ($accionT == 'validaTalla') {
    // ... resto igual
}

// === BUZON DE SUGERENCIAS ===
if ($accionT == 'buzon') {
    // ... resto igual
}

// === REGISTRAR VOTACION hallowen 2025 ===
if ($accionV == 'votacion') {
    // ... resto igual
}

echo json_encode([]);
$conn->close();
exit;
?>
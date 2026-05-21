<?php
header('Content-Type: application/json');
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', 'C:/wamp64/logs/php_error.log');

include '../incidencias/conn.php';

$email = $_POST['InputEmail'] ?? '';
$password = $_POST['InputPassword'] ?? '';
$accion = $_POST['btningresar'] ?? '';
$accionT = $_POST['accion'] ?? '';
$accionV = $_POST['accionV'] ?? '';

// Sanitizar usuario. Quitamos espacios y forzamos minúsculas para comparar
$emailValido = trim(strtolower($email));
$varKPIS = '';



// === LOGIN PRINCIPAL ===
if ($accion == 'Ingresar') {
    $datosUsr = [];        

    // Usamos prepared statements para evitar SQL Injection
    $sql = "SELECT u.id_usuario, u.usuario, u.nombre, u.noEmpleado, u.rol, u.correo, k.Password_KPI
    FROM usuarios u 
    LEFT JOIN accesos_kpis k ON u.correo = k.Correo
    WHERE (u.usuario = ? OR u.usuario LIKE CONCAT(?, '@%')) AND u.password = ?";                
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $emailValido, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $datosUsr[] = [                
                'id' => $row['id_usuario'],
                'usuario' => $row['usuario'],
                'nombre' => $row['nombre'],
                'noEmpleado' => $row['noEmpleado'],
                'rol' => $row['rol'],
                'email' => $row['correo'],
                'kpis' => $row['Password_KPI']
            ];
        }
    }
    echo json_encode($datosUsr);
    $stmt->close();
    $conn->close();
    exit;
}

// === FUNCIONALIDAD PARA REGISTRAR TALLAS ===
if ($accionT == 'registraTallas') {
    $noEmpleado = $_POST['noEmpleado'] ?? '';
    $talla = $_POST['talla'] ?? '';

    $sqlExiste = "SELECT COUNT(*) FROM tallas WHERE noEmpleado = ?";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->bind_param("i", $noEmpleado);
    $stmtExiste->execute();
    $stmtExiste->bind_result($existe);
    $stmtExiste->fetch();
    $stmtExiste->close();

    if ($existe > 0) {
        $sqlUpdate = "UPDATE tallas SET talla = ?, fecha_captura = NOW() WHERE noEmpleado = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("si", $talla, $noEmpleado);
        $success = $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        $sqlInsert = "INSERT INTO tallas (talla, prenda, fecha_captura, noEmpleado) VALUES (?, 'Playera', NOW(), ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("si", $talla, $noEmpleado);
        $success = $stmtInsert->execute();
        $stmtInsert->close();
    }

    echo json_encode(['success' => $success]);
    $conn->close();
    exit;
}

// === VALIDAR TALLA ===
if ($accionT == 'validaTalla') {
    $noEmpleado = $_POST['noEmpleado'] ?? '';

    $sqlValidaTalla = "SELECT talla FROM tallas WHERE noEmpleado = ?";
    $stmt = $conn->prepare($sqlValidaTalla);
    $stmt->bind_param("i", $noEmpleado);
    $stmt->execute();
    $stmt->bind_result($talla);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'exists' => true, 'talla' => $talla]);
    } else {
        echo json_encode(['success' => false]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// === BUZON DE SUGERENCIAS ===
if ($accionT == 'buzon') {
    $tipo = $_POST['tipo'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    $noEmpleado = $_POST['noEmpleado'] ?? '';

    $sqlInsert = "INSERT INTO buzon (noEmpleado, tipo, comentario, fecha_registro) VALUES (?, ?, ?, NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("iss", $noEmpleado, $tipo, $comentario);
    $success = $stmtInsert->execute();
    $stmtInsert->close();

    echo json_encode(['success' => $success]);
    $conn->close();
    exit;
}

// === REGISTRAR VOTACION hallowen 2025 ===
if ($accionV == 'votacion') {
    $noEmpleado = $_POST['noEmpleado'] ?? '';
    $id_foto = $_POST['id_foto'] ?? '';

    //VALIDAR SI YA VOTO
    $sqlCheck = "SELECT COUNT(*) FROM votos_fotos WHERE id_usuario = ? AND encuesta = 'Hallowen2025'";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $noEmpleado);
    $stmtCheck->execute();
    $stmtCheck->bind_result($yaVoto);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($yaVoto > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya votaste']);
        $conn->close();
        exit;
    }

    //REGISTRAR VOTO
    $sqlInsert = "INSERT INTO votos_fotos (id_usuario, id_foto, encuesta, fecha) VALUES (?, ?, 'Hallowen2025', NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $noEmpleado, $id_foto);
    $success = $stmtInsert->execute();
    $stmtInsert->close();

    echo json_encode(['success' => $success]);
    $conn->close();
    exit;
}

// Si no entró a ningún if, regresa array vacío pero siempre JSON válido
echo json_encode([]);
$conn->close();
exit;
?>
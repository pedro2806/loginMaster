<?php
header('Content-Type: application/json');
include '../incidencias/conn.php';

$accion = $_POST["accion"];
$noEmpleado = $_POST["noEmpleado"];
$sistema = $_POST["sistema"];
$opcion = $_POST["opcion"];

// Variables para notificaciones
$id_usuario_Destino = $_COOKIE['noEmpleadoL'] ?? 0;
$nombreUsuarioLogeado = trim((string)($_COOKIE['nombredelusuarioL'] ?? ''));
$idNotificacion = $_POST['idNotificacion'] ?? 0;

if($accion == "ValidarPermisos"){
    // Usamos sentencias preparadas para evitar inyecciones SQL
    $stmt = $conn->prepare("SELECT COUNT(*) AS cuantos FROM accesos_especiales 
                            WHERE noEmpleado = ? AND sistema = ? AND opcion = ? AND estatus = 1");
    $stmt->bind_param("iss", $noEmpleado, $sistema, $opcion);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $row = $result->fetch_assoc();
        // Devolvemos una estructura más simple para facilitar el JS
        echo json_encode([
            'status' => 'success', 
            'data' => [['cuantos' => $row['cuantos']]] 
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error en la consulta']);
    }
    $stmt->close();
}

// Cargar Registros de Notificaciones
if ($accion === 'cargarNotificaciones') {
        $sqlCargarNoti = "  SELECT *
                            FROM notificacion_historial nh
                            WHERE nh.id_usuario_destino = ?
                                AND nh.estatus = 'NoLeida'
                            ORDER BY nh.fecha_creacion DESC";

    $stmt = $conn->prepare($sqlCargarNoti);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar consulta']);
        exit;
    }

    $stmt->bind_param("i", $id_usuario_Destino);
    $stmt->execute();
    $result = $stmt->get_result();

    $notificaciones = [];
    while ($row = $result->fetch_assoc()) {
        $estatus = isset($row['estatus']) ? $row['estatus'] : 'NoLeida';
        $nota = trim((string)($row['nota'] ?? ''));
        $fechaActualizacion = isset($row['fecha_actualizacion']) ? formatearFechaCorta($row['fecha_actualizacion']) : '';
        $fechaCreacion = isset($row['fecha_creacion']) ? formatearFechaCorta($row['fecha_creacion']) : '';
        
        $notificaciones[] = [
            'id' => $row['id'],
            'mensaje' => $nota !== '' ? $nota : $row['accion'],
            'accion' => $row['accion'],
            'sistema' => $row['sistema'],
            'archivo' => $row['archivo'],
            'id_registro_referencia' => $row['id_registro_referencia'],
            'fecha' => $fechaCreacion,
            'fecha_actualizacion' => $fechaActualizacion,
            'fecha_atencion' => $row['fecha_atencion'],
            'recordar' => $row['recordar'],
            'id_usuario_nota' => isset($row['id_usuario_nota']) ? intval($row['id_usuario_nota']) : 0,
            'iniciales' => obtenerIniciales($nombreUsuarioLogeado),
            'nota' => $nota,
            'estatus' => $estatus,
            'leida' => strcasecmp($estatus, 'Leida') === 0 ? 1 : 0
        ];
    }

    $stmt->close();
    echo json_encode([
        'success' => true,
        'total' => count($notificaciones),
        'notificaciones' => $notificaciones
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Contar Notificaciones No Leídas
if ($accion === 'contarNotificaciones') {
    $sqlCuentaNoti = "SELECT COUNT(*) AS total
            FROM notificacion_historial
            WHERE id_usuario_destino = ?
            AND estatus = 'NoLeida'";

    $stmt = $conn->prepare($sqlCuentaNoti);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar conteo']);
        exit;
    }

    $stmt->bind_param("i", $id_usuario_Destino);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $total = isset($row['total']) ? intval($row['total']) : 0;
    echo json_encode(['success' => true, 'total' => $total], JSON_UNESCAPED_UNICODE);
    exit;
}

// Marcar Notificación como Leída
if ($accion === 'marcarLeida') {
    if ($idNotificacion <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de notificación no válido']);
        exit;
    }

    $sql = "UPDATE notificacion_historial
            SET estatus = 'Leida', fecha_atencion = NOW()
            WHERE id = ? AND id_usuario_destino = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar actualización']);
        exit;
    }

    $stmt->bind_param("ii", $idNotificacion, $id_usuario_Destino);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $ok, 'message' => $ok ? 'OK' : 'Error al actualizar la notificación']);
    exit;
}

// Función para obtener iniciales de un nombre completo
function obtenerIniciales($nombreCompleto) {
    $nombreCompleto = trim((string)$nombreCompleto);
    if ($nombreCompleto === '') {
        return 'NA';
    }

    $partes = preg_split('/\s+/', $nombreCompleto);
    $partes = array_values(array_filter($partes, function ($parte) {
        return trim($parte) !== '';
    }));

    $totalPartes = count($partes);
    if ($totalPartes >= 3) {
        $primeraInicial = strtoupper(substr($partes[0], 0, 1));
        $apellidoPaternoInicial = strtoupper(substr($partes[$totalPartes - 2], 0, 1));
        $apellidoMaternoInicial = strtoupper(substr($partes[$totalPartes - 1], 0, 1));
        return $primeraInicial . $apellidoPaternoInicial . $apellidoMaternoInicial;
    }

    $iniciales = '';
    foreach ($partes as $parte) {
        $iniciales .= strtoupper(substr($parte, 0, 1));
    }

    return $iniciales !== '' ? $iniciales : 'NA';
}

// Función para formatear fechas a formato corto (d/m/Y H:i)
function formatearFechaCorta($fecha) {
    $fecha = trim((string)$fecha);
    if ($fecha === '') {
        return '';
    }

    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha;
    }

    return date('d/m/Y H:i', $timestamp);
}

//echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
?>
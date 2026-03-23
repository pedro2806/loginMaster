<?php
require_once '../incidencias/conn.php';

// Seteamos la respuesta como JSON
header('Content-Type: application/json');

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'guardar_evento':
        $nombre = $_POST['nombre'];
        $tipo = $_POST['tipo'];
        $f_inicio = $_POST['fecha_inicio'];
        $f_fin = $_POST['fecha_fin'];
        $descripcion = $_POST['descripcion'] ?? '';
        $id_evento = $_POST['id_evento'] ?? '';

        if (!empty($id_evento)) {
            // Actualizar evento existente
            $sql = "UPDATE enc_eventos SET nombre=?, descripcion=?, tipo=?, fecha_inicio=?, fecha_fin=? WHERE id_evento=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $nombre, $descripcion, $tipo, $f_inicio, $f_fin, $id_evento);
        } else {
            // Insertar nuevo evento
            $sql = "INSERT INTO enc_eventos (nombre, descripcion, tipo, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nombre, $descripcion, $tipo, $f_inicio, $f_fin);
        }

        if ($stmt->execute()) {
            $last_id = !empty($id_evento) ? $id_evento : $conn->insert_id;
            echo json_encode(['status' => 'success', 'id' => $last_id]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $conn->error]);
        }
        break;

    case 'guardar_opcion':
        $id_evento    = $_POST['id_evento'];
        $titulo       = $_POST['titulo'];
        $pregunta     = !empty($_POST['pregunta_texto']) ? $_POST['pregunta_texto'] : 'General';
        $grupo        = !empty($_POST['grupo']) ? $_POST['grupo'] : null;
        $fecha_opcion = !empty($_POST['fecha_opcion']) ? $_POST['fecha_opcion'] : null;
        $ruta_final   = null;

        // Manejo de la imagen (Si aplica para Votación)
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $directorio = "uploads/eventos/ev_" . $id_evento . "/";
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }
            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nombre_archivo = "item_" . uniqid() . "." . $extension;
            $ruta_destinatario = $directorio . $nombre_archivo;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destinatario)) {
                $ruta_final = $ruta_destinatario;
            }
        }

        // Insertamos incluyendo los nuevos campos: grupo y fecha_opcion
        $sql = "INSERT INTO enc_eventos_opciones (id_evento, titulo, pregunta, grupo, fecha_opcion, ruta_imagen) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $id_evento, $titulo, $pregunta, $grupo, $fecha_opcion, $ruta_final);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $conn->error]);
        }
        exit;

        case 'listar_opciones':
            $id_evento = $_POST['id_evento'];
            $sql = "SELECT id_opcion, titulo, ruta_imagen, pregunta FROM enc_eventos_opciones WHERE id_evento = ? ORDER BY id_opcion DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_evento);
            $stmt->execute();
            $res = $stmt->get_result();
            
            $opciones = [];
            while($row = $res->fetch_assoc()){
                $opciones[] = $row;
            }
            echo json_encode($opciones);
        break;

    case 'obtener_evento':
        $id = $_POST['id_evento'];
        $res = $conn->query("SELECT * FROM enc_eventos WHERE id_evento = $id");
        echo json_encode($res->fetch_assoc());
        exit;

    case 'eliminar_evento_completo':
        $id = $_POST['id_evento'];
        // Al borrar el evento, por el FK con CASCADE se borran las opciones, 
        // pero las respuestas debes borrarlas manualmente si no pusiste CASCADE.
        $conn->query("DELETE FROM enc_respuestas WHERE id_evento = $id");
        $conn->query("DELETE FROM enc_eventos WHERE id_evento = $id");
        echo json_encode(['status' => 'success']);
        exit;

    case 'listar_eventos_general':
        $sql = "SELECT id_evento, nombre, tipo, fecha_inicio, fecha_fin, estatus FROM enc_eventos ORDER BY id_evento DESC";
        $res = $conn->query($sql);
        $eventos = [];
        while($row = $res->fetch_assoc()){
            $eventos[] = $row;
        }
        echo json_encode($eventos);
        exit;

    case 'registrar_respuesta':
        $id_ev = $_POST['id_evento'];
        $id_op = $_POST['id_opcion'];
        $id_emp = $_POST['id_empleado'];

        // Validar si ya votó en este evento
        $check = $conn->query("SELECT id_respuesta FROM enc_respuestas WHERE id_evento = $id_ev AND id_empleado = $id_emp");
        
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Ya has participado en este evento.']);
        } else {
            $sql = "INSERT INTO enc_respuestas (id_evento, id_opcion, id_empleado) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $id_ev, $id_op, $id_emp);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            }
        }
        exit;

    case 'registrar_asistencia_multiple':
        $id_ev = $_POST['id_evento'];
        $id_emp = $_POST['id_empleado'];
        $opciones = $_POST['opciones']; // Esto es un array

        // Validar si ya participó (Opcional, según tu regla de negocio)
        $check = $conn->query("SELECT id_respuesta FROM enc_respuestas WHERE id_evento = $id_ev AND id_empleado = $id_emp");
        if($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Ya has registrado tu participación en este evento.']);
            exit;
        }

        foreach($opciones as $id_op) {
            $stmt = $conn->prepare("INSERT INTO enc_respuestas (id_evento, id_opcion, id_empleado) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id_ev, $id_op, $id_emp);
            $stmt->execute();
        }

        echo json_encode(['status' => 'success']);
        exit;
    case 'eliminar_opcion':
        $id_opcion = $_POST['id_opcion'];

        // 1. Primero consultamos si la opción tiene una imagen asociada para borrarla del disco
        $consulta = $conn->prepare("SELECT ruta_imagen FROM enc_eventos_opciones WHERE id_opcion = ?");
        $consulta->bind_param("i", $id_opcion);
        $consulta->execute();
        $resultado = $consulta->get_result();
        $opcion = $resultado->fetch_assoc();

        if ($opcion && !empty($opcion['ruta_imagen'])) {
            // Verificamos si el archivo existe físicamente y lo borramos
            if (file_exists($opcion['ruta_imagen'])) {
                unlink($opcion['ruta_imagen']);
            }
        }

        // 2. Ahora procedemos a borrar el registro de la base de datos
        // Nota: Si tienes respuestas ligadas a esta opción, 
        // deberías tener un DELETE previo para enc_respuestas o usar ON DELETE CASCADE.
        $stmt = $conn->prepare("DELETE FROM enc_eventos_opciones WHERE id_opcion = ?");
        $stmt->bind_param("i", $id_opcion);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $conn->error]);
        }
        exit;
        case 'obtener_resultados_excel':
            $id_ev = $_POST['id_evento'];
            
            // Traemos el nombre del empleado (o ID), la pregunta y la opción elegida
            // Ajusta 'usuarios' y 'nombre_completo' según tu tabla real de empleados
            $sql = "SELECT 
                        e.nombre as evento,
                        r.id_empleado, 
                        o.pregunta, 
                        o.titulo as respuesta,
                        r.fecha_registro, u.nombre as nombre_empleado
                    FROM enc_respuestas r
                    JOIN enc_eventos_opciones o ON r.id_opcion = o.id_opcion
                    JOIN enc_eventos e ON r.id_evento = e.id_evento
                    JOIN usuarios u ON r.id_empleado = u.noEmpleado
                    WHERE r.id_evento = ?
                    ORDER BY r.id_empleado, o.pregunta";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_ev);
            $stmt->execute();
            $res = $stmt->get_result();
            
            $datos = [];
            while($row = $res->fetch_assoc()){
                $datos[] = $row;
            }
            echo json_encode($datos);
        exit;

case 'asignar_usuarios_evento':
        $id_ev = $_POST['id_evento'];
        $empleados = $_POST['empleados']; // Recibe el array del select múltiple

        if (!empty($empleados) && is_array($empleados)) {
            foreach ($empleados as $id_emp) {
                // 1. Validar que no esté ya asignado para no duplicar filas
                $check = $conn->query("SELECT id_asignacion FROM enc_eventos_asignados WHERE id_evento = $id_ev AND id_empleado = $id_emp");
                
                if ($check->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO enc_eventos_asignados (id_evento, id_empleado) VALUES (?, ?)");
                    $stmt->bind_param("ii", $id_ev, $id_emp);
                    $stmt->execute();
                }
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'No se seleccionaron empleados.']);
        }
        exit;

case 'listar_asignados':
        $id_ev = $_POST['id_evento'];
        
        // Ajusta 'usuarios' e 'id_usuario' según tu tabla real de personal
        $sql = "SELECT a.id_asignacion, u.nombre, a.confirmado 
                FROM enc_eventos_asignados a
                JOIN usuarios u ON a.id_empleado = u.noEmpleado
                WHERE a.id_evento = ?
                ORDER BY u.nombre ASC";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_ev);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
        exit;

case 'quitar_asignacion':
        $id_asig = $_POST['id_asignacion'];
        
        $stmt = $conn->prepare("DELETE FROM enc_eventos_asignados WHERE id_asignacion = ?");
        $stmt->bind_param("i", $id_asig);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $conn->error]);
        }
        exit;

case 'registrar_participacion_final':
        $id_ev = $_POST['id_evento'];
        $id_emp = $_POST['id_empleado'];
        $opciones = $_POST['opciones']; // Array de IDs

        // 1. Insertar las respuestas en enc_respuestas
        foreach ($opciones as $id_op) {
            $stmt = $conn->prepare("INSERT INTO enc_respuestas (id_evento, id_opcion, id_empleado) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id_ev, $id_op, $id_emp);
            $stmt->execute();
        }

        // 2. Marcar como CONFIRMADO en la tabla de asignaciones
        $update = $conn->prepare("UPDATE enc_eventos_asignados SET confirmado = 1 WHERE id_evento = ? AND id_empleado = ?");
        $update->bind_param("ii", $id_ev, $id_emp);
        $update->execute();

        echo json_encode(['status' => 'success']);
        exit;
        
case 'listar_eventos_pendientes_empleado':
        $id_emp = $_POST['id_empleado'];

        // Consulta que trae:
        // 1. Eventos tipo 'asistencia' donde el usuario ESTÁ asignado y NO ha confirmado.
        // 2. Eventos tipo 'votacion' o 'encuesta' que estén activos (estos son abiertos).
        $sql = "SELECT e.id_evento, e.nombre, e.tipo, e.fecha_fin 
                FROM enc_eventos e
                LEFT JOIN enc_eventos_asignados a ON e.id_evento = a.id_evento AND a.id_empleado = ?
                WHERE e.estatus = 1 
                AND (
                    (e.tipo = 'asistencia' AND a.id_empleado IS NOT NULL AND a.confirmado = 0)
                    OR 
                    (e.tipo != 'asistencia' AND NOT EXISTS (
                        SELECT 1 FROM enc_respuestas r 
                        WHERE r.id_evento = e.id_evento AND r.id_empleado = ?
                    ))
                )
                ORDER BY e.fecha_fin ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_emp, $id_emp);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
        exit;

case 'listar_mis_actividades_completas':
        $id_emp = $_POST['id_empleado'];

        $sql = "SELECT 
                    e.id_evento, 
                    e.nombre, 
                    e.tipo, 
                    e.fecha_fin,
                    -- Verificamos si ya existe respuesta o confirmación
                    (SELECT COUNT(*) FROM enc_respuestas r WHERE r.id_evento = e.id_evento AND r.id_empleado = ?) as respondido,
                    (SELECT confirmado FROM enc_eventos_asignados a WHERE a.id_evento = e.id_evento AND a.id_empleado = ?) as asignado_confirmado
                FROM enc_eventos e
                LEFT JOIN enc_eventos_asignados asig ON e.id_evento = asig.id_evento AND asig.id_empleado = ?
                WHERE e.estatus = 1 
                AND (
                    (e.tipo = 'asistencia' AND asig.id_empleado IS NOT NULL) -- Solo donde está invitado
                    OR 
                    (e.tipo != 'asistencia') -- O las que son abiertas (encuesta/votación)
                )
                ORDER BY respondido ASC, e.fecha_fin ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $id_emp, $id_emp, $id_emp);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
        exit;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
        break;
}

$conn->close();
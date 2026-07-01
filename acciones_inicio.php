<?php
include '../incidencias/conn.php';

// Si el POST llegó vacío pero el navegador SÍ envió cuerpo (Content-Length > 0),
// PHP descartó todo por exceder post_max_size. Respondemos con un mensaje claro
// en vez de dejar que el flujo termine en un error confuso.
if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'El archivo excede el límite de subida del servidor (post_max_size). Súbelo más pequeño o aumenta el límite.'
    ]);
    exit;
}

$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$noEmpleado = isset($_POST['noEmpleado']) ? $_POST['noEmpleado'] : '';

//MOSTRAR TALLAS
if ($accion == 'ver_tallas') {

    $sqlTallas ="  SELECT ta.noEmpleado, ta.talla, ta.prenda, ta.fecha_captura, us.sexo, us.nombre
                    FROM tallas ta
                    INNER JOIN usuarios us ON ta.noEmpleado= us.noEmpleado 
                    ORDER BY noEmpleado ASC";
    $result = $conn->query($sqlTallas);

    $tallasData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tallasData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'tallas' => $tallasData]);
    $conn->close();
    exit;
}

//CONTEO DE TALLAS
if ($accion == 'conteo_tallas') {

    $sqlTotalTallas = "  SELECT ta.talla, us.sexo, COUNT(*) AS cantidad
                    FROM tallas ta
                    INNER JOIN usuarios us ON ta.noEmpleado = us.noEmpleado
                    WHERE ta.talla IN ('XS', 'S', 'M', 'L', 'XL')
                    GROUP BY ta.talla, us.sexo
                    ORDER BY us.sexo ASC, 
                             FIELD(ta.talla, 'XS', 'S', 'M', 'L', 'XL')";
    $result = $conn->query($sqlTotalTallas);

    $tallasData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tallasData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'tallas' => $tallasData]);
    $conn->close();
    exit;
}

//VER VOTOS DEL CARRUSEL
if ($accion == 'conteo_votos') {
    $sqlTotalVotos = "  SELECT id_foto, COUNT(*) AS cantidad
                    FROM votos_fotos
                    GROUP BY id_foto";
    $result = $conn->query($sqlTotalVotos);

    $votosData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $votosData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'votos' => $votosData]);
    $conn->close();
    exit;
}

//VER BUZON DE SUGERENCIAS
if ($accion == 'ver_buzon') {

    $sqlBuzon = "  SELECT b.noEmpleado, us.nombre, b.tipo , b.comentario, b.fecha_registro
                    FROM buzon b
                    INNER JOIN usuarios us ON b.noEmpleado = us.noEmpleado
                    ORDER BY fecha_registro DESC";
    $result = $conn->query($sqlBuzon);

    $buzonData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $buzonData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'buzon' => $buzonData]);
    $conn->close();
    exit;
}

if ($accion == 'guardar_asistencia') {

    $noEmpleado = $_POST['noEmpleado'] ?? '';
    $cursos = isset($_POST['cursos']) ? (array)$_POST['cursos'] : [];
    $encuesta = 'CapacitacionesBrigada2026';

    // 1. Preparamos la consulta de inserción
    $sqlInsert = "INSERT INTO votos_fotos (id_usuario, id_voto, encuesta, fecha) VALUES (?, ?, ?, NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    
    // 2. Preparamos una consulta de verificación para evitar duplicados
    $sqlCheck = "SELECT COUNT(*) FROM votos_fotos WHERE id_usuario = ? AND id_voto = ? AND encuesta = ?";
    $stmtCheck = $conn->prepare($sqlCheck);

    $success = true;
    $registrados = 0;
    $omitidos = 0;

    foreach ($cursos as $valorCurso) {
        // Verificamos si ya existe el registro
        $stmtCheck->bind_param("iss", $noEmpleado, $valorCurso, $encuesta);
        $stmtCheck->execute();
        $stmtCheck->bind_result($count);
        $stmtCheck->fetch();
        
        // Importante: liberar el resultado para la siguiente vuelta del ciclo
        $stmtCheck->free_result(); 

        if ($count == 0) {
            // Si no existe, procedemos a insertar
            $stmtInsert->bind_param("iss", $noEmpleado, $valorCurso, $encuesta);
            if ($stmtInsert->execute()) {
                $registrados++;
            } else {
                $success = false;
            }
        } else {
            $omitidos++;
        }
    }

    $stmtInsert->close();
    $stmtCheck->close();
    $conn->close();

    echo json_encode([
        'success' => $success,
        'message' => "Proceso terminado. Registrados: $registrados, Ya existentes: $omitidos",
        'registrados' => $registrados,
        'omitidos' => $omitidos
    ]);
    exit;
}

//CARGAR CURSOS PARA ASISTENCIA
if ($accion == 'cargar_cursos') {

    $noEmpleado = $_POST['noEmpleado'] ?? '';

    $sqlCursos = "SELECT id_voto, encuesta as nombre_curso
                    FROM votos_fotos
                    WHERE id_usuario = ? AND encuesta = 'CapacitacionesBrigada2026'
                    ORDER BY nombre_curso ASC";
    $stmt = $conn->prepare($sqlCursos);
    $stmt->bind_param("i", $noEmpleado);
    $stmt->execute();
    $result = $stmt->get_result();

    $cursosData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cursosData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'cursos' => $cursosData]);
    $conn->close();
    exit;
}

// VERIFICAR USUARIO POR CORREO
if ($accion == 'validar_usuario') {    
    ob_clean(); 
    header('Content-Type: application/json');

    $correo = $_POST['correo'] ?? '';
    
    $sqlUsuario = "SELECT us.noEmpleado, us.nombre, dep.departamento, reg.region 
                    FROM usuarios us
                    LEFT JOIN departamento dep ON us.departamento = dep.id
                    LEFT JOIN region reg ON us.region = reg.id WHERE correo = ? LIMIT 1";
    $stmt = $conn->prepare($sqlUsuario);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'usuario' => [
                'nombre' => $row['nombre'],
                'departamento' => $row['departamento'],
                'region' => $row['region']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// REGISTRAR ASISTENCIA
if ($accion == 'registrar_asistencia') {    
    if (ob_get_length()) ob_clean(); 
    header('Content-Type: application/json');
    
    $correo     = $_POST['correo'] ?? '';
    $nombre     = $_POST['nombre'] ?? '';
    $area       = $_POST['area'] ?? '';
    $nave       = $_POST['nave'] ?? '';
    $curso      = $_POST['curso'] ?? '';
    $fecha      = $_POST['fecha'] ?? '';
    $instructor = $_POST['instructor'] ?? '';
    $duracion   = $_POST['duracion'] ?? '';

    $sqlCheck = "SELECT id FROM asistencias WHERE correo = ? AND curso = ? AND fecha_curso = ? LIMIT 1";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("sss", $correo, $curso, $fecha);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Atención: Ya habías registrado tu asistencia para este curso el día de hoy.'
        ]);
        $stmtCheck->close();
        $conn->close();
        exit;
    }
    $stmtCheck->close();
    
    $sqlInsert = "INSERT INTO asistencias (correo, nombre, area, nave, curso, fecha_curso, instructor, duracion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlInsert);

    if ($stmt) {
        $stmt->bind_param("ssssssss", 
            $correo, 
            $nombre, 
            $area, 
            $nave, 
            $curso, 
            $fecha, 
            $instructor, 
            $duracion
        );

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => '¡Asistencia registrada con éxito!'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al guardar en la base de datos.',
                'error' => $stmt->error
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error en la preparación de la consulta.',
            'error' => $conn->error
        ]);
    }

    $conn->close();
    exit;
}

//REGISISTROS DE ASISTENCIA 
if ($accion == 'obtener_asistencias') {    
    header('Content-Type: application/json');
    
    $curso = $_POST['curso'] ?? '';
    $fecha = $_POST['fecha'] ?? '';

    // Si quieres filtrar, añade un WHERE a tu SQL
    $sqlAsistencias = "SELECT id, correo, nombre, area, nave, curso, fecha_curso, instructor, duracion, fecha_curso, registrado_el 
                        FROM asistencias ORDER BY fecha_curso DESC";
    
    $result = $conn->query($sqlAsistencias);

    $asistenciasData = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $asistenciasData[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'asistencias' => $asistenciasData
    ]);

    $conn->close();
    exit;
}

// SUBIR PDF DEL MURAL (solo admins de RRHH) — reemplaza el PDF vigente
if ($accion == 'subir_mural') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    // Mismo conjunto de admins que en inicio.php
    $empleadosAdmin = [276, 403, 569, 523, 183];
    $noEmpSesion = isset($_COOKIE['noEmpleadoL']) ? intval($_COOKIE['noEmpleadoL']) : 0;
    if (!in_array($noEmpSesion, $empleadosAdmin, true)) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para actualizar el mural.']);
        exit;
    }

    if (!isset($_FILES['mural']) || $_FILES['mural']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No se recibió el archivo o hubo un error en la carga.']);
        exit;
    }

    $archivo = $_FILES['mural'];

    // Tamaño máximo: 20 MB
    if ($archivo['size'] > 20 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'El archivo supera el tamaño máximo de 20 MB.']);
        exit;
    }

    // Validar que realmente sea un PDF. Se valida por la FIRMA de bytes ("%PDF-"),
    // que no depende de la extensión fileinfo (finfo_open) — evita un fatal 500 en
    // servidores donde 'fileinfo' está deshabilitada. Si finfo está disponible, se
    // usa como verificación adicional.
    $ext   = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $esPdf = ($ext === 'pdf');

    if ($esPdf) {
        $fh = @fopen($archivo['tmp_name'], 'rb');
        if ($fh) {
            $firma = fread($fh, 5);
            fclose($fh);
            $esPdf = (strncmp($firma, '%PDF-', 5) === 0);
        } else {
            $esPdf = false;
        }
    }

    if ($esPdf && function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $archivo['tmp_name']);
            finfo_close($finfo);
            $esPdf = ($mime === 'application/pdf');
        }
    }

    if (!$esPdf) {
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF.']);
        exit;
    }

    $dir = __DIR__ . '/uploads/mural/';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio de destino.']);
        exit;
    }

    $destino = $dir . 'mural_actual.pdf';
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo en el servidor.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Mural actualizado correctamente.',
        'src' => 'uploads/mural/mural_actual.pdf?v=' . time()
    ]);
    exit;
}
?>
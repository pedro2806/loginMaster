<?php
include '../incidencias/conn.php';

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

?>
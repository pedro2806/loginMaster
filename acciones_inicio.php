<?php
include '../incidencias/conn.php';

// Si el POST llegó vacío pero el navegador SÍ envió cuerpo (Content-Length > 0),
// PHP descartó todo por exceder post_max_size. Respondemos con un mensaje claro
// en vez de dejar que el flujo termine en un error confuso.
if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'El archivo excede el límite de subida del servidor. Súbelo más pequeño o aumenta el límite.'
    ]);
    exit;
}

$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$noEmpleado = isset($_POST['noEmpleado']) ? $_POST['noEmpleado'] : '';

// ===== MessbookID =====
// Apodo corto para la profile card. Se guarda SIN la arroba (es prefijo de
// presentación) en usuarios.messbookID, con índice único. La columna admite
// NULL a propósito: sin valor, se deriva de la parte del correo anterior al @,
// que es el default que se acordó. Se deriva en vez de precargarse para que no
// haya que migrar 268 filas y para que siga al correo si este cambia.
// 20 caracteres: el default más largo de la plantilla actual mide 19
// (sebastian.gutierrez), así que a nadie le sale truncado.
define('MESSBOOKID_MAX', 20);

function messbookIdDefault($correo) {
    $correo = (string) $correo;
    $pos = strpos($correo, '@');
    $base = $pos === false ? $correo : substr($correo, 0, $pos);
    return substr($base, 0, MESSBOOKID_MAX);
}

// Devuelve el MessbookID efectivo: el guardado, o el derivado del correo.
function messbookIdEfectivo($guardado, $correo) {
    $guardado = trim((string) $guardado);
    return $guardado !== '' ? $guardado : messbookIdDefault($correo);
}

// Reglas de personalización: letras, números, punto, guion y guion bajo. Sin
// espacios ni arrobas, para que se pueda escribir de corrido y usarse después
// en menciones. Devuelve '' si es válido, o el motivo del rechazo.
function messbookIdInvalido($valor) {
    if ($valor === '') return 'El MessbookID no puede quedar vacío.';
    if (mb_strlen($valor) > MESSBOOKID_MAX) {
        return 'El MessbookID no puede pasar de ' . MESSBOOKID_MAX . ' caracteres.';
    }
    if (mb_strlen($valor) < 3) return 'El MessbookID debe tener al menos 3 caracteres.';
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $valor)) {
        return 'Solo se permiten letras, números, punto, guion y guion bajo.';
    }
    return '';
}

if ($accion == 'messbookid_leer' || $accion == 'messbookid_guardar') {
    ob_clean();
    header('Content-Type: application/json');

    $noEmpSesion = intval($noEmpleado);
    if ($noEmpSesion <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
        $conn->close();
        exit;
    }

    if ($accion == 'messbookid_guardar') {
        // Se guarda sin la arroba aunque el usuario la escriba por costumbre.
        $nuevo = ltrim(trim((string)($_POST['messbookID'] ?? '')), '@');

        $error = messbookIdInvalido($nuevo);
        if ($error !== '') {
            echo json_encode(['success' => false, 'message' => $error]);
            $conn->close();
            exit;
        }

        // Choque con otro empleado. Se revisa antes de escribir para responder
        // con un mensaje claro en vez de dejar que reviente el índice único.
        $stmtDup = $conn->prepare(
            "SELECT noEmpleado FROM usuarios WHERE messbookID = ? AND noEmpleado <> ? LIMIT 1"
        );
        if ($stmtDup) {
            $stmtDup->bind_param('si', $nuevo, $noEmpSesion);
            $stmtDup->execute();
            $ocupado = (bool) $stmtDup->get_result()->fetch_assoc();
            $stmtDup->close();
            if ($ocupado) {
                echo json_encode(['success' => false, 'message' => 'Ese MessbookID ya está en uso.']);
                $conn->close();
                exit;
            }
        }

        // Choque contra el default de alguien más: esa persona aún no guarda
        // nada, pero el portal la muestra así, y dos @pedro dejarían de
        // identificar a nadie.
        $stmtDef = $conn->prepare(
            "SELECT noEmpleado FROM usuarios
             WHERE messbookID IS NULL AND noEmpleado <> ?
               AND SUBSTRING_INDEX(correo, '@', 1) = ?
             LIMIT 1"
        );
        if ($stmtDef) {
            $stmtDef->bind_param('is', $noEmpSesion, $nuevo);
            $stmtDef->execute();
            $chocaDefault = (bool) $stmtDef->get_result()->fetch_assoc();
            $stmtDef->close();
            if ($chocaDefault) {
                echo json_encode(['success' => false, 'message' => 'Ese MessbookID ya está en uso.']);
                $conn->close();
                exit;
            }
        }

        $stmtUp = $conn->prepare("UPDATE usuarios SET messbookID = ? WHERE noEmpleado = ?");
        if ($stmtUp) {
            $stmtUp->bind_param('si', $nuevo, $noEmpSesion);
            $stmtUp->execute();
            $stmtUp->close();
        }
    }

    $stmtL = $conn->prepare("SELECT messbookID, correo FROM usuarios WHERE noEmpleado = ? LIMIT 1");
    $messbookID = '';
    $personalizado = false;
    if ($stmtL) {
        $stmtL->bind_param('i', $noEmpSesion);
        $stmtL->execute();
        $row = $stmtL->get_result()->fetch_assoc();
        $stmtL->close();
        if ($row) {
            $personalizado = trim((string)$row['messbookID']) !== '';
            $messbookID = messbookIdEfectivo($row['messbookID'], $row['correo']);
        }
    }

    echo json_encode([
        'success'       => true,
        'messbookID'    => $messbookID,
        'personalizado' => $personalizado,
        'max'           => MESSBOOKID_MAX
    ]);
    $conn->close();
    exit;
}

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

// CUMPLEAÑOS DEL DÍA
// Se compara mes y día, nunca el año: fechaNacimiento guarda el año real.
// Devuelve por separado si le toca al usuario de la sesión, para que el portal
// pueda felicitarlo a él y, aparte, listar a los demás para todos.
// Fecha centinela de la preferencia de cumpleaños. Va fuera del rango de
// cualquier felicitación real, que siempre se registra con CURDATE().
define('CUMPLE_PREF_FECHA', '1900-01-01');

if ($accion == 'cumpleanos_hoy') {
    ob_clean();
    header('Content-Type: application/json');

    $noEmpSesion = intval($noEmpleado);

    // La preferencia de "no compartir mi cumpleaños" se guarda en la misma tabla
    // cumple_aplausos, como fila centinela: origen = destino y fecha CUMPLE_PREF_FECHA.
    // No hay riesgo de chocar con una felicitación real porque esas siempre se
    // insertan con CURDATE(), y nadie se felicita a sí mismo. Es un apaño para no
    // crear tabla nueva; si algún día hay más preferencias, conviene separarlas.
    $sqlCump = "SELECT u.noEmpleado, u.nombre, u.fechaNacimiento,
                       (pref.no_empleado_destino IS NULL) AS mostrar
                FROM usuarios u
                LEFT JOIN cumple_aplausos pref
                       ON pref.no_empleado_destino = u.noEmpleado
                      AND pref.no_empleado_origen  = u.noEmpleado
                      AND pref.fecha = '" . CUMPLE_PREF_FECHA . "'
                WHERE u.estatus = 1
                  AND u.fechaNacimiento IS NOT NULL
                  AND MONTH(u.fechaNacimiento) = MONTH(CURDATE())
                  AND DAY(u.fechaNacimiento)   = DAY(CURDATE())
                ORDER BY u.nombre";
    $resCump = $conn->query($sqlCump);

    $otros = [];
    $esMiCumple = false;
    $miNombre = '';
    $miPrefMostrar = true;

    if ($resCump) {
        while ($row = $resCump->fetch_assoc()) {
            if ($noEmpSesion > 0 && intval($row['noEmpleado']) === $noEmpSesion) {
                // Al cumpleañero lo felicitamos aparte; no se lista a sí mismo.
                $esMiCumple = true;
                $miNombre = $row['nombre'];
                continue;
            }
            // Quien pidió no compartirlo no aparece en la lista de los demás.
            if (!$row['mostrar']) continue;

            $otros[] = [
                'noEmpleado' => intval($row['noEmpleado']),
                'nombre'     => $row['nombre']
            ];
        }
    }

    // La preferencia se lee aparte y NO dentro del bucle de arriba: ese solo
    // recorre a quienes cumplen hoy, así que en cualquier otro día del año la
    // respuesta salía con el valor por defecto y el botón se revertía solo.
    if ($noEmpSesion > 0) {
        $stmtPref = $conn->prepare(
            "SELECT 1 FROM cumple_aplausos
             WHERE no_empleado_destino = ? AND no_empleado_origen = ? AND fecha = ?
             LIMIT 1"
        );
        if ($stmtPref) {
            $fechaPref = CUMPLE_PREF_FECHA;
            $stmtPref->bind_param('iis', $noEmpSesion, $noEmpSesion, $fechaPref);
            $stmtPref->execute();
            $miPrefMostrar = !$stmtPref->get_result()->fetch_assoc();
            $stmtPref->close();
        }
    }

    // Quién ya lo felicitó hoy. Se devuelven NOMBRES, no un total: enterarse de
    // quién se acordó de ti es cálido, mientras que un marcador invita a comparar.
    $meFelicitaron = [];
    if ($esMiCumple && $noEmpSesion > 0) {
        $stmtFel = $conn->prepare(
            "SELECT u.nombre
             FROM cumple_aplausos a
             INNER JOIN usuarios u ON u.noEmpleado = a.no_empleado_origen
             WHERE a.no_empleado_destino = ? AND a.fecha = CURDATE()
             ORDER BY u.nombre"
        );
        if ($stmtFel) {
            $stmtFel->bind_param('i', $noEmpSesion);
            $stmtFel->execute();
            $resFel = $stmtFel->get_result();
            while ($f = $resFel->fetch_assoc()) $meFelicitaron[] = $f['nombre'];
            $stmtFel->close();
        }
    }

    echo json_encode([
        'success'         => true,
        'es_mi_cumple'    => $esMiCumple,
        'mi_nombre'       => $miNombre,
        'otros'           => $otros,
        'me_felicitaron'  => $meFelicitaron,
        'mi_pref_mostrar' => $miPrefMostrar
    ]);
    $conn->close();
    exit;
}

// PREFERENCIA: compartir o no mi cumpleaños con los demás.
// Se guarda como fila centinela en cumple_aplausos (ver CUMPLE_PREF_FECHA):
// existe la fila = NO compartir. Sin fila = compartir, que es el comportamiento
// por defecto y el que ya tenían todos.
// Solo se guarda: la lectura viaja dentro de la respuesta de cumpleanos_hoy
// (campo mi_pref_mostrar), que el portal ya pide al cargar. Un endpoint de
// lectura aparte obligaría a una segunda petición para el mismo dato.
if ($accion == 'cumple_pref_guardar') {
    ob_clean();
    header('Content-Type: application/json');

    $noEmpSesion = intval($noEmpleado);
    if ($noEmpSesion <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
        $conn->close();
        exit;
    }

    // mostrar = 1 borra la fila centinela; mostrar = 0 la crea.
    $mostrar = isset($_POST['mostrar']) && $_POST['mostrar'] !== '0';

    if ($mostrar) {
        $stmt = $conn->prepare(
            "DELETE FROM cumple_aplausos
             WHERE no_empleado_destino = ? AND no_empleado_origen = ? AND fecha = ?"
        );
    } else {
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO cumple_aplausos (no_empleado_destino, no_empleado_origen, fecha)
             VALUES (?, ?, ?)"
        );
    }
    if ($stmt) {
        $fecha = CUMPLE_PREF_FECHA;
        $stmt->bind_param('iis', $noEmpSesion, $noEmpSesion, $fecha);
        $stmt->execute();
        $stmt->close();
    }

    // Se relee siempre, para que la respuesta refleje lo que quedó en la base.
    $mostrarActual = true;
    $stmtL = $conn->prepare(
        "SELECT 1 FROM cumple_aplausos
         WHERE no_empleado_destino = ? AND no_empleado_origen = ? AND fecha = ?
         LIMIT 1"
    );
    if ($stmtL) {
        $fecha = CUMPLE_PREF_FECHA;
        $stmtL->bind_param('iis', $noEmpSesion, $noEmpSesion, $fecha);
        $stmtL->execute();
        $mostrarActual = !$stmtL->get_result()->fetch_assoc();
        $stmtL->close();
    }

    echo json_encode(['success' => true, 'mostrar' => $mostrarActual]);
    $conn->close();
    exit;
}

// REGISTRAR UNA FELICITACIÓN
// Solo se guarda que alguien felicitó, no cuántas veces: al festejado le importa
// quién se acordó, y así el minijuego puede seguir sumando clics sin tocar la red.
// El INSERT ... SELECT valida en la misma sentencia que el destino de verdad
// cumpla años hoy y esté activo, así que no se pueden sembrar filas arbitrarias.
if ($accion == 'cumple_aplaudir') {
    ob_clean();
    header('Content-Type: application/json');

    // El origen sale de la cookie, nunca del POST: si viniera del cliente,
    // cualquiera podría felicitar en nombre de otro.
    $origen  = intval($_COOKIE['noEmpleadoL'] ?? 0);
    $destino = intval($_POST['destino'] ?? 0);

    if ($origen <= 0 || $destino <= 0 || $origen === $destino) {
        echo json_encode(['success' => false, 'message' => 'Felicitación no válida.']);
        $conn->close();
        exit;
    }

    $stmtAp = $conn->prepare(
        "INSERT IGNORE INTO cumple_aplausos (no_empleado_destino, no_empleado_origen, fecha)
         SELECT u.noEmpleado, ?, CURDATE()
         FROM usuarios u
         WHERE u.noEmpleado = ?
           AND u.estatus = 1
           AND u.fechaNacimiento IS NOT NULL
           AND MONTH(u.fechaNacimiento) = MONTH(CURDATE())
           AND DAY(u.fechaNacimiento)   = DAY(CURDATE())"
    );
    if (!$stmtAp) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar el registro.']);
        $conn->close();
        exit;
    }
    $stmtAp->bind_param('ii', $origen, $destino);
    $stmtAp->execute();
    $stmtAp->close();

    echo json_encode(['success' => true]);
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

//FUNCION AGREGAR ACCESOS A INGENIEROS
if($accion == 'agregar_accesos_plantas'){
    $noEmpleado = $_POST['noEmpleado'] ?? '';
    $accesos = $_POST['accesos'] ?? [];

    if (empty($noEmpleado) || empty($accesos) || !is_array($accesos)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos.']);
        exit;
    }

    $sqlInsertAcceso = "INSERT INTO accesos_plantas_ingenieros (noEmpleado, cliente, vigencia) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sqlInsertAcceso);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en la preparación de la consulta.', 'error' => $conn->error]);
        $conn->close();
        exit;
    }

    $exitoTotal = true;
    $errorMsg = '';

    // Iterar sobre cada cliente/planta enviado desde el formulario dinámico
    foreach ($accesos as $item) {
        $cliente = $item['cliente'] ?? '';
        $vigencia = $item['vigencia'] ?? '';

        if (!empty($cliente) && !empty($vigencia)) {
            // "iss" -> i (integer para noEmpleado), s (string para cliente), s (string para vigencia)
            $stmt->bind_param("iss", $noEmpleado, $cliente, $vigencia);
            
            if (!$stmt->execute()) {
                $exitoTotal = false;
                $errorMsg = $stmt->error;
                break; // Si uno falla, podemos detenernos o continuar según prefieras
            }
        }
    }

    $stmt->close();
    $conn->close();

    if ($exitoTotal) {
        echo json_encode(['success' => true, 'message' => 'Accesos agregados correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar algunos accesos.', 'error' => $errorMsg]);
    }
    
    exit;
}

// Obtener accesos actuales del empleado
if($accion == 'obtener_accesos_plantas'){
    $noEmpleado = $_POST['noEmpleado'] ?? '';
    
    $sql = "SELECT id, cliente, vigencia FROM accesos_plantas_ingenieros WHERE noEmpleado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $noEmpleado);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $accesos = [];
    while ($row = $result->fetch_assoc()) {
        $accesos[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true, 'accesos' => $accesos]);
    exit;
}

// Eliminar un acceso existente
if($accion == 'eliminar_acceso_planta'){
    $id = $_POST['id'] ?? '';
    
    $sql = "DELETE FROM accesos_plantas_ingenieros WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Acceso eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el acceso.']);
    }
    
    $stmt->close();
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
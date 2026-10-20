<?php
/**
 * acciones_avisos.php — Endpoint JSON del módulo de Avisos (pestaña AVISOS y
 * pop-up "NUEVO AVISO" de inicio.php; la vista está en modalAvisos.php +
 * js/avisos.js).
 *
 *   listar     — los vigentes, del más nuevo al más viejo (sin el HTML
 *                completo: un extracto en texto para la tarjeta y el buscador);
 *                los vencidos, sólo a quien puede editarlos
 *   ver        — uno completo (vencido: igual que en listar)
 *   pendientes — vigentes que este empleado no ha aceptado: el pop-up
 *   aceptar    — "Aceptar" del pop-up
 *   guardar    — crear o editar (con PDF opcional); al crear, notifica a todos
 *                en la campana del portal (avNotificar)
 *   ocultar    — deja de verse; no se borra
 *   quienes    — quién aceptó y quién falta (autor o admin)
 *
 * Todos los avisos son para todos. Quién publica y qué puede editar lo decide
 * avPermiso() (includes/avisos.php). CREDENCIAL: la cookie noEmpleadoL, como
 * el resto del portal; no va firmada (ver acciones_album.php).
 *
 * El contenido llega como HTML (editor o pegado de Outlook) y se guarda ya
 * limpio con mbHtmlLimpio() (includes/html_limpio.php): la vista lo pinta tal
 * cual en la pantalla de todos, así que nada sin limpiar entra a la BD.
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
// ../incidencias/conn.php imprime saltos de línea: que no se cuelen antes del JSON.
ob_start();

require_once __DIR__ . '/includes/avisos.php';
require_once __DIR__ . '/includes/html_limpio.php';

const AV_TITULO_MAX    = 120;
const AV_SUBTITULO_MAX = 160;
const AV_HTML_MAX      = 60000;    // caracteres del HTML ya limpio
const AV_EXTRACTO      = 400;      // caracteres del texto que viaja en `listar`

// Vigente = no ha pasado su fecha. Con el reloj de MySQL en todos lados (pop-up,
// lista y "ver más") para que no se contradigan si PHP y MySQL difieren de hora.
// Al vencer, el empleado deja de verlo; quien puede editarlo lo sigue viendo
// marcado como vencido, para extender la fecha o revisar quién lo leyó.
const AV_VIGENTE = '(a.vigencia_hasta >= NOW())';


function avResponder(bool $success, string $message = '', array $extra = []): void {
    ob_end_clean();
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/** El error real va al log; al empleado, un mensaje que entienda. */
function avFallo(string $mensaje, Throwable $e): void {
    error_log('messbook acciones_avisos: ' . $e->getMessage());
    avResponder(false, $mensaje);
}

/** Texto plano del HTML, en una línea, para la tarjeta y el buscador. */
function avTexto(string $html, int $max): string {
    // Un espacio entre bloques: "</p><p>" no debe pegar las palabras.
    $html  = preg_replace('~<(br|/p|/div|/li|/h[3-5]|/tr|/td|/th)[^>]*>~i', ' ', $html);
    $texto = html_entity_decode(strip_tags((string)$html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $texto = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $texto));
    return mb_strlen($texto, 'UTF-8') > $max ? rtrim(mb_substr($texto, 0, $max, 'UTF-8')) . '…' : $texto;
}

/** ¿Puede editar / ocultar este aviso? El autor (si sigue con permiso) o un admin. */
function avPuedeEditar(array $aviso, array $perm, string $noEmpleado): bool {
    return $perm['admin'] || ($perm['crear'] && (string)$aviso['publicado_por'] === $noEmpleado);
}

/**
 * Fila de un aviso con su área, o null. Con `$soloVisibles`, los ocultos no existen.
 * Los vencidos sí: quien puede editarlo todavía lo abre (ver `ver`).
 */
function avAviso(mysqli $conn, int $id, bool $soloVisibles = true): ?array {
    $stmt = $conn->prepare('SELECT a.*, ar.nombre AS area, ar.color, ' . AV_VIGENTE . ' AS vigente
                              FROM mess_rrhh.messbook_avisos a
                              JOIN mess_rrhh.messbook_avisos_areas ar ON ar.id = a.id_area
                             WHERE a.id = ?' . ($soloVisibles ? ' AND a.estatus = 1' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

/** Empleados activos (usuarios es MyISAM y noEmpleado se repite: se cuentan distintos). */
function avTotalEmpleados(mysqli $conn): int {
    return (int)$conn->query('SELECT COUNT(DISTINCT noEmpleado) FROM mess_rrhh.usuarios WHERE estatus = 1')->fetch_row()[0];
}

/**
 * Campana del portal (mess_rrhh.notificacion_historial, la que leen todos los
 * sistemas): una notificación por empleado activo, menos el autor. Va aparte
 * del pop-up: aceptar el aviso no la marca leída ni al revés. Si falla, el
 * aviso ya quedó publicado: se registra en el log y se sigue.
 * El REGEXP deja fuera los noEmpleado vacíos o con letras, que en modo
 * estricto tumbarían todo el INSERT al meterlos en id_usuario_destino.
 */
function avNotificar(mysqli $conn, int $idAviso, string $autor, string $area, string $titulo): void {
    $texto    = mb_substr('Nuevo aviso de ' . $area . ': ' . $titulo, 0, 500, 'UTF-8');
    $autorInt = (int)$autor;
    try {
        $stmt = $conn->prepare("INSERT INTO mess_rrhh.notificacion_historial
                                       (id_usuario_actualiza, id_usuario_destino, accion, sistema, archivo,
                                        id_registro_referencia, fecha_creacion, fecha_atencion, recordar, estatus)
                                SELECT ?, u.noEmpleado, 'NuevoAviso', 'avisos', 'aviso', ?, NOW(), NULL, ?, 'NoLeida'
                                  FROM (SELECT DISTINCT noEmpleado FROM mess_rrhh.usuarios
                                         WHERE estatus = 1 AND noEmpleado REGEXP '^[0-9]+$' AND noEmpleado <> ?) u");
        $stmt->bind_param('iiss', $autorInt, $idAviso, $texto, $autor);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('messbook acciones_avisos: no se crearon las notificaciones del aviso ' . $idAviso . ': ' . $e->getMessage());
    }
}

/** El aviso tal como lo consume la vista. `$completo`: con el HTML. */
function avSalida(array $a, array $perm, string $noEmpleado, bool $completo): array {
    $editar = avPuedeEditar($a, $perm, $noEmpleado);
    $salida = [
        'id'        => (int)$a['id'],
        'id_area'   => (int)$a['id_area'],
        'area'      => (string)$a['area'],
        'color'     => (string)$a['color'],
        'titulo'    => (string)$a['titulo'],
        'subtitulo' => (string)$a['subtitulo'],
        'pdf'       => avRutaPdf((string)$a['pdf']) !== null ? (string)$a['pdf'] : null,
        'fecha'     => avFecha((string)$a['publicado_el']),
        'vigencia'  => avFecha((string)$a['vigencia_hasta']),
        // Para el <input type="datetime-local"> del modal de edición.
        'vigencia_input' => $editar ? date('Y-m-d\TH:i', strtotime((string)$a['vigencia_hasta'])) : null,
        'vigente'   => (bool)$a['vigente'],                 // AV_VIGENTE, del SELECT
        'editar'    => $editar,
    ];
    if ($completo) $salida['html']  = (string)$a['contenido_html'];
    else           $salida['texto'] = avTexto((string)$a['contenido_html'], AV_EXTRACTO);
    if ($editar && isset($a['aceptados'])) $salida['aceptados'] = (int)$a['aceptados'];
    return $salida;
}


// ── Credencial ───────────────────────────────────────────────────────────────
$noEmpleado = trim((string)($_COOKIE['noEmpleadoL'] ?? ''));
if (!preg_match('/^\d{1,11}$/', $noEmpleado)) {
    avResponder(false, 'Tu sesión expiró. Vuelve a iniciar sesión.', ['expirada' => true]);
}

try {
    require_once __DIR__ . '/../incidencias/conn.php';
    mysqli_set_charset($conn, 'utf8mb4');

    // La cookie identifica; el empleado tiene que existir y estar activo.
    $stmt = $conn->prepare('SELECT 1 FROM mess_rrhh.usuarios WHERE noEmpleado = ? AND estatus = 1 LIMIT 1');
    $stmt->bind_param('s', $noEmpleado);
    $stmt->execute();
    $activo = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();

    $perm = avPermiso($conn, $noEmpleado);
} catch (Throwable $e) {
    avFallo('No pudimos conectar con los avisos. Inténtalo de nuevo en un momento.', $e);
}
if (!$activo) avResponder(false, 'Tu sesión expiró. Vuelve a iniciar sesión.', ['expirada' => true]);

$accion = (string)($_POST['accion'] ?? '');


// ── listar ───────────────────────────────────────────────────────────────────
// Los vigentes; los vencidos, sólo a quien puede editarlos (AV_VIGENTE). Para
// quien puede editar, además cuántos lo aceptaron de cuántos empleados.
if ($accion === 'listar') {
    try {
        $res = $conn->query('SELECT a.id, a.id_area, a.titulo, a.subtitulo, a.contenido_html, a.pdf,
                                    a.vigencia_hasta, a.publicado_por, a.publicado_el,
                                    ar.nombre AS area, ar.color, ' . AV_VIGENTE . ' AS vigente,
                                    (SELECT COUNT(*) FROM mess_rrhh.messbook_avisos_aceptados ac WHERE ac.id_aviso = a.id) AS aceptados
                               FROM mess_rrhh.messbook_avisos a
                               JOIN mess_rrhh.messbook_avisos_areas ar ON ar.id = a.id_area
                              WHERE a.estatus = 1
                              ORDER BY a.publicado_el DESC, a.id DESC');
        $avisos = array_map(function ($a) use ($perm, $noEmpleado) { return avSalida($a, $perm, $noEmpleado, false); },
                            $res->fetch_all(MYSQLI_ASSOC));
        $avisos = array_values(array_filter($avisos, function ($a) { return $a['vigente'] || $a['editar']; }));

        $extra = ['avisos' => $avisos, 'puede_crear' => $perm['crear']];
        if ($perm['crear']) {
            // Opciones del formulario: sólo las áreas con las que puede publicar.
            $areas = array_values(avAreas($conn));
            if ($perm['area'] !== null) $areas = array_values(array_filter($areas, function ($ar) use ($perm) { return $ar['id'] === $perm['area']; }));
            $extra['areas'] = $areas;
        }
        foreach ($avisos as $a) { if ($a['editar']) { $extra['total_empleados'] = avTotalEmpleados($conn); break; } }
        avResponder(true, '', $extra);
    } catch (Throwable $e) {
        avFallo('No pudimos cargar los avisos.', $e);
    }
}


// ── ver ──────────────────────────────────────────────────────────────────────
if ($accion === 'ver') {
    try {
        $a = avAviso($conn, (int)($_POST['id'] ?? 0));
        // Vencido: igual que en `listar`, sólo para quien puede editarlo (p. ej.
        // una notificación de la campana que se abre ya fuera de vigencia).
        if (!$a || (!$a['vigente'] && !avPuedeEditar($a, $perm, $noEmpleado))) avResponder(false, 'Ese aviso ya no está disponible.');
        $salida = avSalida($a, $perm, $noEmpleado, true);
        if ($salida['editar']) {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM mess_rrhh.messbook_avisos_aceptados WHERE id_aviso = ?');
            $stmt->bind_param('i', $salida['id']);
            $stmt->execute();
            $salida['aceptados'] = (int)$stmt->get_result()->fetch_row()[0];
            $stmt->close();
            $salida['total_empleados'] = avTotalEmpleados($conn);
        }
        avResponder(true, '', ['aviso' => $salida]);
    } catch (Throwable $e) {
        avFallo('No pudimos abrir el aviso.', $e);
    }
}


// ── pendientes ───────────────────────────────────────────────────────────────
// El pop-up de inicio.php: los vigentes que no ha aceptado, del más viejo al
// más nuevo (así se leen en el orden en que se publicaron).
if ($accion === 'pendientes') {
    try {
        $stmt = $conn->prepare('SELECT a.*, ar.nombre AS area, ar.color, 1 AS vigente
                                  FROM mess_rrhh.messbook_avisos a
                                  JOIN mess_rrhh.messbook_avisos_areas ar ON ar.id = a.id_area
                                 WHERE a.estatus = 1 AND ' . AV_VIGENTE . '
                                   AND NOT EXISTS (SELECT 1 FROM mess_rrhh.messbook_avisos_aceptados ac
                                                    WHERE ac.id_aviso = a.id AND ac.noEmpleado = ?)
                                 ORDER BY a.publicado_el ASC, a.id ASC');
        $stmt->bind_param('s', $noEmpleado);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        avResponder(true, '', ['avisos' => array_map(function ($a) use ($perm, $noEmpleado) {
            return avSalida($a, $perm, $noEmpleado, true);
        }, $filas)]);
    } catch (Throwable $e) {
        avFallo('No pudimos revisar los avisos nuevos.', $e);
    }
}


// ── aceptar ──────────────────────────────────────────────────────────────────
if ($accion === 'aceptar') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        // no_disponible: se ocultó mientras estaba en pantalla; el pop-up pasa al siguiente.
        if (!avAviso($conn, $id)) avResponder(false, 'Ese aviso ya no está disponible.', ['no_disponible' => true]);
        // INSERT IGNORE: aceptarlo dos veces (dos pestañas abiertas) no es error;
        // se queda la primera fecha.
        $stmt = $conn->prepare('INSERT IGNORE INTO mess_rrhh.messbook_avisos_aceptados (id_aviso, noEmpleado, aceptado_el) VALUES (?, ?, NOW())');
        $stmt->bind_param('is', $id, $noEmpleado);
        $stmt->execute();
        $stmt->close();
        avResponder(true);
    } catch (Throwable $e) {
        avFallo('No pudimos registrar que lo leíste. Inténtalo de nuevo.', $e);
    }
}


// ── guardar ──────────────────────────────────────────────────────────────────
// Crear (id = 0) o editar. Editar no vuelve a sacar el pop-up a quien ya lo
// aceptó. PDF: uno nuevo reemplaza al anterior; `quitar_pdf` lo quita; sin
// ninguno de los dos, se queda el que tenía.
if ($accion === 'guardar') {
    if (!$perm['crear']) avResponder(false, 'No tienes permiso para publicar avisos. Solicítalo al área de BI.');

    $id        = (int)($_POST['id'] ?? 0);
    $esNuevo   = $id === 0;
    $limpia1   = function ($s) { return trim((string)preg_replace('/[\p{Cc}\s]+/u', ' ', (string)$s)); };
    $titulo    = $limpia1($_POST['titulo'] ?? '');
    $subtitulo = $limpia1($_POST['subtitulo'] ?? '');
    $idArea    = (int)($_POST['id_area'] ?? 0);
    $vigencia  = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', (string)($_POST['vigencia'] ?? ''));

    if ($titulo === '')                                         avResponder(false, 'Escribe el título del aviso.');
    if (mb_strlen($titulo, 'UTF-8') > AV_TITULO_MAX)            avResponder(false, 'El título es muy largo: máximo ' . AV_TITULO_MAX . ' caracteres.');
    if (mb_strlen($subtitulo, 'UTF-8') > AV_SUBTITULO_MAX)      avResponder(false, 'El subtítulo es muy largo: máximo ' . AV_SUBTITULO_MAX . ' caracteres.');
    if (!$vigencia || $vigencia->format('Y-m-d\TH:i') !== (string)$_POST['vigencia']) avResponder(false, 'Elige hasta cuándo es vigente el aviso.');

    $html = mbHtmlLimpio((string)($_POST['contenido'] ?? ''));
    if ($html === '')                                           avResponder(false, 'Escribe el contenido del aviso.');
    if (mb_strlen($html, 'UTF-8') > AV_HTML_MAX)                avResponder(false, 'El contenido es muy largo. Adjunta el detalle en el PDF.');

    // PDF (opcional). Se valida por la FIRMA ("%PDF-"), no por la extensión ni
    // por lo que diga el navegador, igual que el mural (subir_mural).
    $archivo = $_FILES['pdf'] ?? null;
    $errPdf  = is_array($archivo) ? (int)$archivo['error'] : UPLOAD_ERR_NO_FILE;
    if ($errPdf === UPLOAD_ERR_INI_SIZE || $errPdf === UPLOAD_ERR_FORM_SIZE) avResponder(false, 'El PDF es demasiado pesado: máximo 20 MB.');
    if ($errPdf !== UPLOAD_ERR_OK && $errPdf !== UPLOAD_ERR_NO_FILE)         avResponder(false, 'No recibimos el PDF. Inténtalo de nuevo.');
    $conPdf = $errPdf === UPLOAD_ERR_OK && is_uploaded_file((string)$archivo['tmp_name']);
    if ($conPdf) {
        if ((int)$archivo['size'] > AV_PDF_MAX) avResponder(false, 'El PDF es demasiado pesado: máximo 20 MB.');
        $fh = @fopen((string)$archivo['tmp_name'], 'rb');
        $firma = $fh ? fread($fh, 5) : '';
        if ($fh) fclose($fh);
        if ($firma !== '%PDF-') avResponder(false, 'Ese archivo no es un PDF.');
    }

    try {
        $anterior = null;
        if ($id > 0) {
            $aviso = avAviso($conn, $id);
            if (!$aviso) avResponder(false, 'Ese aviso ya no está disponible. Recarga la lista.');
            if (!avPuedeEditar($aviso, $perm, $noEmpleado)) avResponder(false, 'Sólo quien publicó el aviso puede editarlo.');
            $anterior = avRutaPdf((string)$aviso['pdf']) !== null ? (string)$aviso['pdf'] : null;
        } elseif ($vigencia->getTimestamp() < time()) {
            avResponder(false, 'La vigencia ya pasó: elige una fecha futura para que salga el aviso.');
        }

        // Área: la de su permiso, o la que eligió si puede publicar en cualquiera.
        $areas = avAreas($conn);
        if ($perm['area'] !== null) $idArea = $perm['area'];
        if (!isset($areas[$idArea])) avResponder(false, 'Elige el área que publica el aviso.');

        $pdf   = !empty($_POST['quitar_pdf']) ? null : $anterior;
        $nuevo = null;
        if ($conPdf) {
            $dir = __DIR__ . '/' . AV_CARPETA;
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            if (!is_dir($dir) || !is_writable($dir)) {
                error_log('messbook acciones_avisos: la carpeta ' . AV_CARPETA . '/ no existe o no tiene permiso de escritura.');
                avResponder(false, 'Los PDF no están disponibles en este momento. Avísale a BI.');
            }
            $nuevo = AV_CARPETA . '/' . bin2hex(random_bytes(12)) . '.pdf';
            if (!move_uploaded_file((string)$archivo['tmp_name'], avRutaPdf($nuevo))) {
                avResponder(false, 'No pudimos guardar el PDF. Inténtalo de nuevo.');
            }
            $pdf = $nuevo;
        }

        $vig = $vigencia->format('Y-m-d H:i:00');
        try {
            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE mess_rrhh.messbook_avisos
                                           SET id_area = ?, titulo = ?, subtitulo = ?, contenido_html = ?, pdf = ?,
                                               vigencia_hasta = ?, actualizado_por = ?
                                         WHERE id = ?');
                $stmt->bind_param('issssssi', $idArea, $titulo, $subtitulo, $html, $pdf, $vig, $noEmpleado, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO mess_rrhh.messbook_avisos
                                               (id_area, titulo, subtitulo, contenido_html, pdf, vigencia_hasta, publicado_por, publicado_el)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->bind_param('issssss', $idArea, $titulo, $subtitulo, $html, $pdf, $vig, $noEmpleado);
            }
            $stmt->execute();
            if ($id === 0) $id = (int)$conn->insert_id;
            $stmt->close();
            // Quien lo publica ya lo leyó: a él no le sale en el pop-up.
            if ($esNuevo) {
                $stmt = $conn->prepare('INSERT IGNORE INTO mess_rrhh.messbook_avisos_aceptados (id_aviso, noEmpleado, aceptado_el) VALUES (?, ?, NOW())');
                $stmt->bind_param('is', $id, $noEmpleado);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $e) {
            if ($nuevo !== null) @unlink(avRutaPdf($nuevo));      // sin fila, el archivo sobra
            throw $e;
        }
        // El PDF que se reemplazó o se quitó ya no lo usa nadie.
        if ($anterior !== null && $anterior !== $pdf) @unlink(avRutaPdf($anterior));

        // Sólo al publicarlo: editarlo no vuelve a notificar.
        if ($esNuevo) avNotificar($conn, $id, $noEmpleado, $areas[$idArea]['nombre'], $titulo);

        avResponder(true, $esNuevo ? '¡Aviso publicado!' : 'Aviso actualizado.', ['id' => $id]);
    } catch (Throwable $e) {
        avFallo('No pudimos guardar el aviso.', $e);
    }
}


// ── ocultar ──────────────────────────────────────────────────────────────────
// No se borra: deja de salir en la lista y en el pop-up. El PDF se queda.
if ($accion === 'ocultar') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $aviso = avAviso($conn, $id);
        if (!$aviso) avResponder(false, 'Ese aviso ya no está disponible.');
        if (!avPuedeEditar($aviso, $perm, $noEmpleado)) avResponder(false, 'Sólo quien publicó el aviso puede ocultarlo.');
        $stmt = $conn->prepare('UPDATE mess_rrhh.messbook_avisos SET estatus = 0, actualizado_por = ? WHERE id = ?');
        $stmt->bind_param('si', $noEmpleado, $id);
        $stmt->execute();
        $stmt->close();
        avResponder(true, 'Aviso ocultado.');
    } catch (Throwable $e) {
        avFallo('No pudimos ocultar el aviso.', $e);
    }
}


// ── quienes ──────────────────────────────────────────────────────────────────
// Trazabilidad para el autor o un admin: quién aceptó (y cuándo) y quién falta.
if ($accion === 'quienes') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $aviso = avAviso($conn, $id);
        if (!$aviso) avResponder(false, 'Ese aviso ya no está disponible.');
        if (!avPuedeEditar($aviso, $perm, $noEmpleado)) avResponder(false, 'Sólo quien publicó el aviso puede ver quién lo leyó.');

        // usuarios.noEmpleado es latin1; las tablas de avisos, utf8mb4.
        $sql = "SELECT CONVERT(u.noEmpleado USING utf8mb4) COLLATE utf8mb4_spanish_ci AS noEmpleado,
                       MAX(CONVERT(u.nombre USING utf8mb4)) AS nombre,
                       MAX(ac.aceptado_el) AS aceptado_el
                  FROM mess_rrhh.usuarios u
                  LEFT JOIN mess_rrhh.messbook_avisos_aceptados ac
                         ON ac.id_aviso = ? AND ac.noEmpleado = CONVERT(u.noEmpleado USING utf8mb4) COLLATE utf8mb4_spanish_ci
                 WHERE u.estatus = 1
                 GROUP BY 1
                 ORDER BY nombre";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $aceptaron = []; $faltan = [];
        foreach ($filas as $f) {
            if ($f['aceptado_el'] !== null) $aceptaron[] = ['nombre' => (string)$f['nombre'], 'fecha' => avFecha((string)$f['aceptado_el'])];
            else                            $faltan[]    = ['nombre' => (string)$f['nombre']];
        }
        avResponder(true, '', ['aceptaron' => $aceptaron, 'faltan' => $faltan]);
    } catch (Throwable $e) {
        avFallo('No pudimos cargar quién leyó el aviso.', $e);
    }
}


avResponder(false, 'Acción no válida.');

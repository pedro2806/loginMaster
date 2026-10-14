<?php
/**
 * avisos.php — Lo que comparten acciones_avisos.php (el endpoint) e inicio.php
 * (botón "Crear Aviso" y logo) del módulo de Avisos.
 *
 * Tablas (sql/2026-10-14_avisos.sql), en mess_rrhh:
 *   messbook_avisos_areas      — quién publica: nombre y color de la barra.
 *   messbook_avisos            — los avisos; contenido_html ya limpio.
 *   messbook_avisos_aceptados  — quién aceptó cada uno en el pop-up y cuándo.
 *
 * Todos los avisos son para todos los empleados. El ÁREA es el remitente (la
 * barra de color y el encabezado), no el destinatario.
 */

// Mismos admins que $empleadosAdmin de inicio.php: crean avisos de cualquier
// área sin permiso especial y pueden editar u ocultar los de todos.
const AV_ADMINS = [276, 403, 569, 523, 183];

// PDF adjunto: uploads/avisos/<24 hex>.pdf. /uploads está en el .gitignore.
const AV_CARPETA = 'uploads/avisos';
const AV_PDF_MAX = 20 * 1024 * 1024;     // igual que el mural (acciones_inicio.php, subir_mural)

// Logo de messbook con el megáfono, uno por tema (la vista enseña el que toca
// con CSS). Si falta un archivo, el logo de siempre.
const AV_LOGO_CLARO    = 'img/avisos/Avisos.png';
const AV_LOGO_OSCURO   = 'img/avisos/Avisos Fondo oscuro.png';
const AV_LOGO_RESPALDO = 'img/messbook_logo3.png';

/**
 * Qué puede hacer este empleado con los avisos:
 *   crear — ve "Crear Aviso".
 *   admin — edita u oculta cualquiera.
 *   area  — id del área con la que publica; null = elige cualquiera.
 *
 * El permiso es accesos_especiales (sistema 'avisos', opcion 'crearAviso'),
 * que se da desde el modal de Accesos Especiales. inf_adicional = id del área;
 * vacío o 'TODAS' = cualquier área (la misma convención de entradasEq).
 * OJO: $noEmpleado sale de la cookie noEmpleadoL, que no va firmada.
 */
function avPermiso(mysqli $conn, string $noEmpleado): array {
    $sin = ['crear' => false, 'admin' => false, 'area' => null];
    if (!preg_match('/^\d{1,11}$/', $noEmpleado)) return $sin;
    if (in_array((int)$noEmpleado, AV_ADMINS, true)) return ['crear' => true, 'admin' => true, 'area' => null];

    $n = (int)$noEmpleado;
    $stmt = $conn->prepare("SELECT inf_adicional FROM mess_rrhh.accesos_especiales
                             WHERE noEmpleado = ? AND sistema = 'avisos' AND opcion = 'crearAviso' AND estatus = 1
                             ORDER BY id LIMIT 1");
    $stmt->bind_param('i', $n);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$fila) return $sin;

    $inf = strtoupper(trim((string)$fila[0]));
    return ['crear' => true, 'admin' => false, 'area' => ctype_digit($inf) ? (int)$inf : null];
}

/** Las áreas, en su orden: [id => ['id', 'nombre', 'color']]. */
function avAreas(mysqli $conn): array {
    $areas = [];
    $res = $conn->query('SELECT id, nombre, color FROM mess_rrhh.messbook_avisos_areas ORDER BY orden, id');
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $a) {
        $areas[(int)$a['id']] = ['id' => (int)$a['id'], 'nombre' => (string)$a['nombre'], 'color' => (string)$a['color']];
    }
    return $areas;
}

/** Ruta en disco del PDF de un aviso. null si la url no es de las nuestras. */
function avRutaPdf(string $url): ?string {
    if (!preg_match('~^' . AV_CARPETA . '/[a-f0-9]{24}\.pdf$~', $url)) return null;
    return dirname(__DIR__) . '/' . $url;
}

/**
 * Los dos <img> del logo (tema claro y oscuro) para la pestaña y los modales.
 * CSS esconde el que no corresponde al tema (.avisos-logo--claro/--oscuro).
 * $clase: la clase de tamaño de donde va (avisos-logo, aviso-modal__logo).
 */
function avLogosHtml(string $clase): string {
    $html = '';
    foreach (['claro' => AV_LOGO_CLARO, 'oscuro' => AV_LOGO_OSCURO] as $tema => $ruta) {
        if (!is_file(dirname(__DIR__) . '/' . $ruta)) $ruta = AV_LOGO_RESPALDO;
        // El nombre del archivo lleva espacios: va codificado en la URL.
        $src   = implode('/', array_map('rawurlencode', explode('/', $ruta)));
        $html .= '<img class="' . htmlspecialchars($clase) . ' avisos-logo--' . $tema . '" src="' . htmlspecialchars($src) . '" alt="messbook">';
    }
    return $html;
}

/** "2026-10-13 11:09:00" → "13/10/2026 11:09 a. m." (como en la propuesta). */
function avFecha(string $fecha): string {
    $ts = strtotime($fecha);
    if ($ts === false) return '';
    return date('d/m/Y h:i', $ts) . (date('H', $ts) < 12 ? ' a. m.' : ' p. m.');
}

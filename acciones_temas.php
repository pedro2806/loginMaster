<?php
/**
 * acciones_temas.php — Endpoint JSON de los logos y colores de messbook
 * (modalTemas.php).
 *
 * Las versiones de evento de los logos y colores (includes/tema.php): listar,
 * guardar (datos, logos y colores en un solo envío) y borrar. Los colores sólo
 * se aceptan como '#rrggbb' (mbTemaColorValido): es lo que inicio.php e
 * index.php imprimen dentro de un <style>, así que nada más puede colarse. TODAS las acciones son sólo para
 * BI (mbTemaPuedeAdministrar); inicio.php no incluye el modal para nadie más,
 * pero el permiso se vuelve a revisar aquí en cada petición.
 *
 * CREDENCIAL: la cookie noEmpleadoL, como el resto del portal. OJO: no va
 * firmada; quien la cambie a mano por la de alguien de BI puede cambiar los
 * logos. Lo que sube se vuelve a pintar con GD como PNG (mbGuardarPngLimpio),
 * así que en el disco nunca queda el archivo que mandó el navegador, y la
 * carpeta lleva un .htaccess que sólo sirve PNG.
 *
 * Los archivos van a uploads/temas/ (en el .gitignore): un deploy no los pisa.
 * Cada subida lleva nombre nuevo, así que el navegador no se queda con el
 * logo viejo en caché. El que se reemplaza o se quita se borra del disco
 * después del commit; los de img/ (del repo) nunca se borran.
 */
header('Content-Type: application/json; charset=utf-8');
// Este archivo sólo habla JSON: un aviso de PHP impreso rompe la respuesta.
ini_set('display_errors', '0');

// ../incidencias/conn.php imprime saltos de línea entre sus dos bloques <?php;
// se atrapan aquí para que no se cuelen antes del JSON.
ob_start();

require_once __DIR__ . '/includes/imagen.php';
require_once __DIR__ . '/includes/tema.php';

const MB_TEMA_LADO         = 1200;  // px del lado largo de un logo
const MB_TEMA_LADO_FAVICON = 256;   // ídem, el favicon
const MB_TEMA_NOMBRE_MAX   = 80;    // messbook_temas.nombre

function tmResponder(bool $success, string $message = '', array $extra = []): void {
    ob_end_clean();
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/** El error real va al log; a quien usa el modal, un mensaje que entienda. */
function tmFallo(string $mensaje, Throwable $e): void {
    error_log('messbook acciones_temas: ' . $e->getMessage());
    tmResponder(false, $mensaje);
}

/** "2026-12-01T08:00" (datetime-local) → "2026-12-01 08:00:00". null si no es fecha. */
function tmFechaHora(string $f): ?string {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', trim($f), new DateTimeZone(MB_TEMA_ZONA));
    return $d ? $d->format('Y-m-d H:i:s') : null;
}

/** "2026-12-01 08:00:00" → "2026-12-01T08:00", para el input del modal. */
function tmLocal(?string $f): string {
    return $f ? str_replace(' ', 'T', substr($f, 0, 16)) : '';
}

/** Nombre del lugar para los mensajes: "Logo del portal", "Tarjeta Activos". */
function tmNombreLugar(string $lugar): string {
    if (isset(MB_TEMA_LUGARES[$lugar])) return MB_TEMA_LUGARES[$lugar];
    return 'Tarjeta ' . MB_TEMA_SISTEMAS[substr($lugar, 4)][0];
}

/**
 * Lo que mandó el navegador para un lugar en el campo `logo` u `oscuro`
 * (logo[portal], oscuro[sis:divNest]...). null si no mandó nada; si mandó
 * algo que no sirve, responde el error y termina.
 */
function tmArchivo(string $campo, string $lugar): ?string {
    $f = $_FILES[$campo] ?? null;
    if (!is_array($f) || !isset($f['error'][$lugar])) return null;
    $err = (int)$f['error'][$lugar];
    if ($err === UPLOAD_ERR_NO_FILE) return null;
    $quien = tmNombreLugar($lugar) . ($campo === 'oscuro' ? ' (tema oscuro)' : '');
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        tmResponder(false, $quien . ': la imagen es demasiado pesada. Prueba con otra.');
    }
    $tmp = (string)$f['tmp_name'][$lugar];
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
        tmResponder(false, $quien . ': no recibimos la imagen. Inténtalo de nuevo.');
    }
    $error = mbRevisarImagen($tmp);
    if ($error !== '') tmResponder(false, $quien . ': ' . $error);
    return $tmp;
}

/**
 * La carpeta de los logos, con su .htaccess: ahí sólo se sirven PNG (los que
 * escribe este archivo). Por el incidente de 2026 (un PHP subido a la carpeta
 * de justificantes de Practicantes), aunque GD ya no deje pasar otra cosa.
 */
function tmCarpeta(): string {
    $dir = __DIR__ . '/' . MB_TEMA_CARPETA;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('No se pudo crear ' . $dir);
    }
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "# Lo escribe acciones_temas.php: aquí sólo hay logos PNG.\n"
            . "<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>\n"
            . "Require all denied\n"
            . "<FilesMatch \"^[A-Za-z0-9_-]+\\.png$\">\n    Require all granted\n</FilesMatch>\n");
    }
    return $dir;
}

/** Borra del disco los logos subidos que ya no usa nadie. Los de img/ no. */
function tmBorrarArchivos(array $rutas): void {
    foreach (array_unique($rutas) as $r) {
        if (mbTemaEsSubida($r)) @unlink(__DIR__ . '/' . $r);
    }
}

/**
 * ¿Ya existe la tabla de colores (sql/2026-11-27_temas_colores.sql)? Sin ella
 * los logos siguen funcionando y la sección de colores del modal lo avisa.
 */
function tmHayColores(mysqli $conn): bool {
    static $hay = null;
    if ($hay === null) {
        try {
            $conn->query('SELECT 1 FROM mess_rrhh.messbook_tema_colores LIMIT 1');
            $hay = true;
        } catch (Throwable $e) {
            $hay = false;
        }
    }
    return $hay;
}

/** Los colores de una versión: clave → ['claro', 'oscuro']. [] sin la tabla. */
function tmColoresDe(mysqli $conn, int $id): array {
    if (!tmHayColores($conn)) return [];
    $colores = [];
    $stmt = $conn->prepare('SELECT clave, claro, oscuro FROM mess_rrhh.messbook_tema_colores WHERE id_tema = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($c = $res->fetch_assoc()) {
        if (!isset(MB_TEMA_COLORES[$c['clave']]) || !mbTemaColorValido($c['claro'])) continue;
        $colores[(string)$c['clave']] = [
            'claro'  => strtolower($c['claro']),
            'oscuro' => mbTemaColorValido($c['oscuro']) ? strtolower($c['oscuro']) : null,
        ];
    }
    $stmt->close();
    return $colores;
}

/**
 * Los colores que mandó el modal para la versión: el juego completo, como
 * JSON {clave: {claro, oscuro|null}}. Lo que no viene se quita (vuelve al de
 * siempre). null si no mandó el campo: entonces los colores no se tocan. Si
 * algo no sirve, responde el error y termina.
 */
function tmColoresPedidos(): ?array {
    if (!isset($_POST['colores'])) return null;
    $datos = json_decode((string)$_POST['colores'], true);
    if (!is_array($datos)) tmResponder(false, 'No entendimos los colores. Recarga la página e inténtalo de nuevo.');
    $colores = [];
    foreach ($datos as $clave => $c) {
        if (!isset(MB_TEMA_COLORES[$clave]) || !is_array($c)) {
            tmResponder(false, 'Hay un color que no existe. Recarga la página e inténtalo de nuevo.');
        }
        $nombre = MB_TEMA_COLORES[$clave][0];
        $claro  = $c['claro'] ?? null;
        $oscuro = $c['oscuro'] ?? null;
        if ($claro === null || $claro === '') {
            tmResponder(false, $nombre . ': escoge el color del tema claro; el oscuro sólo lo acompaña.');
        }
        if (!mbTemaColorValido($claro)) tmResponder(false, $nombre . ': no es un color válido.');
        if ($oscuro !== null && $oscuro !== '') {
            if (MB_TEMA_COLORES[$clave][3] === null) tmResponder(false, $nombre . ' va igual en los dos temas: no lleva color oscuro.');
            if (!mbTemaColorValido($oscuro)) tmResponder(false, $nombre . ' (tema oscuro): no es un color válido.');
        } else {
            $oscuro = null;
        }
        $colores[$clave] = ['claro' => strtolower($claro), 'oscuro' => $oscuro === null ? null : strtolower($oscuro)];
    }
    return $colores;
}

/** Una versión, con sus logos por lugar. null si no existe. */
function tmVersion(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT id_tema, nombre, predeterminada, desde, hasta FROM mess_rrhh.messbook_temas WHERE id_tema = ? AND visible = 1 LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $t = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$t) return null;
    $t['logos'] = [];
    $stmt = $conn->prepare('SELECT lugar, archivo, archivo_oscuro FROM mess_rrhh.messbook_tema_logos WHERE id_tema = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($l = $res->fetch_assoc()) {
        $t['logos'][(string)$l['lugar']] = ['archivo' => (string)$l['archivo'], 'oscuro' => $l['archivo_oscuro']];
    }
    $stmt->close();
    $t['colores'] = tmColoresDe($conn, $id);
    return $t;
}

/** Nombre de la otra versión visible que se encima con [desde, hasta), o ''. */
function tmEncimada(mysqli $conn, int $id, string $desde, string $hasta): string {
    $stmt = $conn->prepare('SELECT nombre FROM mess_rrhh.messbook_temas
                             WHERE visible = 1 AND predeterminada = 0 AND id_tema <> ?
                               AND desde < ? AND hasta > ?
                             LIMIT 1');
    $stmt->bind_param('iss', $id, $hasta, $desde);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $r ? (string)$r[0] : '';
}


// ── Sesión y permiso ─────────────────────────────────────────────────────────
$noEmpleado = trim((string)($_COOKIE['noEmpleadoL'] ?? ''));
try {
    include '../incidencias/conn.php';
    if (!isset($conn) || $conn->connect_error) throw new RuntimeException('Sin conexión a la BD');
    $conn->set_charset('utf8mb4');
    $puede = mbTemaPuedeAdministrar($conn, $noEmpleado);
} catch (Throwable $e) {
    tmFallo('No pudimos conectar con el servidor. Inténtalo de nuevo en un momento.', $e);
}
if (!$puede) {
    tmResponder(false, 'Sólo el equipo de BI puede cambiar los logos y colores.');
}

$accion = (string)($_POST['accion'] ?? '');


// ── listar ───────────────────────────────────────────────────────────────────
// Las versiones (la predeterminada primero, luego de la más nueva a la más
// vieja) con su estado y sus logos, más lo de código para las vistas previas.
if ($accion === 'listar') {
    try {
        $ahora = mbTemaAhora();
        $versiones = [];
        $res = $conn->query('SELECT id_tema FROM mess_rrhh.messbook_temas WHERE visible = 1 ORDER BY predeterminada DESC, desde DESC, id_tema DESC');
        while ($r = $res->fetch_row()) {
            $t = tmVersion($conn, (int)$r[0]);
            if (!$t) continue;
            $pred = (int)$t['predeterminada'] === 1;
            $estado = $pred ? 'predeterminada'
                    : ($t['desde'] > $ahora ? 'programada' : ($t['hasta'] > $ahora ? 'activa' : 'terminada'));
            $versiones[] = [
                'id'             => (int)$t['id_tema'],
                'nombre'         => (string)$t['nombre'],
                'predeterminada' => $pred,
                'desde'          => tmLocal($t['desde']),
                'hasta'          => tmLocal($t['hasta']),
                'estado'         => $estado,
                'logos'          => $t['logos'],
                'colores'        => $t['colores'],
            ];
        }
        $defecto = [];
        foreach (MB_TEMA_DEFECTO as $lugar => [$claro, $oscuro]) {
            $defecto[$lugar] = ['archivo' => $claro, 'oscuro' => $oscuro];
        }
        tmResponder(true, '', [
            'versiones'   => $versiones,
            'defecto'     => $defecto,
            'hayColores'  => tmHayColores($conn),   // false: falta correr sql/2026-11-27_temas_colores.sql
        ]);
    } catch (Throwable $e) {
        tmFallo('No pudimos cargar las versiones. ¿Ya se corrió el SQL de temas?', $e);
    }
}


// ── guardar ──────────────────────────────────────────────────────────────────
// Crear o editar una versión, sus logos y sus colores en un solo envío:
// nombre, desde y hasta (la predeterminada no lleva fechas ni cambia de
// nombre), logo[<lugar>] y oscuro[<lugar>] con las imágenes nuevas, quitar[]
// (el lugar vuelve al de siempre), quitar_oscuro[] (sólo la versión oscura) y
// colores (JSON con el juego completo de colores de la versión).
if ($accion === 'guardar') {
    $id = (int)($_POST['id_tema'] ?? 0);

    try {
        $actual = $id > 0 ? tmVersion($conn, $id) : null;
    } catch (Throwable $e) {
        tmFallo('No pudimos leer la versión. Inténtalo de nuevo.', $e);
    }
    if ($id > 0 && !$actual) tmResponder(false, 'Esa versión ya no existe. Recarga la lista.');
    $pred = $actual && (int)$actual['predeterminada'] === 1;

    $nombre = trim(preg_replace('/\s+/u', ' ', (string)($_POST['nombre'] ?? '')));
    $desde = $hasta = null;
    if (!$pred) {
        if ($nombre === '') tmResponder(false, 'Ponle nombre a la versión (ej. Navidad 2026).');
        if (mb_strlen($nombre) > MB_TEMA_NOMBRE_MAX) tmResponder(false, 'El nombre es demasiado largo (máximo ' . MB_TEMA_NOMBRE_MAX . ' letras).');
        $desde = tmFechaHora((string)($_POST['desde'] ?? ''));
        $hasta = tmFechaHora((string)($_POST['hasta'] ?? ''));
        if (!$desde || !$hasta) tmResponder(false, 'Indica cuándo empieza y cuándo termina la versión, con fecha y hora.');
        if ($hasta <= $desde) tmResponder(false, 'La versión tiene que terminar después de que empieza.');
    }

    // Qué cambia en cada lugar. Las imágenes se revisan antes de tocar la BD.
    $quitar       = array_filter((array)($_POST['quitar'] ?? []), 'is_string');
    $quitarOscuro = array_filter((array)($_POST['quitar_oscuro'] ?? []), 'is_string');
    $nuevos = [];   // lugar => ['claro' => tmp|null, 'oscuro' => tmp|null]
    foreach (mbTemaTodosLugares() as $lugar) {
        $claro  = tmArchivo('logo', $lugar);
        $oscuro = strncmp($lugar, 'sis:', 4) === 0 ? tmArchivo('oscuro', $lugar) : null;
        if ($claro || $oscuro) $nuevos[$lugar] = ['claro' => $claro, 'oscuro' => $oscuro];
    }
    // Colores: el juego completo de la versión (null = no se tocan).
    $colores = tmColoresPedidos();
    if ($colores !== null && !tmHayColores($conn)) {
        tmResponder(false, 'Los colores todavía no se pueden guardar: falta correr sql/2026-11-27_temas_colores.sql.');
    }

    $logosActuales = $actual ? $actual['logos'] : [];
    foreach ($nuevos as $lugar => $n) {
        $quedaClaro = $n['claro'] || (isset($logosActuales[$lugar]) && !in_array($lugar, $quitar, true));
        if ($n['oscuro'] && !$quedaClaro) {
            tmResponder(false, tmNombreLugar($lugar) . ': sube también el logo del tema claro; el oscuro sólo lo acompaña.');
        }
    }

    $escritos = [];   // archivos nuevos en disco: si algo falla, se borran
    $sobran   = [];   // archivos que dejan de usarse: se borran tras el commit
    try {
        $conn->begin_transaction();

        if (!$pred) {
            $encima = tmEncimada($conn, $id, $desde, $hasta);
            if ($encima !== '') {
                $conn->rollback();
                tmResponder(false, 'Se encima con la versión "' . $encima . '". Dos versiones no pueden estar activas a la vez.');
            }
        }

        if ($id > 0) {
            if ($pred) {
                $stmt = $conn->prepare('UPDATE mess_rrhh.messbook_temas SET actualizado_por = ?, actualizado_en = NOW() WHERE id_tema = ?');
                $stmt->bind_param('si', $noEmpleado, $id);
            } else {
                $stmt = $conn->prepare('UPDATE mess_rrhh.messbook_temas SET nombre = ?, desde = ?, hasta = ?, actualizado_por = ?, actualizado_en = NOW() WHERE id_tema = ?');
                $stmt->bind_param('ssssi', $nombre, $desde, $hasta, $noEmpleado, $id);
            }
        } else {
            $stmt = $conn->prepare('INSERT INTO mess_rrhh.messbook_temas (nombre, predeterminada, desde, hasta, visible, creado_por, creado_en)
                                    VALUES (?, 0, ?, ?, 1, ?, NOW())');
            $stmt->bind_param('ssss', $nombre, $desde, $hasta, $noEmpleado);
        }
        $stmt->execute();
        $stmt->close();
        if ($id <= 0) $id = (int)$conn->insert_id;

        // Quitar: el lugar vuelve al de siempre (predeterminada o código).
        foreach ($quitar as $lugar) {
            if (!mbTemaLugarValido($lugar) || !isset($logosActuales[$lugar]) || isset($nuevos[$lugar]['claro'])) continue;
            $sobran[] = $logosActuales[$lugar]['archivo'];
            $sobran[] = $logosActuales[$lugar]['oscuro'];
            $stmt = $conn->prepare('DELETE FROM mess_rrhh.messbook_tema_logos WHERE id_tema = ? AND lugar = ?');
            $stmt->bind_param('is', $id, $lugar);
            $stmt->execute();
            $stmt->close();
            unset($logosActuales[$lugar]);
        }
        foreach ($quitarOscuro as $lugar) {
            if (!isset($logosActuales[$lugar]) || isset($nuevos[$lugar]['oscuro'])) continue;
            $sobran[] = $logosActuales[$lugar]['oscuro'];
            $stmt = $conn->prepare('UPDATE mess_rrhh.messbook_tema_logos SET archivo_oscuro = NULL WHERE id_tema = ? AND lugar = ?');
            $stmt->bind_param('is', $id, $lugar);
            $stmt->execute();
            $stmt->close();
            $logosActuales[$lugar]['oscuro'] = null;
        }

        // Los nuevos: se pintan como PNG en uploads/temas/ y se apunta la fila.
        if ($nuevos) $dir = tmCarpeta();
        foreach ($nuevos as $lugar => $n) {
            $lado  = $lugar === 'favicon' ? MB_TEMA_LADO_FAVICON : MB_TEMA_LADO;
            $prev  = $logosActuales[$lugar] ?? ['archivo' => null, 'oscuro' => null];
            $rutas = ['claro' => $prev['archivo'], 'oscuro' => $prev['oscuro']];
            foreach (['claro', 'oscuro'] as $cual) {
                if (!$n[$cual]) continue;
                $ruta = MB_TEMA_CARPETA . '/' . str_replace(':', '-', $lugar) . ($cual === 'oscuro' ? '-oscuro' : '')
                      . '_' . $id . '_' . bin2hex(random_bytes(6)) . '.png';
                if (!mbGuardarPngLimpio($n[$cual], __DIR__ . '/' . $ruta, $lado)) {
                    throw new RuntimeException('GD no pudo guardar ' . $ruta);
                }
                $escritos[] = $ruta;
                $sobran[]   = $rutas[$cual];
                $rutas[$cual] = $ruta;
            }
            $stmt = $conn->prepare('INSERT INTO mess_rrhh.messbook_tema_logos (id_tema, lugar, archivo, archivo_oscuro)
                                    VALUES (?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE archivo = VALUES(archivo), archivo_oscuro = VALUES(archivo_oscuro)');
            $stmt->bind_param('isss', $id, $lugar, $rutas['claro'], $rutas['oscuro']);
            $stmt->execute();
            $stmt->close();
        }

        // Colores: se reemplaza el juego completo; lo que no vino vuelve al de siempre.
        if ($colores !== null) {
            $stmt = $conn->prepare('DELETE FROM mess_rrhh.messbook_tema_colores WHERE id_tema = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            if ($colores) {
                $stmt = $conn->prepare('INSERT INTO mess_rrhh.messbook_tema_colores (id_tema, clave, claro, oscuro) VALUES (?, ?, ?, ?)');
                foreach ($colores as $clave => $c) {
                    $stmt->bind_param('isss', $id, $clave, $c['claro'], $c['oscuro']);
                    $stmt->execute();
                }
                $stmt->close();
            }
        }

        $conn->commit();
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        tmBorrarArchivos($escritos);
        tmFallo('No pudimos guardar la versión. Inténtalo de nuevo.', $e);
    }
    tmBorrarArchivos(array_filter($sobran));

    $estado = $pred ? 'predeterminada' : (mbTemaAhora() >= $desde && mbTemaAhora() < $hasta ? 'activa' : 'otra');
    tmResponder(true, '', ['id' => $id, 'estado' => $estado]);
}


// ── borrar ───────────────────────────────────────────────────────────────────
// Cualquier versión menos la predeterminada, con sus logos (los del disco
// también, salvo los de img/). Sus colores los borra la BD (ON DELETE CASCADE).
if ($accion === 'borrar') {
    $id = (int)($_POST['id_tema'] ?? 0);
    try {
        $t = $id > 0 ? tmVersion($conn, $id) : null;
        if (!$t) tmResponder(false, 'Esa versión ya no existe. Recarga la lista.');
        if ((int)$t['predeterminada'] === 1) tmResponder(false, 'La versión predeterminada no se borra: es la de todos los días.');

        $conn->begin_transaction();
        $stmt = $conn->prepare('DELETE FROM mess_rrhh.messbook_tema_logos WHERE id_tema = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare('DELETE FROM mess_rrhh.messbook_temas WHERE id_tema = ? AND predeterminada = 0');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        tmFallo('No pudimos borrar la versión. Inténtalo de nuevo.', $e);
    }
    $rutas = [];
    foreach ($t['logos'] as $l) { $rutas[] = $l['archivo']; $rutas[] = $l['oscuro']; }
    tmBorrarArchivos(array_filter($rutas));
    tmResponder(true);
}


tmResponder(false, 'Acción no válida.');

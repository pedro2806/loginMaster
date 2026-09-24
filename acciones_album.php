<?php
/**
 * acciones_album.php — Endpoint JSON del álbum de fotos (pestaña "Fotos" de inicio.php).
 *
 * Es el mismo muro que el de oktoberMESS (acciones_cliente.php: fotos,
 * foto_subir, foto_borrar), adaptado a messbook: aquí suben los empleados.
 *
 * DOS ORÍGENES en el mismo muro:
 *   'MB'  — mess_rrhh.messbook_fotos: las de los empleados. Se suben y se
 *           borran aquí; el tope de 30 cuenta sólo éstas.
 *   'OKT' — mess_oktobermess.foto_evento: las de los invitados del evento.
 *           SÓLO LECTURA: se ven marcadas como "Invitado", pero nadie las
 *           borra desde messbook (las borra el invitado en su app). Misma
 *           instancia de MySQL: se leen con la conexión de siempre
 *           (mess_incidencias tiene permiso), calificando la base.
 * Los ids de las dos tablas chocan, y también id_usr (invitados.id en una,
 * noEmpleado en la otra): una foto se identifica SIEMPRE por origen + id, y
 * su dueño por origen + id_usr. Si no, el empleado 42 sería dueño de la foto
 * del invitado 42.
 *
 * CREDENCIAL: la cookie noEmpleadoL, como el resto del portal (decisión del
 * 2026-09-23). OJO: esa cookie no va firmada, así que quien la cambie a mano
 * puede subir a nombre de otro y borrar sus fotos. La regla "cada quien borra
 * sólo las suyas" vale contra la pantalla, no contra alguien que edite su
 * cookie. Si el portal llega a guardar el noEmpleado en $_SESSION al hacer
 * login, basta con cambiar mbEmpleadoSesion().
 *
 * El noEmpleado se toma SIEMPRE de ahí, nunca del POST: aunque el navegador
 * mande un noEmpleado, aquí se ignora.
 *
 * ERRORES: desde PHP 8.1 mysqli lanza excepciones. Cada acción envuelve su BD
 * en try/catch y responde JSON; un 500 con HTML en pantalla sólo diría "no se
 * pudo contactar al servidor".
 */

header('Content-Type: application/json; charset=utf-8');

// Este archivo sólo habla JSON: un aviso de PHP impreso rompe la respuesta.
ini_set('display_errors', '0');

// ../incidencias/conn.php imprime saltos de línea entre sus dos bloques <?php;
// se atrapan aquí para que no se cuelen antes del JSON.
ob_start();

require_once __DIR__ . '/includes/imagen.php';

/*
 * Los álbumes que muestra la pestaña, uno por evento. La llave es el
 * id_evento de enc_eventos (sql/2026-09-23_album_fotos.sql da de alta el de
 * OktoberMESS); de ahí salen la fecha y, si no se pone `titulo`, el nombre.
 *
 *   titulo    — lo que se lee en la portada. Opcional: si falta, el nombre de
 *               enc_eventos (que para OktoberMESS trae "- Album de fotos").
 *   invitados — valor de mess_oktobermess.foto_evento.evento cuyas fotos
 *               entran a este álbum como sólo lectura. null: sólo empleados.
 *
 * Para abrir otro álbum: dar de alta su evento en enc_eventos (tipo
 * 'asistencia', estatus 0, sin asignados: ver el SQL) y agregarlo aquí. Con la
 * lista vacía, la pestaña dice que no hay álbumes abiertos.
 *
 * En el espejo local OktoberMESS es el 16. En producción NO asumirlo: con otro
 * número las fotos se colgarían de un evento ajeno (la llave foránea lo acepta).
 */
const MB_ALBUMES = [
    16 => ['titulo' => 'OktoberMESS 2026', 'invitados' => 'oktobermess2026'],
];

const MB_MAX_FOTOS        = 30;    // por empleado, en cada álbum
const MB_FOTO_LADO        = 1600;  // px del lado largo al guardar
const MB_FOTOS_POR_PAGINA = 30;
const MB_CARPETA          = 'fotos_album';

// Fotos de los invitados. foto_evento.url guarda 'fotos/<24 hex>.jpg' relativo
// a la raíz de oktoberMESS; desde messbook se llega con este prefijo (igual en
// local, /loginMaster + /oktobermess, que en producción, raíz + /oktobermess).
// Se sirven sin sesión, así que basta un <img src>. Qué fotos entran a cada
// álbum lo dice `invitados` en MB_ALBUMES.
const MB_OKT_PREFIJO_URL  = '/oktobermess/';


function mbResponder(bool $success, string $message = '', array $extra = []): void {
    ob_end_clean();
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/** El error real va al log; al empleado, un mensaje que entienda. */
function mbFallo(string $mensaje, Throwable $e): void {
    error_log('messbook acciones_album: ' . $e->getMessage());
    mbResponder(false, $mensaje);
}

/** noEmpleado de quien hace la petición. '' si no hay sesión. */
function mbEmpleadoSesion(): string {
    $no = trim((string)($_COOKIE['noEmpleadoL'] ?? ''));
    return preg_match('/^\d{1,11}$/', $no) ? $no : '';
}

/** "Juan Carlos" + "Pérez López" → "Juan Pérez". Lo que se enseña bajo cada foto. */
function mbNombreCorto(array $f): string {
    $primera = function ($s) { $s = trim((string)$s); return $s === '' ? '' : preg_split('/\s+/u', $s)[0]; };
    $corto = trim($primera($f['nombres'] ?? '') . ' ' . $primera($f['apellidos'] ?? ''));
    if ($corto !== '') return $corto;
    $partes = preg_split('/\s+/u', trim((string)($f['nombre'] ?? '')));
    $corto  = trim(implode(' ', array_slice($partes, 0, 2)));
    return $corto !== '' ? $corto : 'Empleado';
}

/** "María Fernanda López" → "María". Del invitado sólo se enseña el nombre
    de pila, igual que en su propia app (es gente de fuera). */
function mbNombreInvitado(array $f): string {
    $nombre = trim((string)($f['nombre'] ?? ''));
    return $nombre === '' ? 'Invitado' : preg_split('/\s+/u', $nombre)[0];
}

/** URL con la que la vista pide la foto. null si la url guardada no tiene la
    forma de las nuestras (p. ej. una fila capturada a mano): no se enseña. */
function mbUrlPublica(array $f): ?string {
    $url = (string)$f['url'];
    if ($f['origen'] === 'MB')  return mbRutaFoto($url) !== null ? $url : null;
    if ($f['origen'] === 'OKT') return preg_match('~^fotos/[a-f0-9]{24}\.jpg$~', $url) ? MB_OKT_PREFIJO_URL . $url : null;
    return null;
}

/** Foto del muro tal como la consume la vista. */
function mbFotoSalida(array $f, string $noEmpleado): array {
    $invitado = $f['origen'] === 'OKT';
    return [
        // `clave` es lo que identifica a la foto en la pantalla: el id solo
        // se repite entre las dos tablas.
        'clave'    => $f['origen'] . '-' . (int)$f['id'],
        'origen'   => (string)$f['origen'],
        'id'       => (int)$f['id'],
        'url'      => mbUrlPublica($f),
        'autor'    => $invitado ? mbNombreInvitado($f) : mbNombreCorto($f),
        'invitado' => $invitado,
        'hora'     => date('d/m H:i', strtotime((string)$f['fecha'])),
        // Dueño por origen + id_usr: el invitado 42 no es el empleado 42.
        'mia'      => !$invitado && (string)$f['id_usr'] === $noEmpleado,
        // Posición en el muro, para pedir "más viejas / más nuevas que ésta".
        'cursor'   => [(string)$f['fecha'], (string)$f['origen'], (int)$f['id']],
    ];
}

/** Ruta en disco de una foto guardada. null si la url no es de las nuestras. */
function mbRutaFoto(string $url): ?string {
    if (!preg_match('~^' . MB_CARPETA . '/[a-f0-9]{24}\.jpg$~', $url)) return null;
    return __DIR__ . '/' . $url;
}

/**
 * Cursor del muro que manda la vista: (fecha, origen, id) de la foto de la
 * orilla. Los tres, porque fecha es DATETIME de un segundo y en un mismo
 * segundo caben varias fotos de las dos tablas. null si no vino o no es válido.
 */
function mbCursor(string $prefijo): ?array {
    $fecha  = (string)($_POST[$prefijo . '_fecha'] ?? '');
    $origen = (string)($_POST[$prefijo . '_origen'] ?? '');
    $id     = (int)($_POST[$prefijo . '_id'] ?? 0);
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fecha)) return null;
    if (!in_array($origen, ['MB', 'OKT'], true) || $id <= 0) return null;
    return [$fecha, $origen, $id];
}

/*
 * Las dos tablas en un solo muro: empleados siempre, invitados sólo si el
 * álbum tiene `invitados`. Ver mbMuro().
 *
 * CONVERT … COLLATE en cada columna de texto: usuarios mezcla latin1 y utf8mb3,
 * foto_evento e invitados usan utf8mb4_0900_ai_ci y messbook_fotos
 * utf8mb4_spanish_ci. Sin igualarlas, MySQL rechaza el UNION ("Illegal mix of
 * collations").
 *
 * La forma de la url se filtra aquí además de en mbUrlPublica(): así el número
 * de fotos de la portada cuenta exactamente las que se enseñan.
 *
 * `usuarios` es MyISAM y noEmpleado no es único ahí: el JOIN va a una sola
 * fila activa.
 */
const MB_SQL_EMPLEADOS = "
    SELECT 'MB' AS origen, f.id, f.fecha,
           CONVERT(f.noEmpleado USING utf8mb4) COLLATE utf8mb4_spanish_ci AS id_usr,
           CONVERT(f.url        USING utf8mb4) COLLATE utf8mb4_spanish_ci AS url,
           CONVERT(u.nombre     USING utf8mb4) COLLATE utf8mb4_spanish_ci AS nombre,
           CONVERT(u.nombres    USING utf8mb4) COLLATE utf8mb4_spanish_ci AS nombres,
           CONVERT(u.apellidos  USING utf8mb4) COLLATE utf8mb4_spanish_ci AS apellidos
      FROM mess_rrhh.messbook_fotos f
      LEFT JOIN mess_rrhh.usuarios u
             ON u.id = (SELECT MIN(u2.id) FROM mess_rrhh.usuarios u2
                         WHERE u2.noEmpleado = f.noEmpleado AND u2.estatus = 1)
     WHERE f.id_evento = ?
       AND f.url REGEXP '^fotos_album/[a-f0-9]{24}[.]jpg$'";

const MB_SQL_INVITADOS = "
    SELECT 'OKT', o.id, o.fecha,
           CONVERT(o.id_usr USING utf8mb4) COLLATE utf8mb4_spanish_ci,
           CONVERT(o.url    USING utf8mb4) COLLATE utf8mb4_spanish_ci,
           CONVERT(i.nombre USING utf8mb4) COLLATE utf8mb4_spanish_ci,
           NULL, NULL
      FROM mess_oktobermess.foto_evento o
      LEFT JOIN mess_oktobermess.invitados i ON i.id = CAST(o.id_usr AS UNSIGNED)
     WHERE o.evento = ?
       AND o.url REGEXP '^fotos/[a-f0-9]{24}[.]jpg$'";

/**
 * El muro de un álbum como tabla derivada `t`, con sus tipos y parámetros para
 * bind_param. Quien la usa agrega su WHERE / ORDER BY sobre t.
 */
function mbMuro(int $album): array {
    $sql = MB_SQL_EMPLEADOS; $tipos = 'i'; $params = [$album];
    $invitados = MB_ALBUMES[$album]['invitados'] ?? null;
    if (is_string($invitados) && $invitados !== '') {
        $sql .= ' UNION ALL ' . MB_SQL_INVITADOS; $tipos .= 's'; $params[] = $invitados;
    }
    return ['(' . $sql . ') t', $tipos, $params];
}

/** Álbum que pide la vista. Tiene que estar en MB_ALBUMES: nunca se sube ni
    se lee de un id_evento que mande el navegador sin más. */
function mbAlbumPedido(): int {
    $album = (int)($_POST['album'] ?? 0);
    if (!isset(MB_ALBUMES[$album])) mbResponder(false, 'Ese álbum no existe o ya no está abierto.', ['sin_album' => true]);
    return $album;
}

/** "2026-10-01 00:00:00" → "1 oct 2026". */
function mbFechaCorta(string $fecha): string {
    $ts = strtotime($fecha);
    if ($ts === false) return '';
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return date('j', $ts) . ' ' . $meses[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}


// ── Credencial ───────────────────────────────────────────────────────────────
$noEmpleado = mbEmpleadoSesion();
if ($noEmpleado === '') {
    mbResponder(false, 'Tu sesión expiró. Vuelve a iniciar sesión.', ['expirada' => true]);
}

try {
    require_once __DIR__ . '/../incidencias/conn.php';
    mysqli_set_charset($conn, 'utf8mb4');

    // La cookie sirve para identificar, no como dato: el empleado tiene que
    // existir y estar activo.
    $stmt = $conn->prepare('SELECT 1 FROM usuarios WHERE noEmpleado = ? AND estatus = 1 LIMIT 1');
    $stmt->bind_param('s', $noEmpleado);
    $stmt->execute();
    $activo = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
} catch (Throwable $e) {
    mbFallo('No pudimos conectar con el álbum. Inténtalo de nuevo en un momento.', $e);
}
if (!$activo) {
    mbResponder(false, 'Tu sesión expiró. Vuelve a iniciar sesión.', ['expirada' => true]);
}

if (!MB_ALBUMES) {
    mbResponder(false, 'Todavía no hay álbumes abiertos.', ['sin_album' => true]);
}

$accion = $_POST['accion'] ?? '';


// ── albumes ──────────────────────────────────────────────────────────────────
// Las portadas: una por álbum, con la foto más reciente (de empleados o de
// invitados), cuántas fotos tiene y la fecha del evento. Las más nuevas primero.
if ($accion === 'albumes') {
    try {
        $ids    = array_keys(MB_ALBUMES);
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt   = $conn->prepare("SELECT id_evento, nombre, fecha_inicio FROM enc_eventos WHERE id_evento IN ($marcas)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $eventos = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $e) $eventos[(int)$e['id_evento']] = $e;
        $stmt->close();

        $albumes = [];
        foreach (MB_ALBUMES as $id => $cfg) {
            // Un id que no está en enc_eventos es un error de configuración:
            // se avisa en el log y el álbum no sale, en vez de romper la lista.
            if (!isset($eventos[$id])) {
                error_log("messbook acciones_album: el álbum $id de MB_ALBUMES no existe en enc_eventos.");
                continue;
            }
            [$muro, $tipos, $params] = mbMuro($id);

            $stmt = $conn->prepare("SELECT COUNT(*) FROM $muro");
            $stmt->bind_param($tipos, ...$params);
            $stmt->execute();
            $total = (int)$stmt->get_result()->fetch_row()[0];
            $stmt->close();

            $stmt = $conn->prepare("SELECT t.origen, t.url FROM $muro ORDER BY t.fecha DESC, t.origen DESC, t.id DESC LIMIT 1");
            $stmt->bind_param($tipos, ...$params);
            $stmt->execute();
            $ultima = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $albumes[] = [
                'id'      => $id,
                'titulo'  => (string)($cfg['titulo'] ?? $eventos[$id]['nombre']),
                'fecha'   => mbFechaCorta((string)$eventos[$id]['fecha_inicio']),
                'orden'   => (string)$eventos[$id]['fecha_inicio'],
                'total'   => $total,
                'portada' => $ultima ? mbUrlPublica($ultima) : null,
            ];
        }
        usort($albumes, function ($a, $b) { return strcmp($b['orden'], $a['orden']); });

        mbResponder(true, '', ['albumes' => $albumes]);
    } catch (Throwable $e) {
        mbFallo('No pudimos cargar los álbumes.', $e);
    }
}


// ── fotos ────────────────────────────────────────────────────────────────────
// Se publican al instante: lo que se sube lo ven todos en la siguiente
// actualización. Quien la subió puede borrarla; nadie más. Las de invitados
// entran al mismo muro, sólo para verse.
if ($accion === 'fotos') {
    // `antes`: página siguiente (fotos más viejas). `despues`: sólo las nuevas
    // desde la más nueva que ya tiene la pantalla — es lo que consulta cada 20 s.
    // Los dos son la tupla (fecha, origen, id); MySQL compara filas completas,
    // en el mismo orden que el ORDER BY.
    $album   = mbAlbumPedido();
    $antes   = mbCursor('antes');
    $despues = mbCursor('despues');

    try {
        [$muro, $tipos, $params] = mbMuro($album);
        $sql = "SELECT t.* FROM $muro";
        if ($despues !== null) {
            // Las nuevas se piden de la más vieja a la más nueva: si en 20 s
            // llegaron más de una página, las que no caben salen en el
            // siguiente sondeo en vez de quedarse en un hueco del muro.
            $sql .= ' WHERE (t.fecha, t.origen, t.id) > (?, ?, ?)
                      ORDER BY t.fecha ASC, t.origen ASC, t.id ASC';
            $tipos .= 'ssi'; array_push($params, ...$despues);
        } else {
            if ($antes !== null) {
                $sql .= ' WHERE (t.fecha, t.origen, t.id) < (?, ?, ?)';
                $tipos .= 'ssi'; array_push($params, ...$antes);
            }
            $sql .= ' ORDER BY t.fecha DESC, t.origen DESC, t.id DESC';
        }
        $sql .= ' LIMIT ' . (MB_FOTOS_POR_PAGINA + 1);

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($tipos, ...$params);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Se pide una de más sólo para saber si hay otra página.
        $hayMas = count($filas) > MB_FOTOS_POR_PAGINA;
        $filas  = array_slice($filas, 0, MB_FOTOS_POR_PAGINA);
        // La vista siempre recibe el muro de la más nueva a la más vieja.
        if ($despues !== null) $filas = array_reverse($filas);

        $fotos = array_map(function ($f) use ($noEmpleado) { return mbFotoSalida($f, $noEmpleado); }, $filas);
        // Las orillas se toman ANTES de filtrar: si la de la orilla es una que
        // no se enseña, el cursor igual tiene que pasarla, o la pantalla la
        // volvería a pedir para siempre.
        $orillas = $fotos ? ['mas_nueva' => $fotos[0]['cursor'], 'mas_vieja' => end($fotos)['cursor']] : [];
        // Una fila con url que no es nuestra (p. ej. capturada a mano) no se enseña.
        $fotos = array_values(array_filter($fotos, function ($f) { return $f['url'] !== null; }));

        mbResponder(true, '', array_merge([
            'fotos'   => $fotos,
            'hay_mas' => $hayMas,
        ], $orillas));
    } catch (Throwable $e) {
        mbFallo('No pudimos cargar las fotos.', $e);
    }
}

if ($accion === 'foto_subir') {
    $album   = mbAlbumPedido();
    $archivo = $_FILES['foto'] ?? null;
    $error   = is_array($archivo) ? (int)$archivo['error'] : UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        mbResponder(false, 'La foto es demasiado pesada. Prueba con otra o tómala con menos resolución.');
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string)$archivo['tmp_name'])) {
        mbResponder(false, 'No recibimos la foto. Revisa tu señal e inténtalo de nuevo.');
    }

    try {
        // El tipo se decide por el CONTENIDO (getimagesize + finfo), no por el
        // nombre ni por lo que dice el navegador: ver includes/imagen.php.
        $tmp    = (string)$archivo['tmp_name'];
        $motivo = mbRevisarImagen($tmp);
        if ($motivo !== '') mbResponder(false, $motivo);

        $evento = $album;
        $stmt = $conn->prepare('SELECT COUNT(*) FROM messbook_fotos WHERE id_evento = ? AND noEmpleado = ?');
        $stmt->bind_param('is', $evento, $noEmpleado);
        $stmt->execute();
        $ya = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($ya >= MB_MAX_FOTOS) {
            mbResponder(false, 'Llegaste al máximo de ' . MB_MAX_FOTOS . ' fotos en este álbum. Borra alguna para subir otra.');
        }

        $dir = __DIR__ . '/' . MB_CARPETA;
        if (!is_dir($dir) || !is_writable($dir)) {
            error_log('messbook foto_subir: la carpeta ' . MB_CARPETA . '/ no existe o no tiene permiso de escritura.');
            mbResponder(false, 'Las fotos no están disponibles en este momento. Avísale a BI.');
        }

        // Nombre aleatorio: nunca el del archivo original (lo elige quien sube)
        // y nunca adivinable, para que nadie recorra la carpeta por números.
        $url  = MB_CARPETA . '/' . bin2hex(random_bytes(12)) . '.jpg';
        $ruta = __DIR__ . '/' . $url;
        if (!mbGuardarJpegLimpio($tmp, $ruta, MB_FOTO_LADO)) {
            mbResponder(false, 'No pudimos procesar esa foto. Prueba con otra.');
        }

        try {
            $stmt = $conn->prepare('INSERT INTO messbook_fotos (id_evento, noEmpleado, url, fecha) VALUES (?, ?, ?, NOW())');
            $stmt->bind_param('iss', $evento, $noEmpleado, $url);
            $stmt->execute();
            $idFoto = (int)$conn->insert_id;
            $stmt->close();
        } catch (Throwable $e) {
            @unlink($ruta);                             // sin fila, el archivo sobra
            throw $e;
        }

        // Se relee de la BD en vez de armarla aquí: la hora tiene que ser la
        // que guardó NOW(), no la de PHP, que puede estar en otra zona.
        // Por la misma consulta del muro, para que salga idéntica (y con su cursor).
        [$muro, $tipos, $params] = mbMuro($album);
        $params[] = $idFoto;
        $stmt = $conn->prepare("SELECT t.* FROM $muro WHERE t.origen = 'MB' AND t.id = ?");
        $stmt->bind_param($tipos . 'i', ...$params);
        $stmt->execute();
        $nueva = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        mbResponder(true, '¡Foto publicada!', ['foto' => mbFotoSalida($nueva, $noEmpleado)]);
    } catch (Throwable $e) {
        mbFallo('No pudimos publicar tu foto. Inténtalo de nuevo en un momento.', $e);
    }
}

if ($accion === 'foto_borrar') {
    $idFoto = (int)($_POST['id'] ?? 0);
    if ($idFoto <= 0) mbResponder(false, 'No encontramos esa foto.');
    // Las de invitados son de sólo lectura aquí. Aunque llegara aquí sin este
    // aviso, el DELETE de abajo sólo toca messbook_fotos: el id de una foto de
    // invitado nunca alcanza su archivo.
    if (($_POST['origen'] ?? 'MB') !== 'MB') {
        mbResponder(false, 'Las fotos de los invitados sólo se pueden borrar desde la app del evento.');
    }

    try {
        // El dueño se comprueba en el WHERE, no después: aunque alguien mande
        // el id de una foto ajena, la consulta simplemente no la encuentra.
        $stmt = $conn->prepare('SELECT url FROM messbook_fotos WHERE id = ? AND noEmpleado = ? LIMIT 1');
        $stmt->bind_param('is', $idFoto, $noEmpleado);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$fila) mbResponder(false, 'Solo puedes borrar las fotos que tú subiste.');

        $stmt = $conn->prepare('DELETE FROM messbook_fotos WHERE id = ? AND noEmpleado = ?');
        $stmt->bind_param('is', $idFoto, $noEmpleado);
        $stmt->execute();
        $stmt->close();

        $ruta = mbRutaFoto((string)$fila['url']);
        if ($ruta !== null && is_file($ruta)) @unlink($ruta);

        mbResponder(true, 'Foto borrada.', ['id' => $idFoto]);
    } catch (Throwable $e) {
        mbFallo('No pudimos borrar la foto. Inténtalo de nuevo.', $e);
    }
}


mbResponder(false, 'Acción no válida.');

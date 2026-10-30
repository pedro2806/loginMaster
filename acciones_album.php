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
 * CONCURSO (tipo de álbum, includes/album.php): 1 foto por empleado y
 * MB_CONCURSO_VOTOS likes para las de los demás (messbook_votos). Las reglas se
 * cuidan aquí: foto_subir (tope de 1), foto_borrar (sólo antes de la votación),
 * voto (dar / quitar like) y ranking (sólo BI y RRHH). En un concurso
 * `sube_rrhh` las fotos las sube RRHH, con el nombre del participante en el
 * texto: sin tope de 1 y sin la regla de "no a la propia".
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
// Qué álbumes hay (tabla messbook_albumes) y el horario de subida: inicio.php
// también lo usa para el botón de la tarjeta de usuario.
require_once __DIR__ . '/includes/album.php';

const MB_MAX_FOTOS        = 30;    // por empleado, en cada álbum
const MB_MAX_FOTOS_RRHH   = 300;   // ídem, quien sube en un concurso `sube_rrhh` (una por participante)
const MB_TEXTO_MAX        = 140;   // caracteres del texto del post (messbook_fotos.texto)
const MB_FOTO_LADO        = 1600;  // px del lado largo al guardar
const MB_PORTADA_LADO     = 1200;  // ídem, la portada que se elige en el modal
const MB_FOTOS_POR_PAGINA = 30;
const MB_CARPETA          = 'fotos_album';

// Fotos de los invitados. foto_evento.url guarda 'fotos/<24 hex>.jpg' relativo
// a la raíz de oktoberMESS; desde messbook se llega con este prefijo (igual en
// local, /loginMaster + /oktobermess, que en producción, raíz + /oktobermess).
// Se sirven sin sesión, así que basta un <img src>. Qué fotos entran a cada
// álbum lo dicen invitados_evento / invitados_sedes de messbook_albumes.
const MB_OKT_PREFIJO_URL  = '/oktobermess/';

// El staff del evento (empleados) sube desde fotos-staff.php de oktoberMESS al
// mismo foto_evento, con id_usr = 'staff-<noEmpleado>' (OKT_FOTOS_PREFIJO_STAFF
// de su includes/fotos.php). Si cambia allá, cambia aquí y en los LIKE
// 'staff-%' de MB_SQL_INVITADOS y mbMuro (y el 7 del SUBSTRING).
const MB_OKT_PREFIJO_STAFF = 'staff-';

// Fotos del staff de oktoberMESS: un álbum de evento puede recibirlas desde
// ANTES de su día (y el anterior deja de recibirlas ese mismo día). Llave =
// fecha_inicio del álbum; valor = desde qué día. Es el mismo dato de
// oktoberMESS (config_evento.php → eventos.<sede>.fotos_staff_desde): si cambia
// allá, cambia aquí. 2026-10-15: lo que sube el equipo desde hoy ya es de SLP.
const MB_OKT_STAFF_DESDE = [
    '2026-10-23' => '2026-10-15',   // SLP
];

// Álbum de evento de oktoberMESS ya CERRADO que conserva fotos de la app
// subidas después de su ventana (MB_EVENTO_HORAS): las toma hasta ese día, no
// hasta que acaba la ventana. Llave = fecha_inicio del álbum; valor = hasta qué
// día (sin incluirlo). 2026-10-15: el Bajío se queda con las 27 que el staff
// subió el 11-oct; lo que llegue después ya no es suyo.
const MB_OKT_HASTA = [
    '2026-10-09' => '2026-10-12',   // Bajío
];


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
    // Las del staff vienen de foto_evento (sólo lectura, como las de invitados),
    // pero son de empleados: salen con su nombre y sin la marca de "Invitado".
    $staff    = $f['origen'] === 'OKT' && strpos((string)$f['id_usr'], MB_OKT_PREFIJO_STAFF) === 0;
    $invitado = $f['origen'] === 'OKT' && !$staff;
    return [
        // `clave` es lo que identifica a la foto en la pantalla: el id solo
        // se repite entre las dos tablas.
        'clave'    => $f['origen'] . '-' . (int)$f['id'],
        'origen'   => (string)$f['origen'],
        'id'       => (int)$f['id'],
        'url'      => mbUrlPublica($f),
        'autor'    => $invitado ? mbNombreInvitado($f) : mbNombreCorto($f),
        'invitado' => $invitado,
        // Lo que escribió al publicar ('' si nada; las de invitados no traen).
        // Texto plano: la vista lo escapa.
        'texto'    => (string)($f['texto'] ?? ''),
        'hora'     => date('d/m H:i', strtotime((string)$f['fecha'])),
        // Dueño por origen + id_usr: el invitado 42 no es el empleado 42. Las
        // del staff tampoco son "mías" aquí: se borran desde la app del evento.
        'mia'      => $f['origen'] === 'MB' && (string)$f['id_usr'] === $noEmpleado,
        // Quien la subió la puede borrar. Casi siempre es `mia`; en un concurso
        // `sube_rrhh` no (mbConLikes): RRHH borra la que subió, pero no es suya.
        'borrable' => $f['origen'] === 'MB' && (string)$f['id_usr'] === $noEmpleado,
        // Posición en el muro, para pedir "más viejas / más nuevas que ésta".
        'cursor'   => [(string)$f['fecha'], (string)$f['origen'], (int)$f['id']],
    ];
}

/** Portada elegida en el modal (messbook_albumes.portada), o null si no hay:
    entonces la portada es la foto más reciente. Vive en MB_CARPETA, igual que
    las fotos. */
function mbPortadaPropia(array $evento): ?string {
    $url = (string)($evento['portada'] ?? '');
    return $url !== '' && mbRutaFoto($url) !== null ? $url : null;
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
           CONVERT(u.apellidos  USING utf8mb4) COLLATE utf8mb4_spanish_ci AS apellidos,
           f.texto
      FROM mess_rrhh.messbook_fotos f
      LEFT JOIN mess_rrhh.usuarios u
             ON u.id = (SELECT MIN(u2.id) FROM mess_rrhh.usuarios u2
                         WHERE u2.noEmpleado = f.noEmpleado AND u2.estatus = 1)
     WHERE f.id_evento = ?
       AND f.url REGEXP '^fotos_album/[a-f0-9]{24}[.]jpg$'";

/*
 * En foto_evento suben invitados (id_usr = invitados.id) y staff
 * ('staff-<noEmpleado>', MB_OKT_PREFIJO_STAFF): cada uno se busca en su tabla.
 * Sin separarlos, CAST('staff-523' AS UNSIGNED) da 0, la foto del staff no
 * encontraba a nadie y quedaba sin nombre y "sin sede".
 */
const MB_SQL_INVITADOS = "
    SELECT 'OKT', o.id, o.fecha,
           CONVERT(o.id_usr USING utf8mb4) COLLATE utf8mb4_spanish_ci,
           CONVERT(o.url    USING utf8mb4) COLLATE utf8mb4_spanish_ci,
           COALESCE(CONVERT(i.nombre USING utf8mb4) COLLATE utf8mb4_spanish_ci,
                    CONVERT(u.nombre USING utf8mb4) COLLATE utf8mb4_spanish_ci),
           CONVERT(u.nombres   USING utf8mb4) COLLATE utf8mb4_spanish_ci,
           CONVERT(u.apellidos USING utf8mb4) COLLATE utf8mb4_spanish_ci,
           NULL
      FROM mess_oktobermess.foto_evento o
      LEFT JOIN mess_oktobermess.invitados i
             ON o.id_usr NOT LIKE 'staff-%' AND i.id = CAST(o.id_usr AS UNSIGNED)
      LEFT JOIN mess_rrhh.usuarios u
             ON o.id_usr LIKE 'staff-%'
            AND u.id = (SELECT MIN(u2.id) FROM mess_rrhh.usuarios u2
                         WHERE u2.noEmpleado = SUBSTRING(o.id_usr, 7) AND u2.estatus = 1)
     WHERE o.evento = ?
       AND o.url REGEXP '^fotos/[a-f0-9]{24}[.]jpg$'";

/**
 * El muro de un álbum (una fila de mbEventosAlbum) como tabla derivada `t`, con
 * sus tipos y parámetros para bind_param. Quien la usa agrega su WHERE /
 * ORDER BY sobre t.
 *
 * Lo de la app (staff e invitados) entra por la fecha en que se subió
 * (mbRangoOkt): foto_evento no dice a qué evento de oktoberMESS pertenece.
 * Además, los invitados de las dos sedes suben con el mismo foto_evento.evento,
 * así que cada álbum se queda con los de sus `sedes` (invitados.sede; null =
 * sin sede); el staff no tiene sede. Los filtros van al final, así que caen en
 * la rama de invitados del UNION.
 */
function mbMuro(array $evento): array {
    $sql = MB_SQL_EMPLEADOS; $tipos = 'i'; $params = [(int)$evento['id_evento']];
    $inv = $evento['cfg']['invitados'] ?? null;
    if (is_array($inv) && !empty($inv['evento'])) {
        $sql .= ' UNION ALL ' . MB_SQL_INVITADOS; $tipos .= 's'; $params[] = (string)$inv['evento'];
        $rango = mbRangoOkt($evento);
        if ($rango) { $sql .= ' AND o.fecha >= ? AND o.fecha < ?'; $tipos .= 'ss'; array_push($params, ...$rango); }
        else $sql .= ' AND 0';

        if (isset($inv['sedes']) && is_array($inv['sedes'])) {
            $conSede = array_values(array_filter($inv['sedes'], 'is_string'));
            $cond = [];
            if ($conSede) {
                $cond[] = 'i.sede IN (' . implode(',', array_fill(0, count($conSede), '?')) . ')';
                $tipos .= str_repeat('s', count($conSede));
                array_push($params, ...$conSede);
            }
            if (in_array(null, $inv['sedes'], true)) $cond[] = 'i.sede IS NULL';
            $invitados = $cond ? implode(' OR ', $cond) : '0';

            $sql .= " AND (o.id_usr LIKE 'staff-%' OR ($invitados))";
        }
    }
    return ['(' . $sql . ') t', $tipos, $params];
}

/**
 * Desde / hasta (DATETIME, hora de MB_ZONA) de las fotos de la app (staff e
 * invitados) que le tocan a este álbum. El staff sube igual en Bajío (9-oct)
 * que en SLP (23-oct): la única pista es la fecha (decisión del 2026-10-02).
 *
 * Evento: la misma ventana con la que suben los empleados (mbVentanaEvento,
 * MB_EVENTO_HORAS); si es de oktoberMESS, puede empezar antes
 * (MB_OKT_STAFF_DESDE), acabar en un día fijo (MB_OKT_HASTA) y nunca pasa de
 * donde empieza el siguiente (mbInicioSiguienteOkt). General: los días de su
 * periodo. Lo subido fuera no sale en ninguno. null si las fechas del álbum no
 * se pueden leer.
 */
function mbRangoOkt(array $evento): ?array {
    $zona = new DateTimeZone(MB_ZONA);
    $dia  = mbVentanaEvento($evento);
    if ($dia === null) {
        $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', substr((string)$evento['fecha_inicio'], 0, 10), $zona);
        if (!$inicio) return null;
        $fin = DateTimeImmutable::createFromFormat('!Y-m-d', substr((string)($evento['fecha_fin'] ?? ''), 0, 10), $zona);
        if (!$fin || $fin < $inicio) $fin = $inicio;
        $dia = [$inicio->getTimestamp(), $fin->modify('+1 day')->getTimestamp()];
    } elseif (!empty($evento['cfg']['invitados'])) {
        // Evento de oktoberMESS (decisión del 2026-10-15): lo de la app cuenta
        // en la ventana del evento, salvo los días que fijen MB_OKT_STAFF_DESDE
        // y MB_OKT_HASTA, y nunca se mete en el siguiente evento.
        $hasta = MB_OKT_HASTA[substr((string)$evento['fecha_inicio'], 0, 10)] ?? null;
        $hasta = $hasta ? DateTimeImmutable::createFromFormat('!Y-m-d', $hasta, $zona) : null;
        if ($hasta) $dia[1] = $hasta->getTimestamp();
        $siguiente = mbInicioSiguienteOkt($evento);
        if ($siguiente !== null) $dia[1] = min($dia[1], $siguiente);
        $dia[0] = mbInicioStaffOkt($evento) ?? $dia[0];
    }
    // foto_evento.fecha la pone NOW() de MySQL, en hora local, igual que
    // messbook_fotos.fecha.
    return array_map(function ($ts) use ($zona) {
        return (new DateTimeImmutable('@' . $ts))->setTimezone($zona)->format('Y-m-d H:i:s');
    }, $dia);
}

/**
 * 00:00 (MB_ZONA) del día del siguiente álbum de evento ligado a oktoberMESS
 * después de `$evento` (visible u oculto), o null si no hay. Así las fotos del
 * staff de foto_evento caen en un solo álbum: las de antes de que empiece SLP,
 * en Bajío; desde ese día, en SLP.
 */
function mbInicioSiguienteOkt(array $evento): ?int {
    global $conn;
    $zona  = new DateTimeZone(MB_ZONA);
    $desde = substr((string)$evento['fecha_inicio'], 0, 10);
    $siguiente = null;
    $sigEvento = null;
    foreach (mbEventosAlbum($conn, true) as $id => $e) {
        if ((int)$id === (int)$evento['id_evento'] || empty($e['cfg']['invitados'])) continue;
        $dia = substr((string)$e['fecha_inicio'], 0, 10);
        if ($dia > $desde && ($siguiente === null || $dia < $siguiente)) { $siguiente = $dia; $sigEvento = $e; }
    }
    if ($siguiente === null) return null;
    // Si el siguiente recibe fotos del staff desde antes de su día, aquí se corta ese día
    $adelanto = mbInicioStaffOkt($sigEvento);
    if ($adelanto !== null) return $adelanto;
    $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $siguiente, $zona);
    return $inicio ? $inicio->getTimestamp() : null;
}

/**
 * 00:00 (MB_ZONA) del día desde el que un álbum de evento recibe las fotos del
 * staff de oktoberMESS, si es antes de su día (MB_OKT_STAFF_DESDE); null si
 * empieza en su día, como todos.
 */
function mbInicioStaffOkt(array $evento): ?int {
    $desde = MB_OKT_STAFF_DESDE[substr((string)$evento['fecha_inicio'], 0, 10)] ?? null;
    if ($desde === null) return null;
    $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $desde, new DateTimeZone(MB_ZONA));
    return $inicio ? $inicio->getTimestamp() : null;
}

/** Álbum que pide la vista. Tiene que ser uno de los visibles: nunca se sube ni
    se lee de un id_evento que mande el navegador sin más. */
function mbAlbumPedido(array $eventos): int {
    $album = (int)($_POST['album'] ?? 0);
    if (!isset($eventos[$album])) mbResponder(false, 'Ese álbum no existe o ya no está abierto.', ['sin_album' => true]);
    return $album;
}

/** "Juan Carlos" + "Pérez López" → "Juan Carlos Pérez López". El ranking lo
    lee RRHH: ahí va el nombre completo, no el corto del muro. */
function mbNombreCompleto(array $f): string {
    $completo = trim((string)preg_replace('/\s+/u', ' ', trim((string)($f['nombres'] ?? '')) . ' ' . trim((string)($f['apellidos'] ?? ''))));
    if ($completo !== '') return $completo;
    $nombre = trim((string)preg_replace('/\s+/u', ' ', (string)($f['nombre'] ?? '')));
    return $nombre !== '' ? $nombre : 'Empleado ' . ($f['noEmpleado'] ?? '');
}

/** Fotos que `$noEmpleado` tiene en un álbum. */
function mbFotosDe(mysqli $conn, int $idEvento, string $noEmpleado): int {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM messbook_fotos WHERE id_evento = ? AND noEmpleado = ?');
    $stmt->bind_param('is', $idEvento, $noEmpleado);
    $stmt->execute();
    $n = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $n;
}

/** Por qué no puede subir otra foto al concurso. */
function mbYaParticipas(array $evento, int $ahora): string {
    return mbVotacionEmpezo($evento, $ahora)
        ? 'Ya participas en el concurso con tu foto.'
        : 'Ya participas en el concurso con tu foto. Si quieres cambiarla, bórrala primero: se puede hasta que abra la votación.';
}

/**
 * Bloquea la fila del concurso hasta el COMMIT. Las subidas y los likes de un
 * concurso pasan así de uno en uno, y dos peticiones a la vez (doble toque, dos
 * pestañas) no se saltan el tope de 1 foto ni el de 3 likes, que se cuentan
 * después de esto. Es un bloqueo de fila: vale con cualquier nivel de
 * aislamiento.
 */
function mbBloquearConcurso(mysqli $conn, int $idEvento): void {
    $stmt = $conn->prepare('SELECT id_evento FROM messbook_albumes WHERE id_evento = ? FOR UPDATE');
    $stmt->bind_param('i', $idEvento);
    $stmt->execute();
    $stmt->get_result();
    $stmt->close();
}

/**
 * Lo del concurso que depende de quién pregunta: los likes que le quedan y si
 * ya subió su foto (o, si las sube RRHH, si él puede subirlas). Va con las
 * portadas y con cada página del muro, junto con los periodos (Unix), para que
 * la vista decida qué enseñar con su reloj.
 */
function mbEstadoConcurso(mysqli $conn, array $evento, string $noEmpleado): array {
    $id = (int)$evento['id_evento'];
    $stmt = $conn->prepare('SELECT COUNT(*) FROM messbook_votos WHERE id_evento = ? AND noEmpleado = ?');
    $stmt->bind_param('is', $id, $noEmpleado);
    $stmt->execute();
    $dados = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    $rrhh = mbSubeRrhh($evento);
    return [
        'subida'      => $evento['cfg']['concurso']['subida'],
        'votacion'    => $evento['cfg']['concurso']['votacion'],
        'votos_max'   => MB_CONCURSO_VOTOS,
        'restantes'   => max(0, MB_CONCURSO_VOTOS - $dados),
        'rrhh'        => $rrhh,
        'puede_subir' => !$rrhh || mbPuedeSubirRrhh($conn, $noEmpleado),
        'mi_foto'     => !$rrhh && mbFotosDe($conn, $id, $noEmpleado) >= MB_CONCURSO_FOTOS,
    ];
}

/**
 * Likes de las fotos del concurso (las de mbFotoSalida): `votado`, si quien
 * pregunta ya le dio like, y `votos`, cuántos lleva. `votos` es null mientras
 * la votación no cierra (decisión del 2026-10-21): así nadie vota por la que
 * va ganando. El ranking en vivo es aparte, sólo para BI y RRHH.
 *
 * En un concurso `sube_rrhh` la foto es del participante que dice su texto, no
 * de quien la subió: ése es el `autor` (y el texto ya no se repite debajo), y
 * para quien la subió no es `mia`, así que también puede darle like.
 */
function mbConLikes(mysqli $conn, array $evento, array $fotos, string $noEmpleado): array {
    $ids = [];
    foreach ($fotos as $f) if ($f['origen'] === 'MB') $ids[] = (int)$f['id'];
    $likes = [];
    if ($ids) {
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("SELECT id_foto, COUNT(*), MAX(noEmpleado = ?) FROM messbook_votos
                                 WHERE id_evento = ? AND id_foto IN ($marcas) GROUP BY id_foto");
        $params = array_merge([$noEmpleado, (int)$evento['id_evento']], $ids);
        $stmt->bind_param('si' . str_repeat('i', count($ids)), ...$params);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all() as [$idFoto, $n, $mio]) $likes[(int)$idFoto] = [(int)$n, (bool)$mio];
        $stmt->close();
    }
    $cerrada = mbVotacionCerrada($evento, time());
    $rrhh    = mbSubeRrhh($evento);
    return array_map(function ($f) use ($likes, $cerrada, $rrhh) {
        if ($f['origen'] !== 'MB') return $f;
        [$n, $mio] = $likes[$f['id']] ?? [0, false];
        $f['votado'] = $mio;
        $f['votos']  = $cerrada ? $n : null;
        if ($rrhh) {
            $f['autor'] = $f['texto'] !== '' ? $f['texto'] : 'Participante';
            $f['texto'] = '';
            $f['mia']   = false;
        }
        return $f;
    }, $fotos);
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

    // Los álbumes (includes/album.php), con su id_evento y su horario.
    $eventos = mbEventosAlbum($conn);
} catch (Throwable $e) {
    mbFallo('No pudimos conectar con el álbum. Inténtalo de nuevo en un momento.', $e);
}
if (!$activo) {
    mbResponder(false, 'Tu sesión expiró. Vuelve a iniciar sesión.', ['expirada' => true]);
}

$accion = (string)($_POST['accion'] ?? '');

// Administración de álbumes (modalAlbumes.php). Va antes del aviso de "no hay
// álbumes": justo cuando no hay ninguno es cuando hace falta crear uno.
if (strpos($accion, 'adm_') === 0) {
    require __DIR__ . '/includes/album_admin.php';
}


// ── ranking ──────────────────────────────────────────────────────────────────
// Las fotos de un concurso de más a menos likes, con el nombre completo de
// quién la subió. Sólo BI y RRHH (mbPuedeVerRanking), en vivo aunque la
// votación siga abierta. Va antes del aviso de "no hay álbumes" y lee también
// los ocultos: el modal de álbumes lo abre desde cualquier fila.
if ($accion === 'ranking') {
    try {
        if (!mbPuedeVerRanking($conn, $noEmpleado)) mbResponder(false, 'No tienes permiso para ver el ranking.');
        $todos = mbEventosAlbum($conn, true);
        $album = (int)($_POST['album'] ?? 0);
        if (!isset($todos[$album]) || !mbEsConcurso($todos[$album])) mbResponder(false, 'Ese concurso no existe.');

        // Los likes en una tabla derivada, no con GROUP BY sobre el JOIN: así
        // vale también con ONLY_FULL_GROUP_BY (usuarios y departamento no
        // dependen de f.id para MySQL).
        $stmt = $conn->prepare("SELECT f.id, f.url, f.texto, f.fecha, f.noEmpleado,
                                       u.nombre, u.nombres, u.apellidos, d.departamento AS area,
                                       COALESCE(v.votos, 0) AS votos
                                  FROM messbook_fotos f
                                  LEFT JOIN (SELECT id_foto, COUNT(*) AS votos FROM messbook_votos
                                              WHERE id_evento = ? GROUP BY id_foto) v ON v.id_foto = f.id
                                  LEFT JOIN usuarios u
                                         ON u.id = (SELECT MIN(u2.id) FROM usuarios u2
                                                     WHERE u2.noEmpleado = f.noEmpleado AND u2.estatus = 1)
                                  LEFT JOIN departamento d ON d.id = u.departamento
                                 WHERE f.id_evento = ?
                                 ORDER BY votos DESC, f.fecha ASC, f.id ASC");
        $stmt->bind_param('ii', $album, $album);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $conn->prepare('SELECT COUNT(DISTINCT noEmpleado) FROM messbook_votos WHERE id_evento = ?');
        $stmt->bind_param('i', $album);
        $stmt->execute();
        $votantes = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();

        // Los empates comparten lugar y el siguiente salta: 1, 1, 3.
        // Si las fotos las sube RRHH, el participante es el texto de la foto y
        // el empleado de la fila es quien la subió (`subio`).
        $rrhh    = mbSubeRrhh($todos[$album]);
        $ranking = [];
        $lugar   = 0;
        $previo  = null;
        foreach ($filas as $i => $f) {
            if ((int)$f['votos'] !== $previo) { $lugar = $i + 1; $previo = (int)$f['votos']; }
            $texto = (string)($f['texto'] ?? '');
            $ranking[] = [
                'lugar'      => $lugar,
                'id'         => (int)$f['id'],
                'url'        => mbUrlPublica(['origen' => 'MB', 'url' => $f['url']]),
                'nombre'     => $rrhh ? ($texto !== '' ? $texto : 'Participante') : mbNombreCompleto($f),
                'noEmpleado' => $rrhh ? '' : (string)$f['noEmpleado'],
                'area'       => $rrhh ? '' : (string)($f['area'] ?? ''),
                'texto'      => $rrhh ? '' : $texto,
                'subio'      => $rrhh ? mbNombreCompleto($f) : '',
                'votos'      => (int)$f['votos'],
                'hora'       => date('d/m H:i', strtotime((string)$f['fecha'])),
            ];
        }

        mbResponder(true, '', [
            'titulo'        => mbTituloAlbum($todos[$album]),
            'votacion'      => mbVotacion($todos[$album]),
            'ranking'       => $ranking,
            'participantes' => count($ranking),
            'votantes'      => $votantes,
            'likes'         => array_sum(array_column($ranking, 'votos')),
        ]);
    } catch (Throwable $e) {
        mbFallo('No pudimos cargar el ranking.', $e);
    }
}

if (!$eventos) {
    mbResponder(false, 'Todavía no hay álbumes abiertos.', ['sin_album' => true]);
}


// ── albumes ──────────────────────────────────────────────────────────────────
// Las portadas: una por álbum, con la que se eligió en el modal o, si no hay,
// la foto más reciente (de empleados o de invitados); cuántas fotos tiene y la
// fecha del evento. Las más nuevas primero.
// `ventanas` es el horario de subida: con él la vista decide si enseña la caja
// de subir o el aviso de cuándo abre (la regla la hace cumplir foto_subir).
if ($accion === 'albumes') {
    try {
        $albumes = [];
        foreach ($eventos as $id => $ev) {
            [$muro, $tipos, $params] = mbMuro($ev);

            $stmt = $conn->prepare("SELECT COUNT(*) FROM $muro");
            $stmt->bind_param($tipos, ...$params);
            $stmt->execute();
            $total = (int)$stmt->get_result()->fetch_row()[0];
            $stmt->close();

            $portada = mbPortadaPropia($ev);
            if ($portada === null) {
                $stmt = $conn->prepare("SELECT t.origen, t.url FROM $muro ORDER BY t.fecha DESC, t.origen DESC, t.id DESC LIMIT 1");
                $stmt->bind_param($tipos, ...$params);
                $stmt->execute();
                $ultima = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $portada = $ultima ? mbUrlPublica($ultima) : null;
            }

            $albumes[] = [
                'id'       => $id,
                'titulo'   => mbTituloAlbum($ev),
                'logo'     => mbLogoAlbum($ev),
                'fecha'    => mbFechaCorta((string)$ev['fecha_inicio']),
                'orden'    => (string)$ev['fecha_inicio'],
                'total'    => $total,
                'portada'  => $portada,
                'ventanas' => $ev['ventanas'],
                // Sólo en el concurso: sus periodos y lo que le toca a quien pregunta.
                'concurso' => mbEsConcurso($ev) ? mbEstadoConcurso($conn, $ev, $noEmpleado) : null,
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
    // desde la más nueva que ya tiene la pantalla — es lo que consulta cada 5 min.
    // Los dos son la tupla (fecha, origen, id); MySQL compara filas completas,
    // en el mismo orden que el ORDER BY.
    $album   = mbAlbumPedido($eventos);
    $antes  = mbCursor('antes');
    $despues = mbCursor('despues');

    try {
        [$muro, $tipos, $params] = mbMuro($eventos[$album]);
        $sql = "SELECT t.* FROM $muro";
        if ($despues !== null) {
            // Las nuevas se piden de la más vieja a la más nueva: si en 5 min
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

        // El concurso: los likes de cada foto y lo que le toca a quien mira,
        // al día con cada página (también con el sondeo de cada 5 min).
        $concurso = null;
        if (mbEsConcurso($eventos[$album])) {
            $fotos    = mbConLikes($conn, $eventos[$album], $fotos, $noEmpleado);
            $concurso = mbEstadoConcurso($conn, $eventos[$album], $noEmpleado);
        }

        mbResponder(true, '', array_merge([
            'fotos'    => $fotos,
            'hay_mas'  => $hayMas,
            'concurso' => $concurso,
        ], $orillas));
    } catch (Throwable $e) {
        mbFallo('No pudimos cargar las fotos.', $e);
    }
}

if ($accion === 'foto_subir') {
    $album   = mbAlbumPedido($eventos);
    $archivo = $_FILES['foto'] ?? null;
    $error   = is_array($archivo) ? (int)$archivo['error'] : UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        mbResponder(false, 'La foto es demasiado pesada. Prueba con otra o tómala con menos resolución.');
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string)$archivo['tmp_name'])) {
        mbResponder(false, 'No recibimos la foto. Revisa tu señal e inténtalo de nuevo.');
    }

    // Texto del post, opcional. Una sola línea: los saltos y caracteres de
    // control se vuelven espacio. Se guarda tal cual (texto plano); quien lo
    // pinta lo escapa. null si vino vacío o no es UTF-8 válido (la regex falla).
    $texto = preg_replace('/[\p{Cc}\s]+/u', ' ', (string)($_POST['texto'] ?? ''));
    $texto = $texto === null ? '' : trim($texto);
    if (mb_strlen($texto, 'UTF-8') > MB_TEXTO_MAX) {
        mbResponder(false, 'El texto es muy largo: máximo ' . MB_TEXTO_MAX . ' caracteres.');
    }
    $texto = $texto === '' ? null : $texto;

    try {
        // Horario (includes/album.php). Lo primero, antes de gastar en revisar
        // la imagen: la pantalla ya esconde la caja fuera de horario, pero una
        // pestaña que se quedó abierta o una llamada directa llegan hasta aquí.
        // Sólo el álbum activo recibe fotos: el de evento en su día, el general
        // en sus ventanas.
        $ahora = time();
        if (!mbSubidaAbierta($eventos[$album]['ventanas'], $ahora)) {
            mbResponder(false, mbMotivoCerrada($album, $eventos, $ahora), ['fuera_de_horario' => true]);
        }

        // El tipo se decide por el CONTENIDO (getimagesize + finfo), no por el
        // nombre ni por lo que dice el navegador: ver includes/imagen.php.
        $tmp    = (string)$archivo['tmp_name'];
        $motivo = mbRevisarImagen($tmp);
        if ($motivo !== '') mbResponder(false, $motivo);

        $evento     = $album;
        $deRrhh     = mbSubeRrhh($eventos[$album]);
        // Concurso que sube RRHH: una foto por participante, sin tope de 1, y
        // el texto es su nombre (lo que se lee en el muro y en el ranking).
        $esConcurso = mbEsConcurso($eventos[$album]) && !$deRrhh;
        if ($deRrhh) {
            if (!mbPuedeSubirRrhh($conn, $noEmpleado)) mbResponder(false, 'Las fotos de este concurso las sube Recursos Humanos.');
            if ($texto === null) mbResponder(false, 'Escribe el nombre del participante o del equipo.');
        }
        // Aviso temprano, antes de gastar en guardar la imagen. En el concurso
        // se vuelve a contar abajo, con la fila bloqueada.
        $ya   = mbFotosDe($conn, $evento, $noEmpleado);
        $tope = $deRrhh ? MB_MAX_FOTOS_RRHH : MB_MAX_FOTOS;
        if ($esConcurso && $ya >= MB_CONCURSO_FOTOS) {
            mbResponder(false, mbYaParticipas($eventos[$album], $ahora), ['ya_participa' => true]);
        }
        if ($ya >= $tope) {
            mbResponder(false, 'Llegaste al máximo de ' . $tope . ' fotos en este álbum. Borra alguna para subir otra.');
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
            $conn->begin_transaction();
            if ($esConcurso) {
                mbBloquearConcurso($conn, $evento);
                if (mbFotosDe($conn, $evento, $noEmpleado) >= MB_CONCURSO_FOTOS) {
                    $conn->rollback();
                    @unlink($ruta);
                    mbResponder(false, mbYaParticipas($eventos[$album], $ahora), ['ya_participa' => true]);
                }
            }
            $stmt = $conn->prepare('INSERT INTO messbook_fotos (id_evento, noEmpleado, url, texto, fecha) VALUES (?, ?, ?, ?, NOW())');
            $stmt->bind_param('isss', $evento, $noEmpleado, $url, $texto);
            $stmt->execute();
            $idFoto = (int)$conn->insert_id;
            $stmt->close();
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            @unlink($ruta);                             // sin fila, el archivo sobra
            throw $e;
        }

        // Se relee de la BD en vez de armarla aquí: la hora tiene que ser la
        // que guardó NOW(), no la de PHP, que puede estar en otra zona.
        // Por la misma consulta del muro, para que salga idéntica (y con su cursor).
        [$muro, $tipos, $params] = mbMuro($eventos[$album]);
        $params[] = $idFoto;
        $stmt = $conn->prepare("SELECT t.* FROM $muro WHERE t.origen = 'MB' AND t.id = ?");
        $stmt->bind_param($tipos . 'i', ...$params);
        $stmt->execute();
        $nueva = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $salida = mbFotoSalida($nueva, $noEmpleado);
        if ($esConcurso || $deRrhh) {
            mbResponder(true, $deRrhh ? 'Foto publicada en el concurso.' : '¡Ya participas en el concurso!', [
                'foto'     => mbConLikes($conn, $eventos[$album], [$salida], $noEmpleado)[0],
                'concurso' => mbEstadoConcurso($conn, $eventos[$album], $noEmpleado),
            ]);
        }
        mbResponder(true, '¡Foto publicada!', ['foto' => $salida]);
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
        $stmt = $conn->prepare('SELECT url, id_evento FROM messbook_fotos WHERE id = ? AND noEmpleado = ? LIMIT 1');
        $stmt->bind_param('is', $idFoto, $noEmpleado);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$fila) mbResponder(false, 'Solo puedes borrar las fotos que tú subiste.');

        // La del concurso se queda desde que abre la votación (decisión del
        // 2026-10-21): borrarla tiraría los likes que ya tenga.
        $suAlbum = mbEventosAlbum($conn, true)[(int)$fila['id_evento']] ?? null;
        $esConcurso = $suAlbum !== null && mbEsConcurso($suAlbum);
        if ($esConcurso && mbVotacionEmpezo($suAlbum, time())) {
            mbResponder(false, 'La votación ya empezó: tu foto del concurso ya no se puede borrar.');
        }

        $stmt = $conn->prepare('DELETE FROM messbook_fotos WHERE id = ? AND noEmpleado = ?');
        $stmt->bind_param('is', $idFoto, $noEmpleado);
        $stmt->execute();
        $stmt->close();

        $ruta = mbRutaFoto((string)$fila['url']);
        if ($ruta !== null && is_file($ruta)) @unlink($ruta);

        mbResponder(true, 'Foto borrada.', [
            'id'       => $idFoto,
            // En el concurso, para que la caja de subir vuelva a aparecer.
            'concurso' => $esConcurso ? mbEstadoConcurso($conn, $suAlbum, $noEmpleado) : null,
        ]);
    } catch (Throwable $e) {
        mbFallo('No pudimos borrar la foto. Inténtalo de nuevo.', $e);
    }
}


// ── voto ─────────────────────────────────────────────────────────────────────
// Dar (dar=1) o quitar (dar=0) un like en el concurso. Idempotente: dar uno que
// ya está o quitar uno que no está no cambia nada, así un doble toque no lo
// revierte. Reglas (decisión del 2026-10-21): sólo con la votación abierta, a lo
// más MB_CONCURSO_VOTOS por persona, uno por foto y nunca a la propia (salvo
// si las sube RRHH: quien la subió no es el participante).
if ($accion === 'voto') {
    $album  = mbAlbumPedido($eventos);
    $ev     = $eventos[$album];
    $idFoto = (int)($_POST['id'] ?? 0);
    $dar    = !empty($_POST['dar']);
    if (!mbEsConcurso($ev)) mbResponder(false, 'En este álbum no se vota.');

    $ahora = time();
    if (!mbVotacionAbierta($ev, $ahora)) {
        $v = mbVotacion($ev);
        mbResponder(false, $v && $ahora < $v[0]
            ? 'La votación todavía no abre: empieza ' . mbCuando($v[0], $ahora) . '.'
            : 'La votación ya cerró.', ['fuera_de_horario' => true]);
    }
    // En un concurso sólo hay fotos de empleados ('MB'): el id de otra tabla
    // nunca se confunde con uno de aquí.
    if ($idFoto <= 0 || ($_POST['origen'] ?? 'MB') !== 'MB') mbResponder(false, 'No encontramos esa foto.');

    try {
        $conn->begin_transaction();
        mbBloquearConcurso($conn, $album);

        $stmt = $conn->prepare('SELECT noEmpleado FROM messbook_fotos WHERE id = ? AND id_evento = ? LIMIT 1');
        $stmt->bind_param('ii', $idFoto, $album);
        $stmt->execute();
        $foto = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $error = !$foto ? 'Esa foto ya no está en el concurso.'
               : ((string)$foto['noEmpleado'] === $noEmpleado && !mbSubeRrhh($ev) ? 'No puedes darle like a tu propia foto.' : '');
        if ($error !== '') {
            $conn->rollback();
            mbResponder(false, $error);
        }

        // Los likes que ya dio en este concurso.
        $stmt = $conn->prepare('SELECT id_foto FROM messbook_votos WHERE id_evento = ? AND noEmpleado = ?');
        $stmt->bind_param('is', $album, $noEmpleado);
        $stmt->execute();
        $mios = array_map('intval', array_column($stmt->get_result()->fetch_all(), 0));
        $stmt->close();
        $ya = in_array($idFoto, $mios, true);

        if ($dar && !$ya) {
            if (count($mios) >= MB_CONCURSO_VOTOS) {
                $conn->rollback();
                mbResponder(false, MB_CONCURSO_VOTOS === 1
                    ? 'Ya diste tu like. Quítaselo a la otra foto para dárselo a ésta.'
                    : 'Ya usaste tus ' . MB_CONCURSO_VOTOS . ' likes. Quita uno para dárselo a otra foto.', ['restantes' => 0]);
            }
            $stmt = $conn->prepare('INSERT INTO messbook_votos (id_evento, id_foto, noEmpleado, fecha) VALUES (?, ?, ?, NOW())');
            $stmt->bind_param('iis', $album, $idFoto, $noEmpleado);
            $stmt->execute();
            $stmt->close();
            $mios[] = $idFoto;
        } elseif (!$dar && $ya) {
            $stmt = $conn->prepare('DELETE FROM messbook_votos WHERE id_foto = ? AND noEmpleado = ?');
            $stmt->bind_param('is', $idFoto, $noEmpleado);
            $stmt->execute();
            $stmt->close();
            $mios = array_values(array_diff($mios, [$idFoto]));
        }
        $conn->commit();

        mbResponder(true, '', ['id' => $idFoto, 'votado' => $dar, 'restantes' => max(0, MB_CONCURSO_VOTOS - count($mios))]);
    } catch (Throwable $e) {
        $conn->rollback();
        mbFallo('No pudimos registrar tu like. Inténtalo de nuevo.', $e);
    }
}


mbResponder(false, 'Acción no válida.');

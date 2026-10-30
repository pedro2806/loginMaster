<?php
/**
 * album.php — Qué álbumes hay y cuándo recibe fotos cada uno.
 *
 * Lo comparten acciones_album.php, que hace cumplir el horario, e inicio.php,
 * que dibuja el botón "Subir fotos" bajo la tarjeta de usuario. La vista
 * vuelve a calcular el horario en JS (inicio.php, estadoSubida) con las mismas
 * ventanas que salen de aquí: si cambia la regla, cambia aquí; allá sólo se
 * enseña.
 */

/*
 * LOS ÁLBUMES viven en la BD (sql/2026-09-25_album_admin.sql) y se
 * administran desde el modal de la pestaña Fotos (modalAlbumes.php: quien tenga
 * el acceso especial, ver mbPuedeAdministrar):
 * una fila de mess_rrhh.messbook_albumes por álbum, ligada 1 a 1 con su fila
 * de enc_eventos (tipo 'album', estatus 0), de donde salen las fechas.
 *
 * Hay dos tipos de álbum (decisión del 2026-09-25):
 *
 *   EVENTO — recibe fotos SÓLO en su día (la fecha de fecha_inicio) y lo que
 *   sigue, a cualquier hora: MB_EVENTO_HORAS desde las 00:00 de ese día
 *   (decisión del 2026-10-15; antes, hasta las 12:00 del día siguiente).
 *   Mientras tanto, todo lo que se sube va a él.
 *
 *   GENERAL ("Pruebas") — recibe fotos en su periodo (fecha_inicio a
 *   fecha_fin; sólo cuenta el día) y sólo en las ventanas de MB_VENTANAS,
 *   menos los días de evento: esos, las fotos van al evento.
 *
 * Así, en cada momento a lo más un álbum de evento o general recibe fotos (el
 * "activo"), y el botón de la tarjeta sube siempre a ése. El modal no deja
 * guardar dos eventos con ventanas encimadas ni dos generales con periodos
 * encimados. Un álbum oculto (visible = 0) no sale en la pestaña ni recibe fotos.
 *
 *   CONCURSO (decisión del 2026-10-21, disfraces) — cada empleado sube
 *   MB_CONCURSO_FOTOS foto y reparte sus likes entre las de los demás
 *   (messbook_votos, sql/2026-10-21_album_concurso.sql). Cuántos likes tiene
 *   cada quien lo dice el concurso (`votos_max`, se elige en el modal;
 *   decisión del 2026-10-30: calaveras lleva 2, los demás 1). Tiene tres
 *   periodos: la vigencia (fecha_inicio..fecha_fin, como los demás), que sólo
 *   enmarca a los otros dos, porque el álbum se ve siempre; la subida
 *   (subida_desde..subida_hasta, a cualquier hora), que es su ventana; y la
 *   votación (votacion_desde..votacion_hasta). Recibe fotos APARTE: no es "el
 *   activo", así que no le quita las fotos al general ni al botón de la
 *   tarjeta. La foto se borra o se cambia sólo antes de que abra la votación;
 *   los likes de cada foto se enseñan al cerrarla, y el ranking lo ven en vivo
 *   BI y RRHH (mbPuedeVerRanking).
 *
 *   Un concurso con `sube_rrhh` (decisión del 2026-10-30: DISFRACES 2026 y
 *   DISEÑO DE CALAVERAS) no lo llenan los empleados: las fotos las suben RRHH
 *   y quien administra los álbumes (mbPuedeSubirRrhh), sin tope de 1, con el
 *   nombre del participante o del equipo en el texto. Todos votan, también
 *   quien subió la foto: no es "la suya" (sql/2026-10-30_album_concurso_rrhh.sql).
 *
 * Fotos de invitados: las de mess_oktobermess.foto_evento cuyo `evento` sea
 * invitados_evento, de las sedes de invitados_sedes (códigos de
 * invitados.sede; MB_SIN_SEDE = invitados registrados antes de que existiera
 * el campo, cuando sólo había Bajío).
 */
const MB_SIN_SEDE = 'SIN_SEDE';

const MB_CONCURSO_FOTOS = 1;          // por empleado, en cada concurso
const MB_CONCURSO_VOTOS = 1;          // likes por empleado de un concurso nuevo; cada uno trae los suyos (votos_max)
const MB_CONCURSO_VOTOS_MAX = 10;     // tope de likes por empleado que deja elegir el modal
const MB_DEPTOS_RANKING = [27, 47];   // ven el ranking: BI y Recursos Humanos (usuarios.departamento)
const MB_DEPTOS_SUBEN   = [47];       // suben las fotos de un concurso `sube_rrhh`: Recursos Humanos

/*
 * HORARIO, en hora de la Ciudad de México. Una ventana cuyo fin es menor que
 * su inicio termina al día siguiente: la de 18:00 a 06:00 cubre la noche. Las
 * que se tocan se juntan en una sola, para que la pantalla diga la hora real
 * a la que se cierra.
 *
 * Fuera de horario el muro se sigue viendo y cada quien puede borrar las
 * suyas; sólo no se sube.
 */
const MB_ZONA      = 'America/Mexico_City';
const MB_VENTANAS  = [['12:00', '15:00'], ['18:00', '06:00']];   // álbum general
const MB_EVENTO_HORAS = 48;    // álbum de evento: desde las 00:00 de su día
const MB_MAX_DIAS  = 366;       // tope de días por álbum: una fecha_fin mal capturada no genera años de ventanas


/**
 * Los álbumes, por id_evento y del más viejo al más nuevo. Cada uno trae su
 * fila (id_evento, nombre, fecha_inicio, fecha_fin, visible…), su
 * configuración en `cfg` (titulo, dia, invitados) y sus `ventanas` de subida.
 *
 * Por omisión sólo los visibles, que son los que salen en la pestaña y los
 * únicos que reciben fotos. `$conOcultos` es para el modal de administración:
 * a los ocultos no se les calculan ventanas (quedan en []). Se consulta una
 * vez por petición; mbOlvidarAlbumes() tira el caché tras guardar.
 */
function mbEventosAlbum(mysqli $conn, bool $conOcultos = false): array {
    static $cache = null;
    if ($cache === null || !empty($GLOBALS['mb_albumes_sucio'])) {
        $GLOBALS['mb_albumes_sucio'] = false;
        $cache = [];
        $res = $conn->query("SELECT e.id_evento, e.nombre, e.fecha_inicio, e.fecha_fin,
                                    a.titulo, a.tipo, a.invitados_evento, a.invitados_sedes, a.portada, a.visible,
                                    a.subida_desde, a.subida_hasta, a.votacion_desde, a.votacion_hasta, a.sube_rrhh, a.votos_max
                               FROM messbook_albumes a
                               JOIN enc_eventos e ON e.id_evento = a.id_evento
                              ORDER BY e.fecha_inicio, e.id_evento");
        foreach ($res->fetch_all(MYSQLI_ASSOC) as $e) {
            $esConcurso = $e['tipo'] === 'concurso';
            $e['cfg'] = [
                'titulo'    => (string)$e['titulo'],
                'dia'       => $e['tipo'] === 'evento' ? substr((string)$e['fecha_inicio'], 0, 10) : null,
                // Un concurso es sólo de empleados: nunca trae fotos de invitados.
                'invitados' => !$esConcurso && $e['invitados_evento'] !== null && $e['invitados_evento'] !== ''
                    ? ['evento' => (string)$e['invitados_evento'], 'sedes' => mbSedesDeTexto($e['invitados_sedes'])]
                    : null,
                // [inicio, fin) en Unix de la subida y de la votación, si las
                // fotos las sube RRHH y los likes por empleado; null si no es concurso.
                'concurso'  => $esConcurso
                    ? ['subida'   => mbTramo($e['subida_desde'], $e['subida_hasta']),
                       'votacion' => mbTramo($e['votacion_desde'], $e['votacion_hasta']),
                       'rrhh'     => !empty($e['sube_rrhh']),
                       'votos'    => max(1, (int)$e['votos_max'])]
                    : null,
            ];
            $e['ventanas'] = [];
            $cache[(int)$e['id_evento']] = $e;
        }

        // Las ventanas dependen de todos los visibles: el general se apaga en
        // los días de evento. Los ocultos no cuentan para nada.
        $diasEvento = [];
        foreach ($cache as $e) {
            $d = $e['visible'] ? mbVentanaEvento($e) : null;
            if ($d) $diasEvento[] = $d;
        }
        foreach ($cache as $id => $e) {
            if (!$e['visible']) continue;
            if (mbEsConcurso($e)) {
                // A cualquier hora dentro de su subida, sin descontar los días
                // de evento: recibe aparte de los demás.
                $s = $e['cfg']['concurso']['subida'];
                $cache[$id]['ventanas'] = $s ? [$s] : [];
                continue;
            }
            $d = mbVentanaEvento($e);
            $cache[$id]['ventanas'] = $d ? [$d] : mbRestar(mbVentanasPeriodo($e), $diasEvento);
        }
    }
    if ($conOcultos) return $cache;
    return array_filter($cache, function ($e) { return (bool)$e['visible']; });
}

/** Tras guardar en el modal: que la siguiente consulta relea la BD. */
function mbOlvidarAlbumes(): void {
    // El caché es un static de mbEventosAlbum; se reinicia con esta bandera.
    $GLOBALS['mb_albumes_sucio'] = true;
}

/**
 * invitados_sedes ('Bajio,SIN_SEDE') → ['Bajio', null] para mbMuro; null si
 * vino vacío (todas las sedes). MB_SIN_SEDE se vuelve null: "sin sede".
 */
function mbSedesDeTexto(?string $texto): ?array {
    $texto = trim((string)$texto);
    if ($texto === '') return null;
    return array_map(function ($s) { return $s === MB_SIN_SEDE ? null : $s; },
                     array_values(array_filter(array_map('trim', explode(',', $texto)), 'strlen')));
}

/*
 * Quién administra los álbumes (decisión del 2026-10-22): SÓLO quien tenga en
 * mess_rrhh.accesos_especiales una fila con este sistema y esta opción, que se
 * da y se quita desde el modal de Accesos Especiales. Ya no va por
 * departamento: BI también necesita su fila. inf_adicional no se usa.
 */
const MB_ACCESO_SISTEMA = 'album';
const MB_ACCESO_OPCION  = 'administrarAlbumes';

/**
 * ¿Puede administrar los álbumes? Quien tenga el acceso especial
 * MB_ACCESO_SISTEMA / MB_ACCESO_OPCION y siga activo. Se revisa en el servidor
 * en cada acción del modal; OJO: se basa en la cookie noEmpleadoL, que no va
 * firmada.
 */
function mbPuedeAdministrar(mysqli $conn, string $noEmpleado): bool {
    if (!preg_match('/^\d{1,11}$/', $noEmpleado)) return false;
    $sistema = MB_ACCESO_SISTEMA;
    $opcion  = MB_ACCESO_OPCION;
    // accesos_especiales.noEmpleado es INT y usuarios.noEmpleado VARCHAR: se
    // comparan como número.
    $stmt = $conn->prepare("SELECT 1 FROM mess_rrhh.usuarios u
                             WHERE u.noEmpleado = ? AND u.estatus = 1
                               AND EXISTS (SELECT 1 FROM mess_rrhh.accesos_especiales ae
                                            WHERE ae.noEmpleado = u.noEmpleado AND ae.sistema = ? AND ae.opcion = ? AND ae.estatus = 1)
                             LIMIT 1");
    $stmt->bind_param('sss', $noEmpleado, $sistema, $opcion);
    $stmt->execute();
    $si = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $si;
}

/**
 * ¿Puede ver el ranking de un concurso? BI y RRHH (MB_DEPTOS_RANKING), en vivo,
 * aunque la votación siga abierta: los demás sólo ven los likes al cerrar.
 * También quien administra los álbumes (mbPuedeAdministrar): abre el ranking
 * desde el modal. Mismas reservas: se basa en la cookie noEmpleadoL.
 */
function mbPuedeVerRanking(mysqli $conn, string $noEmpleado): bool {
    if (!preg_match('/^\d{1,11}$/', $noEmpleado)) return false;
    $deptos = implode(',', array_map('intval', MB_DEPTOS_RANKING));
    $stmt = $conn->prepare("SELECT 1 FROM mess_rrhh.usuarios WHERE noEmpleado = ? AND estatus = 1 AND departamento IN ($deptos) LIMIT 1");
    $stmt->bind_param('s', $noEmpleado);
    $stmt->execute();
    $si = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $si || mbPuedeAdministrar($conn, $noEmpleado);
}

/**
 * ¿Puede subir fotos a un concurso `sube_rrhh`? Recursos Humanos
 * (MB_DEPTOS_SUBEN) y quien administra los álbumes (mbPuedeAdministrar). Se
 * consulta una vez por petición. Mismas reservas: se basa en la cookie
 * noEmpleadoL.
 */
function mbPuedeSubirRrhh(mysqli $conn, string $noEmpleado): bool {
    static $cache = [];
    if (isset($cache[$noEmpleado])) return $cache[$noEmpleado];
    if (!preg_match('/^\d{1,11}$/', $noEmpleado)) return false;
    $deptos = implode(',', array_map('intval', MB_DEPTOS_SUBEN));
    $stmt = $conn->prepare("SELECT 1 FROM mess_rrhh.usuarios WHERE noEmpleado = ? AND estatus = 1 AND departamento IN ($deptos) LIMIT 1");
    $stmt->bind_param('s', $noEmpleado);
    $stmt->execute();
    $si = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $cache[$noEmpleado] = $si || mbPuedeAdministrar($conn, $noEmpleado);
}

/** ¿Es un concurso? (tipo 'concurso', ver CONCURSO arriba) */
function mbEsConcurso(array $evento): bool {
    return ($evento['tipo'] ?? '') === 'concurso';
}

/** ¿Es un concurso cuyas fotos sube RRHH? (`sube_rrhh`, ver CONCURSO arriba) */
function mbSubeRrhh(array $evento): bool {
    return !empty($evento['cfg']['concurso']['rrhh']);
}

/** Likes que tiene cada empleado en este concurso (`votos_max`, ver CONCURSO arriba). */
function mbVotosConcurso(array $evento): int {
    return $evento['cfg']['concurso']['votos'] ?? MB_CONCURSO_VOTOS;
}

/** Dos DATETIME en hora de MB_ZONA → [inicio, fin) en Unix. null si falta uno
    o si el fin no es posterior al inicio. */
function mbTramo(?string $desde, ?string $hasta): ?array {
    $zona = new DateTimeZone(MB_ZONA);
    $d = $desde ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $desde, $zona) : false;
    $h = $hasta ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $hasta, $zona) : false;
    if (!$d || !$h || $h <= $d) return null;
    return [$d->getTimestamp(), $h->getTimestamp()];
}

/** La votación de un concurso, [inicio, fin) en Unix; null si no es concurso
    o no tiene fechas válidas (entonces nunca se vota). */
function mbVotacion(array $evento): ?array {
    return $evento['cfg']['concurso']['votacion'] ?? null;
}

/** Se pueden dar y quitar likes en este momento. */
function mbVotacionAbierta(array $evento, int $ahora): bool {
    $v = mbVotacion($evento);
    return $v !== null && $ahora >= $v[0] && $ahora < $v[1];
}

/** Ya abrió la votación: desde entonces la foto del concurso ya no se borra. */
function mbVotacionEmpezo(array $evento, int $ahora): bool {
    $v = mbVotacion($evento);
    return $v !== null && $ahora >= $v[0];
}

/** Ya cerró: los likes de cada foto se enseñan a todos. */
function mbVotacionCerrada(array $evento, int $ahora): bool {
    $v = mbVotacion($evento);
    return $v !== null && $ahora >= $v[1];
}

/**
 * Lo que el concurso le pide hoy a `$noEmpleado`, para recordárselo en Mi
 * Espacio (inicio.php, "Encuestas / Votaciones"): votar mientras la votación
 * está abierta, o subir su foto si la subida está abierta y no ha subido (no
 * en un concurso `sube_rrhh`: ahí nadie sube la suya). null si no hay nada que
 * hacer.
 */
function mbPendienteConcurso(mysqli $conn, array $evento, string $noEmpleado, int $ahora): ?array {
    $c = $evento['cfg']['concurso'] ?? null;
    if (!$c) return null;
    $id = (int)$evento['id_evento'];
    if (mbVotacionAbierta($evento, $ahora)) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM messbook_votos WHERE id_evento = ? AND noEmpleado = ?');
        $stmt->bind_param('is', $id, $noEmpleado);
        $stmt->execute();
        $dados = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
        return ['id' => $id, 'titulo' => mbTituloAlbum($evento), 'fase' => 'votacion',
                'restantes' => max(0, mbVotosConcurso($evento) - $dados), 'votos_max' => mbVotosConcurso($evento),
                'cierra' => mbCuando($c['votacion'][1], $ahora)];
    }
    if (!$c['rrhh'] && $c['subida'] && mbSubidaAbierta([$c['subida']], $ahora)) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM messbook_fotos WHERE id_evento = ? AND noEmpleado = ?');
        $stmt->bind_param('is', $id, $noEmpleado);
        $stmt->execute();
        $mias = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($mias >= MB_CONCURSO_FOTOS) return null;
        return ['id' => $id, 'titulo' => mbTituloAlbum($evento), 'fase' => 'subida',
                'cierra' => mbCuando($c['subida'][1], $ahora)];
    }
    return null;
}

/** Lo que se lee en la portada: el `titulo` o el nombre del evento. */
function mbTituloAlbum(array $evento): string {
    return (string)($evento['cfg']['titulo'] ?? $evento['nombre']);
}

/*
 * LOGO de oktoberMESS de cada sede, por código de invitados.sede. Salen de
 * las landings: Bajío, el del inicio de oktobermess2026 (img/landing/image2.png);
 * SLP, recortado del pin del mapa de oktobermess2026SLP (img/pin-slp.png), sin
 * el marco ni la flecha. Los dos a 200 px de alto.
 */
const MB_LOGOS_SEDE = [
    'Bajio' => 'img/album/oktobermess-bajio.png',
    'SLP'   => 'img/album/oktobermess-slp.png',
];

/** Logo del álbum: el de la primera de sus sedes que tenga uno. null si el
    álbum no separa por sede (sin invitados o con todas las sedes). */
function mbLogoAlbum(array $evento): ?string {
    foreach ($evento['cfg']['invitados']['sedes'] ?? [] as $sede) {
        if (is_string($sede) && isset(MB_LOGOS_SEDE[$sede])) return MB_LOGOS_SEDE[$sede];
    }
    return null;
}

/**
 * La ventana de un álbum de evento como [inicio, fin) en segundos Unix:
 * MB_EVENTO_HORAS desde las 00:00 de `dia`. null si es álbum general.
 */
function mbVentanaEvento(array $evento): ?array {
    $dia = DateTimeImmutable::createFromFormat('!Y-m-d', (string)($evento['cfg']['dia'] ?? ''), new DateTimeZone(MB_ZONA));
    if (!$dia) return null;
    return [$dia->getTimestamp(), $dia->modify('+' . MB_EVENTO_HORAS . ' hours')->getTimestamp()];
}

/** Ventanas de MB_VENTANAS en cada día del periodo, juntas y en orden. */
function mbVentanasPeriodo(array $evento): array {
    $zona = new DateTimeZone(MB_ZONA);
    $dia  = DateTimeImmutable::createFromFormat('!Y-m-d', substr((string)$evento['fecha_inicio'], 0, 10), $zona);
    if (!$dia) return [];
    $ultimo = DateTimeImmutable::createFromFormat('!Y-m-d', substr((string)($evento['fecha_fin'] ?? ''), 0, 10), $zona);
    if (!$ultimo || $ultimo < $dia) $ultimo = $dia;

    $ventanas = [];
    for ($n = 0; $dia <= $ultimo && $n < MB_MAX_DIAS; $n++, $dia = $dia->modify('+1 day')) {
        foreach (MB_VENTANAS as [$de, $a]) {
            $inicio = $dia->modify($de);
            $fin    = $dia->modify($a);
            if ($fin <= $inicio) $fin = $fin->modify('+1 day');
            $ventanas[] = [$inicio->getTimestamp(), $fin->getTimestamp()];
        }
    }

    sort($ventanas);
    $juntas = [];
    foreach ($ventanas as $v) {
        $n = count($juntas) - 1;
        if ($n >= 0 && $v[0] <= $juntas[$n][1]) $juntas[$n][1] = max($juntas[$n][1], $v[1]);
        else $juntas[] = $v;
    }
    return $juntas;
}

/** Las ventanas sin los tramos de `$quitar` (los días de evento). */
function mbRestar(array $ventanas, array $quitar): array {
    foreach ($quitar as [$qi, $qf]) {
        $quedan = [];
        foreach ($ventanas as [$i, $f]) {
            if ($qf <= $i || $qi >= $f) { $quedan[] = [$i, $f]; continue; }   // no se tocan
            if ($i < $qi) $quedan[] = [$i, $qi];                             // lo de antes
            if ($f > $qf) $quedan[] = [$qf, $f];                             // lo de después
        }
        $ventanas = $quedan;
    }
    return $ventanas;
}

function mbSubidaAbierta(array $ventanas, int $ahora): bool {
    foreach ($ventanas as [$inicio, $fin]) {
        if ($ahora >= $inicio && $ahora < $fin) return true;
    }
    return false;
}

/** id del álbum que recibe fotos en este momento, o null si ninguno. El
    concurso no cuenta: recibe aparte (ver CONCURSO arriba). */
function mbAlbumActivo(array $eventos, int $ahora): ?int {
    foreach ($eventos as $id => $e) {
        if (mbEsConcurso($e)) continue;
        if (mbSubidaAbierta($e['ventanas'], $ahora)) return $id;
    }
    return null;
}

/** ¿El álbum todavía va a recibir fotos? (no ha cerrado su última ventana) */
function mbAlbumVigente(array $ventanas, int $ahora): bool {
    return $ventanas && $ahora < end($ventanas)[1];
}

/** "2026-10-01 00:00:00" → "1 oct 2026". */
function mbFechaCorta(string $fecha): string {
    $ts = strtotime($fecha);
    if ($ts === false) return '';
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return date('j', $ts) . ' ' . $meses[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/** "hoy a las 18:00", "mañana a las 12:00", "el 9 oct a las 12:00". */
function mbCuando(int $ts, int $ahora): string {
    $zona  = new DateTimeZone(MB_ZONA);
    $fecha = (new DateTimeImmutable('@' . $ts))->setTimezone($zona);
    $hoy   = (new DateTimeImmutable('@' . $ahora))->setTimezone($zona);
    $hora  = 'a las ' . $fecha->format('H:i');
    if ($fecha->format('Y-m-d') === $hoy->format('Y-m-d'))                     return "hoy $hora";
    if ($fecha->format('Y-m-d') === $hoy->modify('+1 day')->format('Y-m-d'))   return "mañana $hora";
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return 'el ' . $fecha->format('j') . ' ' . $meses[(int)$fecha->format('n') - 1] . " $hora";
}

/**
 * Por qué no se puede subir a este álbum en este momento, dicho para el
 * empleado. Si otro álbum está recibiendo fotos, se le manda para allá.
 */
function mbMotivoCerrada(int $album, array $eventos, int $ahora): string {
    // El concurso tiene su propia subida: no se manda al álbum activo.
    if (mbEsConcurso($eventos[$album] ?? [])) {
        $s = $eventos[$album]['cfg']['concurso']['subida'];
        return $s && $ahora < $s[0]
            ? 'Todavía no se pueden subir fotos al concurso: abre ' . mbCuando($s[0], $ahora) . '.'
            : 'El concurso ya no recibe fotos.';
    }
    $activo = mbAlbumActivo($eventos, $ahora);
    if ($activo !== null && $activo !== $album) {
        return 'Ahora las fotos se suben al álbum «' . mbTituloAlbum($eventos[$activo]) . '».';
    }
    $ventanas = $eventos[$album]['ventanas'] ?? [];
    foreach ($ventanas as [$inicio]) {
        if ($inicio <= $ahora) continue;
        return $ahora < $ventanas[0][0]
            ? 'Todavía no se pueden subir fotos a este álbum: abre ' . mbCuando($inicio, $ahora) . '.'
            : 'La subida de fotos está en pausa. Se vuelve a abrir ' . mbCuando($inicio, $ahora) . '.';
    }
    return 'Este álbum ya no recibe fotos.';
}

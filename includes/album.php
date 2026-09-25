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
 * administran desde el modal de la pestaña Fotos (modalAlbumes.php, sólo BI):
 * una fila de mess_rrhh.messbook_albumes por álbum, ligada 1 a 1 con su fila
 * de enc_eventos (tipo 'album', estatus 0), de donde salen las fechas.
 *
 * Hay dos tipos de álbum (decisión del 2026-09-25):
 *
 *   EVENTO — recibe fotos SÓLO en su día (la fecha de fecha_inicio), a
 *   cualquier hora: de las 00:00 a MB_DIA_HASTA del día siguiente. Mientras
 *   tanto, todo lo que se sube va a él.
 *
 *   GENERAL ("Pruebas") — recibe fotos en su periodo (fecha_inicio a
 *   fecha_fin; sólo cuenta el día) y sólo en las ventanas de MB_VENTANAS,
 *   menos los días de evento: esos, las fotos van al evento.
 *
 * Así, en cada momento a lo más un álbum recibe fotos (el "activo"), y el
 * botón de la tarjeta sube siempre a ése. El modal no deja guardar dos eventos
 * el mismo día ni dos generales con periodos encimados. Un álbum oculto
 * (visible = 0) no sale en la pestaña ni recibe fotos.
 *
 * Fotos de invitados: las de mess_oktobermess.foto_evento cuyo `evento` sea
 * invitados_evento, de las sedes de invitados_sedes (códigos de
 * invitados.sede; MB_SIN_SEDE = invitados registrados antes de que existiera
 * el campo, cuando sólo había Bajío).
 */
const MB_SIN_SEDE = 'SIN_SEDE';

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
const MB_DIA_HASTA = '12:00';   // del día siguiente al evento
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
                                    a.titulo, a.tipo, a.invitados_evento, a.invitados_sedes, a.visible
                               FROM messbook_albumes a
                               JOIN enc_eventos e ON e.id_evento = a.id_evento
                              ORDER BY e.fecha_inicio, e.id_evento");
        foreach ($res->fetch_all(MYSQLI_ASSOC) as $e) {
            $e['cfg'] = [
                'titulo'    => (string)$e['titulo'],
                'dia'       => $e['tipo'] === 'evento' ? substr((string)$e['fecha_inicio'], 0, 10) : null,
                'invitados' => $e['invitados_evento'] !== null && $e['invitados_evento'] !== ''
                    ? ['evento' => (string)$e['invitados_evento'], 'sedes' => mbSedesDeTexto($e['invitados_sedes'])]
                    : null,
            ];
            $e['ventanas'] = [];
            $cache[(int)$e['id_evento']] = $e;
        }

        // Las ventanas dependen de todos los visibles: el general se apaga en
        // los días de evento. Los ocultos no cuentan para nada.
        $diasEvento = [];
        foreach ($cache as $e) {
            $d = $e['visible'] ? mbDiaCompleto($e) : null;
            if ($d) $diasEvento[] = $d;
        }
        foreach ($cache as $id => $e) {
            if (!$e['visible']) continue;
            $d = mbDiaCompleto($e);
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
 * invitados_sedes ('BAJIO,SIN_SEDE') → ['BAJIO', null] para mbMuro; null si
 * vino vacío (todas las sedes). MB_SIN_SEDE se vuelve null: "sin sede".
 */
function mbSedesDeTexto(?string $texto): ?array {
    $texto = trim((string)$texto);
    if ($texto === '') return null;
    return array_map(function ($s) { return $s === MB_SIN_SEDE ? null : $s; },
                     array_values(array_filter(array_map('trim', explode(',', $texto)), 'strlen')));
}

/**
 * ¿Puede administrar los álbumes? De momento, BI (usuarios.departamento = 27),
 * la misma regla que hoy enseña la pestaña. Se revisa en el servidor en cada
 * acción del modal; OJO: se basa en la cookie noEmpleadoL, que no va firmada.
 */
function mbPuedeAdministrar(mysqli $conn, string $noEmpleado): bool {
    if (!preg_match('/^\d{1,11}$/', $noEmpleado)) return false;
    $stmt = $conn->prepare('SELECT 1 FROM mess_rrhh.usuarios WHERE noEmpleado = ? AND estatus = 1 AND departamento = 27 LIMIT 1');
    $stmt->bind_param('s', $noEmpleado);
    $stmt->execute();
    $si = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $si;
}

/** Lo que se lee en la portada: el `titulo` o el nombre del evento. */
function mbTituloAlbum(array $evento): string {
    return (string)($evento['cfg']['titulo'] ?? $evento['nombre']);
}

/**
 * El día de un álbum de evento como [inicio, fin) en segundos Unix: de las
 * 00:00 de `dia` a MB_DIA_HASTA del siguiente. null si es álbum general.
 */
function mbDiaCompleto(array $evento): ?array {
    $dia = DateTimeImmutable::createFromFormat('!Y-m-d', (string)($evento['cfg']['dia'] ?? ''), new DateTimeZone(MB_ZONA));
    if (!$dia) return null;
    return [$dia->getTimestamp(), $dia->modify('+1 day')->modify(MB_DIA_HASTA)->getTimestamp()];
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

/** id del álbum que recibe fotos en este momento, o null si ninguno. */
function mbAlbumActivo(array $eventos, int $ahora): ?int {
    foreach ($eventos as $id => $e) {
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

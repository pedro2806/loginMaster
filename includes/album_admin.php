<?php
/**
 * album_admin.php — Acciones del modal "Administrar álbumes" (modalAlbumes.php).
 *
 * Sólo se carga desde acciones_album.php, con la sesión ya revisada y $conn,
 * $noEmpleado y $accion listos; responde con mbResponder() como el resto del
 * endpoint. Cada acción termina la petición.
 *
 *   adm_listar  — todos los álbumes (también los ocultos) + opciones del form
 *   adm_guardar — crear o editar, con su portada (opcional)
 *   adm_visible — ocultar / mostrar
 *   adm_borrar  — borrar, sólo si no tiene fotos de empleados
 *
 * Un álbum = una fila de enc_eventos (tipo 'album', estatus 0: así nunca sale
 * como actividad pendiente; ver acciones_eventos.php) + una de
 * messbook_albumes. Se escriben juntas, en una transacción.
 *
 * Reglas que cuida el guardado, para que en cada momento haya a lo más un
 * álbum recibiendo fotos (includes/album.php):
 *   - dos álbumes de EVENTO visibles no se enciman (MB_EVENTO_HORAS cada uno);
 *   - dos GENERALES visibles no se enciman en su periodo.
 * El CONCURSO recibe aparte y no choca con nadie. Lleva vigencia (fecha_inicio..
 * fecha_fin) y, dentro de ella, la subida y la votación (fecha y hora); la
 * votación no abre antes que la subida ni la subida cierra después que la
 * votación. Un álbum con fotos no se vuelve concurso ni deja de serlo, ni
 * cambia quién sube las fotos (`sube_rrhh`). Los likes por empleado
 * (`votos_max`) se pueden subir siempre, pero no bajar de los que alguien ya dio.
 */

if (!isset($conn, $noEmpleado, $accion)) exit;   // sólo vía acciones_album.php

try {
    $puede = mbPuedeAdministrar($conn, $noEmpleado);
} catch (Throwable $e) {
    mbFallo('No pudimos revisar tus permisos.', $e);
}
if (!$puede) mbResponder(false, 'No tienes permiso para administrar los álbumes.');


/** 'AAAA-MM-DD' válido o null. */
function mbAdmFecha(string $f): ?string {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f ? $f : null;
}

/** Lo que manda un <input type="datetime-local"> ('AAAA-MM-DDThh:mm', a veces
    con segundos) → 'AAAA-MM-DD hh:mm:ss' para la BD, o null si no es válido. */
function mbAdmFechaHora(string $f): ?string {
    $f = str_replace('T', ' ', trim($f));
    foreach (['Y-m-d H:i', 'Y-m-d H:i:s'] as $formato) {
        $d = DateTimeImmutable::createFromFormat('!' . $formato, $f);
        if ($d && $d->format($formato) === $f) return $d->format('Y-m-d H:i:s');
    }
    return null;
}

/** DATETIME de la BD → valor de un <input type="datetime-local"> ('' si no hay). */
function mbAdmLocal(?string $f): string {
    return $f ? str_replace(' ', 'T', substr($f, 0, 16)) : '';
}

/**
 * ¿Choca con otro álbum visible? Devuelve el aviso para la pantalla o ''.
 * Evento: otro evento cuya ventana se encima (a menos de MB_EVENTO_HORAS, en
 * días, uno del otro). General: otro general con periodo encimado.
 */
function mbAdmConflicto(mysqli $conn, int $id, string $tipo, string $desde, string $hasta): string {
    if ($tipo === 'concurso') return '';   // recibe aparte: no le quita fotos a nadie
    $diasEvento = (int)ceil(MB_EVENTO_HORAS / 24);
    if ($tipo === 'evento') {
        $stmt = $conn->prepare("SELECT a.titulo FROM messbook_albumes a JOIN enc_eventos e ON e.id_evento = a.id_evento
                                 WHERE a.visible = 1 AND a.tipo = 'evento' AND a.id_evento <> ?
                                   AND ABS(DATEDIFF(DATE(e.fecha_inicio), ?)) < ? LIMIT 1");
        $stmt->bind_param('isi', $id, $desde, $diasEvento);
    } else {
        $stmt = $conn->prepare("SELECT a.titulo FROM messbook_albumes a JOIN enc_eventos e ON e.id_evento = a.id_evento
                                 WHERE a.visible = 1 AND a.tipo = 'general' AND a.id_evento <> ?
                                   AND DATE(e.fecha_inicio) <= ? AND DATE(e.fecha_fin) >= ? LIMIT 1");
        $stmt->bind_param('iss', $id, $hasta, $desde);
    }
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$fila) return '';
    return $tipo === 'evento'
        ? 'Se encima con el álbum de evento «' . $fila[0] . '»: cada uno recibe fotos ' . MB_EVENTO_HORAS
          . ' horas desde su día, así que tienen que estar a ' . $diasEvento . ' días o más. Cambia la fecha u oculta el otro.'
        : 'El periodo se encima con el álbum general «' . $fila[0] . '». Ajusta las fechas u oculta el otro.';
}

/** Fotos de empleados que tiene un álbum. */
function mbAdmFotos(mysqli $conn, int $id): int {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM messbook_fotos WHERE id_evento = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $n = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $n;
}

/** Más likes que ha dado un mismo empleado en un concurso (0 si nadie ha votado). */
function mbAdmMasLikes(mysqli $conn, int $id): int {
    $stmt = $conn->prepare('SELECT COUNT(*) AS n FROM messbook_votos WHERE id_evento = ? GROUP BY noEmpleado ORDER BY n DESC LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $fila ? (int)$fila[0] : 0;
}

/** Fila de messbook_albumes + fechas, o null. */
function mbAdmAlbum(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT a.*, DATE(e.fecha_inicio) AS desde, DATE(e.fecha_fin) AS hasta
                              FROM messbook_albumes a JOIN enc_eventos e ON e.id_evento = a.id_evento
                             WHERE a.id_evento = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}


// ── adm_listar ───────────────────────────────────────────────────────────────
if ($accion === 'adm_listar') {
    try {
        $todos  = mbEventosAlbum($conn, true);
        $activo = mbAlbumActivo(mbEventosAlbum($conn), time());

        // Fotos de empleados por álbum (las de invitados no cuentan para borrar).
        $fotos = [];
        $res = $conn->query('SELECT id_evento, COUNT(*) FROM messbook_fotos GROUP BY id_evento');
        foreach ($res->fetch_all() as [$idEv, $n]) $fotos[(int)$idEv] = (int)$n;

        // Likes por concurso, para la columna de fotos.
        $likes = [];
        $res = $conn->query('SELECT id_evento, COUNT(*) FROM messbook_votos GROUP BY id_evento');
        foreach ($res->fetch_all() as [$idEv, $n]) $likes[(int)$idEv] = (int)$n;

        $ahora   = time();
        $albumes = [];
        foreach (array_reverse($todos, true) as $id => $e) {   // los más nuevos arriba
            $albumes[] = [
                'id'        => $id,
                'titulo'    => (string)$e['titulo'],
                'tipo'      => (string)$e['tipo'],
                'desde'     => substr((string)$e['fecha_inicio'], 0, 10),
                'hasta'     => substr((string)$e['fecha_fin'], 0, 10),
                'invitados' => (string)($e['invitados_evento'] ?? ''),
                'sedes'     => array_values(array_filter(explode(',', (string)($e['invitados_sedes'] ?? '')), 'strlen')),
                'portada'   => mbPortadaPropia($e) ?? '',
                'visible'   => (bool)$e['visible'],
                'fotos'     => $fotos[$id] ?? 0,
                'likes'     => $likes[$id] ?? 0,
                // El concurso no es "el activo", pero también se marca mientras recibe.
                'activo'    => $id === $activo || (mbEsConcurso($e) && mbSubidaAbierta($e['ventanas'], $ahora)),
                'subida_desde'   => mbAdmLocal($e['subida_desde']),
                'subida_hasta'   => mbAdmLocal($e['subida_hasta']),
                'votacion_desde' => mbAdmLocal($e['votacion_desde']),
                'votacion_hasta' => mbAdmLocal($e['votacion_hasta']),
                'sube_rrhh'      => mbSubeRrhh($e),
                'votos_max'      => max(1, (int)$e['votos_max']),
            ];
        }

        // Opciones del formulario, de lo que ya hay en oktoberMESS.
        $eventosOkt = [];
        $res = $conn->query('SELECT DISTINCT evento FROM mess_oktobermess.foto_evento ORDER BY evento');
        foreach ($res->fetch_all() as [$ev]) $eventosOkt[] = (string)$ev;
        foreach ($albumes as $a) if ($a['invitados'] !== '' && !in_array($a['invitados'], $eventosOkt, true)) $eventosOkt[] = $a['invitados'];

        $sedes = [];
        $res = $conn->query('SELECT DISTINCT sede FROM mess_oktobermess.invitados WHERE sede IS NOT NULL AND sede <> "" ORDER BY sede');
        foreach ($res->fetch_all() as [$s]) $sedes[] = (string)$s;
        $sedes[] = MB_SIN_SEDE;

        mbResponder(true, '', ['albumes' => $albumes, 'eventos_okt' => $eventosOkt, 'sedes' => $sedes]);
    } catch (Throwable $e) {
        mbFallo('No pudimos cargar los álbumes.', $e);
    }
}


// ── adm_guardar ──────────────────────────────────────────────────────────────
if ($accion === 'adm_guardar') {
    $id     = (int)($_POST['id'] ?? 0);
    $titulo = trim((string)preg_replace('/[\p{Cc}\s]+/u', ' ', (string)($_POST['titulo'] ?? '')));
    $tipo   = (string)($_POST['tipo'] ?? '');
    $visible = (int)!empty($_POST['visible']);
    // Concurso: quién sube las fotos (cada empleado o RRHH) y cuántos likes
    // tiene cada empleado (includes/album.php, CONCURSO). En los demás tipos no
    // cuentan: se guardan 0 y el valor por omisión.
    $subeRrhh = (int)($tipo === 'concurso' && !empty($_POST['sube_rrhh']));
    $votosMax = $tipo === 'concurso' ? (int)($_POST['votos_max'] ?? 0) : MB_CONCURSO_VOTOS;

    if ($titulo === '')                           mbResponder(false, 'Escribe el nombre del álbum.');
    if (mb_strlen($titulo, 'UTF-8') > 80)          mbResponder(false, 'El nombre es muy largo: máximo 80 caracteres.');
    if (!in_array($tipo, ['evento', 'general', 'concurso'], true)) mbResponder(false, 'Elige si es un evento, un álbum general o un concurso.');
    if ($votosMax < 1 || $votosMax > MB_CONCURSO_VOTOS_MAX) {
        mbResponder(false, 'Los likes por empleado van de 1 a ' . MB_CONCURSO_VOTOS_MAX . '.');
    }

    if ($tipo === 'evento') {
        $desde = mbAdmFecha((string)($_POST['dia'] ?? ''));
        if (!$desde) mbResponder(false, 'Elige el día del evento.');
        $hasta = $desde;
    } else {
        // General: su periodo. Concurso: su vigencia.
        $desde = mbAdmFecha((string)($_POST['desde'] ?? ''));
        $hasta = mbAdmFecha((string)($_POST['hasta'] ?? ''));
        if (!$desde || !$hasta) mbResponder(false, $tipo === 'concurso' ? 'Elige el inicio y el fin de la vigencia del concurso.' : 'Elige el inicio y el fin del periodo.');
        if ($hasta < $desde)    mbResponder(false, 'El fin del periodo no puede ser antes del inicio.');
        $dias = (new DateTime($desde))->diff(new DateTime($hasta))->days + 1;
        if ($dias > MB_MAX_DIAS) mbResponder(false, 'El periodo no puede pasar de ' . MB_MAX_DIAS . ' días.');
    }

    // Concurso: la subida de fotos y la votación, con fecha y hora, dentro de la
    // vigencia. NULL en los demás tipos.
    $periodos = ['subida_desde' => null, 'subida_hasta' => null, 'votacion_desde' => null, 'votacion_hasta' => null];
    if ($tipo === 'concurso') {
        $nombres = [
            'subida_desde'   => 'la apertura de la subida de fotos',
            'subida_hasta'   => 'el cierre de la subida de fotos',
            'votacion_desde' => 'la apertura de la votación',
            'votacion_hasta' => 'el cierre de la votación',
        ];
        foreach ($nombres as $campo => $nombre) {
            $periodos[$campo] = mbAdmFechaHora((string)($_POST[$campo] ?? ''));
            if ($periodos[$campo] === null) mbResponder(false, 'Elige la fecha y la hora de ' . $nombre . '.');
        }
        // Todo va como 'AAAA-MM-DD hh:mm:ss': se compara bien como texto. La
        // vigencia acaba al terminar su último día.
        $vigInicio = $desde . ' 00:00:00';
        $vigFin    = (new DateTimeImmutable($hasta))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        if ($periodos['subida_hasta'] <= $periodos['subida_desde'])     mbResponder(false, 'La subida de fotos tiene que cerrar después de abrir.');
        if ($periodos['votacion_hasta'] <= $periodos['votacion_desde']) mbResponder(false, 'La votación tiene que cerrar después de abrir.');
        if ($periodos['votacion_desde'] < $periodos['subida_desde'])    mbResponder(false, 'La votación no puede abrir antes que la subida de fotos.');
        if ($periodos['subida_hasta'] > $periodos['votacion_hasta'])    mbResponder(false, 'La subida de fotos no puede cerrar después de la votación: esas fotos ya no recibirían likes.');
        if ($periodos['subida_desde'] < $vigInicio || $periodos['votacion_hasta'] > $vigFin) {
            mbResponder(false, 'La subida y la votación tienen que caer dentro de la vigencia del concurso.');
        }
    }

    // Invitados de oktoberMESS: opcional. Sedes vacías = todas. El concurso es
    // sólo de empleados: nunca lleva.
    $invEvento = $tipo === 'concurso' ? '' : trim((string)($_POST['invitados'] ?? ''));
    $invSedes  = null;
    if ($invEvento !== '') {
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $invEvento)) mbResponder(false, 'El evento de invitados sólo lleva letras, números, - y _.');
        $sedes = array_values(array_unique(array_filter(array_map('trim', (array)($_POST['sedes'] ?? [])), 'strlen')));
        // Tal como vienen de invitados.sede ('Bajio', 'SLP'), con minúsculas:
        // el modal marca las casillas comparando el texto exacto.
        foreach ($sedes as $s) {
            if (!preg_match('/^[A-Za-z0-9_]{1,20}$/', $s)) mbResponder(false, 'Sede no válida: ' . $s);
        }
        $invSedes = $sedes ? implode(',', $sedes) : null;
    } else {
        $invEvento = null;
    }

    // Portada, opcional. Una imagen nueva reemplaza a la que hubiera;
    // `quitar_portada` la quita y la pestaña vuelve a la foto más reciente. Sin
    // ninguna de las dos, se queda la que tenía. El modal ya la manda reducida.
    $archivo = $_FILES['portada'] ?? null;
    $errPortada = is_array($archivo) ? (int)$archivo['error'] : UPLOAD_ERR_NO_FILE;
    if ($errPortada === UPLOAD_ERR_INI_SIZE || $errPortada === UPLOAD_ERR_FORM_SIZE) {
        mbResponder(false, 'La portada es demasiado pesada. Prueba con otra imagen.');
    }
    if ($errPortada !== UPLOAD_ERR_OK && $errPortada !== UPLOAD_ERR_NO_FILE) {
        mbResponder(false, 'No recibimos la portada. Inténtalo de nuevo.');
    }
    $conPortada    = $errPortada === UPLOAD_ERR_OK && is_uploaded_file((string)$archivo['tmp_name']);
    $quitarPortada = !empty($_POST['quitar_portada']);

    try {
        $album = $id > 0 ? mbAdmAlbum($conn, $id) : null;
        if ($id > 0 && !$album) mbResponder(false, 'Ese álbum ya no existe. Recarga la lista.');
        // Con fotos, un álbum no se vuelve concurso ni deja de serlo: sus reglas
        // (1 foto por persona, likes) no se aplican hacia atrás.
        if ($album && ($album['tipo'] === 'concurso') !== ($tipo === 'concurso') && mbAdmFotos($conn, $id) > 0) {
            mbResponder(false, $tipo === 'concurso'
                ? 'Este álbum ya tiene fotos: no se puede convertir en concurso. Crea uno nuevo.'
                : 'Este concurso ya tiene fotos: no se puede cambiar a otro tipo.');
        }
        // Ni quién las sube: las que subió RRHH no son de quien las subió.
        if ($album && $tipo === 'concurso' && (int)$album['sube_rrhh'] !== $subeRrhh && mbAdmFotos($conn, $id) > 0) {
            mbResponder(false, 'Este concurso ya tiene fotos: ya no se puede cambiar quién las sube.');
        }
        // Bajar los likes dejaría a alguien con más de los permitidos.
        if ($album && $tipo === 'concurso') {
            $dados = mbAdmMasLikes($conn, $id);
            if ($votosMax < $dados) {
                mbResponder(false, 'Ya hay quien dio ' . $dados . ' likes en este concurso: no se puede bajar a menos de ' . $dados . '.');
            }
        }
        if ($visible) {
            $choque = mbAdmConflicto($conn, $id, $tipo, $desde, $hasta);
            if ($choque !== '') mbResponder(false, $choque);
        }

        // Lo último antes de escribir en la BD: si algo falla de aquí en
        // adelante, el archivo nuevo se borra.
        $anterior = $album ? mbPortadaPropia($album) : null;
        $portada  = $quitarPortada ? null : $anterior;
        $nueva    = null;
        if ($conPortada) {
            $tmp    = (string)$archivo['tmp_name'];
            $motivo = mbRevisarImagen($tmp);
            if ($motivo !== '') mbResponder(false, $motivo);
            $dir = dirname(__DIR__) . '/' . MB_CARPETA;
            if (!is_dir($dir) || !is_writable($dir)) {
                error_log('messbook adm_guardar: la carpeta ' . MB_CARPETA . '/ no existe o no tiene permiso de escritura.');
                mbResponder(false, 'Las imágenes no están disponibles en este momento. Avísale a BI.');
            }
            $nueva = MB_CARPETA . '/' . bin2hex(random_bytes(12)) . '.jpg';
            if (!mbGuardarJpegLimpio($tmp, mbRutaFoto($nueva), MB_PORTADA_LADO)) {
                mbResponder(false, 'No pudimos procesar esa imagen. Prueba con otra.');
            }
            $portada = $nueva;
        }

        // enc_eventos.nombre es latin1: fuera lo que no quepa ahí (emojis). El
        // nombre sólo identifica la fila en la BD; en pantalla sale `titulo`.
        $nombre = preg_replace('/[^\x{0000}-\x{00FF}]/u', '', $titulo);
        $nombre = mb_substr('Album de fotos: ' . trim(preg_replace('/\s+/u', ' ', $nombre)), 0, 150, 'UTF-8');
        $inicio = $desde . ' 00:00:00';
        $fin    = $hasta . ' 23:59:59';

        $conn->begin_transaction();
        try {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE enc_eventos SET nombre = ?, tipo = 'album', fecha_inicio = ?, fecha_fin = ?, estatus = 0 WHERE id_evento = ?");
                $stmt->bind_param('sssi', $nombre, $inicio, $fin, $id);
                $stmt->execute();
                $stmt->close();
                $stmt = $conn->prepare('UPDATE messbook_albumes SET titulo = ?, tipo = ?, invitados_evento = ?, invitados_sedes = ?, portada = ?, visible = ?,
                                               subida_desde = ?, subida_hasta = ?, votacion_desde = ?, votacion_hasta = ?, sube_rrhh = ?, votos_max = ?,
                                               actualizado_por = ?
                                         WHERE id_evento = ?');
                $stmt->bind_param('sssssissssiisi', $titulo, $tipo, $invEvento, $invSedes, $portada, $visible,
                                  $periodos['subida_desde'], $periodos['subida_hasta'], $periodos['votacion_desde'], $periodos['votacion_hasta'],
                                  $subeRrhh, $votosMax, $noEmpleado, $id);
                $stmt->execute();
                $stmt->close();
            } else {
                $desc = 'Album de la pestana Fotos de messbook. Se administra desde ahi (includes/album_admin.php): no editar ni asignar empleados aqui.';
                $stmt = $conn->prepare("INSERT INTO enc_eventos (nombre, descripcion, tipo, fecha_inicio, fecha_fin, estatus) VALUES (?, ?, 'album', ?, ?, 0)");
                $stmt->bind_param('ssss', $nombre, $desc, $inicio, $fin);
                $stmt->execute();
                $id = (int)$conn->insert_id;
                $stmt->close();
                $stmt = $conn->prepare('INSERT INTO messbook_albumes (id_evento, titulo, tipo, invitados_evento, invitados_sedes, portada, visible,
                                                                     subida_desde, subida_hasta, votacion_desde, votacion_hasta, sube_rrhh, votos_max, actualizado_por)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('isssssissssiis', $id, $titulo, $tipo, $invEvento, $invSedes, $portada, $visible,
                                  $periodos['subida_desde'], $periodos['subida_hasta'], $periodos['votacion_desde'], $periodos['votacion_hasta'],
                                  $subeRrhh, $votosMax, $noEmpleado);
                $stmt->execute();
                $stmt->close();
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            if ($nueva !== null) @unlink(mbRutaFoto($nueva));    // sin fila, el archivo sobra
            throw $e;
        }
        // La portada que se reemplazó o se quitó ya no la usa nadie.
        if ($anterior !== null && $anterior !== $portada) @unlink(mbRutaFoto($anterior));
        mbOlvidarAlbumes();
        mbResponder(true, 'Álbum guardado.', ['id' => $id]);
    } catch (Throwable $e) {
        mbFallo('No pudimos guardar el álbum.', $e);
    }
}


// ── adm_visible ──────────────────────────────────────────────────────────────
// Ocultar no borra nada: el álbum deja de salir en la pestaña y de recibir
// fotos. Al volver a mostrarlo se revisan los choques como al guardar.
if ($accion === 'adm_visible') {
    $id      = (int)($_POST['id'] ?? 0);
    $visible = (int)!empty($_POST['visible']);
    try {
        $a = mbAdmAlbum($conn, $id);
        if (!$a) mbResponder(false, 'Ese álbum ya no existe. Recarga la lista.');
        if ($visible) {
            $choque = mbAdmConflicto($conn, $id, (string)$a['tipo'], (string)$a['desde'], (string)$a['hasta']);
            if ($choque !== '') mbResponder(false, $choque);
        }
        $stmt = $conn->prepare('UPDATE messbook_albumes SET visible = ?, actualizado_por = ? WHERE id_evento = ?');
        $stmt->bind_param('isi', $visible, $noEmpleado, $id);
        $stmt->execute();
        $stmt->close();
        mbOlvidarAlbumes();
        mbResponder(true, $visible ? 'El álbum vuelve a verse.' : 'Álbum oculto. Sus fotos no se borraron.');
    } catch (Throwable $e) {
        mbFallo('No pudimos cambiar el álbum.', $e);
    }
}


// ── adm_borrar ───────────────────────────────────────────────────────────────
// Sólo un álbum sin fotos de empleados: con fotos, se oculta (la llave foránea
// de messbook_fotos tampoco dejaría). Las de invitados viven en oktoberMESS y
// no se tocan.
if ($accion === 'adm_borrar') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $album = mbAdmAlbum($conn, $id);
        if (!$album) mbResponder(false, 'Ese álbum ya no existe. Recarga la lista.');
        $n = mbAdmFotos($conn, $id);
        if ($n > 0) mbResponder(false, "Este álbum tiene $n " . ($n === 1 ? 'foto' : 'fotos') . ': no se puede borrar. Ocúltalo.');

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('DELETE FROM messbook_albumes WHERE id_evento = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare("DELETE FROM enc_eventos WHERE id_evento = ? AND tipo = 'album'");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
        $portada = mbPortadaPropia($album);
        if ($portada !== null) @unlink(mbRutaFoto($portada));
        mbOlvidarAlbumes();
        mbResponder(true, 'Álbum borrado.');
    } catch (Throwable $e) {
        mbFallo('No pudimos borrar el álbum.', $e);
    }
}

mbResponder(false, 'Acción no válida.');

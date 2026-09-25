<?php
/**
 * album_admin.php — Acciones del modal "Administrar álbumes" (modalAlbumes.php).
 *
 * Sólo se carga desde acciones_album.php, con la sesión ya revisada y $conn,
 * $noEmpleado y $accion listos; responde con mbResponder() como el resto del
 * endpoint. Cada acción termina la petición.
 *
 *   adm_listar  — todos los álbumes (también los ocultos) + opciones del form
 *   adm_guardar — crear o editar
 *   adm_visible — ocultar / mostrar
 *   adm_borrar  — borrar, sólo si no tiene fotos de empleados
 *
 * Un álbum = una fila de enc_eventos (tipo 'album', estatus 0: así nunca sale
 * como actividad pendiente; ver acciones_eventos.php) + una de
 * messbook_albumes. Se escriben juntas, en una transacción.
 *
 * Reglas que cuida el guardado, para que en cada momento haya a lo más un
 * álbum recibiendo fotos (includes/album.php):
 *   - dos álbumes de EVENTO visibles no caen el mismo día;
 *   - dos GENERALES visibles no se enciman en su periodo.
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

/**
 * ¿Choca con otro álbum visible? Devuelve el aviso para la pantalla o ''.
 * Evento: otro evento el mismo día. General: otro general con periodo encimado.
 */
function mbAdmConflicto(mysqli $conn, int $id, string $tipo, string $desde, string $hasta): string {
    if ($tipo === 'evento') {
        $stmt = $conn->prepare("SELECT a.titulo FROM messbook_albumes a JOIN enc_eventos e ON e.id_evento = a.id_evento
                                 WHERE a.visible = 1 AND a.tipo = 'evento' AND a.id_evento <> ?
                                   AND DATE(e.fecha_inicio) = ? LIMIT 1");
        $stmt->bind_param('is', $id, $desde);
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
        ? 'Ya hay un álbum de evento ese día: «' . $fila[0] . '». Cambia la fecha u oculta el otro.'
        : 'El periodo se encima con el álbum general «' . $fila[0] . '». Ajusta las fechas u oculta el otro.';
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
                'visible'   => (bool)$e['visible'],
                'fotos'     => $fotos[$id] ?? 0,
                'activo'    => $id === $activo,
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

    if ($titulo === '')                            mbResponder(false, 'Escribe el nombre del álbum.');
    if (mb_strlen($titulo, 'UTF-8') > 80)          mbResponder(false, 'El nombre es muy largo: máximo 80 caracteres.');
    if (!in_array($tipo, ['evento', 'general'], true)) mbResponder(false, 'Elige si es un evento o un álbum general.');

    if ($tipo === 'evento') {
        $desde = mbAdmFecha((string)($_POST['dia'] ?? ''));
        if (!$desde) mbResponder(false, 'Elige el día del evento.');
        $hasta = $desde;
    } else {
        $desde = mbAdmFecha((string)($_POST['desde'] ?? ''));
        $hasta = mbAdmFecha((string)($_POST['hasta'] ?? ''));
        if (!$desde || !$hasta) mbResponder(false, 'Elige el inicio y el fin del periodo.');
        if ($hasta < $desde)    mbResponder(false, 'El fin del periodo no puede ser antes del inicio.');
        $dias = (new DateTime($desde))->diff(new DateTime($hasta))->days + 1;
        if ($dias > MB_MAX_DIAS) mbResponder(false, 'El periodo no puede pasar de ' . MB_MAX_DIAS . ' días.');
    }

    // Invitados de oktoberMESS: opcional. Sedes vacías = todas.
    $invEvento = trim((string)($_POST['invitados'] ?? ''));
    $invSedes  = null;
    if ($invEvento !== '') {
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $invEvento)) mbResponder(false, 'El evento de invitados sólo lleva letras, números, - y _.');
        $sedes = array_values(array_unique(array_filter(array_map('trim', (array)($_POST['sedes'] ?? [])), 'strlen')));
        foreach ($sedes as $s) {
            if (!preg_match('/^[A-Z0-9_]{1,20}$/', $s)) mbResponder(false, 'Sede no válida: ' . $s);
        }
        $invSedes = $sedes ? implode(',', $sedes) : null;
    } else {
        $invEvento = null;
    }

    try {
        if ($id > 0 && !mbAdmAlbum($conn, $id)) mbResponder(false, 'Ese álbum ya no existe. Recarga la lista.');
        if ($visible) {
            $choque = mbAdmConflicto($conn, $id, $tipo, $desde, $hasta);
            if ($choque !== '') mbResponder(false, $choque);
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
                $stmt = $conn->prepare('UPDATE messbook_albumes SET titulo = ?, tipo = ?, invitados_evento = ?, invitados_sedes = ?, visible = ?, actualizado_por = ? WHERE id_evento = ?');
                $stmt->bind_param('ssssisi', $titulo, $tipo, $invEvento, $invSedes, $visible, $noEmpleado, $id);
                $stmt->execute();
                $stmt->close();
            } else {
                $desc = 'Album de la pestana Fotos de messbook. Se administra desde ahi (includes/album_admin.php): no editar ni asignar empleados aqui.';
                $stmt = $conn->prepare("INSERT INTO enc_eventos (nombre, descripcion, tipo, fecha_inicio, fecha_fin, estatus) VALUES (?, ?, 'album', ?, ?, 0)");
                $stmt->bind_param('ssss', $nombre, $desc, $inicio, $fin);
                $stmt->execute();
                $id = (int)$conn->insert_id;
                $stmt->close();
                $stmt = $conn->prepare('INSERT INTO messbook_albumes (id_evento, titulo, tipo, invitados_evento, invitados_sedes, visible, actualizado_por) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('issssis', $id, $titulo, $tipo, $invEvento, $invSedes, $visible, $noEmpleado);
                $stmt->execute();
                $stmt->close();
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
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
        if (!mbAdmAlbum($conn, $id)) mbResponder(false, 'Ese álbum ya no existe. Recarga la lista.');
        $stmt = $conn->prepare('SELECT COUNT(*) FROM messbook_fotos WHERE id_evento = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $n = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
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
        mbOlvidarAlbumes();
        mbResponder(true, 'Álbum borrado.');
    } catch (Throwable $e) {
        mbFallo('No pudimos borrar el álbum.', $e);
    }
}

mbResponder(false, 'Acción no válida.');

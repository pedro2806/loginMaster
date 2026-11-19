<?php
/**
 * tema.php — Versiones de evento de los logos de messbook.
 *
 * BI (departamento 27) cambia los logos sin tocar código desde modalTemas.php,
 * que abre el logo de la columna de inicio.php; todo se guarda vía
 * acciones_temas.php. Reemplaza a includes/temporada.php, que tenía el corte de
 * Día de Muertos escrito aquí.
 *
 * Una VERSIÓN (mess_rrhh.messbook_temas) tiene nombre, inicio y fin (hora de la
 * Ciudad de México) y sus logos (messbook_tema_logos), uno por LUGAR:
 *   portal       el logo sobre la tarjeta del usuario (inicio.php)
 *   login        el del login (index.php), sólo en computadora y tableta: en
 *                celular el login es blanco y sale siempre messbook_logo3.png
 *   favicon      el ícono de la pestaña del navegador (inicio.php e index.php)
 *   pie          el del pie de la columna del perfil
 *   sis:<id>     el de una tarjeta de Sistemas, con versión clara y oscura
 * La versión PREDETERMINADA (una sola fila, sin fechas) es la de todos los días.
 *
 * Qué logo sale en cada lugar: el de la versión activa en este momento; si no
 * lo tiene, el de la predeterminada; si tampoco, el de código (MB_TEMA_DEFECTO),
 * que es lo que se veía antes de que existieran las tablas. Si la BD falla, se
 * queda lo de código: el portal y el login nunca se caen por esto.
 *
 * Los colores NO se cambian aquí: son institucionales (decisión de Sebastian,
 * 2026-11-19).
 */

const MB_TEMA_ZONA      = 'America/Mexico_City';
const MB_TEMA_DEPTOS    = [27];               // BI: quien administra los logos
const MB_TEMA_CARPETA   = 'uploads/temas';    // relativa a loginMaster; /uploads está en el .gitignore

// Lugares fijos, en el orden en que salen en el modal.
const MB_TEMA_LUGARES = [
    'portal'  => 'Logo del portal',
    'login'   => 'Logo del login',
    'pie'     => 'Logo del pie',
    'favicon' => 'Favicon (ícono de la pestaña)',
];

// Tarjetas de Sistemas (#tabSistemas de inicio.php): id del div → nombre,
// ícono y texto de siempre. Sin logo, la tarjeta se ve con ese ícono y texto.
const MB_TEMA_SISTEMAS = [
    'divVacaciones'       => ['Vacaciones',        'far fa-check-square',  'Vacaciones'],
    'divControlVehicular' => ['Control Vehicular', 'fas fa-car',           'Ctrl Vehicular'],
    'divHorasExtra'       => ['Horas Extra',       'fas fa-clock',         'Hrs Extra'],
    'divIncidencias'      => ['Incidencias',       'fas fa-list',          'Incidencias'],
    'divPlaneacion'       => ['Planeación',        'fas fa-calendar',      'Planeación'],
    'divCapacitacion'     => ['Capacitación',      'fas fa-list',          'Capacitación'],
    'divActivos'          => ['Activos',           'fas fa-box',           'Activos'],
    'divEntradasEq'       => ['Entradas Eq',       'fas fa-calendar',      'Entradas Eq'],
    'divPracticantes'     => ['Practicantes',      'fas fa-user-clock',    'Practicantes'],
    'divTicketsBI'        => ['Tickets BI',        'fas fa-ticket-alt',    'Tickets BI'],
    'divGestionPersonal'  => ['Gestión Personal',  'fas fa-id-card-alt',   'Gestión Personal'],
    'divControlSGC'       => ['Control SGC',       'fas fa-check',         'Control SGC'],
    'divCotizadorIA'      => ['Cotizador IA',      'fas fa-robot',         'Cotizador IA'],
    'divNest'             => ['NEST',              'fas fa-briefcase',     'NEST'],
    'divTrace'            => ['Trace',             'fas fa-map-marked-alt', 'Trace'],
];

// Lo que se veía antes de las tablas: [claro, oscuro]. Rutas relativas a
// loginMaster (las de los sistemas hermanos viven en su propio repo). Las
// tarjetas que no están aquí no tienen imagen y van con su ícono.
const MB_TEMA_DEFECTO = [
    'portal'                  => ['img/messbook_agentes_ia.png', null],
    'login'                   => ['img/messbook_logo3.png', null],
    'pie'                     => ['img/B1_blanco.png', null],
    'favicon'                 => ['img/fav.png', null],
    'sis:divControlVehicular' => ['../ControlVehicular/img/QRide_grande.png', null],
    'sis:divTicketsBI'        => ['../Tickets/img/Tickets%20Messbook.png', null],
    'sis:divCotizadorIA'      => ['img/messias/messias_claro.png', 'img/messias/messias_oscuro.png'],
    'sis:divNest'             => ['../SIVAC/img/NEST/nest-logo.png', null],
];

/** ¿Existe ese lugar? 'portal', 'login', 'pie', 'favicon' o 'sis:<id de tarjeta>'. */
function mbTemaLugarValido(string $lugar): bool {
    if (isset(MB_TEMA_LUGARES[$lugar])) return true;
    return strncmp($lugar, 'sis:', 4) === 0 && isset(MB_TEMA_SISTEMAS[substr($lugar, 4)]);
}

/** Todos los lugares, en el orden del modal. */
function mbTemaTodosLugares(): array {
    $l = array_keys(MB_TEMA_LUGARES);
    foreach (array_keys(MB_TEMA_SISTEMAS) as $id) $l[] = 'sis:' . $id;
    return $l;
}

/**
 * ¿La ruta guardada es una de las nuestras? Sólo imágenes del repo (img/) o
 * subidas por el modal (uploads/temas/). Una fila capturada a mano con otra
 * cosa no se enseña: se queda lo de código.
 */
function mbTemaRutaValida(?string $ruta): bool {
    if ($ruta === null || $ruta === '' || strpos($ruta, '..') !== false) return false;
    return (bool)preg_match('~^(img/[A-Za-z0-9_./%-]+\.(png|jpe?g|webp|ico)|' . MB_TEMA_CARPETA . '/[A-Za-z0-9_-]+\.png)$~', $ruta);
}

/** ¿La subió el modal? Sólo ésas se borran del disco; nunca las de img/. */
function mbTemaEsSubida(?string $ruta): bool {
    return $ruta !== null && mbTemaRutaValida($ruta) && strncmp($ruta, MB_TEMA_CARPETA . '/', strlen(MB_TEMA_CARPETA) + 1) === 0;
}

/** Ahora, en la hora de la Ciudad de México, como la guarda la tabla. */
function mbTemaAhora(): string {
    return (new DateTimeImmutable('now', new DateTimeZone(MB_TEMA_ZONA)))->format('Y-m-d H:i:s');
}

/**
 * Los logos que tocan ahora, por lugar: ['archivo', 'oscuro', 'origen'], con
 * origen 'version', 'predeterminada' o 'codigo'. Una consulta por petición.
 *
 * La hora se pasa desde PHP (zona de CDMX) y no con NOW() de MySQL: la del
 * servidor de BD puede estar en otra zona.
 */
function mbTemaLogos(?mysqli $conn): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $logos = [];
    foreach (MB_TEMA_DEFECTO as $lugar => [$claro, $oscuro]) {
        $logos[$lugar] = ['archivo' => $claro, 'oscuro' => $oscuro, 'origen' => 'codigo'];
    }
    if (!$conn) return $cache = $logos;

    try {
        $ahora = mbTemaAhora();
        // La predeterminada primero y las versiones después, de la más vieja a
        // la más nueva: cada fila pisa a la anterior. El modal no deja que dos
        // versiones se encimen; si alguien lo hiciera a mano, gana la más nueva.
        $stmt = $conn->prepare("SELECT t.predeterminada, l.lugar, l.archivo, l.archivo_oscuro
                                  FROM mess_rrhh.messbook_temas t
                                  JOIN mess_rrhh.messbook_tema_logos l ON l.id_tema = t.id_tema
                                 WHERE t.visible = 1
                                   AND (t.predeterminada = 1 OR (t.desde <= ? AND t.hasta > ?))
                                 ORDER BY t.predeterminada DESC, t.desde, t.id_tema");
        $stmt->bind_param('ss', $ahora, $ahora);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $lugar = (string)$r['lugar'];
            if (!mbTemaLugarValido($lugar) || !mbTemaRutaValida($r['archivo'])) continue;
            $logos[$lugar] = [
                'archivo' => (string)$r['archivo'],
                'oscuro'  => mbTemaRutaValida($r['archivo_oscuro']) ? (string)$r['archivo_oscuro'] : null,
                'origen'  => (int)$r['predeterminada'] === 1 ? 'predeterminada' : 'version',
            ];
        }
        $stmt->close();
    } catch (Throwable $e) {
        // Sin las tablas (SQL aún sin correr) o con la BD caída: lo de código.
        error_log('messbook tema: ' . $e->getMessage());
    }
    return $cache = $logos;
}

/** La ruta de un lugar fijo (portal, login, pie, favicon), lista para un src. */
function mbTemaSrc(array $logos, string $lugar): string {
    return htmlspecialchars((string)($logos[$lugar]['archivo'] ?? MB_TEMA_DEFECTO[$lugar][0]), ENT_QUOTES, 'UTF-8');
}

/**
 * El contenido del botón de una tarjeta de Sistemas: su logo a 48 px de alto
 * (como QRide y NEST) o, sin logo, el ícono y el texto de siempre. Con versión
 * oscura salen las dos y el CSS enseña la del tema (.logo-tema-claro /
 * .logo-tema-oscuro, en loginMaster.css).
 */
function mbTemaSistemaHtml(array $logos, string $id): string {
    [$nombre, $icono, $texto] = MB_TEMA_SISTEMAS[$id];
    $logo = $logos['sis:' . $id] ?? null;
    if (!$logo) {
        return '<i class="' . $icono . ' fa-lg d-block mb-2"></i> ' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
    $alt    = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $estilo = 'style="height:48px;width:auto;max-width:100%"';
    $img    = function (string $src, string $clase) use ($alt, $estilo) {
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" class="' . $clase . ' mx-auto mb-0" ' . $estilo . '>';
    };
    if ($logo['oscuro']) {
        return $img($logo['archivo'], 'logo-tema-claro') . $img($logo['oscuro'], 'logo-tema-oscuro');
    }
    return $img($logo['archivo'], 'd-block');
}

/**
 * ¿Puede administrar los logos? BI (MB_TEMA_DEPTOS), con cuenta activa. Mismas
 * reservas que el resto del portal: la credencial es la cookie noEmpleadoL, que
 * no va firmada.
 */
function mbTemaPuedeAdministrar(mysqli $conn, string $noEmpleado): bool {
    if (!preg_match('/^\d{1,11}$/', $noEmpleado)) return false;
    $deptos = implode(',', array_map('intval', MB_TEMA_DEPTOS));
    $stmt = $conn->prepare("SELECT 1 FROM mess_rrhh.usuarios WHERE noEmpleado = ? AND estatus = 1 AND departamento IN ($deptos) LIMIT 1");
    $stmt->bind_param('s', $noEmpleado);
    $stmt->execute();
    $si = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $si;
}

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
 * COLORES (desde el 2026-11-27; el 2026-11-19 se habían dejado fuera): cada
 * versión puede cambiar los colores del tema (MB_TEMA_COLORES, tabla
 * messbook_tema_colores), con la misma cadena: versión activa → predeterminada
 * → código. Los .css NO se reescriben: los colores son variables CSS
 * (loginMaster.css) y mbTemaColoresCss() imprime, después de la hoja, un
 * <style> sólo con las que cambian. Sin la tabla, o sin cambios, no imprime
 * nada y todo se ve como siempre.
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


// ═════════════════════════════════════════════════════════════════════════════
// Colores
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Colores que se pueden cambiar, en el orden del modal: clave → [nombre,
 * variable CSS, claro de código, oscuro de código, ayuda]. Los valores de
 * código son los de css/loginMaster.css (:root y body.theme-dark); si se
 * cambian allá, hay que cambiarlos aquí.
 *
 * Oscuro de código null = la marca: la columna es azul en los dos temas, así
 * que lleva un solo color. Éxito, advertencia, peligro e info no están: tienen
 * significado (el rojo es error). El fondo de la página (--bg) tampoco: en el
 * portal no se ve, lo tapa la columna de la marca.
 */
const MB_TEMA_COLORES = [
    'marca1'        => ['Azul de la columna (arriba)', '--marca-1',    '#074480', null,
                        'La columna del perfil, la barra de pestañas y el pie, y el fondo del login en computadora. Lleva texto blanco encima. Igual en los dos temas.'],
    'marca2'        => ['Azul de la columna (abajo)',  '--marca-2',    '#0a1c61', null,
                        'El final del degradado de la columna. Igual en los dos temas.'],
    'acento'        => ['Acento',                      '--accent',     '#1c83f1', '#4dabff',
                        'Botones, links, pestaña activa y detalles. Lleva texto blanco encima. Sin color oscuro, se aclara solo.'],
    'tarjeta'       => ['Fondo de las tarjetas',       '--card-bg',    '#ffffff', '#121a2b',
                        'El fondo de las tarjetas, listas y ventanas.'],
    'tarjeta_suave' => ['Encabezados de tarjeta',      '--card-soft',  '#f8f9fa', '#1a2438',
                        'Encabezados de tarjeta y fondos suaves.'],
    'texto'         => ['Texto',                       '--text',       '#1f2937', '#e6edf7',
                        'El texto normal.'],
    'texto_sec'     => ['Texto secundario',            '--text-muted', '#6c757d', '#94a3b8',
                        'Fechas, notas y textos grises.'],
    'borde'         => ['Bordes',                      '--border',     '#0b499b', '#243049',
                        'Bordes de tarjetas, líneas y separadores.'],
];

/** ¿Es un color '#rrggbb'? Lo único que se guarda y lo único que se imprime en el CSS. */
function mbTemaColorValido($c): bool {
    return is_string($c) && (bool)preg_match('/^#[0-9a-f]{6}$/i', $c);
}

/** Mezcla dos colores '#rrggbb': $peso es cuánto de $b (0 = sólo $a, 1 = sólo $b). */
function mbTemaMezclar(string $a, string $b, float $peso): string {
    $ca = sscanf($a, '#%02x%02x%02x');
    $cb = sscanf($b, '#%02x%02x%02x');
    $r = '#';
    for ($i = 0; $i < 3; $i++) {
        $r .= sprintf('%02x', (int)round($ca[$i] + ($cb[$i] - $ca[$i]) * $peso));
    }
    return $r;
}

/** El oscuro del acento cuando la versión no lo trae: el claro, 30 % hacia blanco (como #1c83f1 → #4dabff). */
function mbTemaAcentoOscuro(string $claro): string {
    return mbTemaMezclar($claro, '#ffffff', 0.30);
}

/**
 * Los colores que tocan ahora: clave → ['claro', 'oscuro', 'origen'], con
 * origen 'version', 'predeterminada' o 'codigo'. Una consulta por petición.
 *
 * Cuando una versión pone un color sin oscuro: el acento se aclara solo (que
 * el color del evento también se vea en el tema oscuro); los demás se quedan
 * con el oscuro de siempre (el de la predeterminada o el de código).
 */
function mbTemaColores(?mysqli $conn): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $colores = [];
    foreach (MB_TEMA_COLORES as $clave => [, , $claro, $oscuro]) {
        $colores[$clave] = ['claro' => $claro, 'oscuro' => $oscuro, 'origen' => 'codigo'];
    }
    if (!$conn) return $cache = $colores;

    try {
        $ahora = mbTemaAhora();
        // Igual que mbTemaLogos(): la predeterminada primero y la versión
        // activa después, para que ésta pise a aquélla.
        $stmt = $conn->prepare("SELECT t.predeterminada, c.clave, c.claro, c.oscuro
                                  FROM mess_rrhh.messbook_temas t
                                  JOIN mess_rrhh.messbook_tema_colores c ON c.id_tema = t.id_tema
                                 WHERE t.visible = 1
                                   AND (t.predeterminada = 1 OR (t.desde <= ? AND t.hasta > ?))
                                 ORDER BY t.predeterminada DESC, t.desde, t.id_tema");
        $stmt->bind_param('ss', $ahora, $ahora);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $clave = (string)$r['clave'];
            if (!isset(MB_TEMA_COLORES[$clave]) || !mbTemaColorValido($r['claro'])) continue;
            $c = &$colores[$clave];
            $c['claro'] = strtolower($r['claro']);
            if (MB_TEMA_COLORES[$clave][3] !== null) {          // la marca no tiene oscuro
                if (mbTemaColorValido($r['oscuro']))  $c['oscuro'] = strtolower($r['oscuro']);
                elseif ($clave === 'acento')          $c['oscuro'] = mbTemaAcentoOscuro($c['claro']);
            }
            $c['origen'] = (int)$r['predeterminada'] === 1 ? 'predeterminada' : 'version';
            unset($c);
        }
        $stmt->close();
    } catch (Throwable $e) {
        // Sin la tabla (SQL aún sin correr) o con la BD caída: los de código.
        error_log('messbook tema colores: ' . $e->getMessage());
    }
    return $cache = $colores;
}

/**
 * El <style> con los colores que no son los de código, para imprimir justo
 * después de la hoja de estilos ('portal': inicio.php, después de
 * loginMaster.css; 'login': index.php, después de login.css). Cadena vacía si
 * nada cambia: así, sin versiones con colores, la página es idéntica a la de
 * antes. Sólo lleva colores validados como '#rrggbb'.
 */
function mbTemaColoresCss(array $colores, string $donde = 'portal'): string {
    $c = function (string $clave, string $cual) use ($colores): string {
        $v = $colores[$clave][$cual] ?? null;
        $cod = MB_TEMA_COLORES[$clave][$cual === 'claro' ? 2 : 3];
        return mbTemaColorValido($v) ? strtolower($v) : (string)$cod;
    };
    $cambio = function (string $clave, string $cual) use ($c): bool {
        $cod = MB_TEMA_COLORES[$clave][$cual === 'claro' ? 2 : 3];
        return $cod !== null && $c($clave, $cual) !== $cod;
    };
    $marca = $cambio('marca1', 'claro') || $cambio('marca2', 'claro');

    // Login: sólo el fondo en computadora y tableta (en celular es blanco).
    if ($donde === 'login') {
        if (!$marca) return '';
        $m1 = $c('marca1', 'claro');
        $m2 = $c('marca2', 'claro');
        return '<style id="tema-colores">@media (min-width: 577px){body{background:linear-gradient(135deg,'
             . mbTemaMezclar($m2, '#000000', 0.35) . ' 0%,' . $m1 . ' 50%,' . mbTemaMezclar($m1, '#ffffff', 0.25)
             . " 100%) !important;}}</style>\n";
    }

    $claro = [];
    $oscuro = [];
    foreach (MB_TEMA_COLORES as $clave => [, $var]) {
        if ($cambio($clave, 'claro'))  $claro[$var]  = $c($clave, 'claro');
        if ($cambio($clave, 'oscuro')) $oscuro[$var] = $c($clave, 'oscuro');
    }
    // Tonos que salen de la marca (botón de KPIs, encabezado de avisos).
    if ($marca) {
        $claro['--marca-3'] = mbTemaMezclar($c('marca1', 'claro'), $c('marca2', 'claro'), 0.25);
        $claro['--marca-4'] = mbTemaMezclar($c('marca1', 'claro'), $c('marca2', 'claro'), 0.50);
    }
    // El acento suave sale del acento sobre el fondo de las tarjetas.
    $acento = $cambio('acento', 'claro') || $cambio('acento', 'oscuro');
    if ($cambio('acento', 'claro') || $cambio('tarjeta', 'claro')) {
        $claro['--accent-soft'] = mbTemaMezclar($c('tarjeta', 'claro'), $c('acento', 'claro'), 0.09);
    }
    if ($cambio('acento', 'oscuro') || $cambio('tarjeta', 'oscuro')) {
        $oscuro['--accent-soft'] = mbTemaMezclar($c('tarjeta', 'oscuro'), $c('acento', 'oscuro'), 0.12);
    }
    if ($acento) {
        $claro['--accent-hover']  = mbTemaMezclar($c('acento', 'claro'), '#000000', 0.15);
        $oscuro['--accent-hover'] = mbTemaMezclar($c('acento', 'oscuro'), '#000000', 0.15);
    }
    if (!$claro && !$oscuro) return '';

    $bloque = function (string $sel, array $vars): string {
        if (!$vars) return '';
        $s = $sel . '{';
        foreach ($vars as $k => $v) $s .= $k . ':' . $v . ';';
        return $s . "}\n";
    };
    $css = $bloque(':root', $claro) . $bloque('body.theme-dark', $oscuro);

    // Con otro acento, lo "primary" de sb-admin-2 (#4e73df: botones, links,
    // badges, paginación) toma el acento. Sin cambio de acento no se toca.
    if ($acento) {
        $css .= "a{color:var(--accent)}a:hover{color:var(--accent-hover)}\n"
              . ".btn-primary,.btn-primary.disabled,.btn-primary:disabled{color:#fff;background-color:var(--accent);border-color:var(--accent)}\n"
              . ".btn-primary:hover,.btn-primary:focus,.btn-primary.focus,.btn-primary:not(:disabled):not(.disabled):active,.btn-primary.dropdown-toggle{color:#fff;background-color:var(--accent-hover);border-color:var(--accent-hover)}\n"
              . ".btn-outline-primary,.btn-outline-primary.disabled,.btn-outline-primary:disabled{color:var(--accent);border-color:var(--accent)}\n"
              . ".btn-outline-primary:hover,.btn-outline-primary:not(:disabled):not(.disabled):active,.btn-outline-primary.dropdown-toggle{color:#fff;background-color:var(--accent);border-color:var(--accent)}\n"
              . ".bg-primary{background-color:var(--accent)!important}.text-primary{color:var(--accent)!important}\n"
              . ".border-left-primary{border-left-color:var(--accent)!important}.badge-primary{color:#fff;background-color:var(--accent)}\n"
              . ".page-item.active .page-link,.nav-pills .nav-link.active,.dropdown-item.active,.dropdown-item:active{color:#fff;background-color:var(--accent);border-color:var(--accent)}\n"
              . ".custom-control-input:checked~.custom-control-label::before{border-color:var(--accent);background-color:var(--accent)}\n";
    }
    return "<style id=\"tema-colores\">\n" . $css . "</style>\n";
}

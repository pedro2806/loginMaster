/**
 * temas.js — "Logos de messbook" (modalTemas.php), sólo BI.
 *
 * Versiones de evento de los logos: la lista a la izquierda (la predeterminada
 * primero) y, a la derecha, la versión elegida con una fila por lugar (portal,
 * login, pie, favicon y las tarjetas de Sistemas). Cada fila enseña lo que se
 * verá: el logo nuevo sin guardar, el de esta versión o, con la marca "Como
 * siempre", el que sale si la versión no lo cambia (el de la predeterminada o
 * el de código). "Guardar" manda todo junto a acciones_temas.php.
 *
 * COLORES (sección "Colores"): una fila por color del catálogo
 * (MB_TEMA_COLORES). Al dar clic en la muestra se abre una rueda (iro.js; sin
 * la librería, el selector del navegador) con campos HEX y RGB. Arriba, una
 * vista previa clara y oscura y los avisos de poco contraste (avisan, no
 * bloquean). Se guarda el juego completo de colores de la versión.
 *
 * El modal lo abre el logo de la columna de inicio.php. Los cambios se ven al
 * recargar la página: los logos y colores los pinta el servidor
 * (includes/tema.php).
 */
(function () {
    'use strict';

    var URL_TEM = 'acciones_temas.php';
    var MAX     = 8 * 1024 * 1024;     // el servidor los reduce; esto sólo evita esperas largas
    var CAT     = JSON.parse(document.getElementById('temaCatalogo').textContent);

    var estado = {
        versiones: [], defecto: {},    // lo último de `listar`
        actual: null,                  // la versión elegida (null = nueva)
        pend: {},                      // lugar → { claro: File, oscuro: File }
        quitar: {}, quitarOscuro: {},  // lugar → true
        urls: [],                      // objectURL de las vistas previas, para soltarlos
        sucio: false,
        hayColores: true,              // false: falta el SQL de colores
        col: {},                       // colores de esta versión: clave → { claro, oscuro|null }
        editor: null,                  // { clave, cual } del color abierto en la rueda
        picker: null                   // la rueda de iro.js abierta
    };

    // ── Utilidades ───────────────────────────────────────────────────────
    function $t(id) { return document.getElementById(id); }

    function pedir(accion, datos) {
        var fd = datos instanceof FormData ? datos : new FormData();
        if (!(datos instanceof FormData)) {
            Object.keys(datos || {}).forEach(function (k) { fd.append(k, datos[k]); });
        }
        fd.append('accion', accion);
        return fetch(URL_TEM, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function esc(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function error(msg) { Swal.fire({ icon: 'error', title: msg || 'No se pudo contactar al servidor.' }); }

    /** "2026-12-01T08:00" → "01/12/2026 08:00". */
    function fecha(f) {
        var m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}:\d{2})/.exec(f || '');
        return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] : '';
    }

    function esSistema(lugar) { return lugar.indexOf('sis:') === 0; }

    function predeterminada() {
        for (var i = 0; i < estado.versiones.length; i++) {
            if (estado.versiones[i].predeterminada) return estado.versiones[i];
        }
        return null;
    }

    /** Lo que sale en un lugar si esta versión no lo cambia: la predeterminada o código. */
    function comoSiempre(lugar) {
        var pred = predeterminada();
        var esPred = estado.actual && estado.actual.predeterminada;
        if (!esPred && pred && pred.logos[lugar]) return pred.logos[lugar];
        return estado.defecto[lugar] || null;
    }

    function urlDe(file) {
        var u = URL.createObjectURL(file);
        estado.urls.push(u);
        return u;
    }

    function soltarUrls() {
        estado.urls.forEach(function (u) { URL.revokeObjectURL(u); });
        estado.urls = [];
    }

    // ── Lista de versiones ───────────────────────────────────────────────
    var ETIQUETA = {
        predeterminada: ['Todos los días', 'badge-primary'],
        activa:         ['Activa ahora',   'badge-success'],
        programada:     ['Programada',     'badge-info'],
        terminada:      ['Terminada',      'badge-secondary']
    };

    function pintarLista() {
        var idActual = estado.actual ? estado.actual.id : 0;
        $t('tem_lista').innerHTML = estado.versiones.map(function (v) {
            var et = ETIQUETA[v.estado] || ['', 'badge-light'];
            var fechas = v.predeterminada ? 'Cuando no hay ninguna versión activa'
                                          : fecha(v.desde) + ' → ' + fecha(v.hasta);
            return '<button type="button" class="list-group-item list-group-item-action tema-version'
                 + (v.id === idActual ? ' active' : '') + '" data-id="' + v.id + '">'
                 + '<div class="d-flex justify-content-between align-items-center">'
                 + '<strong class="text-truncate">' + esc(v.nombre) + '</strong>'
                 + '<span class="badge ' + et[1] + ' ml-2">' + et[0] + '</span></div>'
                 + '<small class="tema-version__fechas">' + esc(fechas) + '</small></button>';
        }).join('') || '<div class="text-center text-muted py-3 small">Sin versiones.</div>';
    }

    function cargar(idElegir) {
        return pedir('listar').then(function (r) {
            if (!r.success) { $t('tem_lista').innerHTML = '<div class="text-danger small p-2">' + esc(r.message) + '</div>'; return; }
            estado.versiones  = r.versiones || [];
            estado.defecto    = r.defecto || {};
            estado.hayColores = r.hayColores !== false;
            var elegir = null;
            estado.versiones.forEach(function (v) { if (v.id === idElegir) elegir = v; });
            elegirVersion(elegir || predeterminada());
        }).catch(function () { error(); });
    }

    // ── La versión elegida ───────────────────────────────────────────────
    function elegirVersion(v) {
        soltarUrls();
        estado.actual = v;                 // null = nueva
        estado.pend = {}; estado.quitar = {}; estado.quitarOscuro = {};
        estado.sucio = false;
        estado.col = {};
        Object.keys((v && v.colores) || {}).forEach(function (k) {
            estado.col[k] = { claro: v.colores[k].claro, oscuro: v.colores[k].oscuro || null };
        });
        estado.editor = null; estado.picker = null;

        var pred = !!(v && v.predeterminada);
        $t('tem_id').value     = v ? v.id : '';
        $t('tem_nombre').value = v ? v.nombre : '';
        $t('tem_desde').value  = v ? v.desde : '';
        $t('tem_hasta').value  = v ? v.hasta : '';
        $t('tem_tituloForm').textContent = v ? v.nombre : 'Nueva versión';
        $t('tem_datos').hidden    = pred;
        $t('tem_notaPred').hidden = !pred;
        $t('tem_borrar').hidden   = !v || pred;

        pintarLista();
        pintarFilas();
        pintarColores();
    }

    function pintarFilas() {
        $t('tem_fijos').innerHTML = CAT.fijos.map(function (l) { return filaHtml(l, false); }).join('');
        $t('tem_sis').innerHTML   = CAT.sistemas.map(function (l) { return filaHtml(l, true); }).join('');
    }

    /** El logo de esta versión en un lugar (null si no lo cambia o se va a quitar). */
    function propio(lugar) {
        if (estado.quitar[lugar] || !estado.actual) return null;
        return estado.actual.logos[lugar] || null;
    }

    /**
     * Qué se verá en un lugar, claro y oscuro: { src, marca } con marca 'nuevo',
     * 'version' o 'siempre'. src null = sin imagen (la tarjeta va con su ícono).
     */
    function vista(lugar) {
        var p = estado.pend[lugar] || {}, mio = propio(lugar), siempre = comoSiempre(lugar);
        var claro, oscuro;
        if (p.claro)      claro = { src: urlDe(p.claro), marca: 'nuevo' };
        else if (mio)     claro = { src: mio.archivo, marca: 'version' };
        else              claro = { src: siempre ? siempre.archivo : null, marca: 'siempre' };

        var oscMio = mio && !estado.quitarOscuro[lugar] ? mio.oscuro : null;
        if (p.oscuro)                      oscuro = { src: urlDe(p.oscuro), marca: 'nuevo' };
        else if (oscMio)                   oscuro = { src: oscMio, marca: 'version' };
        else if (claro.marca !== 'siempre') oscuro = { src: claro.src, marca: claro.marca, sinOscuro: true };
        else oscuro = { src: siempre ? (siempre.oscuro || siempre.archivo) : null, marca: 'siempre' };
        return { claro: claro, oscuro: oscuro, tieneAlgo: !!(p.claro || p.oscuro || mio) };
    }

    var MARCA = {
        nuevo:   '<span class="badge badge-warning">Nuevo, sin guardar</span>',
        version: '<span class="badge badge-success">De esta versión</span>',
        siempre: '<span class="badge badge-light">Como siempre</span>'
    };

    function previaHtml(v, fondo, l) {
        var dentro = v.src
            ? '<img src="' + esc(v.src) + '" alt="">'
            : '<span class="tema-previa__icono"><i class="' + esc(l.icono) + '"></i> ' + esc(l.texto) + '</span>';
        // El login de código (messbook_logo3.png) es azul: en la página lo
        // blanquea un filtro (login.css), y aquí también.
        var codigo = estado.defecto[l.lugar];
        var blanquear = l.lugar === 'login' && codigo && v.src === codigo.archivo;
        return '<div class="tema-previa tema-previa--' + fondo + (v.marca === 'siempre' ? ' tema-previa--siempre' : '')
             + (blanquear ? ' tema-previa--blanquear' : '')
             + (l.lugar === 'favicon' ? ' tema-previa--favicon' : '') + '">' + dentro + '</div>';
    }

    function botonCambiar(campo, texto) {
        return '<label class="btn btn-sm btn-outline-primary mb-0">' + texto
             + '<input type="file" accept="image/png,image/jpeg,image/webp" hidden data-campo="' + campo + '"></label>';
    }

    function filaHtml(l, sistema) {
        var v = vista(l.lugar);
        var ayuda = CAT.ayuda[l.lugar] || '';
        var html = '<div class="tema-fila" data-lugar="' + esc(l.lugar) + '">'
                 + '<div class="tema-fila__info"><strong>' + esc(l.nombre) + '</strong> ' + MARCA[v.claro.marca]
                 + (ayuda ? '<small class="text-muted d-block">' + esc(ayuda) + '</small>' : '') + '</div>';
        if (!sistema) {
            html += '<div class="tema-fila__previas">' + previaHtml(v.claro, 'azul', l) + '</div>'
                  + '<div class="tema-fila__acciones">' + botonCambiar('claro', 'Cambiar');
        } else {
            html += '<div class="tema-fila__previas">'
                  + '<div class="text-center">' + previaHtml(v.claro, 'claro', l) + '<small class="text-muted">Claro</small></div>'
                  + '<div class="text-center">' + previaHtml(v.oscuro, 'oscuro', l)
                  + '<small class="text-muted">' + (v.oscuro.sinOscuro ? 'Oscuro (usa el claro)' : 'Oscuro') + '</small></div>'
                  + '</div>'
                  + '<div class="tema-fila__acciones">' + botonCambiar('claro', 'Claro') + botonCambiar('oscuro', 'Oscuro');
            if (v.oscuro.marca !== 'siempre' && !v.oscuro.sinOscuro) {
                html += '<button type="button" class="btn btn-sm btn-link text-danger p-0" data-quitar="oscuro">Quitar oscuro</button>';
            }
        }
        if (v.tieneAlgo) {
            html += '<button type="button" class="btn btn-sm btn-link text-danger p-0" data-quitar="claro">Quitar (como siempre)</button>';
        }
        return html + '</div></div>';
    }

    function repintarFila(lugar) {
        var fila = document.querySelector('#modalTemas .tema-fila[data-lugar="' + lugar + '"]');
        if (!fila) return;
        var l = CAT.fijos.concat(CAT.sistemas).filter(function (x) { return x.lugar === lugar; })[0];
        fila.outerHTML = filaHtml(l, esSistema(lugar));
    }

    // ── Eventos de las filas ─────────────────────────────────────────────
    $('#modalTemas').on('change', '.tema-fila input[type=file]', function () {
        var archivo = this.files && this.files[0];
        var lugar = $(this).closest('.tema-fila').data('lugar');
        var campo = this.getAttribute('data-campo');
        if (!archivo) return;
        if (!/^image\/(png|jpeg|webp)$/.test(archivo.type)) { error('Sube un PNG, JPG o WebP.'); return; }
        if (archivo.size > MAX) { error('La imagen es demasiado pesada (máximo 8 MB).'); return; }
        estado.pend[lugar] = estado.pend[lugar] || {};
        estado.pend[lugar][campo] = archivo;
        if (campo === 'claro') delete estado.quitar[lugar];
        else delete estado.quitarOscuro[lugar];
        estado.sucio = true;
        repintarFila(lugar);
    });

    $('#modalTemas').on('click', '.tema-fila [data-quitar]', function () {
        var lugar = $(this).closest('.tema-fila').data('lugar');
        var p = estado.pend[lugar] || {};
        if (this.getAttribute('data-quitar') === 'oscuro') {
            delete p.oscuro;
            if (propio(lugar) && propio(lugar).oscuro) estado.quitarOscuro[lugar] = true;
        } else {
            delete estado.pend[lugar];
            if (estado.actual && estado.actual.logos[lugar]) estado.quitar[lugar] = true;
        }
        estado.sucio = true;
        repintarFila(lugar);
    });

    $('#tem_nombre, #tem_desde, #tem_hasta').on('input change', function () { estado.sucio = true; });

    // ── Colores ──────────────────────────────────────────────────────────
    var HEX = /^#[0-9a-f]{6}$/i;

    /** Sólo '#rrggbb': lo único que se mete en un style. */
    function hex(v) { return HEX.test(v || '') ? String(v).toLowerCase() : '#000000'; }

    /** Igual que mbTemaMezclar (includes/tema.php): peso = cuánto de b. */
    function mezclar(a, b, peso) {
        var r = '#';
        for (var i = 1; i < 7; i += 2) {
            var x = parseInt(a.substr(i, 2), 16), y = parseInt(b.substr(i, 2), 16);
            r += ('0' + Math.round(x + (y - x) * peso).toString(16)).slice(-2);
        }
        return r;
    }

    /** Igual que mbTemaAcentoOscuro: el claro, 30 % hacia blanco. */
    function acentoOscuro(claro) { return mezclar(claro, '#ffffff', 0.30); }

    function catColor(clave) {
        return CAT.colores.filter(function (c) { return c.clave === clave; })[0];
    }

    /** Lo que sale si esta versión no cambia el color: la predeterminada o código (como mbTemaColores). */
    function colorSiempre(clave) {
        var c = catColor(clave), pred = predeterminada();
        var esPred = estado.actual && estado.actual.predeterminada;
        var p = !esPred && pred && pred.colores ? pred.colores[clave] : null;
        if (!p) return { claro: c.claro, oscuro: c.oscuro };
        var osc = c.oscuro === null ? null : (p.oscuro || (clave === 'acento' ? acentoOscuro(p.claro) : c.oscuro));
        return { claro: p.claro, oscuro: osc };
    }

    /**
     * Lo que se verá con esta versión: { claro, oscuro, marca, oscuroPropio }.
     * Sin oscuro propio, el acento se aclara solo y los demás quedan como siempre.
     */
    function colorEfectivo(clave) {
        var c = catColor(clave), mio = estado.col[clave], siempre = colorSiempre(clave);
        if (!mio) return { claro: siempre.claro, oscuro: siempre.oscuro, marca: 'siempre', oscuroPropio: false };
        var orig = estado.actual && estado.actual.colores ? estado.actual.colores[clave] : null;
        var nuevo = !orig || orig.claro !== mio.claro || (orig.oscuro || null) !== (mio.oscuro || null);
        var osc = c.oscuro === null ? null : (mio.oscuro || (clave === 'acento' ? acentoOscuro(mio.claro) : siempre.oscuro));
        return { claro: mio.claro, oscuro: osc, marca: nuevo ? 'nuevo' : 'version', oscuroPropio: !!mio.oscuro };
    }

    /** El color de una clave en un tema ('claro' u 'oscuro'); la marca es la misma en los dos. */
    function colorEn(clave, tema) {
        var e = colorEfectivo(clave);
        return hex(tema === 'oscuro' && e.oscuro ? e.oscuro : e.claro);
    }

    function luminancia(h) {
        var c = [1, 3, 5].map(function (i) {
            var v = parseInt(h.substr(i, 2), 16) / 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
    }

    /** Contraste WCAG entre dos colores (1 a 21). */
    function contraste(a, b) {
        var x = luminancia(a), y = luminancia(b);
        return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05);
    }

    /** Texto legible sobre una muestra. */
    function textoSobre(h) { return luminancia(h) > 0.35 ? '#1f2937' : '#ffffff'; }

    // [frente, fondo, mínimo, qué es, sólo en este tema]. '#ffffff' = el texto
    // blanco que va encima. Con los colores de código ninguno avisa.
    var PARES = [
        ['texto', 'tarjeta', 4.5, 'Texto sobre el fondo de las tarjetas'],
        ['texto', 'tarjeta_suave', 4.5, 'Texto sobre los encabezados de tarjeta'],
        ['texto_sec', 'tarjeta', 3, 'Texto secundario sobre el fondo de las tarjetas'],
        ['acento', 'tarjeta', 3, 'Links (acento) sobre el fondo de las tarjetas'],
        ['#ffffff', 'acento', 3, 'Texto blanco de los botones sobre el acento', 'claro'],
        ['#ffffff', 'marca1', 4.5, 'Texto blanco sobre la columna (arriba)', 'claro'],
        ['#ffffff', 'marca2', 4.5, 'Texto blanco sobre la columna (abajo)', 'claro']
    ];

    /** Los pares con poco contraste: [{ texto, valor, minimo }]. */
    function avisosContraste() {
        var avisos = [];
        ['claro', 'oscuro'].forEach(function (tema) {
            PARES.forEach(function (p) {
                if (p[4] && p[4] !== tema) return;
                var frente = p[0].charAt(0) === '#' ? p[0] : colorEn(p[0], tema);
                var valor = contraste(frente, colorEn(p[1], tema));
                if (valor < p[2]) {
                    avisos.push({ texto: p[3] + (p[4] ? '' : ' (tema ' + tema + ')'), valor: valor, minimo: p[2] });
                }
            });
        });
        return avisos;
    }

    function previaColoresHtml(tema) {
        var v = function (k) { return colorEn(k, tema); };
        var ac = v('acento');
        return '<div class="tema-cprev" style="background:linear-gradient(180deg,' + v('marca1') + ' 0%,' + v('marca2') + ' 100%)">'
             + '<div class="tema-cprev__etiqueta">Tema ' + tema + ' · <i class="fas fa-user-circle"></i> Nombre del usuario</div>'
             + '<div class="tema-cprev__tabs">'
             +   '<span class="tema-cprev__tab" style="background:' + v('tarjeta') + ';color:' + ac + '"><i class="fas fa-user"></i></span>'
             +   '<span class="tema-cprev__tab tema-cprev__tab--off"><i class="fas fa-th-large"></i></span>'
             +   '<span class="tema-cprev__tab tema-cprev__tab--off"><i class="fas fa-calendar-alt"></i></span>'
             + '</div>'
             + '<div class="tema-cprev__card" style="background:' + v('tarjeta') + ';border-color:' + v('borde') + ';color:' + v('texto') + '">'
             +   '<div class="tema-cprev__head" style="background:' + v('tarjeta_suave') + ';border-color:' + v('borde') + '"><i class="fas fa-bell"></i> Notificaciones</div>'
             +   '<div class="tema-cprev__body">Texto normal de una tarjeta.'
             +     '<small class="d-block" style="color:' + v('texto_sec') + '">27/11 · texto secundario</small>'
             +     '<div class="tema-cprev__acciones"><span style="color:' + ac + ';text-decoration:underline">Un link</span>'
             +     '<span class="tema-cprev__btn" style="background:' + ac + '">Botón</span></div>'
             +   '</div>'
             + '</div></div>';
    }

    function muestraHtml(c, cual, e) {
        var mio = estado.col[c.clave];
        var valor = hex(cual === 'oscuro' ? e.oscuro : e.claro);
        var titulo = cual === 'claro'
            ? (c.oscuro === null ? 'Los dos temas' : 'Tema claro')
            : (e.oscuroPropio ? 'Tema oscuro' : (mio && c.clave === 'acento' ? 'Oscuro (automático)' : 'Oscuro (como siempre)'));
        var activa = cual === 'claro' || e.oscuroPropio;
        var editando = estado.editor && estado.editor.clave === c.clave && estado.editor.cual === cual;
        return '<button type="button" class="tema-color__muestra' + (editando ? ' is-editando' : '') + '" data-cual="' + cual + '"'
             + (activa ? '' : ' disabled') + ' style="background:' + valor + ';color:' + textoSobre(valor) + '"'
             + ' title="' + (activa ? 'Cambiar' : 'Marca la casilla para escoger otro') + '">'
             + '<small>' + titulo + '</small><span>' + valor + '</span></button>';
    }

    function filaColorHtml(c) {
        var e = colorEfectivo(c.clave), mio = estado.col[c.clave];
        var html = '<div class="tema-fila tema-color" data-clave="' + esc(c.clave) + '">'
                 + '<div class="tema-fila__info"><strong>' + esc(c.nombre) + '</strong> <span class="tema-color__marca">' + MARCA[e.marca] + '</span>'
                 + '<small class="text-muted d-block">' + esc(c.ayuda) + '</small></div>'
                 + '<div class="tema-color__muestras">' + muestraHtml(c, 'claro', e)
                 + (c.oscuro !== null ? muestraHtml(c, 'oscuro', e) : '') + '</div>'
                 + '<div class="tema-fila__acciones">';
        if (c.oscuro !== null) {
            html += '<div class="custom-control custom-checkbox small">'
                  + '<input type="checkbox" class="custom-control-input" id="tem_osc_' + esc(c.clave) + '" data-oscuro' + (e.oscuroPropio ? ' checked' : '') + '>'
                  + '<label class="custom-control-label" for="tem_osc_' + esc(c.clave) + '">Otro color para el tema oscuro</label></div>';
        }
        html += '<button type="button" class="btn btn-sm btn-link text-danger p-0" data-quitar-color' + (mio ? '' : ' hidden') + '>Quitar (como siempre)</button>'
              + '</div>';
        if (estado.editor && estado.editor.clave === c.clave) html += '<div class="tema-color__editor"></div>';
        return html + '</div>';
    }

    /** Vista previa, avisos de contraste y cuántos colores cambia la versión. */
    function pintarResumenColores() {
        $t('tem_colPrevia').innerHTML = previaColoresHtml('claro') + previaColoresHtml('oscuro');
        var avisos = avisosContraste();
        $t('tem_colAvisos').innerHTML = avisos.length
            ? '<div class="alert alert-warning small py-2"><strong><i class="fas fa-exclamation-triangle mr-1"></i>Poco contraste:</strong> puede costar leerlo.<ul class="mb-0 pl-3">'
              + avisos.map(function (a) {
                    return '<li>' + esc(a.texto) + ': ' + a.valor.toFixed(1) + ':1 (mínimo ' + a.minimo + ':1)</li>';
                }).join('') + '</ul></div>'
            : '';
        var n = Object.keys(estado.col).length;
        $t('tem_colCuenta').hidden = !n;
        $t('tem_colCuenta').textContent = n + (n === 1 ? ' cambiado' : ' cambiados');
    }

    /** Todas las filas (al elegir versión, quitar, abrir o cerrar la rueda). */
    function pintarColores() {
        $t('tem_colSinTabla').hidden = estado.hayColores;
        $t('tem_colCuerpo').hidden = !estado.hayColores;
        if (!estado.hayColores) { $t('tem_colCuenta').hidden = true; return; }
        $t('tem_colLista').innerHTML = CAT.colores.map(filaColorHtml).join('');
        pintarResumenColores();
        if (estado.editor) montarEditor();
        else estado.picker = null;
    }

    /** Sólo las muestras y la marca de una fila: mientras se arrastra la rueda no se rehace la fila. */
    function refrescarColor(clave) {
        var fila = document.querySelector('#tem_colLista .tema-color[data-clave="' + clave + '"]');
        if (!fila) return;
        var c = catColor(clave), e = colorEfectivo(clave);
        fila.querySelector('.tema-color__marca').innerHTML = MARCA[e.marca];
        fila.querySelector('.tema-color__muestras').innerHTML = muestraHtml(c, 'claro', e) + (c.oscuro !== null ? muestraHtml(c, 'oscuro', e) : '');
        fila.querySelector('[data-quitar-color]').hidden = !estado.col[clave];
        pintarResumenColores();
    }

    function abrirEditor(clave, cual) {
        estado.editor = { clave: clave, cual: cual };
        pintarColores();
    }

    function cerrarEditor() {
        estado.editor = null;
        pintarColores();
    }

    /** La rueda (o el selector del navegador) con HEX y RGB, en la fila del color abierto. */
    function montarEditor() {
        var ed = estado.editor;
        var cont = document.querySelector('#tem_colLista .tema-color[data-clave="' + ed.clave + '"] .tema-color__editor');
        if (!cont) return;
        var e = colorEfectivo(ed.clave);
        var valor = hex(ed.cual === 'oscuro' ? e.oscuro : e.claro);
        cont.innerHTML = '<div class="tema-color__rueda"></div>'
            + '<div class="tema-color__campos">'
            +   '<div class="small font-weight-bold">' + esc(catColor(ed.clave).nombre) + (ed.cual === 'oscuro' ? ' · tema oscuro' : '') + '</div>'
            +   '<label class="small mb-0">HEX<input type="text" class="form-control form-control-sm" data-hex maxlength="7" spellcheck="false"></label>'
            +   '<div class="tema-color__rgb">'
            +     ['r', 'g', 'b'].map(function (k) {
                      return '<label class="small mb-0">' + k.toUpperCase()
                           + '<input type="number" class="form-control form-control-sm" min="0" max="255" step="1" data-rgb="' + k + '"></label>';
                  }).join('')
            +   '</div>'
            +   '<button type="button" class="btn btn-sm btn-primary" data-cerrar-editor><i class="fas fa-check mr-1"></i>Listo</button>'
            + '</div>';
        var rueda = cont.querySelector('.tema-color__rueda');
        if (window.iro && iro.ColorPicker) {
            estado.picker = new iro.ColorPicker(rueda, {
                width: 170, color: valor, borderWidth: 1, borderColor: '#ced4da',
                layout: [{ component: iro.ui.Wheel }, { component: iro.ui.Slider, options: { sliderType: 'value' } }]
            });
            estado.picker.on('input:change', function (color) { ponerColor(color.hexString, 'rueda'); });
        } else {
            // Sin iro.js (el CDN no respondió): el selector del navegador.
            estado.picker = null;
            rueda.innerHTML = '<input type="color" class="tema-color__nativo" value="' + valor + '" aria-label="Escoger color">';
        }
        llenarCampos(valor, '');
    }

    function llenarCampos(valor, menos) {
        var cont = document.querySelector('#tem_colLista .tema-color__editor');
        if (!cont) return;
        if (menos !== 'hex') cont.querySelector('[data-hex]').value = valor;
        if (menos !== 'rgb') {
            ['r', 'g', 'b'].forEach(function (k, i) {
                cont.querySelector('[data-rgb="' + k + '"]').value = parseInt(valor.substr(1 + i * 2, 2), 16);
            });
        }
        var nativo = cont.querySelector('.tema-color__nativo');
        if (nativo && menos !== 'nativo') nativo.value = valor;
    }

    /** Un color nuevo para el que está abierto. origen: 'rueda', 'hex', 'rgb' o 'nativo' (ése no se reescribe). */
    function ponerColor(valor, origen) {
        var ed = estado.editor;
        if (!ed || !HEX.test(valor || '')) return;
        valor = valor.toLowerCase();
        var e = colorEfectivo(ed.clave);
        var mio = estado.col[ed.clave] || (estado.col[ed.clave] = { claro: e.claro, oscuro: null });
        if (ed.cual === 'oscuro') mio.oscuro = valor; else mio.claro = valor;
        estado.sucio = true;
        if (origen !== 'rueda' && estado.picker) estado.picker.color.hexString = valor;
        llenarCampos(valor, origen);
        refrescarColor(ed.clave);
    }

    $('#tem_colLista').on('click', '.tema-color__muestra', function () {
        var clave = $(this).closest('.tema-color').data('clave');
        var cual = this.getAttribute('data-cual');
        if (estado.editor && estado.editor.clave === clave && estado.editor.cual === cual) cerrarEditor();
        else abrirEditor(clave, cual);
    });
    $('#tem_colLista').on('click', '[data-cerrar-editor]', cerrarEditor);
    $('#tem_colLista').on('input', '[data-hex]', function () {
        var v = this.value.trim();
        if (v.charAt(0) !== '#') v = '#' + v;
        if (HEX.test(v)) ponerColor(v, 'hex');
    });
    $('#tem_colLista').on('input', '[data-rgb]', function () {
        var cont = $(this).closest('.tema-color__editor')[0], v = '#', ok = true;
        ['r', 'g', 'b'].forEach(function (k) {
            var n = parseInt(cont.querySelector('[data-rgb="' + k + '"]').value, 10);
            if (isNaN(n) || n < 0 || n > 255) ok = false;
            else v += ('0' + n.toString(16)).slice(-2);
        });
        if (ok) ponerColor(v, 'rgb');
    });
    $('#tem_colLista').on('input', '.tema-color__nativo', function () { ponerColor(this.value, 'nativo'); });
    $('#tem_colLista').on('change', '[data-oscuro]', function () {
        var clave = $(this).closest('.tema-color').data('clave');
        var e = colorEfectivo(clave);
        if (this.checked) {
            // Arranca con el oscuro que se ve hoy y se abre la rueda en él.
            var mio = estado.col[clave] || (estado.col[clave] = { claro: e.claro, oscuro: null });
            mio.oscuro = hex(e.oscuro);
            estado.sucio = true;
            abrirEditor(clave, 'oscuro');
        } else if (estado.col[clave]) {
            estado.col[clave].oscuro = null;
            estado.sucio = true;
            if (estado.editor && estado.editor.clave === clave && estado.editor.cual === 'oscuro') estado.editor = null;
            pintarColores();
        }
    });
    $('#tem_colLista').on('click', '[data-quitar-color]', function () {
        var clave = $(this).closest('.tema-color').data('clave');
        delete estado.col[clave];
        estado.sucio = true;
        if (estado.editor && estado.editor.clave === clave) estado.editor = null;
        pintarColores();
    });

    // ── Cambiar de versión, nueva, guardar, borrar ───────────────────────
    function siDescarta() {
        if (!estado.sucio) return Promise.resolve(true);
        return Swal.fire({
            icon: 'warning', title: 'Hay cambios sin guardar', text: '¿Los descartas?',
            showCancelButton: true, confirmButtonText: 'Descartar', cancelButtonText: 'Seguir editando'
        }).then(function (r) { return r.isConfirmed; });
    }

    $('#tem_lista').on('click', '.tema-version', function () {
        var id = parseInt(this.getAttribute('data-id'), 10);
        siDescarta().then(function (ok) {
            if (!ok) return;
            elegirVersion(estado.versiones.filter(function (v) { return v.id === id; })[0] || null);
        });
    });

    $t('tem_nueva').addEventListener('click', function () {
        siDescarta().then(function (ok) {
            if (!ok) return;
            elegirVersion(null);
            $t('tem_nombre').focus();
        });
    });

    $t('tem_guardar').addEventListener('click', function () {
        var btn = this;
        // Poco contraste: se avisa, pero la decisión es de BI.
        var avisos = estado.hayColores ? avisosContraste() : [];
        if (!avisos.length) { guardar(btn); return; }
        Swal.fire({
            icon: 'warning', title: 'Hay colores con poco contraste',
            html: 'Puede costar leer:<ul class="text-left small mt-2 mb-0">'
                + avisos.map(function (a) { return '<li>' + esc(a.texto) + ' (' + a.valor.toFixed(1) + ':1)</li>'; }).join('')
                + '</ul>',
            showCancelButton: true, confirmButtonText: 'Guardar de todos modos', cancelButtonText: 'Seguir editando'
        }).then(function (s) { if (s.isConfirmed) guardar(btn); });
    });

    function guardar(btn) {
        var fd = new FormData();
        fd.append('id_tema', $t('tem_id').value || '0');
        fd.append('nombre', $t('tem_nombre').value);
        fd.append('desde', $t('tem_desde').value);
        fd.append('hasta', $t('tem_hasta').value);
        Object.keys(estado.pend).forEach(function (lugar) {
            var p = estado.pend[lugar];
            if (p.claro)  fd.append('logo[' + lugar + ']', p.claro);
            if (p.oscuro) fd.append('oscuro[' + lugar + ']', p.oscuro);
        });
        Object.keys(estado.quitar).forEach(function (lugar) { fd.append('quitar[]', lugar); });
        Object.keys(estado.quitarOscuro).forEach(function (lugar) { fd.append('quitar_oscuro[]', lugar); });
        // Los colores van completos: lo que no esté vuelve al de siempre.
        if (estado.hayColores) fd.append('colores', JSON.stringify(estado.col));

        btn.disabled = true;
        pedir('guardar', fd).then(function (r) {
            if (!r.success) { error(r.message); return; }
            estado.sucio = false;
            var seVe = r.estado === 'activa' || r.estado === 'predeterminada';
            cargar(r.id);
            Swal.fire({
                icon: 'success', title: 'Guardado',
                text: seVe ? 'Recarga la página para ver los logos y colores nuevos.'
                           : 'Los logos y colores salen solos cuando empiece la versión.',
                showCancelButton: seVe, confirmButtonText: seVe ? 'Recargar ahora' : 'Aceptar', cancelButtonText: 'Después'
            }).then(function (s) { if (seVe && s.isConfirmed) location.reload(); });
        }).catch(function () { error(); })
          .then(function () { btn.disabled = false; });
    }

    $t('tem_borrar').addEventListener('click', function () {
        var v = estado.actual;
        if (!v || v.predeterminada) return;
        Swal.fire({
            icon: 'warning', title: '¿Borrar "' + v.nombre + '"?',
            text: 'Se borran también sus logos y colores. Lo que no esté en otra versión vuelve a salir como siempre.',
            showCancelButton: true, confirmButtonText: 'Borrar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc3545'
        }).then(function (s) {
            if (!s.isConfirmed) return;
            pedir('borrar', { id_tema: v.id }).then(function (r) {
                if (!r.success) { error(r.message); return; }
                estado.sucio = false;
                cargar(0);
            }).catch(function () { error(); });
        });
    });

    // Cada vez que se abre: la lista fresca y la predeterminada elegida.
    $('#modalTemas').on('show.bs.modal', function (e) {
        if (e.target !== this) return;
        estado.sucio = false;
        cargar(estado.actual ? estado.actual.id : 0);
    });
    $('#modalTemas').on('hide.bs.modal', function (e) {
        if (e.target !== this || !estado.sucio) return;
        e.preventDefault();
        siDescarta().then(function (ok) {
            if (!ok) return;
            estado.sucio = false;
            $('#modalTemas').modal('hide');
        });
    });
})();

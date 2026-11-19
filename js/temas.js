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
 * El modal lo abre el logo de la columna de inicio.php. Los cambios se ven al
 * recargar la página: los logos los pinta el servidor (includes/tema.php).
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
        sucio: false
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
            estado.versiones = r.versiones || [];
            estado.defecto   = r.defecto || {};
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

        btn.disabled = true;
        pedir('guardar', fd).then(function (r) {
            if (!r.success) { error(r.message); return; }
            estado.sucio = false;
            var seVe = r.estado === 'activa' || r.estado === 'predeterminada';
            cargar(r.id);
            Swal.fire({
                icon: 'success', title: 'Guardado',
                text: seVe ? 'Recarga la página para ver los logos nuevos.'
                           : 'Los logos salen solos cuando empiece la versión.',
                showCancelButton: seVe, confirmButtonText: seVe ? 'Recargar ahora' : 'Aceptar', cancelButtonText: 'Después'
            }).then(function (s) { if (seVe && s.isConfirmed) location.reload(); });
        }).catch(function () { error(); })
          .then(function () { btn.disabled = false; });
    });

    $t('tem_borrar').addEventListener('click', function () {
        var v = estado.actual;
        if (!v || v.predeterminada) return;
        Swal.fire({
            icon: 'warning', title: '¿Borrar "' + v.nombre + '"?',
            text: 'Se borran también sus logos. Lo que no esté en otra versión vuelve a salir como siempre.',
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

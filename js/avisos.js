/**
 * avisos.js — Módulo de Avisos de inicio.php.
 *
 *   1. Pop-up "NUEVO AVISO" al entrar: los avisos vigentes que el empleado no
 *      ha aceptado, uno tras otro. Sólo se cierra con "Aceptar".
 *   2. Pestaña Avisos: tarjetas con buscador; "ver más" abre el aviso completo.
 *   3. Crear / editar (sólo con permiso): modal con editor (Summernote) y PDF.
 *
 * Todo pasa por acciones_avisos.php. El HTML de los avisos llega ya limpio del
 * servidor (includes/html_limpio.php) y se pinta tal cual; el resto del texto
 * (título, subtítulo, extracto…) se escapa aquí. Los modales están en
 * modalAvisos.php, que también deja la configuración en window.MB_AVISOS.
 */
(function () {
    'use strict';

    var URL_AV  = 'acciones_avisos.php';
    var PDF_MAX = 20 * 1024 * 1024;                 // igual que el servidor
    var CFG     = window.MB_AVISOS || { logo: '', puedeCrear: false };

    var estado = {
        lista: [], areas: [], totalEmpleados: 0,    // lo último de `listar`
        pendientes: [], totalPendientes: 0,         // cola del pop-up
        actual: null,                               // el del pop-up en pantalla
        ver: null,                                  // el de "ver más"
        editor: false,                              // Summernote ya iniciado
        pdf: null, quitarPdf: false                 // PDF del formulario
    };

    // ── Utilidades ───────────────────────────────────────────────────────
    function pedir(accion, datos) {
        var fd = datos instanceof FormData ? datos : new FormData();
        if (!(datos instanceof FormData)) {
            Object.keys(datos || {}).forEach(function (k) { fd.append(k, datos[k]); });
        }
        fd.append('accion', accion);
        return fetch(URL_AV, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function esc(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /** El color va a un atributo style: sólo '#RRGGBB'. */
    function color(c) { return /^#[0-9a-f]{6}$/i.test(c) ? c : '#5B9BD5'; }

    /** Para buscar sin importar mayúsculas ni acentos. */
    function normal(s) {
        return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function error(msg) {
        Swal.fire({ icon: 'error', title: msg || 'No se pudo contactar al servidor.' });
    }

    function iconoPdf(a) {
        return a.pdf
            ? '<a class="aviso-pdf" href="' + esc(a.pdf) + '" target="_blank" rel="noopener" title="Abrir PDF" aria-label="Abrir PDF">'
              + '<i class="fas fa-file-pdf"></i></a>'
            : '';
    }

    /** Barra de color + título + subtítulo + contenido (+ PDF). Pop-up y "ver más". */
    function cuerpoHtml(a) {
        return '<div class="aviso-cuerpo" style="--aviso-color:' + color(a.color) + '">'
            +   '<div class="aviso-cuerpo__texto">'
            +     '<div class="aviso-titulo">' + esc(a.titulo) + '</div>'
            +     (a.subtitulo ? '<div class="aviso-subtitulo">' + esc(a.subtitulo) + '</div>' : '')
            // a.html viene limpio del servidor (lista blanca): se pinta tal cual.
            +     '<div class="aviso-html">' + (a.html || '') + '</div>'
            +   '</div>'
            +   iconoPdf(a)
            + '</div>';
    }

    // ── 1. Pop-up "NUEVO AVISO" ──────────────────────────────────────────
    function revisarPendientes() {
        pedir('pendientes').then(function (d) {
            if (!d.success || !d.avisos || !d.avisos.length) return;
            estado.pendientes = d.avisos;
            estado.totalPendientes = d.avisos.length;
            mostrarSiguiente();
        }).catch(function () { /* sin red: saldrá la próxima vez que entre */ });
    }

    function mostrarSiguiente() {
        var a = estado.pendientes[0];
        if (!a) { $('#modalAvisoNuevo').modal('hide'); return; }
        estado.actual = a;
        $('#avNuevoCuerpo').html(cuerpoHtml(a));
        var n = estado.totalPendientes - estado.pendientes.length + 1;
        $('#avNuevoContador').text(estado.totalPendientes > 1 ? n + ' de ' + estado.totalPendientes : '');
        $('#modalAvisoNuevo .modal-body').scrollTop(0);
        $('#modalAvisoNuevo').modal('show');
    }

    $('#avNuevoAceptar').on('click', function () {
        var boton = this;
        if (!estado.actual) return;
        boton.disabled = true;
        pedir('aceptar', { id: estado.actual.id }).then(function (d) {
            boton.disabled = false;
            // Si se ocultó mientras estaba en pantalla, se brinca; cualquier otro
            // error deja el aviso para que lo vuelva a intentar.
            if (!d.success && !d.no_disponible) { error(d.message); return; }
            estado.pendientes.shift();
            mostrarSiguiente();
        }).catch(function () { boton.disabled = false; error(); });
    });

    // ── 2. Pestaña Avisos ────────────────────────────────────────────────
    function cargarLista() {
        if (!estado.lista.length) $('#avisosCargando').prop('hidden', false);
        pedir('listar').then(function (d) {
            $('#avisosCargando').prop('hidden', true);
            if (!d.success) { estado.lista = []; $('#avisosLista').empty(); vacio(d.message); return; }
            estado.lista = d.avisos || [];
            estado.areas = d.areas || [];
            estado.totalEmpleados = d.total_empleados || 0;
            pintarLista();
        }).catch(function () {
            $('#avisosCargando').prop('hidden', true);
            if (!estado.lista.length) vacio('No se pudo contactar al servidor.');
        });
    }

    function vacio(texto) {
        $('#avisosVacioTexto').text(texto);
        $('#avisosVacio').prop('hidden', false);
    }

    function tarjeta(a) {
        var leido = a.editar && estado.totalEmpleados
            ? '<small class="aviso-leido">Leído por ' + (a.aceptados || 0) + ' de ' + estado.totalEmpleados + '</small>'
            : '';
        return '<article class="aviso-tarjeta" style="--aviso-color:' + color(a.color) + '" data-ver="' + a.id + '" title="' + esc(a.area) + '">'
            +   '<div class="aviso-tarjeta__cabeza">'
            +     '<div class="aviso-titulo">' + esc(a.titulo) + '</div>'
            +     '<time class="aviso-fecha">' + esc(a.fecha) + '</time>'
            +   '</div>'
            +   (a.subtitulo ? '<div class="aviso-subtitulo">' + esc(a.subtitulo) + '</div>' : '')
            +   '<div class="aviso-tarjeta__cuerpo">'
            +     '<p class="aviso-extracto">' + esc(a.texto) + '</p>'
            +     iconoPdf(a)
            +   '</div>'
            +   '<div class="aviso-tarjeta__pie">'
            +     '<button type="button" class="btn btn-link aviso-vermas" data-ver="' + a.id + '">ver más</button>'
            +     leido
            +   '</div>'
            + '</article>';
    }

    function pintarLista() {
        var q = normal($('#avisosBuscar').val()).trim();
        var visibles = estado.lista.filter(function (a) {
            return !q || normal([a.area, a.titulo, a.subtitulo, a.texto, a.fecha].join(' ')).indexOf(q) !== -1;
        });
        $('#avisosLista').html(visibles.map(tarjeta).join(''));
        if (!estado.lista.length)  vacio('Todavía no hay avisos.');
        else if (!visibles.length) vacio('Ningún aviso coincide con la búsqueda.');
        else                       $('#avisosVacio').prop('hidden', true);
    }

    $('#tabAvisos-tab').on('shown.bs.tab', cargarLista);
    $('#avisosBuscar').on('input', pintarLista);

    // Toda la tarjeta abre el aviso, menos el ícono del PDF (ése abre el PDF).
    $('#avisosLista').on('click', '[data-ver]', function (e) {
        if ($(e.target).closest('.aviso-pdf').length) return;
        e.stopPropagation();
        abrirVer(parseInt($(this).data('ver'), 10));
    });

    // ── "ver más" ────────────────────────────────────────────────────────
    function abrirVer(id) {
        pedir('ver', { id: id }).then(function (d) {
            if (!d.success) { error(d.message); cargarLista(); return; }
            var a = d.aviso;
            estado.ver = a;
            $('#avVerCuerpo').html(cuerpoHtml(a));
            $('#avVerMeta').text('Publicado por ' + a.area + ' · ' + a.fecha
                + (a.vigente ? ' · Vigente hasta el ' + a.vigencia : ''));
            $('#avVerGestion').prop('hidden', !a.editar);
            $('#avVerQuienesLista').prop('hidden', true).empty();
            if (a.editar) {
                $('#avVerLeido').text('Leído por ' + a.aceptados + ' de ' + a.total_empleados + ' empleados');
            }
            $('#modalAvisoVer').modal('show');
        }).catch(function () { error(); });
    }

    $('#avVerQuienes').on('click', function () {
        var $lista = $('#avVerQuienesLista');
        if (!$lista.prop('hidden')) { $lista.prop('hidden', true); return; }
        pedir('quienes', { id: estado.ver.id }).then(function (d) {
            if (!d.success) { error(d.message); return; }
            function columna(titulo, filas, conFecha) {
                return '<div class="aviso-quienes__col"><strong>' + titulo + ' (' + filas.length + ')</strong><ul>'
                    + (filas.length
                        ? filas.map(function (f) {
                            return '<li>' + esc(f.nombre) + (conFecha ? ' <small class="text-muted">' + esc(f.fecha) + '</small>' : '') + '</li>';
                          }).join('')
                        : '<li class="text-muted">Nadie</li>')
                    + '</ul></div>';
            }
            $lista.html(columna('Faltan', d.faltan, false) + columna('Lo aceptaron', d.aceptaron, true)).prop('hidden', false);
        }).catch(function () { error(); });
    });

    $('#avVerOcultar').on('click', function () {
        var a = estado.ver;
        Swal.fire({
            icon: 'warning', title: '¿Ocultar «' + a.titulo + '»?',
            text: 'Deja de verse en Avisos y ya no sale como pop-up. No se borra.',
            showCancelButton: true, confirmButtonText: 'Sí, ocultar', cancelButtonText: 'Cancelar', confirmButtonColor: '#d33'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            pedir('ocultar', { id: a.id }).then(function (d) {
                if (!d.success) { error(d.message); return; }
                $('#modalAvisoVer').modal('hide');
                cargarLista();
            }).catch(function () { error(); });
        });
    });

    // ── 3. Crear / editar ────────────────────────────────────────────────
    if (CFG.puedeCrear) {
        var $contenido = $('#avContenido');

        /** Summernote al abrir por primera vez. Si el CDN no cargó, un
            contenteditable simple (el servidor limpia de todos modos). */
        function iniciarEditor() {
            if (estado.editor) return;
            if ($.fn.summernote) {
                $contenido.summernote({
                    // Si el archivo de idioma no cargó, en inglés en vez de romperse.
                    lang: $.summernote.lang && $.summernote.lang['es-ES'] ? 'es-ES' : 'en-US',
                    height: 200,
                    dialogsInBody: true,
                    placeholder: 'Escribe o pega aquí el contenido del correo',
                    toolbar: [
                        ['formato',  ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                        ['parrafo',  ['ul', 'ol']],
                        ['insertar', ['link']]
                    ]
                });
            } else {
                $contenido.attr('contenteditable', 'true').addClass('form-control aviso-editor-simple');
            }
            estado.editor = true;
        }
        function contenido(html) {
            var sn = !!$.fn.summernote && $contenido.next('.note-editor').length;
            if (html === undefined) return sn ? $contenido.summernote('code') : $contenido.html();
            if (sn) $contenido.summernote('code', html); else $contenido.html(html);
        }

        /** 'AAAA-MM-DDTHH:MM' de una fecha local, para el datetime-local. */
        function fechaInput(f) {
            function d2(n) { return (n < 10 ? '0' : '') + n; }
            return f.getFullYear() + '-' + d2(f.getMonth() + 1) + '-' + d2(f.getDate()) + 'T' + d2(f.getHours()) + ':' + d2(f.getMinutes());
        }

        function pintarPdf(actual) {
            $('#avDropTexto').text(estado.pdf ? estado.pdf.name : 'Arrastra o selecciona un PDF');
            $('#avDrop').toggleClass('tiene-archivo', !!estado.pdf);
            var hay = !!estado.pdf || (!!actual && !estado.quitarPdf);
            $('#avPdfActual').prop('hidden', !hay);
            if (estado.pdf)  $('#avPdfActualLink').text(estado.pdf.name).removeAttr('href');
            else if (actual) $('#avPdfActualLink').text('PDF adjunto').attr('href', actual);
        }

        function abrirForm(a) {
            iniciarEditor();
            var esNuevo = !a;
            $('#avId').val(esNuevo ? 0 : a.id);
            $('#avFormEtiqueta').html('<i class="fas fa-bullhorn mr-2"></i>' + (esNuevo ? 'Nuevo Aviso' : 'Editar aviso'));
            $('#avGuardarTexto').text(esNuevo ? 'Lanzar aviso' : 'Guardar cambios');

            // Áreas con las que puede publicar (las manda `listar`). Con una
            // sola no hay nada que elegir.
            $('#avArea').html(estado.areas.map(function (ar) {
                return '<option value="' + ar.id + '">' + esc(ar.nombre) + '</option>';
            }).join(''));
            if (!esNuevo) $('#avArea').val(String(a.id_area));
            $('#avAreaCaja').prop('hidden', estado.areas.length < 2);

            $('#avTitulo').val(esNuevo ? '' : a.titulo);
            $('#avSubtitulo').val(esNuevo ? '' : a.subtitulo);
            contenido(esNuevo ? '' : a.html);

            var ahora = new Date();
            $('#avVigencia').attr('min', fechaInput(ahora));
            if (esNuevo) {
                var semana = new Date(ahora.getTime() + 7 * 864e5);
                semana.setHours(18, 0, 0, 0);
                $('#avVigencia').val(fechaInput(semana));        // por omisión, una semana
            } else {
                $('#avVigencia').val(a.vigencia_input || '');
            }

            estado.pdf = null; estado.quitarPdf = false;
            estado.pdfActual = esNuevo ? null : a.pdf;
            $('#avPdf').val('');
            pintarPdf(estado.pdfActual);

            $('#modalAvisoForm').modal('show');
        }

        function elegirPdf(archivo) {
            if (!archivo) return;
            if (archivo.type !== 'application/pdf' && !/\.pdf$/i.test(archivo.name)) { error('Sólo se puede adjuntar un PDF.'); return; }
            if (archivo.size > PDF_MAX) { error('El PDF no debe pasar de 20 MB.'); return; }
            estado.pdf = archivo;
            pintarPdf(estado.pdfActual);
        }

        // "Editar" de "ver más" (sólo sale a quien puede editar).
        $('#avVerEditar').on('click', function () {
            var a = estado.ver;
            $('#modalAvisoVer').one('hidden.bs.modal', function () { abrirForm(a); }).modal('hide');
        });

        $('#avisosCrear').on('click', function () {
            if (!estado.areas.length) { error('Espera a que carguen los avisos e inténtalo de nuevo.'); cargarLista(); return; }
            abrirForm(null);
        });
        $('#avPdf').on('change', function () { elegirPdf(this.files && this.files[0]); this.value = ''; });
        $('#avDrop')
            .on('dragover', function (e) { e.preventDefault(); $(this).addClass('arrastrando'); })
            .on('dragleave drop', function () { $(this).removeClass('arrastrando'); })
            .on('drop', function (e) {
                e.preventDefault();
                var dt = e.originalEvent.dataTransfer;
                elegirPdf(dt && dt.files && dt.files[0]);
            });
        // Quitar uno recién elegido regresa al que ya tenía; si no había uno
        // nuevo, quita el del aviso (se borra al guardar).
        $('#avPdfQuitar').on('click', function () {
            if (estado.pdf) estado.pdf = null; else estado.quitarPdf = true;
            pintarPdf(estado.pdfActual);
        });

        $('#avGuardar').on('click', function () {
            var boton = this;
            var html  = contenido();
            if (!$('#avTitulo').val().trim())                     { error('Escribe el título del aviso.'); return; }
            if (!$('<div>').html(html).text().trim())             { error('Escribe el contenido del aviso.'); return; }
            if (!$('#avVigencia').val())                          { error('Elige hasta cuándo es vigente el aviso.'); return; }

            var fd = new FormData();
            fd.append('id', $('#avId').val());
            fd.append('id_area', $('#avArea').val() || '');
            fd.append('titulo', $('#avTitulo').val());
            fd.append('subtitulo', $('#avSubtitulo').val());
            fd.append('contenido', html);
            fd.append('vigencia', $('#avVigencia').val());
            if (estado.pdf) fd.append('pdf', estado.pdf, estado.pdf.name);
            else if (estado.quitarPdf) fd.append('quitar_pdf', '1');

            boton.disabled = true;
            pedir('guardar', fd).then(function (d) {
                boton.disabled = false;
                if (!d.success) { error(d.message); return; }
                $('#modalAvisoForm').modal('hide');
                Swal.fire({ icon: 'success', title: d.message, timer: 1500, showConfirmButton: false });
                cargarLista();
            }).catch(function () { boton.disabled = false; error(); });
        });
    }

    // Al entrar a messbook: ¿hay avisos nuevos?
    $(revisarPendientes);
})();

<?php
/**
 * modalAlbumes.php — "Administrar álbumes" (engrane de la pestaña Fotos).
 *
 * Crear, editar, ocultar y borrar los álbumes de fotos. Sólo la vista: todo
 * se guarda vía acciones_album.php (acciones adm_*, en
 * includes/album_admin.php), que vuelve a revisar el permiso en cada una.
 *
 * inicio.php lo incluye sólo si $adminAlbum (acceso especial album /
 * administrarAlbumes; includes/album.php, mbPuedeAdministrar). Al guardar
 * avisa con el evento 'mb:albumes' para que la pestaña recargue sus portadas.
 * El 🏆 de un concurso cierra este modal y pide el ranking con 'mb:ranking'
 * (el modal del ranking vive en inicio.php: también lo usa RRHH).
 */
if (empty($adminAlbum)) return;
?>

<div class="modal fade" id="modalAlbumes" tabindex="-1" role="dialog" aria-labelledby="alb_tituloModal" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-left-primary shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="alb_tituloModal"><i class="fas fa-images mr-2"></i>Administrar álbumes</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <!-- Alta / edición -->
                <div class="card mb-3">
                    <div class="card-header py-2" style="background: var(--card-soft); border-color: var(--border);">
                        <h6 class="m-0 font-weight-bold" id="alb_tituloForm">Nuevo álbum</h6>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="alb_id" value="">
                        <div class="form-row">
                            <div class="col-md-6 mb-2">
                                <label class="small mb-1" for="alb_titulo">Nombre del álbum</label>
                                <input type="text" class="form-control form-control-sm" id="alb_titulo" maxlength="80"
                                       placeholder="Ej. OktoberMESS 2026 Bajío" autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-2">
                                <span class="small mb-1 d-block">Tipo</span>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="alb_tipoEvento" name="alb_tipo" value="evento" class="custom-control-input" checked>
                                    <label class="custom-control-label small" for="alb_tipoEvento">Evento</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="alb_tipoGeneral" name="alb_tipo" value="general" class="custom-control-input">
                                    <label class="custom-control-label small" for="alb_tipoGeneral">General</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="alb_tipoConcurso" name="alb_tipo" value="concurso" class="custom-control-input">
                                    <label class="custom-control-label small" for="alb_tipoConcurso">Concurso</label>
                                </div>
                                <small class="text-muted d-block" id="alb_ayudaTipo"></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <!-- Evento: un día -->
                            <div class="col-md-4 mb-2" id="alb_cajaDia">
                                <label class="small mb-1" for="alb_dia">Día del evento</label>
                                <input type="date" class="form-control form-control-sm" id="alb_dia">
                            </div>
                            <!-- General: periodo. Concurso: vigencia. -->
                            <div class="col-md-4 mb-2" id="alb_cajaDesde" hidden>
                                <label class="small mb-1" for="alb_desde" id="alb_lblDesde">Desde</label>
                                <input type="date" class="form-control form-control-sm" id="alb_desde">
                            </div>
                            <div class="col-md-4 mb-2" id="alb_cajaHasta" hidden>
                                <label class="small mb-1" for="alb_hasta" id="alb_lblHasta">Hasta</label>
                                <input type="date" class="form-control form-control-sm" id="alb_hasta">
                            </div>
                            <div class="col-md-4 mb-2 d-flex align-items-end">
                                <div class="custom-control custom-switch mb-1">
                                    <input type="checkbox" class="custom-control-input" id="alb_visible" checked>
                                    <label class="custom-control-label small" for="alb_visible">Visible en la pestaña Fotos</label>
                                </div>
                            </div>
                        </div>

                        <!-- Concurso: la subida de fotos y la votación, con hora, dentro de la vigencia -->
                        <div class="form-row" id="alb_cajaConcurso" hidden>
                            <div class="col-md-3 col-6 mb-2">
                                <label class="small mb-1" for="alb_subidaDesde">Subida de fotos: abre</label>
                                <input type="datetime-local" class="form-control form-control-sm" id="alb_subidaDesde">
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <label class="small mb-1" for="alb_subidaHasta">Subida de fotos: cierra</label>
                                <input type="datetime-local" class="form-control form-control-sm" id="alb_subidaHasta">
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <label class="small mb-1" for="alb_votacionDesde">Votación: abre</label>
                                <input type="datetime-local" class="form-control form-control-sm" id="alb_votacionDesde">
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <label class="small mb-1" for="alb_votacionHasta">Votación: cierra</label>
                                <input type="datetime-local" class="form-control form-control-sm" id="alb_votacionHasta">
                            </div>
                            <div class="col-12 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="alb_subeRrhh">
                                    <label class="custom-control-label small" for="alb_subeRrhh">Las fotos las sube RRHH</label>
                                </div>
                                <small class="text-muted d-block">Los empleados no suben la suya, sólo votan. Las suben RRHH y quien administra los álbumes, con el nombre del participante o del equipo.</small>
                            </div>
                        </div>

                        <!-- Fotos de invitados de oktoberMESS (sólo lectura en messbook) -->
                        <div class="custom-control custom-checkbox mb-2" id="alb_filaInvitados">
                            <input type="checkbox" class="custom-control-input" id="alb_conInvitados">
                            <label class="custom-control-label small" for="alb_conInvitados">Incluir las fotos de los invitados (app de oktoberMESS)</label>
                        </div>
                        <div class="form-row" id="alb_cajaInvitados" hidden>
                            <div class="col-md-4 mb-2">
                                <label class="small mb-1" for="alb_invitados">Evento en oktoberMESS</label>
                                <input type="text" class="form-control form-control-sm" id="alb_invitados" list="alb_listaEventosOkt"
                                       maxlength="64" placeholder="Ej. oktobermess2026" autocomplete="off">
                                <datalist id="alb_listaEventosOkt"></datalist>
                            </div>
                            <div class="col-md-8 mb-2">
                                <span class="small mb-1 d-block">Sedes de los invitados</span>
                                <div id="alb_sedes"></div>
                                <small class="text-muted">Si no marcas ninguna, entran todas.</small>
                            </div>
                        </div>

                        <!-- Portada: sin una propia, la pestaña enseña la foto más reciente del álbum -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="alb-portada mr-3" id="alb_portadaVista"></div>
                            <div>
                                <span class="small mb-1 d-block">Portada</span>
                                <label class="btn btn-sm btn-outline-primary mb-0" for="alb_portada">
                                    <i class="fas fa-image mr-1"></i>Elegir imagen
                                </label>
                                <input type="file" id="alb_portada" accept="image/jpeg,image/png,image/webp" hidden>
                                <button type="button" class="btn btn-sm btn-link text-danger" id="alb_quitarPortada" hidden>Quitar</button>
                                <small class="text-muted d-block" id="alb_ayudaPortada"></small>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-sm btn-secondary" id="alb_btnCancelar" hidden>Cancelar edición</button>
                            <button type="button" class="btn btn-sm btn-primary" id="alb_btnGuardar">
                                <i class="fas fa-save mr-1"></i>Guardar
                            </button>
                        </div>
                    </div>
                </div>

                <p class="small text-muted mb-2">
                    En cada momento sólo un álbum recibe fotos: el de <strong>evento</strong> en su día (a cualquier hora, <?php echo MB_EVENTO_HORAS; ?> horas
                    desde las 00:00) y, fuera de los eventos, el <strong>general</strong> en su periodo (de 12:00 a 15:00 y de 18:00 a 06:00).
                    El <strong>concurso</strong> recibe aparte, en su propia subida, y no le quita fotos a los demás.
                    El que está recibiendo ahora va marcado con <i class="fas fa-circle text-success" style="font-size:.6rem;"></i>.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light small text-uppercase">
                            <tr>
                                <th>Álbum</th>
                                <th>Tipo</th>
                                <th>Fechas</th>
                                <th>Invitados</th>
                                <th class="text-center">Fotos</th>
                                <th class="text-center">Visible</th>
                                <th style="width:90px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="alb_tabla">
                            <tr><td colspan="7" class="text-center text-muted small py-3">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var URL_ALB  = 'acciones_album.php';
    var SIN_SEDE = 'SIN_SEDE';
    var lista    = [];     // lo último de adm_listar
    var sedes    = [];
    // Portada del formulario: la que ya tiene el álbum (url), la nueva que se
    // eligió (ya reducida) y si se pidió quitarla. Ver pintarPortada().
    var portada  = { actual: '', blob: null, vistaUrl: null, quitar: false };

    function $a(id) { return document.getElementById(id); }
    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    function pedir(accion, datos) {
        var fd = new FormData();
        fd.append('accion', accion);
        Object.keys(datos || {}).forEach(function (k) {
            var v = datos[k];
            if (Array.isArray(v)) v.forEach(function (x) { fd.append(k + '[]', x); });
            else fd.append(k, v);
        });
        return fetch(URL_ALB, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }
    function error(msg) { Swal.fire({ icon: 'error', title: msg || 'No se pudo contactar al servidor.' }); }
    function avisarPestana() { document.dispatchEvent(new CustomEvent('mb:albumes')); }

    /** "2026-10-09" → "9 oct 2026". */
    function fecha(f) {
        var m = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        var p = String(f || '').split('-');
        return p.length === 3 ? parseInt(p[2], 10) + ' ' + m[parseInt(p[1], 10) - 1] + ' ' + p[0] : '';
    }
    /** "2026-10-26T08:00" → "26 oct 2026 08:00". */
    function fechaHora(f) {
        var p = String(f || '').split('T');
        return p.length === 2 ? fecha(p[0]) + ' ' + p[1].slice(0, 5) : '';
    }
    function nombreSede(s) { return s === SIN_SEDE ? 'Sin sede (registrados antes)' : s; }

    var TIPOS = { evento: 'Evento', general: 'General', concurso: 'Concurso' };

    // ── Formulario ───────────────────────────────────────────────────────
    function tipoElegido() {
        var r = document.querySelector('input[name="alb_tipo"]:checked');
        return r ? r.value : 'evento';
    }

    function pintarTipo() {
        var tipo = tipoElegido(), concurso = tipo === 'concurso';
        $a('alb_cajaDia').hidden   = tipo !== 'evento';
        $a('alb_cajaDesde').hidden = tipo === 'evento';
        $a('alb_cajaHasta').hidden = tipo === 'evento';
        $a('alb_lblDesde').textContent = concurso ? 'Vigencia: desde' : 'Desde';
        $a('alb_lblHasta').textContent = concurso ? 'Vigencia: hasta' : 'Hasta';
        $a('alb_cajaConcurso').hidden = !concurso;
        // El concurso es sólo de empleados: sin fotos de invitados.
        $a('alb_filaInvitados').hidden = concurso;
        $a('alb_cajaInvitados').hidden = concurso || !$a('alb_conInvitados').checked;
        $a('alb_ayudaTipo').textContent = {
            general:  'Recibe fotos en su periodo, de 12:00 a 15:00 y de 18:00 a 06:00, menos los días de evento.',
            evento:   'Recibe fotos a cualquier hora durante <?php echo MB_EVENTO_HORAS; ?> horas, desde las 00:00 de su día.',
            concurso: ($a('alb_subeRrhh').checked
                        ? 'RRHH sube una foto por participante y cada empleado tiene <?php echo MB_CONCURSO_VOTOS . (MB_CONCURSO_VOTOS === 1 ? ' like' : ' likes'); ?>. '
                        : 'Cada empleado sube <?php echo MB_CONCURSO_FOTOS; ?> foto y tiene <?php echo MB_CONCURSO_VOTOS . (MB_CONCURSO_VOTOS === 1 ? ' like' : ' likes'); ?> para las de los demás. ')
                    + 'La subida y la votación van dentro de la vigencia; los likes se ven al cerrar la votación.'
        }[tipo];
    }
    ['alb_tipoEvento', 'alb_tipoGeneral', 'alb_tipoConcurso', 'alb_subeRrhh'].forEach(function (id) {
        $a(id).addEventListener('change', pintarTipo);
    });

    $a('alb_conInvitados').addEventListener('change', function () {
        $a('alb_cajaInvitados').hidden = !this.checked;
    });

    function pintarSedes(marcadas) {
        $a('alb_sedes').innerHTML = sedes.map(function (s, i) {
            var id = 'alb_sede' + i;
            return '<div class="custom-control custom-checkbox custom-control-inline">'
                + '<input type="checkbox" class="custom-control-input alb-sede" id="' + id + '" value="' + esc(s) + '"'
                + (marcadas.indexOf(s) >= 0 ? ' checked' : '') + '>'
                + '<label class="custom-control-label small" for="' + id + '">' + esc(nombreSede(s)) + '</label></div>';
        }).join('');
    }

    // ── Portada ──────────────────────────────────────────────────────────
    /**
     * La imagen se reduce aquí, antes de subirla: a 1200 px y JPEG, sobre
     * blanco (un PNG con fondo transparente, como un logo, quedaría negro en
     * JPEG). El servidor la vuelve a codificar de todos modos.
     */
    function reducirPortada(archivo) {
        return new Promise(function (resolver, fallar) {
            var url = URL.createObjectURL(archivo);
            var img = new Image();
            img.onload = function () {
                var escala = Math.min(1, 1200 / Math.max(img.naturalWidth, img.naturalHeight));
                var c = document.createElement('canvas');
                c.width  = Math.round(img.naturalWidth * escala);
                c.height = Math.round(img.naturalHeight * escala);
                var g = c.getContext('2d');
                g.fillStyle = '#fff';
                g.fillRect(0, 0, c.width, c.height);
                g.drawImage(img, 0, 0, c.width, c.height);
                URL.revokeObjectURL(url);
                c.toBlob(function (b) { b ? resolver(b) : fallar(); }, 'image/jpeg', 0.88);
            };
            img.onerror = function () { URL.revokeObjectURL(url); fallar(); };
            img.src = url;
        });
    }

    function soltarVista() {
        if (portada.vistaUrl) URL.revokeObjectURL(portada.vistaUrl);
        portada.vistaUrl = null;
    }

    function pintarPortada() {
        var src = portada.blob ? portada.vistaUrl : (!portada.quitar && portada.actual ? portada.actual : '');
        $a('alb_portadaVista').innerHTML = src ? '<img src="' + esc(src) + '" alt="">' : '<i class="far fa-images"></i>';
        $a('alb_quitarPortada').hidden = !src;
        $a('alb_ayudaPortada').textContent = portada.blob
            ? 'Se guarda al darle Guardar.'
            : (src ? '' : 'Sin portada propia: se ve la foto más reciente del álbum.');
    }

    function reiniciarPortada(actual) {
        soltarVista();
        portada = { actual: actual || '', blob: null, vistaUrl: null, quitar: false };
        $a('alb_portada').value = '';
        pintarPortada();
    }

    $a('alb_portada').addEventListener('change', function () {
        var archivo = this.files && this.files[0];
        this.value = '';
        if (!archivo) return;
        if (!/^image\//.test(archivo.type)) { error('Ese archivo no es una imagen. Sube un JPG, PNG o WebP.'); return; }
        reducirPortada(archivo).then(function (blob) {
            soltarVista();
            portada.blob = blob;
            portada.vistaUrl = URL.createObjectURL(blob);
            portada.quitar = false;
            pintarPortada();
        }).catch(function () { error('No pudimos abrir esa imagen. Prueba con otra.'); });
    });

    // Quitar una recién elegida regresa a la que ya tenía el álbum; si no
    // había una nueva, quita la del álbum (se borra al guardar).
    $a('alb_quitarPortada').addEventListener('click', function () {
        if (portada.blob) { soltarVista(); portada.blob = null; }
        else portada.quitar = true;
        pintarPortada();
    });

    function limpiarForm() {
        $a('alb_id').value = '';
        $a('alb_tituloForm').textContent = 'Nuevo álbum';
        $a('alb_titulo').value = '';
        $a('alb_tipoEvento').checked = true;
        $a('alb_dia').value = ''; $a('alb_desde').value = ''; $a('alb_hasta').value = '';
        ['alb_subidaDesde', 'alb_subidaHasta', 'alb_votacionDesde', 'alb_votacionHasta'].forEach(function (id) { $a(id).value = ''; });
        $a('alb_subeRrhh').checked = false;
        $a('alb_visible').checked = true;
        $a('alb_conInvitados').checked = false;
        $a('alb_cajaInvitados').hidden = true;
        $a('alb_invitados').value = '';
        pintarSedes([]);
        reiniciarPortada('');
        $a('alb_btnCancelar').hidden = true;
        pintarTipo();
    }
    $a('alb_btnCancelar').addEventListener('click', limpiarForm);

    function editar(id) {
        var a = lista.filter(function (x) { return x.id === id; })[0];
        if (!a) return;
        $a('alb_id').value = a.id;
        $a('alb_tituloForm').textContent = 'Editar «' + a.titulo + '»';
        $a('alb_titulo').value = a.titulo;
        $a('alb_tipo' + (TIPOS[a.tipo] || 'Evento')).checked = true;
        $a('alb_dia').value   = a.tipo === 'evento' ? a.desde : '';
        $a('alb_desde').value = a.tipo !== 'evento' ? a.desde : '';
        $a('alb_hasta').value = a.tipo !== 'evento' ? a.hasta : '';
        $a('alb_subidaDesde').value   = a.subida_desde;
        $a('alb_subidaHasta').value   = a.subida_hasta;
        $a('alb_votacionDesde').value = a.votacion_desde;
        $a('alb_votacionHasta').value = a.votacion_hasta;
        $a('alb_subeRrhh').checked = !!a.sube_rrhh;
        $a('alb_visible').checked = a.visible;
        $a('alb_conInvitados').checked = !!a.invitados;
        $a('alb_cajaInvitados').hidden = !a.invitados;
        $a('alb_invitados').value = a.invitados;
        pintarSedes(a.sedes);
        reiniciarPortada(a.portada);
        $a('alb_btnCancelar').hidden = false;
        pintarTipo();
        $a('alb_titulo').focus();
    }

    $a('alb_btnGuardar').addEventListener('click', function () {
        var boton = this;
        var tipo = tipoElegido();
        var conInv = tipo !== 'concurso' && $a('alb_conInvitados').checked;
        var datos = {
            id:      $a('alb_id').value || 0,
            titulo:  $a('alb_titulo').value.trim(),
            tipo:    tipo,
            dia:     $a('alb_dia').value,
            desde:   $a('alb_desde').value,
            hasta:   $a('alb_hasta').value,
            subida_desde:   $a('alb_subidaDesde').value,
            subida_hasta:   $a('alb_subidaHasta').value,
            votacion_desde: $a('alb_votacionDesde').value,
            votacion_hasta: $a('alb_votacionHasta').value,
            sube_rrhh: tipo === 'concurso' && $a('alb_subeRrhh').checked ? 1 : 0,
            visible: $a('alb_visible').checked ? 1 : 0,
            invitados: conInv ? $a('alb_invitados').value.trim() : '',
            sedes:   conInv ? Array.prototype.map.call(document.querySelectorAll('.alb-sede:checked'), function (c) { return c.value; }) : []
        };
        if (conInv && !datos.invitados) { error('Escribe el evento de oktoberMESS o desmarca las fotos de invitados.'); return; }
        if (portada.blob) datos.portada = new File([portada.blob], 'portada.jpg', { type: 'image/jpeg' });
        else if (portada.quitar) datos.quitar_portada = 1;
        boton.disabled = true;
        pedir('adm_guardar', datos).then(function (d) {
            boton.disabled = false;
            if (!d.success) { error(d.message); return; }
            Swal.fire({ icon: 'success', title: d.message, timer: 1400, showConfirmButton: false });
            limpiarForm();
            listar();
            avisarPestana();
        }).catch(function () { boton.disabled = false; error(); });
    });

    // ── Lista ────────────────────────────────────────────────────────────
    function fila(a) {
        var concurso = a.tipo === 'concurso';
        var fechas = esc(a.tipo === 'evento' ? fecha(a.desde) : fecha(a.desde) + ' → ' + fecha(a.hasta));
        if (concurso) {
            fechas += '<br><span class="text-muted">Subida: ' + esc(fechaHora(a.subida_desde) + ' → ' + fechaHora(a.subida_hasta)) + '</span>'
                    + '<br><span class="text-muted">Votación: ' + esc(fechaHora(a.votacion_desde) + ' → ' + fechaHora(a.votacion_hasta)) + '</span>';
        }
        var inv = a.invitados
            ? esc(a.invitados) + '<br><span class="text-muted">' + esc(a.sedes.length ? a.sedes.map(nombreSede).join(', ') : 'todas las sedes') + '</span>'
            : '<span class="text-muted">—</span>';
        var borrar = a.fotos > 0
            ? '<button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Tiene fotos: sólo se puede ocultar"><i class="fas fa-trash"></i></button>'
            : '<button type="button" class="btn btn-sm btn-outline-danger" data-borrar="' + a.id + '" title="Borrar"><i class="fas fa-trash"></i></button>';
        var ranking = concurso
            ? '<button type="button" class="btn btn-sm btn-outline-warning mr-1" data-ranking="' + a.id + '" title="Ranking"><i class="fas fa-trophy"></i></button>'
            : '';
        return '<tr' + (a.visible ? '' : ' class="text-muted"') + '>'
            + '<td>' + (a.activo ? '<i class="fas fa-circle text-success mr-1" style="font-size:.6rem;" title="Recibiendo fotos ahora"></i>' : '')
            + (a.portada ? '<img class="alb-mini mr-2" src="' + esc(a.portada) + '" alt="" title="Con portada propia">' : '')
            + '<strong>' + esc(a.titulo) + '</strong></td>'
            + '<td class="small">' + esc(TIPOS[a.tipo] || a.tipo) + (a.sube_rrhh ? '<br><span class="text-muted">Sube RRHH</span>' : '') + '</td>'
            + '<td class="small text-nowrap">' + fechas + '</td>'
            + '<td class="small">' + inv + '</td>'
            + '<td class="text-center">' + a.fotos
            + (concurso ? '<br><span class="small text-muted" title="Likes"><i class="fas fa-heart"></i> ' + a.likes + '</span>' : '') + '</td>'
            + '<td class="text-center"><div class="custom-control custom-switch d-inline-block">'
            + '<input type="checkbox" class="custom-control-input" id="alb_vis' + a.id + '" data-visible="' + a.id + '"' + (a.visible ? ' checked' : '') + '>'
            + '<label class="custom-control-label" for="alb_vis' + a.id + '"><span class="sr-only">Visible</span></label></div></td>'
            + '<td class="text-nowrap">' + ranking
            + '<button type="button" class="btn btn-sm btn-outline-primary mr-1" data-editar="' + a.id + '" title="Editar"><i class="fas fa-pen"></i></button>'
            + borrar + '</td></tr>';
    }

    function listar() {
        return pedir('adm_listar').then(function (d) {
            if (!d.success) { $a('alb_tabla').innerHTML = '<tr><td colspan="7" class="text-center text-danger small py-3">' + esc(d.message) + '</td></tr>'; return; }
            lista = d.albumes;
            sedes = d.sedes;
            $a('alb_listaEventosOkt').innerHTML = d.eventos_okt.map(function (e) { return '<option value="' + esc(e) + '">'; }).join('');
            if (!$a('alb_id').value) pintarSedes([]);
            $a('alb_tabla').innerHTML = lista.length
                ? lista.map(fila).join('')
                : '<tr><td colspan="7" class="text-center text-muted small py-3">Todavía no hay álbumes. Crea el primero arriba.</td></tr>';
        }).catch(function () {
            $a('alb_tabla').innerHTML = '<tr><td colspan="7" class="text-center text-danger small py-3">No se pudo contactar al servidor.</td></tr>';
        });
    }

    $a('alb_tabla').addEventListener('click', function (e) {
        var ed = e.target.closest('[data-editar]');
        if (ed) { editar(parseInt(ed.dataset.editar, 10)); return; }
        // El ranking es otro modal (inicio.php): primero se cierra éste, para
        // no encimar dos modales de Bootstrap.
        var rk = e.target.closest('[data-ranking]');
        if (rk) {
            var idRanking = parseInt(rk.dataset.ranking, 10);
            $('#modalAlbumes').one('hidden.bs.modal', function () {
                document.dispatchEvent(new CustomEvent('mb:ranking', { detail: { id: idRanking } }));
            }).modal('hide');
            return;
        }
        var br = e.target.closest('[data-borrar]');
        if (br) {
            var id = parseInt(br.dataset.borrar, 10);
            var a = lista.filter(function (x) { return x.id === id; })[0];
            Swal.fire({
                icon: 'warning', title: '¿Borrar «' + (a ? a.titulo : '') + '»?',
                text: 'No tiene fotos de empleados. Se borra del todo.',
                showCancelButton: true, confirmButtonText: 'Sí, borrar', cancelButtonText: 'Cancelar', confirmButtonColor: '#d33'
            }).then(function (r) {
                if (!r.isConfirmed) return;
                pedir('adm_borrar', { id: id }).then(function (d) {
                    if (!d.success) { error(d.message); return; }
                    if (String(id) === $a('alb_id').value) limpiarForm();
                    listar();
                    avisarPestana();
                }).catch(function () { error(); });
            });
        }
    });

    $a('alb_tabla').addEventListener('change', function (e) {
        var sw = e.target.closest('[data-visible]');
        if (!sw) return;
        pedir('adm_visible', { id: sw.dataset.visible, visible: sw.checked ? 1 : 0 }).then(function (d) {
            if (!d.success) { sw.checked = !sw.checked; error(d.message); return; }
            listar();
            avisarPestana();
        }).catch(function () { sw.checked = !sw.checked; error(); });
    });

    // jQuery y Bootstrap se cargan al final de inicio.php, después de este modal.
    document.addEventListener('DOMContentLoaded', function () {
        $('#modalAlbumes').on('show.bs.modal', function () { limpiarForm(); listar(); });
    });
})();
</script>

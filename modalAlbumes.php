<?php
/**
 * modalAlbumes.php — "Administrar álbumes" (engrane de la pestaña Fotos).
 *
 * Crear, editar, ocultar y borrar los álbumes de fotos. Sólo la vista: todo
 * se guarda vía acciones_album.php (acciones adm_*, en
 * includes/album_admin.php), que vuelve a revisar el permiso en cada una.
 *
 * inicio.php lo incluye sólo si $adminAlbum (de momento, BI). Al guardar
 * avisa con el evento 'mb:albumes' para que la pestaña recargue sus portadas.
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
                                <small class="text-muted d-block" id="alb_ayudaTipo"></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <!-- Evento: un día -->
                            <div class="col-md-4 mb-2" id="alb_cajaDia">
                                <label class="small mb-1" for="alb_dia">Día del evento</label>
                                <input type="date" class="form-control form-control-sm" id="alb_dia">
                            </div>
                            <!-- General: periodo -->
                            <div class="col-md-4 mb-2" id="alb_cajaDesde" hidden>
                                <label class="small mb-1" for="alb_desde">Desde</label>
                                <input type="date" class="form-control form-control-sm" id="alb_desde">
                            </div>
                            <div class="col-md-4 mb-2" id="alb_cajaHasta" hidden>
                                <label class="small mb-1" for="alb_hasta">Hasta</label>
                                <input type="date" class="form-control form-control-sm" id="alb_hasta">
                            </div>
                            <div class="col-md-4 mb-2 d-flex align-items-end">
                                <div class="custom-control custom-switch mb-1">
                                    <input type="checkbox" class="custom-control-input" id="alb_visible" checked>
                                    <label class="custom-control-label small" for="alb_visible">Visible en la pestaña Fotos</label>
                                </div>
                            </div>
                        </div>

                        <!-- Fotos de invitados de oktoberMESS (sólo lectura en messbook) -->
                        <div class="custom-control custom-checkbox mb-2">
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

                        <div class="text-right">
                            <button type="button" class="btn btn-sm btn-secondary" id="alb_btnCancelar" hidden>Cancelar edición</button>
                            <button type="button" class="btn btn-sm btn-primary" id="alb_btnGuardar">
                                <i class="fas fa-save mr-1"></i>Guardar
                            </button>
                        </div>
                    </div>
                </div>

                <p class="small text-muted mb-2">
                    En cada momento sólo un álbum recibe fotos: el de <strong>evento</strong> en su día (todo el día, hasta las 12:00 del
                    siguiente) y, fuera de los eventos, el <strong>general</strong> en su periodo (de 12:00 a 15:00 y de 18:00 a 06:00).
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
    function nombreSede(s) { return s === SIN_SEDE ? 'Sin sede (registrados antes)' : s; }

    // ── Formulario ───────────────────────────────────────────────────────
    function tipoElegido() { return $a('alb_tipoGeneral').checked ? 'general' : 'evento'; }

    function pintarTipo() {
        var general = tipoElegido() === 'general';
        $a('alb_cajaDia').hidden   = general;
        $a('alb_cajaDesde').hidden = !general;
        $a('alb_cajaHasta').hidden = !general;
        $a('alb_ayudaTipo').textContent = general
            ? 'Recibe fotos en su periodo, de 12:00 a 15:00 y de 18:00 a 06:00, menos los días de evento.'
            : 'Recibe fotos todo su día, desde las 00:00 hasta las 12:00 del día siguiente.';
    }
    $a('alb_tipoEvento').addEventListener('change', pintarTipo);
    $a('alb_tipoGeneral').addEventListener('change', pintarTipo);

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

    function limpiarForm() {
        $a('alb_id').value = '';
        $a('alb_tituloForm').textContent = 'Nuevo álbum';
        $a('alb_titulo').value = '';
        $a('alb_tipoEvento').checked = true;
        $a('alb_dia').value = ''; $a('alb_desde').value = ''; $a('alb_hasta').value = '';
        $a('alb_visible').checked = true;
        $a('alb_conInvitados').checked = false;
        $a('alb_cajaInvitados').hidden = true;
        $a('alb_invitados').value = '';
        pintarSedes([]);
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
        $a(a.tipo === 'general' ? 'alb_tipoGeneral' : 'alb_tipoEvento').checked = true;
        $a('alb_dia').value   = a.tipo === 'evento' ? a.desde : '';
        $a('alb_desde').value = a.tipo === 'general' ? a.desde : '';
        $a('alb_hasta').value = a.tipo === 'general' ? a.hasta : '';
        $a('alb_visible').checked = a.visible;
        $a('alb_conInvitados').checked = !!a.invitados;
        $a('alb_cajaInvitados').hidden = !a.invitados;
        $a('alb_invitados').value = a.invitados;
        pintarSedes(a.sedes);
        $a('alb_btnCancelar').hidden = false;
        pintarTipo();
        $a('alb_titulo').focus();
    }

    $a('alb_btnGuardar').addEventListener('click', function () {
        var boton = this;
        var conInv = $a('alb_conInvitados').checked;
        var datos = {
            id:      $a('alb_id').value || 0,
            titulo:  $a('alb_titulo').value.trim(),
            tipo:    tipoElegido(),
            dia:     $a('alb_dia').value,
            desde:   $a('alb_desde').value,
            hasta:   $a('alb_hasta').value,
            visible: $a('alb_visible').checked ? 1 : 0,
            invitados: conInv ? $a('alb_invitados').value.trim() : '',
            sedes:   conInv ? Array.prototype.map.call(document.querySelectorAll('.alb-sede:checked'), function (c) { return c.value; }) : []
        };
        if (conInv && !datos.invitados) { error('Escribe el evento de oktoberMESS o desmarca las fotos de invitados.'); return; }
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
        var fechas = a.tipo === 'evento' ? fecha(a.desde) : fecha(a.desde) + ' → ' + fecha(a.hasta);
        var inv = a.invitados
            ? esc(a.invitados) + '<br><span class="text-muted">' + esc(a.sedes.length ? a.sedes.map(nombreSede).join(', ') : 'todas las sedes') + '</span>'
            : '<span class="text-muted">—</span>';
        var borrar = a.fotos > 0
            ? '<button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Tiene fotos: sólo se puede ocultar"><i class="fas fa-trash"></i></button>'
            : '<button type="button" class="btn btn-sm btn-outline-danger" data-borrar="' + a.id + '" title="Borrar"><i class="fas fa-trash"></i></button>';
        return '<tr' + (a.visible ? '' : ' class="text-muted"') + '>'
            + '<td>' + (a.activo ? '<i class="fas fa-circle text-success mr-1" style="font-size:.6rem;" title="Recibiendo fotos ahora"></i>' : '')
            + '<strong>' + esc(a.titulo) + '</strong></td>'
            + '<td class="small">' + (a.tipo === 'evento' ? 'Evento' : 'General') + '</td>'
            + '<td class="small text-nowrap">' + esc(fechas) + '</td>'
            + '<td class="small">' + inv + '</td>'
            + '<td class="text-center">' + a.fotos + '</td>'
            + '<td class="text-center"><div class="custom-control custom-switch d-inline-block">'
            + '<input type="checkbox" class="custom-control-input" id="alb_vis' + a.id + '" data-visible="' + a.id + '"' + (a.visible ? ' checked' : '') + '>'
            + '<label class="custom-control-label" for="alb_vis' + a.id + '"><span class="sr-only">Visible</span></label></div></td>'
            + '<td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary mr-1" data-editar="' + a.id + '" title="Editar"><i class="fas fa-pen"></i></button>'
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

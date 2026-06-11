<!-- ===== MODAL: SOLICITAR VACACIONES ===== -->
<div class="modal fade" id="modalSolicitarVac" tabindex="-1" role="dialog" aria-labelledby="modalSolicitarVacLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSolicitarVacLabel"><i class="fas fa-umbrella-beach mr-2"></i>Solicitar vacaciones</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body modal-body-scroll">
                <form id="mv_formSolicitud">
                    <input type="hidden" id="mv_opIncidencia" value="1">

                    <!-- Solicitante -->
                    <div class="form-group" id="mv_divSolicita">
                        <label class="small font-weight-bold">Solicita para</label>
                        <select id="mv_solicita" class="form-control form-control-sm" style="width:100%;"></select>
                        <small class="text-muted">Puedes solicitar para los colaboradores a tu cargo.</small>
                    </div>

                    <!-- Tipo de incidencia -->
                    <div class="form-group">
                        <label class="small font-weight-bold d-block">Tipo de incidencia</label>
                        <div class="btn-group btn-group-toggle flex-wrap" data-toggle="buttons">
                            <label class="btn btn-sm active">
                                <input type="radio" name="mv_tipo" value="1" checked onchange="mv_setTipo(1)"> Vacaciones
                            </label>
                        </div>
                    </div>

                    <!-- Periodos -->
                    <label class="small font-weight-bold d-block">Días fuera de jornada laboral</label>
                    <div id="mv_renglones" class="mb-2"></div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm mv-btn-add" onclick="mv_agregarRenglon()"><i class="fas fa-plus"></i></button>
                        <button type="button" class="btn btn-sm mv-btn-del" onclick="mv_eliminarRenglon()"><i class="fas fa-trash"></i></button>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold">Indicar fechas cuando los días no sean un periodo corrido</label>
                            <textarea id="mv_notas" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold">Comentarios</label>
                            <textarea id="mv_comentarios" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="text-center mt-2">
                        <h6>Autoriza: <span id="mv_autorizaJefe" class="font-weight-bold" style="color:var(--accent);"></span></h6>
                        <button type="button" id="mv_btnSolicitar" class="btn mv-btn-accent px-4 shadow-sm" onclick="mv_enviarSolicitud()">
                            <i class="fas fa-paper-plane mr-1"></i> Solicitar
                        </button>
                        <p id="mv_mensaje" class="small text-muted mt-2 mb-0"></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: ESTATUS DE MIS SOLICITUDES ===== -->
<div class="modal fade" id="modalEstatusVac" tabindex="-1" role="dialog" aria-labelledby="modalEstatusVacLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEstatusVacLabel"><i class="fas fa-list-ul mr-2"></i>Estatus de mis solicitudes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body modal-body-scroll">
                <ul class="nav nav-tabs" id="mv_tabsEstatus" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mv_tabPorAut-tab" data-toggle="tab" data-target="#mv_tabPorAut" type="button" role="tab">Por autorizar</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mv_tabAut-tab" data-toggle="tab" data-target="#mv_tabAut" type="button" role="tab">Autorizadas</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mv_tabCan-tab" data-toggle="tab" data-target="#mv_tabCan" type="button" role="tab">Canceladas</button>
                    </li>
                </ul>
                <div class="tab-content pt-3" id="mv_tabsEstatusContent">
                    <div class="tab-pane fade show active" id="mv_tabPorAut" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="thead-light small text-uppercase">
                                    <tr><th>Tipo / Solicitud</th><th>Días / Periodo</th><th>Notas</th><th>Estatus</th></tr>
                                </thead>
                                <tbody id="mv_bodyPorAut"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="mv_tabAut" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="thead-light small text-uppercase">
                                    <tr><th>Tipo</th><th>Solicitud</th><th>Días</th><th>Periodo</th><th>Notas</th><th>Nota jefe</th><th>Estatus</th></tr>
                                </thead>
                                <tbody id="mv_bodyAut"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="mv_tabCan" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="thead-light small text-uppercase">
                                    <tr><th>Tipo</th><th>Solicitud</th><th>Días</th><th>Periodo</th><th>Notas</th><th>Nota jefe</th><th>Estatus</th></tr>
                                </thead>
                                <tbody id="mv_bodyCan"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ====== Mis Vacaciones: Solicitar + Estatus (reusan backends de ../incidencias/) ====== */

var MV_INC = '../incidencias/';
var mv_renglonCount = 0;

function mv_sync()    { if (typeof syncCookieNoEmpleado === 'function') syncCookieNoEmpleado(); }
function mv_cookie(n) { return (typeof getCookie === 'function') ? getCookie(n) : undefined; }
function mv_esc(v)    { return $('<div>').text(v == null ? '' : v).html(); }

function mv_badgeTipo(t) {
    if (t == 1) return '<span class="badge badge-success">Vacaciones</span>';
    if (t == 2) return '<span class="badge badge-info">Permiso sin goce</span>';
    if (t == 3) return '<span class="badge badge-primary">Permiso con goce</span>';
    return '';
}
function mv_badgeEstatus(e) {
    if (e == 1) return '<span class="badge badge-warning">Por autorizar</span>';
    if (e == 2) return '<span class="badge badge-success">Autorizada</span>';
    if (e == 3) return '<span class="badge badge-danger">Rechazada</span>';
    return '';
}
function mv_badgeRH(r) {
    if (r == 1) return '<span class="badge badge-warning">Por autorizar RH</span>';
    if (r == 2) return '<span class="badge badge-success">Autorizada RH</span>';
    if (r == 3) return '<span class="badge badge-danger">Rechazada RH</span>';
    return '';
}

/* ----------------------- Modal Solicitar ----------------------- */

function mv_setTipo(ti) {
    $('#mv_opIncidencia').val(ti);
    if (ti == 1) {
        var noEmp = $('#mv_solicita').val() || mv_cookie('noEmpleadoL');
        mv_avisoVehiculo(noEmp);
    }
}

function mv_avisoVehiculo(noEmp) {
    if (!noEmp) return;
    $.ajax({
        url: MV_INC + 'getInfoLoginMaster.php',
        method: 'POST', dataType: 'json',
        data: { accion: 'getPlaca', noEmpleado: noEmp }
    }).done(function (data) {
        if (data && data.success === true) {
            Swal.fire({
                title: '¡Aviso importante!',
                html: 'El colaborador tiene un vehículo asignado con placa <b>' + mv_esc(data.placa) + '</b>.<br><br>' +
                      'Durante los días que <b>no labore</b>, deberá dejar el vehículo en las instalaciones de la empresa.',
                icon: 'warning', confirmButtonText: 'Entendido', confirmButtonColor: '#3085d6'
            });
        }
    });
}

function mv_renglonHTML(n) {
    return '<div class="form-row align-items-end mv-renglon mb-2" id="mv_renglon-' + n + '">' +
           '  <div class="col-5"><label class="small mb-0">Inicio</label>' +
           '    <input type="date" class="form-control form-control-sm" id="mv_fi-' + n + '" onchange="mv_dias(' + n + ')" required></div>' +
           '  <div class="col-5"><label class="small mb-0">Término</label>' +
           '    <input type="date" class="form-control form-control-sm" id="mv_ff-' + n + '" onchange="mv_dias(' + n + ')" required></div>' +
           '  <div class="col-2"><label class="small mb-0">Días</label>' +
           '    <input type="number" class="form-control form-control-sm" id="mv_nd-' + n + '" readonly></div>' +
           '</div>';
}

function mv_agregarRenglon() {
    mv_renglonCount++;
    $('#mv_renglones').append(mv_renglonHTML(mv_renglonCount));
}

function mv_eliminarRenglon() {
    if (mv_renglonCount > 1) {
        $('#mv_renglon-' + mv_renglonCount).remove();
        mv_renglonCount--;
    } else {
        Swal.fire('Atención', 'Debe capturar al menos un periodo.', 'warning');
    }
}

// Cuenta días hábiles (lun-vie) entre las dos fechas, inclusive.
function mv_dias(n) {
    var iniStr = $('#mv_fi-' + n).val();
    var finStr = $('#mv_ff-' + n).val();
    if (!iniStr || !finStr) return;
    var pi = iniStr.split('-'), pf = finStr.split('-');
    var desde = new Date(pi[0], pi[1] - 1, pi[2]);
    var hasta = new Date(pf[0], pf[1] - 1, pf[2]);
    var cont = 0, t = new Date(desde);
    while (t <= hasta) {
        var d = t.getDay();
        if (d !== 0 && d !== 6) cont++;
        t.setDate(t.getDate() + 1);
    }
    $('#mv_nd-' + n).val(cont);
}

function mv_cargarSolicita() {
    mv_sync();
    $.ajax({
        url: MV_INC + 'acciones_nuevasolicitud.php',
        method: 'POST', dataType: 'json',
        data: { accion: 'empleadosSolicita' }
    }).done(function (data) {
        var $sel = $('#mv_solicita');
        if ($.fn.select2 && $sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        $sel.empty();
        (data || []).forEach(function (u) {
            $sel.append($('<option></option>').attr('value', u.noEmpleado).text(u.nombre));
        });
        // Autoselecciona al usuario en sesión.
        var yo = mv_cookie('noEmpleadoL');
        if (yo) $sel.val(yo);
        // Si sólo se puede solicitar para uno mismo, oculta el selector.
        $('#mv_divSolicita').toggle((data || []).length > 1);
        if ($.fn.select2) {
            $sel.select2({ width: '100%', dropdownParent: $('#modalSolicitarVac') });
        }
    });
}

function mv_cargarJefe() {
    mv_sync();
    $.ajax({
        url: MV_INC + 'acciones_nuevasolicitud.php',
        method: 'POST', dataType: 'json',
        data: { accion: 'jefeAutoriza' }
    }).done(function (data) {
        $('#mv_autorizaJefe').text((data && data.jefe) ? data.jefe : '—');
    });
}

function mv_resetSolicitud() {
    $('#mv_opIncidencia').val(1);
    $('#mv_formSolicitud .btn-group-toggle .btn').removeClass('active');
    $('#mv_formSolicitud input[name="mv_tipo"]').prop('checked', false);
    $('#mv_formSolicitud input[name="mv_tipo"][value="1"]').prop('checked', true)
        .closest('.btn').addClass('active');
    $('#mv_notas').val('');
    $('#mv_comentarios').val('');
    $('#mv_mensaje').text('');
    mv_renglonCount = 0;
    $('#mv_renglones').empty();
    mv_agregarRenglon();
}

function mv_enviarSolicitud() {
    var solicita     = $('#mv_solicita').val() || mv_cookie('noEmpleadoL');
    var opIncidencia = $('#mv_opIncidencia').val();

    if (!solicita) {
        Swal.fire('Atención', 'No se identificó al solicitante.', 'warning');
        return;
    }
    if (!opIncidencia) {
        Swal.fire('Atención', 'Selecciona el tipo de incidencia.', 'warning');
        return;
    }

    var periodos = [];
    var faltan = false;
    $('#mv_renglones .mv-renglon').each(function () {
        var fi = $(this).find('input[id^="mv_fi-"]').val();
        var ff = $(this).find('input[id^="mv_ff-"]').val();
        var nd = $(this).find('input[id^="mv_nd-"]').val();
        if (!fi || !ff) { faltan = true; return; }
        periodos.push({ fechaInicial: fi, fechaFinal: ff, noDias: nd });
    });

    if (faltan || periodos.length === 0) {
        Swal.fire('Atención', 'Captura las fechas de inicio y término de cada periodo.', 'warning');
        return;
    }

    mv_sync();
    $('#mv_btnSolicitar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...');

    $.ajax({
        url: MV_INC + 'acciones_nuevasolicitud.php',
        method: 'POST', dataType: 'json',
        data: {
            accion: 'agregarSolicitud',
            opIncidencia: opIncidencia,
            notas: $('#mv_notas').val(),
            comentarios: $('#mv_comentarios').val(),
            solicita: solicita,
            periodos: periodos
        }
    }).done(function (data) {
        if (!data || data.success === false) {
            Swal.fire('Error', 'La solicitud no se pudo procesar.', 'error');
            return;
        }
        var idRef = (data && data.id_registro_referencia) ? parseInt(data.id_registro_referencia, 10) : 0;

        // Notifica al jefe (no bloqueante).
        $.ajax({
            url: MV_INC + 'acciones_notificaciones.php',
            method: 'POST', dataType: 'json',
            data: { accion: 'registrarNotificacionVacaciones', solicita: solicita, id_registro_referencia: idRef }
        }).always(function () {
            Swal.fire('¡Listo!', 'La solicitud se procesó con éxito.', 'success').then(function () {
                $('#modalSolicitarVac').modal('hide');
                if (typeof infoEmpleado === 'function') infoEmpleado();
                if (typeof calendarVacaciones !== 'undefined' && calendarVacaciones) calendarVacaciones.refetchEvents();
            });
        });
    }).fail(function () {
        Swal.fire('Error', 'La solicitud no se pudo procesar.', 'error');
    }).always(function () {
        $('#mv_btnSolicitar').prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Solicitar');
    });
}

/* ----------------------- Modal Estatus ----------------------- */

function mv_cargarEstatus() {
    mv_sync();
    $('#mv_bodyPorAut, #mv_bodyAut, #mv_bodyCan')
        .html('<tr><td colspan="7" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando...</td></tr>');

    $.ajax({
        url: MV_INC + 'acciones_solicitudestatus.php',
        method: 'POST', dataType: 'json',
        data: { accion: 'listar' }
    }).done(function (data) {
        if (!data || data.success !== true) {
            $('#mv_bodyPorAut, #mv_bodyAut, #mv_bodyCan')
                .html('<tr><td colspan="7" class="text-center text-danger py-3">No se pudo cargar la información.</td></tr>');
            return;
        }
        mv_renderPorAut(data.porAutorizar || []);
        mv_renderAut(data.autorizadas || []);
        mv_renderCan(data.canceladas || []);
    }).fail(function () {
        $('#mv_bodyPorAut, #mv_bodyAut, #mv_bodyCan')
            .html('<tr><td colspan="7" class="text-center text-danger py-3">Error de conexión.</td></tr>');
    });
}

function mv_renderPorAut(filas) {
    var html = '';
    filas.forEach(function (r) {
        html += '<tr>' +
            '<td>' + mv_badgeTipo(r.tipo) + '<br><span class="small text-muted">' + mv_esc(r.fesolicitud) + '</span></td>' +
            '<td><span class="badge badge-dark">' + mv_esc(r.dias) + ' días</span><br><span class="small">' + mv_esc(r.feinicio) + ' - ' + mv_esc(r.fefin) + '</span></td>' +
            '<td class="small">' + mv_esc(r.notasempleado) + '</td>' +
            '<td>' + mv_badgeEstatus(r.estatus) + ' ' + mv_badgeRH(r.autorizaRH) + '</td>' +
            '</tr>';
    });
    $('#mv_bodyPorAut').html(html || '<tr><td colspan="4" class="text-center text-muted py-3">Sin solicitudes por autorizar.</td></tr>');
}

function mv_renderAut(filas) {
    var html = '';
    filas.forEach(function (r) {
        html += '<tr>' +
            '<td>' + mv_badgeTipo(r.tipo) + '</td>' +
            '<td class="small">' + mv_esc(r.fesolicitud) + '</td>' +
            '<td>' + mv_esc(r.dias) + '</td>' +
            '<td class="small">' + mv_esc(r.feinicio) + ' - ' + mv_esc(r.fefin) + '</td>' +
            '<td class="small">' + mv_esc(r.notasempleado) + '</td>' +
            '<td class="small">' + mv_esc(r.notajefe) + '</td>' +
            '<td>' + mv_badgeEstatus(r.estatus) + '<br>' + mv_badgeRH(r.autorizaRH) + '</td>' +
            '</tr>';
    });
    $('#mv_bodyAut').html(html || '<tr><td colspan="7" class="text-center text-muted py-3">Sin solicitudes autorizadas.</td></tr>');
}

function mv_renderCan(filas) {
    var html = '';
    filas.forEach(function (r) {
        html += '<tr>' +
            '<td>' + mv_badgeTipo(r.tipo) + '</td>' +
            '<td class="small">' + mv_esc(r.fesolicitud) + '</td>' +
            '<td>' + mv_esc(r.dias) + '</td>' +
            '<td class="small">' + mv_esc(r.feinicio) + ' - ' + mv_esc(r.fefin) + '</td>' +
            '<td class="small">' + mv_esc(r.notasempleado) + '</td>' +
            '<td class="small">' + mv_esc(r.notajefe) + '</td>' +
            '<td><span class="badge badge-danger">Cancelada</span></td>' +
            '</tr>';
    });
    $('#mv_bodyCan').html(html || '<tr><td colspan="7" class="text-center text-muted py-3">Sin solicitudes canceladas.</td></tr>');
}

/* ----------------------- Binders (en load: jQuery ya cargado) ----------------------- */

window.addEventListener('load', function () {
    if (typeof window.jQuery === 'undefined') return;

    $('#modalSolicitarVac').on('show.bs.modal', function () {
        mv_resetSolicitud();
        mv_cargarSolicita();
        mv_cargarJefe();
    });
    $('#modalEstatusVac').on('show.bs.modal', mv_cargarEstatus);
});
</script>

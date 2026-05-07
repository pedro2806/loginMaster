<?php
//
// Cuando se llama vía AJAX (POST con accion as_*), resuelve y termina aquí.
// Cuando se incluye desde inicio.php, renderiza el modal.
//
$_as_permitidos  = [276, 183, 523];
$_as_base        = ['divIncidencias', 'divVacaciones', 'divControlVehicular', 'divCapacitacion'];

if (!empty($_POST['accion']) && strpos($_POST['accion'], 'as_') === 0) {
    header('Content-Type: application/json');
    include '../incidencias/conn.php';

    $accion    = $_POST['accion'];
    $as_sesion = intval($_COOKIE['noEmpleadoL'] ?? 0);

    if (!in_array($as_sesion, $_as_permitidos)) {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }

    // Cargar sistemas únicos del catálogo
    if ($accion === 'as_cargar_sistemas') {
        $res      = $conn->query("SELECT DISTINCT sistema FROM accesos ORDER BY sistema ASC");
        $sistemas = [];
        while ($row = $res->fetch_assoc()) $sistemas[] = $row['sistema'];
        echo json_encode($sistemas);
        exit;
    }

    // Cargar todos los usuarios activos
    if ($accion === 'as_cargar_usuarios') {
        $res      = $conn->query("SELECT noEmpleado, nombre FROM usuarios WHERE estatus = 1 ORDER BY nombre ASC");
        $usuarios = [];
        while ($row = $res->fetch_assoc()) $usuarios[] = $row;
        echo json_encode($usuarios);
        exit;
    }

    // Listar accesos con filtros opcionales
    if ($accion === 'as_listar_accesos') {
        $as_sistema = trim($_POST['sistema']      ?? '');
        $as_noEmp   = intval($_POST['noEmpleado'] ?? 0);

        $sql    = "SELECT ac.id, ac.sistema, ac.noEmpleado, ac.estatus,
                          IFNULL(u.nombre, ac.noEmpleado) AS nombre
                   FROM accesos ac
                   LEFT JOIN usuarios u ON ac.noEmpleado = u.noEmpleado
                   WHERE 1=1";
        $params = [];
        $types  = "";

        if ($as_sistema !== '') { $sql .= " AND ac.sistema = ?";    $params[] = $as_sistema; $types .= "s"; }
        if ($as_noEmp   >  0)   { $sql .= " AND ac.noEmpleado = ?"; $params[] = $as_noEmp;   $types .= "i"; }
        $sql .= " ORDER BY ac.estatus DESC, ac.sistema, u.nombre ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        echo json_encode($rows);
        exit;
    }

    // Listar accesos de los sistemas base (filtro opcional por usuario)
    if ($accion === 'as_listar_accesos_base') {
        global $_as_base;
        $as_noEmp    = intval($_POST['noEmpleado'] ?? 0);
        $placeholders = implode(',', array_fill(0, count($_as_base), '?'));

        $sql    = "SELECT ac.id, ac.sistema, ac.noEmpleado, ac.estatus,
                          IFNULL(u.nombre, ac.noEmpleado) AS nombre
                   FROM accesos ac
                   LEFT JOIN usuarios u ON ac.noEmpleado = u.noEmpleado
                   WHERE ac.sistema IN ($placeholders)";
        $params = $_as_base;
        $types  = str_repeat('s', count($_as_base));

        if ($as_noEmp > 0) { $sql .= " AND ac.noEmpleado = ?"; $params[] = $as_noEmp; $types .= "i"; }
        $sql .= " ORDER BY ac.estatus DESC, ac.sistema, u.nombre ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        echo json_encode($rows);
        exit;
    }

    // Agregar acceso individual (insert o reactiva si estatus = 0)
    if ($accion === 'as_agregar_acceso') {
        $as_sistema = trim($_POST['sistema']      ?? '');
        $as_noEmp   = intval($_POST['noEmpleado'] ?? 0);

        if ($as_sistema === '' || $as_noEmp <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']); exit;
        }

        $stmt = $conn->prepare("SELECT id, estatus FROM accesos WHERE sistema = ? AND noEmpleado = ?");
        $stmt->bind_param("si", $as_sistema, $as_noEmp);
        $stmt->execute();
        $existente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existente) {
            if ($existente['estatus'] == 1) {
                echo json_encode(['success' => false, 'message' => 'El usuario ya tiene acceso a este sistema']); exit;
            }
            $stmt = $conn->prepare("UPDATE accesos SET estatus = 1 WHERE id = ?");
            $stmt->bind_param("i", $existente['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO accesos (sistema, noEmpleado, estatus) VALUES (?, ?, 1)");
            $stmt->bind_param("si", $as_sistema, $as_noEmp);
        }
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Acceso agregado' : 'Error al guardar']);
        exit;
    }

    // Agregar los 4 sistemas base de un solo golpe
    if ($accion === 'as_agregar_accesos_base') {
        global $_as_base;
        $as_noEmp = intval($_POST['noEmpleado'] ?? 0);

        if ($as_noEmp <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']); exit;
        }

        $agregados = 0;
        foreach ($_as_base as $sis) {
            $stmt = $conn->prepare("SELECT id, estatus FROM accesos WHERE sistema = ? AND noEmpleado = ?");
            $stmt->bind_param("si", $sis, $as_noEmp);
            $stmt->execute();
            $existente = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existente && $existente['estatus'] == 1) continue; // ya activo, saltar

            if ($existente) {
                $stmt = $conn->prepare("UPDATE accesos SET estatus = 1 WHERE id = ?");
                $stmt->bind_param("i", $existente['id']);
            } else {
                $stmt = $conn->prepare("INSERT INTO accesos (sistema, noEmpleado, estatus) VALUES (?, ?, 1)");
                $stmt->bind_param("si", $sis, $as_noEmp);
            }
            if ($stmt->execute()) $agregados++;
            $stmt->close();
        }

        if ($agregados > 0) {
            echo json_encode(['success' => true,  'message' => "$agregados acceso(s) base agregado(s)"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'El usuario ya tiene todos los accesos base activos']);
        }
        exit;
    }

    // Desactivar acceso (borrado lógico)
    if ($accion === 'as_eliminar_acceso') {
        $as_id = intval($_POST['id'] ?? 0);
        if ($as_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
        }
        $stmt = $conn->prepare("UPDATE accesos SET estatus = 0 WHERE id = ?");
        $stmt->bind_param("i", $as_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Acceso desactivado' : 'Error al desactivar']);
        exit;
    }

    // Reactivar acceso
    if ($accion === 'as_reactivar_acceso') {
        $as_id = intval($_POST['id'] ?? 0);
        if ($as_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
        }
        $stmt = $conn->prepare("UPDATE accesos SET estatus = 1 WHERE id = ?");
        $stmt->bind_param("i", $as_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Acceso reactivado' : 'Error al reactivar']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

//
// Renderizado del modal (solo cuando es incluido desde inicio.php)
//
$_as_actual = intval($_COOKIE['noEmpleadoL'] ?? 0);
if (!in_array($_as_actual, $_as_permitidos)) return;
?>

<div class="modal fade" id="modalAccesoSistemas" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-left-info shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-desktop"></i> Acceso a Sistemas
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <!-- Filtros -->
                <div class="row mb-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-dark mb-1">Sistema</label>
                        <select id="as_selectSistema" class="form-control form-control-sm">
                            <option value="">Selecciona un sistema</option>
                        </select>
                        <textarea id="as_textareaSistemaNuevo" class="form-control form-control-sm mt-2 d-none" rows="2" placeholder="Captura el nombre del nuevo sistema"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-dark mb-1">Usuario</label>
                        <select id="as_selectUsuario" class="form-control form-control-sm">
                            <option value="">Selecciona usuario</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-success btn-sm btn-block shadow-sm"
                                id="as_btnAgregar" onclick="as_agregarAcceso()" disabled>
                            <i class="fas fa-plus-circle"></i> Agregar Acceso
                        </button>
                    </div>
                </div>

                <!-- Tabla de accesos -->
                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                    <table id="as_dataTableAccesos" class="table table-sm table-bordered table-hover">
                        <thead class="thead-dark text-uppercase small font-weight-bold">
                            <tr>
                                <th class="text-center">Sistema</th>
                                <th class="text-center">Usuario</th>
                                <th class="text-center">Estatus</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="as_tablaAccesos">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Selecciona un sistema o usuario para ver los accesos
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
const AS_URL           = 'modalAccesoSistemas.php';
const AS_NUEVO_SISTEMA = '__nuevo_sistema__';
const AS_SISTEMAS_BASE = '__sistemas_base__';

let _as_dt = null; // Instancia DataTable — única fuente de verdad

function as_esNuevo(v)        { return v === AS_NUEVO_SISTEMA; }
function as_esSistemasBase(v) { return v === AS_SISTEMAS_BASE; }

function as_obtenerSistema() {
    const val = $('#as_selectSistema').val();
    if (as_esNuevo(val)) return ($('#as_textareaSistemaNuevo').val() || '').trim();
    return (val || '').trim();
}

function as_actualizarBtnAgregar() {
    const val    = $('#as_selectSistema').val();
    const esBase = as_esSistemasBase(val);
    const sistema = as_obtenerSistema();
    const ok = val && $('#as_selectUsuario').val() && (esBase || sistema !== '');
    $('#as_btnAgregar')
        .prop('disabled', !ok)
        .html(esBase
            ? '<i class="fas fa-layer-group"></i> Agregar Sistemas Base'
            : '<i class="fas fa-plus-circle"></i> Agregar Acceso');
}

function as_destruirDataTable() {
    if (_as_dt) {
        try { _as_dt.destroy(); } catch (e) {}
        _as_dt = null;
    }
}

function as_inicializarDataTable() {
    if (!$.fn.DataTable || _as_dt) return;
    _as_dt = $('#as_dataTableAccesos').DataTable({
        destroy: true,
        pageLength: 10,
        lengthChange: false,
        searching: true,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: 3 }],
        language: {
            emptyTable: 'Sin registros',
            info: 'Mostrando _START_–_END_ de _TOTAL_',
            infoEmpty: '0 registros',
            search: 'Buscar:',
            zeroRecords: 'Sin resultados',
            paginate: { first: 'Primero', last: 'Último', next: 'Sig.', previous: 'Ant.' }
        }
    });
}

function as_tablaVacia() {
    as_destruirDataTable();
    $('#as_tablaAccesos').html('<tr><td colspan="4" class="text-center text-muted py-3">Selecciona un sistema o usuario para ver los accesos</td></tr>');
}

// Inicializar eventos
window.addEventListener('load', function () {
    if (typeof window.jQuery === 'undefined') return;

    $('#modalAccesoSistemas').on('show.bs.modal', function () {
        as_cargarSistemas();
        as_cargarUsuarios();
    });

    if ($.fn.select2) {
        $('#as_selectUsuario').select2({
            theme: 'bootstrap4',
            placeholder: 'Filtra / selecciona usuario',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#modalAccesoSistemas')
        });
    }

    $('#as_selectSistema').on('change', function () {
        const val = $(this).val();
        const esNuevo = as_esNuevo(val);
        $('#as_textareaSistemaNuevo').toggleClass('d-none', !esNuevo);
        if (!esNuevo) $('#as_textareaSistemaNuevo').val('');
        as_actualizarBtnAgregar();
        as_refrescarTabla(); // Siempre refresca al cambiar sistema
    });

    $('#as_selectUsuario').on('change', function () {
        as_actualizarBtnAgregar();
        as_refrescarTabla(); // Siempre refresca al cambiar usuario
    });

    $('#as_textareaSistemaNuevo').on('input', as_actualizarBtnAgregar);
});

function as_cargarSistemas() {
    $.post(AS_URL, { accion: 'as_cargar_sistemas' }, function (data) {
        let opts = '<option value="">Selecciona un sistema</option>';
        opts += '<option value="' + AS_SISTEMAS_BASE + '">&#9889; Sistemas Base</option>';
        if (Array.isArray(data)) data.forEach(function (s) {
            opts += '<option value="' + s + '">' + s + '</option>';
        });
        opts += '<option value="' + AS_NUEVO_SISTEMA + '">+ Nuevo sistema</option>';
        $('#as_selectSistema').html(opts);
        $('#as_textareaSistemaNuevo').addClass('d-none').val('');
        as_tablaVacia();
    }, 'json');
}

function as_cargarUsuarios() {
    $.post(AS_URL, { accion: 'as_cargar_usuarios' }, function (data) {
        let opts = '<option value="">Filtra / selecciona usuario</option>';
        if (Array.isArray(data)) data.forEach(function (u) {
            opts += '<option value="' + u.noEmpleado + '">' + u.nombre + '</option>';
        });
        $('#as_selectUsuario').html(opts);
        if ($.fn.select2) {
            $('#as_selectUsuario').select2({
                theme: 'bootstrap4',
                placeholder: 'Filtra / selecciona usuario',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#modalAccesoSistemas')
            });
        }
    }, 'json');
}

function as_refrescarTabla() {
    const val   = $('#as_selectSistema').val();
    const noEmp = $('#as_selectUsuario').val();

    // Sin ningún filtro: tabla vacía
    if (!val && !noEmp) { as_tablaVacia(); return; }

    let payload;
    if (as_esSistemasBase(val)) {
        // Sistemas Base: muestra los 4 sistemas (con o sin usuario)
        payload = { accion: 'as_listar_accesos_base' };
        if (noEmp) payload.noEmpleado = noEmp;
    } else {
        // Normal: filtra por lo que esté seleccionado
        // "Nuevo sistema" sin usuario no tiene nada que mostrar
        const sistema = as_esNuevo(val) ? '' : val;
        if (!sistema && !noEmp) { as_tablaVacia(); return; }
        payload = { accion: 'as_listar_accesos' };
        if (sistema) payload.sistema    = sistema;
        if (noEmp)   payload.noEmpleado = noEmp;
    }

    $.post(AS_URL, payload, function (data) {
        as_destruirDataTable();
        if (!Array.isArray(data) || data.length === 0) {
            $('#as_tablaAccesos').html('<tr><td colspan="4" class="text-center text-muted py-3">Sin registros encontrados</td></tr>');
            as_inicializarDataTable();
            return;
        }
        let html = '';
        data.forEach(function (row) {
            const badge = row.estatus == 1
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-secondary">Inactivo</span>';
            const btn = row.estatus == 1
                ? '<button class="btn btn-warning btn-sm shadow-sm" onclick="as_eliminarAcceso(' + row.id + ')"><i class="fas fa-minus-circle"></i> Desactivar</button>'
                : '<button class="btn btn-info btn-sm shadow-sm"    onclick="as_reactivarAcceso(' + row.id + ')"><i class="fas fa-check-circle"></i> Reactivar</button>';
            html += '<tr>'
                  + '<td class="align-middle">' + row.sistema + '</td>'
                  + '<td class="align-middle font-weight-bold">' + row.nombre + '</td>'
                  + '<td class="align-middle text-center">' + badge + '</td>'
                  + '<td class="text-center align-middle">' + btn + '</td>'
                  + '</tr>';
        });
        $('#as_tablaAccesos').html(html);
        as_inicializarDataTable();
    }, 'json');
}

function as_agregarAcceso() {
    const val   = $('#as_selectSistema').val();
    const noEmp = $('#as_selectUsuario').val();

    if (!noEmp) {
        return Swal.fire('Atención', 'Debes seleccionar un usuario.', 'warning');
    }

    // Flujo especial: Sistemas Base
    if (as_esSistemasBase(val)) {
        $.post(AS_URL, { accion: 'as_agregar_accesos_base', noEmpleado: noEmp }, function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: res.message, toast: true,
                            position: 'top-end', timer: 2500, showConfirmButton: false });
                as_refrescarTabla();
            } else {
                Swal.fire('Sin cambios', res.message, 'info');
            }
        }, 'json');
        return;
    }

    // Flujo normal: sistema individual
    const sistema = as_obtenerSistema();
    if (!sistema) {
        return Swal.fire('Atención', 'Debes seleccionar un sistema y un usuario.', 'warning');
    }

    $.post(AS_URL, { accion: 'as_agregar_acceso', sistema: sistema, noEmpleado: noEmp }, function (res) {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Acceso agregado', toast: true,
                        position: 'top-end', timer: 2000, showConfirmButton: false });
            as_refrescarTabla();
        } else {
            Swal.fire('Sin cambios', res.message, 'info');
        }
    }, 'json');
}

function as_eliminarAcceso(id) {
    Swal.fire({
        title: '¿Desactivar este acceso?',
        text: 'El usuario perderá acceso a este sistema.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, desactivar'
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post(AS_URL, { accion: 'as_eliminar_acceso', id: id }, function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Acceso desactivado', toast: true,
                            position: 'top-end', timer: 2000, showConfirmButton: false });
                as_refrescarTabla();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });
}

function as_reactivarAcceso(id) {
    Swal.fire({
        title: '¿Reactivar este acceso?',
        text: 'El usuario volverá a tener acceso a este sistema.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, reactivar'
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post(AS_URL, { accion: 'as_reactivar_acceso', id: id }, function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Acceso reactivado', toast: true,
                            position: 'top-end', timer: 2000, showConfirmButton: false });
                as_refrescarTabla();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });
}
</script>

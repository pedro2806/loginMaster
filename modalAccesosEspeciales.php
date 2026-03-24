<?php
// 
// Cuando se llama vía AJAX (POST con accion ae_*), resuelve y termina aquí.
// Cuando se incluye desde inicio.php, renderiza el modal.
// 
$_ae_permitidos = [276, 183, 523];

if (!empty($_POST['accion']) && strpos($_POST['accion'], 'ae_') === 0) {
    header('Content-Type: application/json');
    include '../incidencias/conn.php';

    $accion      = $_POST['accion'];
    $ae_sesion   = intval($_COOKIE['noEmpleadoL'] ?? 0);

    //  Cargar sistemas únicos 
    if ($accion === 'ae_cargar_sistemas') {
        $res      = $conn->query("SELECT DISTINCT sistema FROM accesos_especiales ORDER BY sistema ASC");
        $sistemas = [];
        while ($row = $res->fetch_assoc()) $sistemas[] = $row['sistema'];
        echo json_encode($sistemas);
        exit;
    }

    //  Cargar opciones filtradas por sistema 
    if ($accion === 'ae_cargar_opciones') {
        $ae_sistema = trim($_POST['sistema'] ?? '');
        $stmt = $conn->prepare("SELECT DISTINCT opcion FROM accesos_especiales WHERE sistema = ? ORDER BY opcion ASC");
        $stmt->bind_param("s", $ae_sistema);
        $stmt->execute();
        $res     = $stmt->get_result();
        $opciones = [];
        while ($row = $res->fetch_assoc()) $opciones[] = $row['opcion'];
        $stmt->close();
        echo json_encode($opciones);
        exit;
    }

    //  Cargar todos los usuarios activos 
    if ($accion === 'ae_cargar_usuarios') {
        $res      = $conn->query("SELECT noEmpleado, nombre FROM usuarios WHERE estatus = 1 ORDER BY nombre ASC");
        $usuarios = [];
        while ($row = $res->fetch_assoc()) $usuarios[] = $row;
        echo json_encode($usuarios);
        exit;
    }

    //  Listar accesos (sistema requerido; opcion y noEmpleado opcionales; TODOS los estatus) 
    if ($accion === 'ae_listar_accesos') {
        $ae_sistema = trim($_POST['sistema']      ?? '');
        $ae_opcion  = trim($_POST['opcion']       ?? '');
        $ae_noEmp   = intval($_POST['noEmpleado'] ?? 0);

        $sql    = "SELECT ae.id, ae.sistema, ae.opcion, ae.noEmpleado, ae.estatus,
                          IFNULL(u.nombre, ae.noEmpleado) AS nombre
                   FROM accesos_especiales ae
                   LEFT JOIN usuarios u ON ae.noEmpleado = u.noEmpleado
                   WHERE 1=1";
        $params = [];
        $types  = "";

        if ($ae_sistema !== '') { $sql .= " AND ae.sistema = ?";     $params[] = $ae_sistema; $types .= "s"; }
        if ($ae_opcion !== '')  { $sql .= " AND ae.opcion = ?";      $params[] = $ae_opcion;  $types .= "s"; }
        if ($ae_noEmp  >  0)    { $sql .= " AND ae.noEmpleado = ?";  $params[] = $ae_noEmp;   $types .= "i"; }
        $sql .= " ORDER BY ae.estatus DESC, ae.sistema, ae.opcion, u.nombre ASC";

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

    //  Agregar acceso (insert o reactiva si estatus = 0) 
    if ($accion === 'ae_agregar_acceso') {
        $ae_sistema = trim($_POST['sistema']      ?? '');
        $ae_opcion  = trim($_POST['opcion']       ?? '');
        $ae_noEmp   = intval($_POST['noEmpleado'] ?? 0);

        if ($ae_sistema === '' || $ae_opcion === '' || $ae_noEmp <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']); exit;
        }

        $stmt = $conn->prepare("SELECT id, estatus FROM accesos_especiales
                                WHERE sistema = ? AND opcion = ? AND noEmpleado = ?");
        $stmt->bind_param("ssi", $ae_sistema, $ae_opcion, $ae_noEmp);
        $stmt->execute();
        $existente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existente) {
            if ($existente['estatus'] == 1) {
                echo json_encode(['success' => false, 'message' => 'El usuario ya tiene este acceso']); exit;
            }
            $stmt = $conn->prepare("UPDATE accesos_especiales SET estatus = 1 WHERE id = ?");
            $stmt->bind_param("i", $existente['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO accesos_especiales (sistema, opcion, noEmpleado, estatus) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("ssi", $ae_sistema, $ae_opcion, $ae_noEmp);
        }
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Acceso agregado' : 'Error al guardar']);
        exit;
    }

    //  Eliminar acceso (borrado lógico estatus = 0) 
    if ($accion === 'ae_eliminar_acceso') {
        $ae_id = intval($_POST['id'] ?? 0);
        if ($ae_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
        }
        $stmt = $conn->prepare("UPDATE accesos_especiales SET estatus = 0 WHERE id = ?");
        $stmt->bind_param("i", $ae_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Acceso eliminado' : 'Error al eliminar']);
        exit;
    }

    //  Reactivar acceso (cambiar estatus de 0 a 1) 
    if ($accion === 'ae_reactivar_acceso') {
        $ae_id = intval($_POST['id'] ?? 0);
        if ($ae_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
        }
        $stmt = $conn->prepare("UPDATE accesos_especiales SET estatus = 1 WHERE id = ?");
        $stmt->bind_param("i", $ae_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Acceso reactivado' : 'Error al reactivar']);
        exit;
    }

    // Acción no reconocida
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

// 
// Renderizado del modal (ejecutado solo cuando es incluido desde inicio.php)
// 
$_ae_actual = intval($_COOKIE['noEmpleadoL'] ?? 0);
if (!in_array($_ae_actual, $_ae_permitidos)) return;
?>

<div class="modal fade" id="modalAccesosEspeciales" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-left-primary shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-shield"></i> Accesos Especiales
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <!-- Fila de filtros -->
                <div class="row mb-3 align-items-end">
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-dark mb-1">Sistema</label>
                        <select id="ae_selectSistema" class="form-control form-control-sm">
                            <option value="">Selecciona un sistema</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-dark mb-1">Opción</label>
                        <select id="ae_selectOpcion" class="form-control form-control-sm" disabled>
                            <option value="">Selecciona una opción</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-dark mb-1">Usuario</label>
                        <select id="ae_selectUsuario" class="form-control form-control-sm select2">
                            <option value="">Selecciona usuario</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-success btn-sm btn-block shadow-sm"
                                id="ae_btnAgregar" onclick="ae_agregarAcceso()" disabled>
                            <i class="fas fa-plus-circle"></i> Agregar Acceso
                        </button>
                    </div>
                </div>

                <!-- Tabla de accesos -->
                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="thead-dark text-uppercase small font-weight-bold">
                            <tr>
                                <th>Sistema</th>
                                <th>Opción</th>
                                <th>Usuario</th>
                                <th>Estatus</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="ae_tablaAccesos">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    Selecciona un sistema para ver los accesos
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
const AE_URL = 'modalAccesosEspeciales.php';

function ae_inicializarEventos() {
    if (typeof window.jQuery === 'undefined') return;

    //  Inicializar al abrir el modal 
    $('#modalAccesosEspeciales').on('show.bs.modal', function () {
        ae_cargarSistemas();
        ae_cargarTodosUsuarios();
    });
    
    //  Inicializar Select2 para el campo de usuario (si está disponible)
    if (typeof $.fn.select2 !== 'undefined') {
        $('#ae_selectUsuario').select2({
            theme: 'bootstrap4',
            placeholder: 'Filtra / selecciona usuario',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#modalAccesosEspeciales')
        });
    }

    //  Cambio de sistema  cargar opciones y refrescar tabla 
    $('#ae_selectSistema').on('change', function () {
        const sistema = $(this).val();

        $('#ae_selectOpcion')
            .html('<option value="">Selecciona una opción</option>')
            .prop('disabled', true);
        ae_actualizarBtnAgregar();

        if (!sistema) {
            // Si hay usuario seleccionado, mostrar sus accesos; si no hay usuario, mostrar mensaje
            const usuario = $('#ae_selectUsuario').val();
            if (!usuario) {
                $('#ae_tablaAccesos').html(
                    '<tr><td colspan="5" class="text-center text-muted py-3">Selecciona un sistema o un usuario para ver accesos</td></tr>'
                );
            } else {
                ae_refrescarTabla();
            }
            return;
        }

        $.post(AE_URL, { accion: 'ae_cargar_opciones', sistema: sistema }, function (data) {
            let opts = '<option value="">Selecciona una opción</option>';
            if (Array.isArray(data)) {
                data.forEach(function (o) { opts += '<option value="' + o + '">' + o + '</option>'; });
            }
            $('#ae_selectOpcion').html(opts).prop('disabled', false);
        }, 'json');

        ae_refrescarTabla();
    });

    //  Cambio de opción 
    $('#ae_selectOpcion').on('change', function () {
        ae_actualizarBtnAgregar();
        ae_refrescarTabla();
    });

    //  Cambio de usuario (filtro opcional - ahora independiente de sistema) 
    $('#ae_selectUsuario').on('change', function () {
        ae_actualizarBtnAgregar();
        const usuario = $(this).val();
        const sistema = $('#ae_selectSistema').val();
        if (usuario || sistema) ae_refrescarTabla();
    });
}

window.addEventListener('load', ae_inicializarEventos);

//  Cargar sistemas únicos 
function ae_cargarSistemas() {
    $.post(AE_URL, { accion: 'ae_cargar_sistemas' }, function (data) {
        let opts = '<option value="">Selecciona un sistema</option>';
        if (Array.isArray(data)) {
            data.forEach(function (s) { opts += '<option value="' + s + '">' + s + '</option>'; });
        }
        $('#ae_selectSistema').html(opts);
        $('#ae_selectOpcion').html('<option value="">Selecciona una opción</option>').prop('disabled', true);
        $('#ae_tablaAccesos').html(
            '<tr><td colspan="5" class="text-center text-muted py-3">Selecciona un sistema para ver los accesos</td></tr>'
        );
    }, 'json');
}

//  Cargar todos los usuarios activos 
function ae_cargarTodosUsuarios() {
    $.post(AE_URL, { accion: 'ae_cargar_usuarios' }, function (data) {
        let opts = '<option value="">Filtra / selecciona usuario</option>';
        if (Array.isArray(data)) {
            data.forEach(function (u) {
                opts += '<option value="' + u.noEmpleado + '">' + u.nombre + '</option>';
            });
        }
        $('#ae_selectUsuario').html(opts);
        
        // Reinicializar Select2 después de cargar los usuarios
        if (typeof $.fn.select2 !== 'undefined') {
            $('#ae_selectUsuario').select2({
                theme: 'bootstrap4',
                placeholder: 'Filtra / selecciona usuario',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#modalAccesosEspeciales')
            });
        }
    }, 'json');
}

//  Habilitar botón Agregar (requiere los tres selects con valor) 
function ae_actualizarBtnAgregar() {
    const ok = $('#ae_selectSistema').val() &&
               $('#ae_selectOpcion').val()  &&
               $('#ae_selectUsuario').val();
    $('#ae_btnAgregar').prop('disabled', !ok);
}

//  Refrescar tabla según filtros activos 
function ae_refrescarTabla() {
    const sistema = $('#ae_selectSistema').val();
    const opcion  = $('#ae_selectOpcion').val();
    const noEmp   = $('#ae_selectUsuario').val();

    // Si no hay ningún filtro, mostrar mensaje
    if (!sistema && !noEmp) {
        $('#ae_tablaAccesos').html(
            '<tr><td colspan="5" class="text-center text-muted py-3">Selecciona un sistema o un usuario para ver accesos</td></tr>'
        );
        return;
    }

    const payload = { accion: 'ae_listar_accesos' };
    if (sistema) payload.sistema = sistema;
    if (opcion) payload.opcion = opcion;
    if (noEmp) payload.noEmpleado = noEmp;

    $.post(AE_URL, payload, function (data) {
        if (!Array.isArray(data) || data.length === 0) {
            $('#ae_tablaAccesos').html(
                '<tr><td colspan="5" class="text-center text-muted py-3">Sin accesos registrados para este filtro</td></tr>'
            );
            return;
        }
        let html = '';
        data.forEach(function (row) {
            let badgeEstatus = row.estatus == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-secondary">Inactivo</span>';
            let btnAccion = row.estatus == 1
                ? '<button class="btn btn-warning btn-sm shadow-sm" onclick="ae_eliminarAcceso(' + row.id + ')"><i class="fas fa-minus-circle"></i> Desactivar</button>'
                : '<button class="btn btn-info btn-sm shadow-sm" onclick="ae_reactivarAcceso(' + row.id + ')"><i class="fas fa-check-circle"></i> Reactivar</button>';
            
            html += '<tr>'
                  + '<td class="align-middle">' + row.sistema + '</td>'
                  + '<td class="align-middle">' + row.opcion  + '</td>'
                  + '<td class="align-middle font-weight-bold">' + row.nombre + '</td>'
                  + '<td class="align-middle text-center">' + badgeEstatus + '</td>'
                  + '<td class="text-center align-middle">'
                  +   btnAccion
                  +   '</button>'
                  + '</td>'
                  + '</tr>';
        });
        $('#ae_tablaAccesos').html(html);
    }, 'json');
}

//  Agregar acceso 
function ae_agregarAcceso() {
    const sistema = $('#ae_selectSistema').val();
    const opcion  = $('#ae_selectOpcion').val();
    const noEmp   = $('#ae_selectUsuario').val();

    if (!sistema || !opcion || !noEmp) {
        return Swal.fire('Atención', 'Debes seleccionar sistema, opción y usuario.', 'warning');
    }

    $.post(AE_URL,
        { accion: 'ae_agregar_acceso', sistema: sistema, opcion: opcion, noEmpleado: noEmp },
        function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Acceso agregado', toast: true,
                            position: 'top-end', timer: 2000, showConfirmButton: false });
                ae_refrescarTabla();
            } else {
                Swal.fire('Sin cambios', res.message, 'info');
            }
        }, 'json'
    );
}

//  Desactivar acceso (borrado lógico) 
function ae_eliminarAcceso(id) {
    Swal.fire({
        title: '¿Desactivar este acceso?',
        text: 'El usuario perderá acceso a esta opción del sistema.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, desactivar'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(AE_URL, { accion: 'ae_eliminar_acceso', id: id }, function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Acceso desactivado', toast: true,
                                position: 'top-end', timer: 2000, showConfirmButton: false });
                    ae_refrescarTabla();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}

//  Reactivar acceso 
function ae_reactivarAcceso(id) {
    Swal.fire({
        title: '¿Reactivar este acceso?',
        text: 'El usuario volverá a tener acceso a esta opción del sistema.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, reactivar'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(AE_URL, { accion: 'ae_reactivar_acceso', id: id }, function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Acceso reactivado', toast: true,
                                position: 'top-end', timer: 2000, showConfirmButton: false });
                    ae_refrescarTabla();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>

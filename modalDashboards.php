<?php
//
// Alta/baja de tableros de la pestaña "Dashboards" (tabla mess_rrhh.enlaces_kpis).
// Cuando se llama vía AJAX (POST con accion dsh_*), resuelve y termina aquí.
// Cuando se incluye desde inicio.php, renderiza el modal.
//
// Antes esta administración no existía en el portal: los tableros se daban de
// alta directo en la base y se dibujaban desde kpis_pbi/index.php.
//
$_dsh_permitidos = [276, 183, 523];

if (!empty($_POST['accion']) && strpos($_POST['accion'], 'dsh_') === 0) {
    header('Content-Type: application/json');
    include '../incidencias/conn.php';
    $conn->select_db("mess_rrhh");

    // El permiso se valida también aquí, no solo al pintar el modal: si solo se
    // revisara al renderizar, cualquiera que conociera el endpoint podría dar de
    // alta o borrar tableros mandando el POST a mano.
    $dsh_sesion = intval($_COOKIE['noEmpleadoL'] ?? 0);
    if (!in_array($dsh_sesion, $_dsh_permitidos, true)) {
        echo json_encode(['success' => false, 'message' => 'Sin permiso para administrar tableros.']);
        exit;
    }

    $accion = $_POST['accion'];

    // Cuántos empleados tienen una contraseña dada. No basta con comparar
    // inf_adicional = pk: el campo admite varias separadas por coma
    // ("Seguimiento_Labs26, aM04_LOP", ver inicio.php), así que se le quitan los
    // espacios y se busca con FIND_IN_SET. Ninguna Password_KPI contiene
    // espacios, así que quitarlos no puede provocar un falso positivo.
    function dsh_contarEmpleados($conn, $pk) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM accesos_especiales
                                WHERE sistema = 'kpis' AND opcion = 'verKpis' AND estatus = 1
                                  AND FIND_IN_SET(?, REPLACE(inf_adicional, ' ', '')) > 0");
        if (!$stmt) return 0;
        $stmt->bind_param("s", $pk);
        $stmt->execute();
        $n = (int) $stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();
        return $n;
    }

    //  Lista de contraseñas (pk) existentes, para el filtro
    if ($accion === 'dsh_pks') {
        $res = $conn->query("SELECT Password_KPI, COUNT(*) AS total
                             FROM enlaces_kpis
                             GROUP BY Password_KPI
                             ORDER BY Password_KPI ASC");
        $pks = [];
        while ($row = $res->fetch_assoc()) {
            $row['empleados'] = dsh_contarEmpleados($conn, $row['Password_KPI']);
            $pks[] = $row;
        }
        echo json_encode($pks);
        exit;
    }

    //  Listar tableros, filtrando por pk y/o texto en el nombre
    if ($accion === 'dsh_listar') {
        $dsh_pk    = trim($_POST['pk'] ?? '');
        $dsh_texto = trim($_POST['texto'] ?? '');

        $sql    = "SELECT id_registro, Password_KPI, Nombre, Enlace FROM enlaces_kpis WHERE 1=1";
        $params = [];
        $types  = "";

        if ($dsh_pk !== '') {
            $sql     .= " AND Password_KPI = ?";
            $types   .= "s";
            $params[] = $dsh_pk;
        }
        if ($dsh_texto !== '') {
            $sql     .= " AND Nombre LIKE ?";
            $types   .= "s";
            $params[] = '%' . $dsh_texto . '%';
        }
        $sql .= " ORDER BY Password_KPI ASC, id_registro ASC LIMIT 300";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta']);
            exit;
        }
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $filas = [];
        // Se cuenta una vez por contraseña distinta, no una por fila: un mismo pk
        // trae hasta 13 tableros y repetir la consulta por cada uno no aporta.
        $conteos = [];
        while ($row = $res->fetch_assoc()) {
            $pk = $row['Password_KPI'];
            if (!array_key_exists($pk, $conteos)) {
                $conteos[$pk] = dsh_contarEmpleados($conn, $pk);
            }
            $row['empleados'] = $conteos[$pk];
            $filas[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'tableros' => $filas]);
        exit;
    }

    //  Guardar: da de alta si no viene id, actualiza si viene
    if ($accion === 'dsh_guardar') {
        $dsh_id     = intval($_POST['id'] ?? 0);
        $dsh_pk     = trim($_POST['pk'] ?? '');
        $dsh_nombre = trim($_POST['nombre'] ?? '');
        $dsh_enlace = trim($_POST['enlace'] ?? '');

        if ($dsh_pk === '' || $dsh_nombre === '' || $dsh_enlace === '') {
            echo json_encode(['success' => false, 'message' => 'Contraseña, nombre y enlace son obligatorios.']);
            exit;
        }
        // La columna Enlace es varchar(9900): sin este corte MySQL truncaría en
        // silencio y el tablero quedaría con una URL rota.
        if (strlen($dsh_enlace) > 9900) {
            echo json_encode(['success' => false, 'message' => 'El enlace excede los 9900 caracteres.']);
            exit;
        }
        if (strlen($dsh_nombre) > 200) {
            echo json_encode(['success' => false, 'message' => 'El nombre excede los 200 caracteres.']);
            exit;
        }
        if (strlen($dsh_pk) > 100) {
            echo json_encode(['success' => false, 'message' => 'La contraseña excede los 100 caracteres.']);
            exit;
        }

        if ($dsh_id > 0) {
            $stmt = $conn->prepare("UPDATE enlaces_kpis
                                    SET Password_KPI = ?, Nombre = ?, Enlace = ?
                                    WHERE id_registro = ?");
            $stmt->bind_param("sssi", $dsh_pk, $dsh_nombre, $dsh_enlace, $dsh_id);
            $mensaje = 'Tablero actualizado';
        } else {
            $stmt = $conn->prepare("INSERT INTO enlaces_kpis (Password_KPI, Nombre, Enlace)
                                    VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $dsh_pk, $dsh_nombre, $dsh_enlace);
            $mensaje = 'Tablero agregado';
        }
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta']);
            exit;
        }
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();
        // Se devuelve a cuántos empleados les llega la contraseña. Guardar un
        // tablero bajo un pk que nadie tiene es la causa más común de "lo di de
        // alta y no sale": la fila queda bien en la base y el modal la lista,
        // pero ninguna pestaña la muestra. Antes no había ninguna señal.
        echo json_encode([
            'success'   => $ok,
            'message'   => $ok ? $mensaje : 'Error al guardar: ' . $err,
            'empleados' => $ok ? dsh_contarEmpleados($conn, $dsh_pk) : 0,
            'pk'        => $dsh_pk
        ]);
        exit;
    }

    //  Eliminar. OJO: enlaces_kpis no tiene columna de estatus, así que el
    //  borrado es físico y no se puede deshacer desde aquí.
    if ($accion === 'dsh_eliminar') {
        $dsh_id = intval($_POST['id'] ?? 0);
        if ($dsh_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM enlaces_kpis WHERE id_registro = ?");
        $stmt->bind_param("i", $dsh_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Tablero eliminado' : 'Error al eliminar']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

//
// Renderizado del modal (solo cuando se incluye desde inicio.php)
//
$_dsh_actual = intval($_COOKIE['noEmpleadoL'] ?? 0);
if (!in_array($_dsh_actual, $_dsh_permitidos, true)) return;
?>

<div class="modal fade" id="modalDashboards" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-left-primary shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-chart-pie mr-2"></i>Administrar Dashboards</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <!-- Alta / edición -->
                <div class="card mb-3">
                    <div class="card-header py-2" style="background: var(--card-soft); border-color: var(--border);">
                        <h6 class="m-0 font-weight-bold" id="dsh_tituloForm">Agregar tablero</h6>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="dsh_id" value="">
                        <div class="form-row">
                            <div class="col-md-4 mb-2">
                                <label class="small mb-1" for="dsh_pk">Contraseña de acceso (pk)</label>
                                <input type="text" class="form-control form-control-sm" id="dsh_pk"
                                       placeholder="Ej. mess_dir2023" maxlength="100">
                                <small class="text-muted">Quien tenga esta contraseña verá el tablero.</small>
                            </div>
                            <div class="col-md-8 mb-2">
                                <label class="small mb-1" for="dsh_nombre">Nombre del tablero</label>
                                <input type="text" class="form-control form-control-sm" id="dsh_nombre"
                                       placeholder="Ej. Cuentas Asignadas - BAJIO" maxlength="200">
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="small mb-1" for="dsh_enlace">Enlace de Power BI</label>
                            <textarea class="form-control form-control-sm" id="dsh_enlace" rows="2"
                                      placeholder="https://app.powerbi.com/view?r=..."></textarea>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-sm btn-secondary" id="dsh_btnCancelar"
                                    onclick="dsh_limpiarForm()" style="display:none;">Cancelar edición</button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="dsh_guardar()">
                                <i class="fas fa-save mr-1"></i>Guardar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="form-row align-items-end mb-2">
                    <div class="col-md-5 mb-2">
                        <label class="small mb-1" for="dsh_filtroPk">Filtrar por contraseña</label>
                        <select id="dsh_filtroPk" class="form-control form-control-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-5 mb-2">
                        <label class="small mb-1" for="dsh_filtroTexto">Buscar por nombre</label>
                        <input type="text" class="form-control form-control-sm" id="dsh_filtroTexto"
                               placeholder="Ej. Forecast">
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-block" onclick="dsh_listar()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="thead-light small text-uppercase">
                            <tr>
                                <th>Nombre</th>
                                <th>Contraseña</th>
                                <th>Enlace</th>
                                <th style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="dsh_tabla"></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" id="dsh_conteo"></small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="dsh_paginacion"></ul>
                    </nav>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
var DSH_URL = 'modalDashboards.php';

function dsh_esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

function dsh_cargarPks() {
    $.post(DSH_URL, { accion: 'dsh_pks' }, function (data) {
        var opts = '<option value="">Todas</option>';
        if (Array.isArray(data)) {
            data.forEach(function (p) {
                // "(6 tableros, 2 empleados)". Las que no le llegan a nadie se
                // marcan aquí mismo, que es donde se eligen.
                var quien = p.empleados > 0
                    ? p.empleados + (p.empleados == 1 ? ' empleado' : ' empleados')
                    : 'SIN ASIGNAR';
                opts += '<option value="' + dsh_esc(p.Password_KPI) + '">'
                      + dsh_esc(p.Password_KPI) + ' (' + p.total + ', ' + quien + ')</option>';
            });
        }
        $('#dsh_filtroPk').html(opts);
    }, 'json');
}

// Se guarda lo último listado para poder llenar el formulario al editar sin
// volver a consultar al servidor, y para paginar del lado del cliente.
var dsh_cache = [];
var dsh_pagina = 1;
var DSH_POR_PAGINA = 5;

function dsh_listar() {
    $.post(DSH_URL, {
        accion: 'dsh_listar',
        pk: $('#dsh_filtroPk').val(),
        texto: $('#dsh_filtroTexto').val()
    }, function (res) {
        dsh_cache = (res.success ? res.tableros : []);
        dsh_pagina = 1;
        dsh_pintarPagina();
    }, 'json');
}

// La paginación es del lado del cliente: la consulta ya viene acotada a 300
// filas y un usuario rara vez tiene más de 13 tableros, así que no vale la pena
// ir al servidor por cada página.
function dsh_pintarPagina() {
    var total = dsh_cache.length;
    var paginas = Math.max(1, Math.ceil(total / DSH_POR_PAGINA));
    if (dsh_pagina > paginas) dsh_pagina = paginas;

    var desde = (dsh_pagina - 1) * DSH_POR_PAGINA;
    var visibles = dsh_cache.slice(desde, desde + DSH_POR_PAGINA);

    var html = '';
    if (visibles.length) {
        visibles.forEach(function (t) {
            // El enlace puede medir miles de caracteres: se recorta para que
            // no reviente la tabla y el completo va en el title.
            var corto = t.Enlace.length > 60 ? t.Enlace.substring(0, 60) + '...' : t.Enlace;
            // Debajo de la contraseña, a quién le llega. Un tablero bajo una
            // contraseña sin asignar no lo ve nadie, y hasta ahora eso solo se
            // descubría cuando alguien reclamaba que su tablero no aparecía.
            var aviso = t.empleados > 0
                ? '<span class="small text-muted">' + t.empleados
                  + (t.empleados == 1 ? ' empleado' : ' empleados') + '</span>'
                : '<span class="small text-danger font-weight-bold" '
                  + 'title="Ningún empleado tiene esta contraseña, así que nadie ve este tablero">'
                  + '<i class="fas fa-exclamation-triangle"></i> Nadie lo ve</span>';
            html += '<tr>'
                 +  '<td>' + dsh_esc(t.Nombre) + '</td>'
                 +  '<td><code class="small">' + dsh_esc(t.Password_KPI) + '</code>'
                 +      '<br>' + aviso + '</td>'
                 +  '<td><span class="small text-muted" title="' + dsh_esc(t.Enlace) + '">'
                 +      dsh_esc(corto) + '</span></td>'
                 +  '<td>'
                 +    '<button class="btn btn-sm btn-outline-primary mr-1" title="Editar" '
                 +      'onclick="dsh_editar(' + t.id_registro + ')"><i class="fas fa-edit"></i></button>'
                 +    '<button class="btn btn-sm btn-outline-danger" title="Eliminar" '
                 +      'onclick="dsh_eliminar(' + t.id_registro + ')"><i class="fas fa-trash"></i></button>'
                 +  '</td>'
                 + '</tr>';
        });
    } else {
        html = '<tr><td colspan="4" class="text-center text-muted py-3">Sin tableros que coincidan.</td></tr>';
    }
    $('#dsh_tabla').html(html);

    $('#dsh_conteo').text(
        total ? ('Mostrando ' + (desde + 1) + '-' + (desde + visibles.length) + ' de ' + total) : ''
    );

    var nav = '';
    if (paginas > 1) {
        nav += '<li class="page-item' + (dsh_pagina === 1 ? ' disabled' : '') + '">'
             + '<a class="page-link" href="#" onclick="dsh_irPagina(' + (dsh_pagina - 1) + ');return false;">&laquo;</a></li>';
        for (var p = 1; p <= paginas; p++) {
            nav += '<li class="page-item' + (p === dsh_pagina ? ' active' : '') + '">'
                 + '<a class="page-link" href="#" onclick="dsh_irPagina(' + p + ');return false;">' + p + '</a></li>';
        }
        nav += '<li class="page-item' + (dsh_pagina === paginas ? ' disabled' : '') + '">'
             + '<a class="page-link" href="#" onclick="dsh_irPagina(' + (dsh_pagina + 1) + ');return false;">&raquo;</a></li>';
    }
    $('#dsh_paginacion').html(nav);
}

function dsh_irPagina(p) {
    var paginas = Math.max(1, Math.ceil(dsh_cache.length / DSH_POR_PAGINA));
    if (p < 1 || p > paginas) return;
    dsh_pagina = p;
    dsh_pintarPagina();
}

function dsh_editar(id) {
    var t = dsh_cache.filter(function (x) { return parseInt(x.id_registro, 10) === id; })[0];
    if (!t) return;
    $('#dsh_id').val(t.id_registro);
    $('#dsh_pk').val(t.Password_KPI);
    $('#dsh_nombre').val(t.Nombre);
    $('#dsh_enlace').val(t.Enlace);
    $('#dsh_tituloForm').text('Editando: ' + t.Nombre);
    $('#dsh_btnCancelar').show();
    $('#modalDashboards .modal-body').scrollTop(0);
}

function dsh_limpiarForm() {
    $('#dsh_id, #dsh_pk, #dsh_nombre, #dsh_enlace').val('');
    $('#dsh_tituloForm').text('Agregar tablero');
    $('#dsh_btnCancelar').hide();
}

function dsh_guardar() {
    var pk = $('#dsh_pk').val().trim();
    var nombre = $('#dsh_nombre').val().trim();
    var enlace = $('#dsh_enlace').val().trim();

    if (!pk || !nombre || !enlace) {
        Swal.fire({ title: 'Contraseña, nombre y enlace son obligatorios.', icon: 'warning' });
        return;
    }

    $.post(DSH_URL, {
        accion: 'dsh_guardar',
        id: $('#dsh_id').val(),
        pk: pk, nombre: nombre, enlace: enlace
    }, function (res) {
        if (res.success && res.empleados === 0) {
            // Se guardó bien, pero no le llega a nadie. Se avisa aquí porque es
            // el único momento en que la persona todavía tiene el contexto para
            // corregir la contraseña sin tener que buscar el registro de nuevo.
            Swal.fire({
                title: res.message,
                html: 'Pero <b>ningún empleado tiene la contraseña '
                    + '<code>' + dsh_esc(res.pk) + '</code></b>, así que por ahora '
                    + 'nadie va a ver este tablero.<br><br>'
                    + 'Asígnala en <b>Accesos Especiales</b> (sistema <code>kpis</code>, '
                    + 'opción <code>verKpis</code>), o corrige la contraseña aquí si fue un error.',
                icon: 'warning'
            });
        } else {
            Swal.fire({ title: res.message, icon: res.success ? 'success' : 'error' });
        }
        if (res.success) {
            dsh_limpiarForm();
            dsh_cargarPks();
            dsh_listar();
        }
    }, 'json');
}

function dsh_eliminar(id) {
    var t = dsh_cache.filter(function (x) { return parseInt(x.id_registro, 10) === id; })[0];
    Swal.fire({
        title: '¿Eliminar este tablero?',
        // enlaces_kpis no tiene estatus: el borrado es definitivo, no se
        // desactiva. Conviene decirlo antes de que lo confirmen.
        html: (t ? '<b>' + dsh_esc(t.Nombre) + '</b><br>' : '')
            + 'Se borra de la base y no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post(DSH_URL, { accion: 'dsh_eliminar', id: id }, function (res) {
            Swal.fire({ title: res.message, icon: res.success ? 'success' : 'error' });
            if (res.success) { dsh_cargarPks(); dsh_listar(); }
        }, 'json');
    });
}

// Este include va en el cuerpo de inicio.php (~línea 1335) y jQuery se carga
// hasta la 1414, así que aquí todavía no existe $. Se engancha en el evento
// load, que corre cuando ya cargaron todos los scripts — mismo patrón que
// modalAccesosEspeciales.php.
function dsh_inicializarEventos() {
    if (typeof window.jQuery === 'undefined') return;

    $('#modalDashboards').on('show.bs.modal', function () {
        dsh_cargarPks();
        dsh_listar();
    });
    $('#dsh_filtroPk').on('change', dsh_listar);
    $('#dsh_filtroTexto').on('keyup', function (e) {
        if (e.key === 'Enter') dsh_listar();
    });
}

window.addEventListener('load', dsh_inicializarEventos);
</script>

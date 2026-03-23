<div class="modal fade" id="modalEvento" tabindex="-1" role="dialog" aria-labelledby="modalEventoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-left-primary shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEventoLabel"><i class="fas fa-calendar-plus"></i> Gestión de Evento</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEvento">
                    <input type="hidden" name="id_evento" id="id_evento_edit">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label class="small font-weight-bold text-dark">Nombre del Evento / Encuesta</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej: Concurso de Disfraces 2026" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="small font-weight-bold text-dark">Tipo de Dinámica</label>
                            <select name="tipo" id="tipo_evento_select" class="form-control form-control-sm" required>
                                <option value="votacion">Votación (Galería de Fotos)</option>
                                <option value="asistencia">Asistencia (Checklist de Cursos)</option>
                                <option value="encuesta">Encuesta (Múltiples Preguntas)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold text-dark">Fecha y Hora Inicio</label>
                            <input type="datetime-local" name="fecha_inicio" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold text-dark">Fecha y Hora Fin</label>
                            <input type="datetime-local" name="fecha_fin" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold text-dark">Descripción o Instrucciones</label>
                        <textarea name="descripcion" class="form-control form-control-sm" rows="2" placeholder="Instrucciones para los empleados..."></textarea>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnGuardarEvento" onclick="guardarEvento()">
                            <i class="fas fa-save"></i> Guardar Encabezado
                        </button>
                    </div>
                </form>

                <div id="seccionOpciones" style="display:none;" class="mt-4">
                    <hr class="sidebar-divider">
                    <h6 class="font-weight-bold text-primary"><i class="fas fa-cogs"></i> Configuración de Contenido</h6>
                    
                    <form id="formOpcion" enctype="multipart/form-data" class="bg-light p-3 border rounded shadow-sm">
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label class="small font-weight-bold">Pregunta / Título del Bloque</label>
                                <input type="text" name="pregunta_texto" class="form-control form-control-sm" placeholder="Ej: Cursos Disponibles">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="small font-weight-bold">Nombre del Ítem / Opción</label>
                                <input type="text" name="titulo" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="small font-weight-bold">Grupo</label>
                                <input type="text" name="grupo" class="form-control form-control-sm" placeholder="A, B, Matutino...">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="small font-weight-bold">Fecha Específica</label>
                                <input type="date" name="fecha_opcion" class="form-control form-control-sm">
                            </div>
                            
                            <div class="col-md-12 form-group" id="divFotoAdmin">
                                <label class="small font-weight-bold">Imagen (Solo para Votaciones)</label>
                                <div class="custom-file">
                                    <input type="file" name="foto" class="custom-file-input custom-file-input-sm" id="inputFoto">
                                    <label class="custom-file-label col-form-label-sm" for="inputFoto">Elegir archivo...</label>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-success btn-sm px-4" onclick="guardarOpcion()">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>
                    </form>
                    
                    <div class="table-responsive mt-3" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-striped">
                        <thead class="bg-gray-200 small font-weight-bold">
                            <tr>
                                <th>Pregunta/Bloque</th>
                                <th>Opción/Ítem</th>
                                <th class="text-center">Grupo</th>
                                <th class="text-center">Fecha</th>
                                <th class="text-center">IMG</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="listaOpcionesCargadas">
                            </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalListaEventos" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-left-info shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-list"></i> Historial de Eventos</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover" width="100%">
                        <thead class="thead-light small font-weight-bold text-uppercase">
                            <tr>
                                <th>Evento</th>
                                <th>Tipo</th>
                                <th>Vigencia</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoListaEventos">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalAsignacion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-left-info shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-users-cog"></i> Convocatoria: <span id="nombreEventoAsignar" class="text-warning"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="id_evento_asignar">
                
                <div class="card bg-light mb-3">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-9 form-group mb-0">
                                <label class="small font-weight-bold text-dark">Seleccionar Personal para este Curso</label>
                                <select id="select_empleados" class="form-control form-control-sm select2" multiple="multiple" style="width: 100%;">
                                    <?php 
                                    // Consulta rápida para llenar el select (ajusta 'usuarios' a tu tabla real)
                                    $res_emp = $conn->query("SELECT noEmpleado, nombre FROM usuarios WHERE estatus = 1 ORDER BY nombre ASC");
                                    while($emp = $res_emp->fetch_assoc()): ?>
                                        <option value="<?php echo $emp['noEmpleado']; ?>"><?php echo $emp['nombre']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="d-block">&nbsp;</label>
                                <button type="button" class="btn btn-info btn-sm btn-block shadow-sm" onclick="guardarAsignacionMasiva()">
                                    <i class="fas fa-user-plus"></i> Asignar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="bg-gray-200 small text-dark font-weight-bold">
                            <tr>
                                <th>Nombre del Empleado</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="listaAsignados" class="small text-dark">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// --- FUNCIÓN PARA ABRIR ESTE NUEVO MODAL ---
function abrirModalAsignacion(id, nombre) {
    // Cerramos el de lista si estuviera abierto (opcional)
    // $('#modalListaEventos').modal('hide'); 
    
    $('#id_evento_asignar').val(id);
    $('#nombreEventoAsignar').text(nombre);
    
    // Limpiamos el select (si usas Select2)
    if ($.fn.select2) {
        $('#select_empleados').val(null).trigger('change');
    }
    
    // Abrimos el modal
    $('#modalAsignacion').modal('show');
    
    // Cargamos los usuarios ya asignados
    cargarListaAsignados(id);
}

// --- GUARDAR ASIGNACIÓN ---
function guardarAsignacionMasiva() {
    const id_ev = $('#id_evento_asignar').val();
    const empleados = $('#select_empleados').val();

    if (!empleados || empleados.length === 0) {
        return Swal.fire('Atención', 'Por favor selecciona al menos un empleado.', 'warning');
    }

    $.post('acciones_eventos.php', {
        accion: 'asignar_usuarios_evento',
        id_evento: id_ev,
        empleados: empleados
    }, function(res) {
        if(res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Personal Asignado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
            if ($.fn.select2) $('#select_empleados').val(null).trigger('change');
            cargarListaAsignados(id_ev);
        }
    }, 'json');
}

// --- CARGAR LISTA DE ASIGNADOS ---
function cargarListaAsignados(id) {
    $.post('acciones_eventos.php', { accion: 'listar_asignados', id_evento: id }, function(data) {
        let html = '';
        data.forEach(asig => {
            let badge = asig.confirmado == 1 
                ? '<span class="badge badge-success px-2">Confirmó Asistencia</span>' 
                : '<span class="badge badge-warning px-2">Pendiente</span>';
            
            html += `<tr>
                <td class="align-middle font-weight-bold">${asig.nombre}</td>
                <td class="text-center align-middle">${badge}</td>
                <td class="text-center align-middle">
                    <button class="btn btn-outline-danger btn-xs" onclick="eliminarAsignacion(${asig.id_asignacion}, ${id})">
                        <i class="fas fa-user-minus"></i>
                    </button>
                </td>
            </tr>`;
        });
        $('#listaAsignados').html(html || '<tr><td colspan="3" class="text-center py-3 text-muted">No hay personal convocado para este evento</td></tr>');
    }, 'json');
}

// --- ELIMINAR ASIGNACIÓN ---
function eliminarAsignacion(id_asig, id_ev) {
    $.post('acciones_eventos.php', { accion: 'quitar_asignacion', id_asignacion: id_asig }, function() {
        cargarListaAsignados(id_ev);
    });
}

// --- AL ABRIR UN NUEVO EVENTO ---
function limpiarModalEvento() {
    $('#id_evento_edit').val(''); 
    $('#formEvento')[0].reset();
    $('#seccionOpciones').hide();
    $('#listaOpcionesCargadas').html('');
    $('#modalEventoLabel').html('<i class="fas fa-calendar-plus"></i> Nuevo Evento');
    $('#btnGuardarEvento').html('<i class="fas fa-save"></i> Guardar Encabezado').prop('disabled', false);
}

// --- GUARDAR ENCABEZADO DEL EVENTO ---
function guardarEvento() {
    const form = $('#formEvento');
    if (!form[0].checkValidity()) { form[0].reportValidity(); return; }

    $('#btnGuardarEvento').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    $.ajax({
        url: 'acciones_eventos.php',
        type: 'POST',
        data: form.serialize() + '&accion=guardar_evento',
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Encabezado Guardado', text: 'Ahora puedes agregar las opciones o preguntas.', timer: 2000 });
                $('#id_evento_edit').val(res.id);
                $('#seccionOpciones').fadeIn();
                $('#btnGuardarEvento').html('<i class="fas fa-check"></i> Actualizar Encabezado').prop('disabled', false);
                cargarTablaOpciones(res.id);
            }
        }
    });
}

// --- GUARDAR ÍTEM / PREGUNTA ---
function guardarOpcion() {
    const id_evento = $('#id_evento_edit').val();
    let formData = new FormData($('#formOpcion')[0]);
    formData.append('accion', 'guardar_opcion');
    formData.append('id_evento', id_evento);

    $.ajax({
        url: 'acciones_eventos.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json', // IMPORTANTE: Quitamos JSON.parse() manual
        success: function(res) {
            if(res.status === 'success') {
                $('#formOpcion')[0].reset();
                $('.custom-file-label').html('Elegir foto...');
                cargarTablaOpciones(id_evento);
                Swal.fire({ icon: 'success', title: 'Añadido', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });
            }
        }
    });
}

// --- CARGAR TABLA DE ÍTEMS EN EL MODAL ---
function cargarTablaOpciones(id_evento) {
    $.post('acciones_eventos.php', { accion: 'listar_opciones', id_evento: id_evento }, function(opciones) {
        let html = '';
        opciones.forEach(op => {
            let img = op.ruta_imagen ? `<i class="fas fa-image text-primary" title="Tiene imagen"></i>` : `<i class="fas fa-times text-muted"></i>`;
            
            // Formateamos la fecha para que no se vea vacía si no existe
            let fechaTxt = op.fecha_opcion ? op.fecha_opcion : '---';
            let grupoTxt = op.grupo ? `<span class="badge badge-dark">${op.grupo}</span>` : '---';

            html += `
            <tr>
                <td class="small text-muted">${op.pregunta}</td>
                <td class="font-weight-bold text-dark">${op.titulo}</td>
                <td class="text-center">${grupoTxt}</td>
                <td class="text-center small">${fechaTxt}</td>
                <td class="text-center">${img}</td>
                <td class="text-center">
                    <button class="btn btn-danger btn-xs shadow-sm" onclick="eliminarOpcion(${op.id_opcion}, ${id_evento})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        });
        
        // Si no hay datos, mostramos aviso con 6 columnas de ancho
        $('#listaOpcionesCargadas').html(html || '<tr><td colspan="6" class="text-center text-muted py-3">No hay ítems registrados</td></tr>');
    }, 'json');
}

// --- ELIMINAR OPCIÓN ---
function eliminarOpcion(id_op, id_ev) {
    $.post('acciones_eventos.php', { accion: 'eliminar_opcion', id_opcion: id_op }, function() {
        cargarTablaOpciones(id_ev);
    });
}

// --- CARGAR LISTADO GENERAL ---
function cargarListaEventos() {
    $.post('acciones_eventos.php', { accion: 'listar_eventos_general' }, function(data) {
        let html = '';
        const ahora = new Date();
        
        data.forEach(ev => {
            const fFin = new Date(ev.fecha_fin);
            const badge = (ahora > fFin) ? '<span class="badge badge-secondary">Finalizado</span>' : '<span class="badge badge-success">Activo</span>';
            
            html += `<tr>
                        <td class="font-weight-bold text-dark">${ev.nombre}</td>
                        <td><span class="badge badge-light text-uppercase border">${ev.tipo}</span></td>
                        <td><small>${ev.fecha_inicio} / ${ev.fecha_fin}</small></td>
                        <td class="text-center">${badge}</td>
                        <td class="text-center">
                            <button class="btn btn-success btn-sm" onclick="descargarExcelResultados(${ev.id_evento}, '${ev.nombre}')" title="Excel">
                                <i class="fas fa-file-excel"></i>
                            </button>

                            <button class="btn btn-info btn-sm" onclick="abrirModalAsignacion(${ev.id_evento}, '${ev.nombre}')" title="Asignar Personal">
                                <i class="fas fa-users text-white"></i>
                            </button>

                            <button class="btn btn-primary btn-sm" onclick="prepararEdicion(${ev.id_evento})" title="Editar">
                                <i class="fas fa-edit text-white"></i>
                            </button>

                            <button class="btn btn-danger btn-sm" onclick="eliminarEventoRaiz(${ev.id_evento})" title="Eliminar">
                                <i class="fas fa-trash text-white"></i>
                            </button>
                        </td>
                    </tr>`; 
        });
        
        $('#cuerpoListaEventos').html(html || '<tr><td colspan="5" class="text-center">Vacío</td></tr>');
        $('#modalListaEventos').modal('show');
    }, 'json');
}

// --- EDITAR DESDE LA LISTA ---
function prepararEdicion(id) {
    $('#modalListaEventos').modal('hide');
    $.post('acciones_eventos.php', { accion: 'obtener_evento', id_evento: id }, function(ev) {
        $('#id_evento_edit').val(ev.id_evento);
        $('input[name="nombre"]').val(ev.nombre);
        $('select[name="tipo"]').val(ev.tipo);
        $('input[name="fecha_inicio"]').val(ev.fecha_inicio.replace(" ", "T"));
        $('input[name="fecha_fin"]').val(ev.fecha_fin.replace(" ", "T"));
        $('textarea[name="descripcion"]').val(ev.descripcion);
        
        $('#seccionOpciones').show();
        cargarTablaOpciones(id);
        $('#modalEventoLabel').html('<i class="fas fa-edit"></i> Editando Evento #' + id);
        $('#modalEvento').modal('show');
    }, 'json');
}

// --- ELIMINAR TODO EL EVENTO ---
function eliminarEventoRaiz(id) {
    Swal.fire({ title: '¿Estás seguro?', text: "Se borrará el evento y todas sus respuestas.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, borrar todo' }).then((result) => {
        if (result.isConfirmed) {
            $.post('acciones_eventos.php', { accion: 'eliminar_evento_completo', id_evento: id }, function() {
                cargarListaEventos();
                Swal.fire('Eliminado', 'El registro ha sido borrado.', 'success');
            }, 'json');
        }
    });
}

function eliminarOpcion(id_opcion, id_evento) {
    Swal.fire({
        title: '¿Eliminar este ítem?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        confirmButtonText: 'Sí, borrar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('acciones_eventos.php', { accion: 'eliminar_opcion', id_opcion: id_opcion }, function() {
                cargarTablaOpciones(id_evento);
            });
        }
    });
}

function descargarExcelResultados(id_evento, nombreEvento) {
    $.post('acciones_eventos.php', { accion: 'obtener_resultados_excel', id_evento: id_evento }, function(data) {
        if(data.length === 0) {
            return Swal.fire('Sin datos', 'Aún no hay respuestas para este evento.', 'info');
        }

        // 1. Organizar datos por Empleado (Pivote)
        const reporte = {};
        data.forEach(fila => {
            if (!reporte[fila.id_empleado]) {
                reporte[fila.id_empleado] = { 
                    "ID Empleado": fila.id_empleado,
                    "Nombre": fila.nombre_empleado,
                    "Fecha Participación": fila.fecha_registro 
                };
            }
            // Creamos una columna dinámica con el nombre de la pregunta
            reporte[fila.id_empleado][fila.pregunta] = fila.respuesta;
        });

        // 2. Convertir el objeto a un array plano para SheetJS
        const datosFinales = Object.values(reporte);

        // 3. Crear el libro de Excel
        const ws = XLSX.utils.json_to_sheet(datosFinales);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Resultados");

        // 4. Descargar archivo
        const fechaCorte = new Date().toISOString().slice(0,10);
        XLSX.writeFile(wb, `Reporte_${nombreEvento}_${fechaCorte}.xlsx`);
        
    }, 'json');
}


</script>
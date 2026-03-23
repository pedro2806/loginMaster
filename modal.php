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
                            <div class="col-md-12 form-group" id="divPregunta">
                                <label class="small font-weight-bold">Pregunta / Categoría (Solo para Encuestas)</label>
                                <input type="text" name="pregunta_texto" id="pregunta_texto" class="form-control form-control-sm" placeholder="Ej: ¿Qué tal te pareció el ponente?">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="small font-weight-bold">Texto de la Opción / Ítem</label>
                                <input type="text" name="titulo" class="form-control form-control-sm" placeholder="Ej: Excelente / Juan Pérez" required>
                            </div>
                            <div class="col-md-6 form-group" id="divFotoAdmin">
                                <label class="small font-weight-bold">Imagen (Opcional - Votaciones)</label>
                                <div class="custom-file">
                                    <input type="file" name="foto" class="custom-file-input custom-file-input-sm" id="inputFoto">
                                    <label class="custom-file-label col-form-label-sm" for="inputFoto">Elegir foto...</label>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-success btn-sm px-4 shadow-sm" id="btnAñadirOpcion" onclick="guardarOpcion()">
                                <i class="fas fa-plus"></i> Añadir a la Lista
                            </button>
                        </div>
                    </form>
                    
                    <div class="table-responsive mt-3" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-striped table-hover">
                            <thead class="bg-gray-200 small">
                                <tr>
                                    <th>Pregunta/Bloque</th>
                                    <th>Opción/Ítem</th>
                                    <th class="text-center">IMG</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="listaOpcionesCargadas" class="small">
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

<script>
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
            let img = op.ruta_imagen ? `<i class="fas fa-image text-primary"></i>` : `<i class="fas fa-times text-muted"></i>`;
            html += `<tr>
                <td>${op.pregunta}</td>
                <td class="font-weight-bold">${op.titulo}</td>
                <td class="text-center">${img}</td>
                <td class="text-center">
                    <button class="btn btn-danger btn-xs" onclick="eliminarOpcion(${op.id_opcion}, ${id_evento})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });
        $('#listaOpcionesCargadas').html(html || '<tr><td colspan="4" class="text-center">No hay ítems</td></tr>');
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
                        <button class="btn btn-success btn-sm" onclick="descargarExcelResultados(${ev.id_evento}, '${ev.nombre}')" title="Descargar Excel">
                            <i class="fas fa-file-excel"></i>
                        </button>

                        <button class="btn btn-primary btn-sm" onclick="prepararEdicion(${ev.id_evento})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>

                        <button class="btn btn-danger btn-sm" onclick="eliminarEventoRaiz(${ev.id_evento})" title="Eliminar">
                            <i class="fas fa-trash"></i>
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
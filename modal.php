<div class="modal fade" id="modalEvento" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"> <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-check"></i> Gestión de Evento</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="formEvento">
                    <input type="hidden" name="id_evento" id="id_evento_edit">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nombre</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Tipo</label>
                            <select name="tipo" id="tipo_evento_select" class="form-control form-control-sm" required>
                                <option value="votacion">Votación (Fotos)</option>
                                <option value="asistencia">Asistencia (Cursos)</option>
                                <option value="encuesta">Encuesta</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Inicio</label>
                            <input type="datetime-local" name="fecha_inicio" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Fin</label>
                            <input type="datetime-local" name="fecha_fin" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarEvento" onclick="guardarEvento()">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>

                <hr>

                <div id="seccionOpciones" style="display:none;">
                    <h6 class="font-weight-bold text-primary">Agregar Ítems (Participantes / Cursos)</h6>
                    <form id="formOpcion" enctype="multipart/form-data" class="bg-light p-2 border rounded">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <input type="text" name="titulo" class="form-control form-control-sm" placeholder="Nombre/Título" required>
                            </div>
                            <div class="col-md-4 form-group" id="divFoto">
                                <div class="custom-file">
                                    <input type="file" name="foto" class="custom-file-input custom-file-input-sm" id="inputFoto">
                                    <label class="custom-file-label col-form-label-sm" for="inputFoto">Foto...</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success btn-sm btn-block" onclick="guardarOpcion()">
                                    <i class="fas fa-plus"></i> Añadir
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive mt-3" style="max-height: 200px; overflow-y: auto;">
                        <table class="table table-sm table-bordered">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th>Ítem</th>
                                    <th>Imagen</th>
                                    <th>Acción</th>
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
<script>
function guardarEvento() {
    const form = $('#formEvento');
    $.ajax({
        url: 'acciones_eventos.php',
        type: 'POST',
        data: form.serialize() + '&accion=guardar_evento',
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                Swal.fire('Éxito', 'Evento guardado', 'success');
                // Guardamos el ID en un hidden para que el formulario de opciones sepa a dónde ir
                $('#id_evento_edit').val(res.id);
                // Mostramos la sección de ítems
                $('#seccionOpciones').fadeIn();
            }
        }
    });
}

function guardarOpcion() {
    let formData = new FormData($('#formOpcion')[0]);
    formData.append('accion', 'guardar_opcion');
    formData.append('id_evento', $('#id_evento_edit').val());

    $.ajax({
        url: 'acciones_eventos.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            Swal.fire('Agregado', 'Ítem registrado', 'success');
            $('#formOpcion')[0].reset();
            $('.custom-file-label').html('Foto...');
            // Aquí llamarías a una función para listar las opciones en la tablita de abajo
        }
    });
}
</script>
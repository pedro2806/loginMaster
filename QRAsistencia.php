<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia - Móvil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-card { max-width: 450px; margin: 20px auto; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .course-info { background: #007bff; color: white; border-radius: 20px 20px 0 0; padding: 20px; }
        .form-section { padding: 25px; background: white; border-radius: 0 0 20px 20px; }
        .readonly-custom { background-color: #f8f9fa !important; border: 1px solid #dee2e6; color: #555; }
        .badge-info { background: rgba(255,255,255,0.2); border-radius: 10px; padding: 5px 12px; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="card main-card">
        <div class="course-info p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-shrink-0">
                    <img src="../activos/img/MESS_07_CuboMess_2.png" alt="Logo MESS" width="80px">
                </div>
                
                <div class="flex-grow-1">
                    <h6 id="txt-nombre-curso" class="mb-1 fw-bold text-uppercase" style="font-size: 0.95rem; line-height: 1.2;">
                        Cargando curso...
                    </h6>
                    
                    <div class="d-flex flex-wrap gap-2 mb-1">
                        <small id="txt-fecha" class="badge-info px-8">--/--/--</small>
                        <small id="txt-duracion" class="badge-info px-2">-- hrs</small>
                    </div>
                    
                    <p class="small mb-0" style="font-size: 0.9rem;">
                        Instr: <strong id="txt-instructor">---</strong>
                    </p>
                </div>
            </div>
        </div>

        <div class="form-section" id="formulario-asistencia">            
                <center><h6>Regisra tu asistencia</h6></center>
                <div class="mb-4">
                    <label class="form-label">Correo Institucional</label>
                    <input type="email" id="correo" class="form-control form-control-lg" placeholder="usuario@empresa.com" required>
                </div>

                <div id="campos-extra" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label small">Nombre del Asistente</label>
                        <input type="text" id="nombre" class="form-control readonly-custom" readonly>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label small">Área</label>
                            <input type="text" id="area" class="form-control readonly-custom" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Region</label>
                            <input type="text" id="region" class="form-control readonly-custom" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label bold">Nave</label>
                            <input type="text" id="nave" class="form-control" required>
                        </div>
                    </div>
                    <button type="button" onClick="registrarAsistencia()" class="btn btn-success btn-lg w-100 mt-4">Registrar mi Asistencia</button>
                </div>            
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 1. Capturar datos de la URL mediante GET
    const urlParams = new URLSearchParams(window.location.search);
    
    document.getElementById('txt-nombre-curso').innerText = urlParams.get('curso') || 'Curso no definido';
    document.getElementById('txt-fecha').innerText = urlParams.get('fecha') || 'Fecha pendiente';
    document.getElementById('txt-instructor').innerText = urlParams.get('instructor') || 'N/A';
    document.getElementById('txt-duracion').innerText = urlParams.get('duracion') || '0 min';

    // 2. Lógica de validación de correo
    const inputCorreo = document.getElementById('correo');
    
    function validateEmail(email) {        
        const correo = inputCorreo.value;
        if (correo.includes('@')) {
            
            $.ajax({
                type: 'POST',
                url: 'acciones_inicio.php',
                data: { accion: 'validar_usuario', correo },
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        const usuario = response.usuario;
                        document.getElementById('nombre').value = usuario.nombre;
                        document.getElementById('area').value = usuario.departamento;
                        document.getElementById('region').value = usuario.region;
                        
                        // Mostrar campos y hacer scroll suave
                        document.getElementById('campos-extra').style.display = 'block';
                    } else {
                        swal.fire({
                            icon: 'error',
                            title: 'Usuario no encontrado',
                            text: 'El correo ingresado no corresponde a ningún empleado registrado.',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                },
                error: function() {
                    swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo validar el correo. Intenta nuevamente más tarde.',
                        confirmButtonText: 'Aceptar'
                    });
                }   
            });
        } else {
            swal.fire({
                icon: 'error',
                title: 'Correo inválido',
                text: 'Por favor, ingresa un correo electrónico válido.',
                confirmButtonText: 'Aceptar'
            });                  
        }
    };

    inputCorreo.addEventListener('change', validateEmail);
    
    function registrarAsistencia() {
        //Valida Nave
        const naveInput = document.getElementById('nave');
        if (naveInput.value.trim() === '') {
            swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Por favor, ingresa la nave en la que te encuentras.',
                confirmButtonText: 'Aceptar'
            });
            return;

            $('nave').addClass('error');
        }

        const correo = inputCorreo.value;
        const nombre = document.getElementById('nombre').value;
        const area = document.getElementById('area').value;
        const nave = document.getElementById('nave').value;
        const curso = document.getElementById('txt-nombre-curso').innerText;
        const fecha = document.getElementById('txt-fecha').innerText;
        const instructor = document.getElementById('txt-instructor').innerText;
        const duracion = document.getElementById('txt-duracion').innerText;

        $.ajax({
            type: 'POST',
            url: 'acciones_inicio.php',
            data: { 
                accion: 'registrar_asistencia', 
                correo, nombre, area, nave, curso, fecha, instructor, duracion 
            },
            success: function(response) {
                console.log(response);
                if (response.success) {
                    swal.fire({
                        icon: 'success',
                        title: 'Asistencia registrada',
                        text: 'Tu asistencia ha sido registrada exitosamente.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {                        
                        $('#formulario-asistencia').html('<div class="text-center"><h5 class="text-success">¡Gracias por registrar tu asistencia!</h5><p>Bienvenido al curso.</p><a href="index.php" class="btn btn-primary">Ir al inicio</a></div>');
                    });
                } else {
                    swal.fire({
                        icon: 'warning',
                        title: 'Atencion!',
                        text: response.message || 'No se pudo registrar tu asistencia. Intenta nuevamente.',
                        confirmButtonText: 'Aceptar'
                    });
                }
            },
            error: function() {
                swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo registrar tu asistencia. Intenta nuevamente más tarde.',
                    confirmButtonText: 'Aceptar'
                });
            }
        });

    }
</script>

</body>
</html>
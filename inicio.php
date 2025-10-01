<?php
    session_start();
    include '../ControlVehicular/conn.php';
    if(empty($_COOKIE['noEmpleadoL'])){
        echo '<script>window.location.assign("index.php")</script>';
        exit;
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Panel de Usuario</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">    
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fc; }
        .profile-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
            padding: 2rem 1.5rem;
            margin-bottom: 1rem;
        }
        .profile-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .stat-box {
            border-radius: 0.2rem;
            padding: 0.2rem;
            margin-bottom: 0.2rem;
            background: #f4f6fb;
            text-align: center;
        }
        .stat-box h4 { margin: 0; font-weight: 600; }
        .stat-box p { margin: 0; font-size: 0.95rem; color: #6c757d; }
        .card-action {
            border: none;
            border-radius: 0.5rem;
            transition: box-shadow .2s;
        }
        .card-action:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08);
        }
        .fc .fc-list-event-title { font-weight: 600; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">
                    <div class="row">
                        <!-- Perfil de Usuario -->
                        <div class="col-xl-3 col-md-4">
                            <div class="profile-card text-center shadow">
                                <div class="profile-avatar mx-auto mb-1">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <h3 class="mb-1 px-0 py-0 b" style="color: #1c83f1b9;"><strong><?php echo isset($_COOKIE['nombredelusuarioL']) ? htmlspecialchars($_COOKIE['nombredelusuarioL']) : 'Usuario'; ?></strong></h3>
                                <ul class="list-group list-group-flush mb-1">
                                    <li class="list-group-item px-0 py-0 border-1"><strong style="font-size:1.4rem;">No. Empleado:</strong><p style="font-size:1.4rem;"><?php echo isset($_COOKIE['noEmpleadoL']) ? htmlspecialchars($_COOKIE['noEmpleadoL']) : '0000'; ?></p></li>
                                    <li class="list-group-item px-0 py-0 border-1"><strong style="font-size:1.4rem;">Área:</strong><p style="font-size:1.4rem;" id="lblArea"></p></li>
                                    <li class="list-group-item px-0 py-0 border-1"><strong style="font-size:1.4rem;">Jefe Directo:</strong><p style="font-size:1.4rem;" id="lblJefe"></p></li>
                                </ul>
                                <br>
                                <div class="row">                        
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box mb-1" style="background: #484cacff;">
                                            <h5 id="antig" name="antig" style="color:#fff;"></h5>
                                            <p style="color:#fff;">Antigüedad</p>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box mb-1" style="background: #0fa083ff; ">
                                            <h5 id="fechaIngreso" name="fechaIngreso" style="color:#fff;"></h5>
                                            <p style="color:#fff;">Fecha de ingreso</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                        
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box mb-1" style="background: #484cacff;">
                                            <h5 id ="diasSol" name="diasSol" style="color:#fff;"></h5>
                                            <p style="color:#fff;">Dias Solicitados</p>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box mb-1" style="background: #0fa083ff;">
                                            <h5 id="diasDisp" name="diasDisp" style="color:#fff;"></h5>
                                            <p style="color:#fff;">Días Disponibles</p>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <div class="stat-box mb-1" style="background: #164a98ff;">
                                    <h5 id ="vehiculoAsignado" name="vehiculoAsignado" style="color:#fff;"></h5>
                                    <p style="color:#fff;">Vehículo asignado</p>
                                </div>
                                <div class="stat-box" style="background: #e6fff5; display:none">
                                    <h5 id ="equipoComputo" name="equipoComputo"></h5>
                                    <p>Equipo de cómputo</p>
                                </div>
                                <br>
                                <button class="btn btn-outline-warning btn-block mt-3" data-toggle="modal" data-target="#modalCambiarContrasena">
                                    <i class="fas fa-key"></i> Cambiar Contraseña
                                </button>
                                <a class = "btn btn-outline-danger btn-block mt-3" href = "#" data-toggle = "modal" data-target = "#logoutModalN">
                                    <i class = "fas fa-sign-out-alt fa-sm fa-fw mr-2 text-red-400"></i>
                                    Salir
                                </a>
                                <br>
                            </div>
                        </div>
                        <!-- Accesos rápidos y tablero -->
                        <div class="col-xl-9 col-md-8">
                            <div class="row">
                                <!-- VACACIONES WARNIGN -->
                                <div class="col-md-3 mb-4" id="divVacaciones" style="display:none">
                                    <div class="card card-action border-left-warning shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../incidencias/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuario" id="id_usuario" value="">
                                                <input type="hidden" name="nombredelusuario" id="nombredelusuario" value="">
                                                <input type="hidden" name="noEmpleado" id="noEmpleado" value="">
                                                <input type="hidden" name="correo" id="correo" value="">
                                                <input type="hidden" name="sistema" id="sistema" value="vacaciones">
                                                <button type="submit" class="btn btn-outline-warning btn-block">
                                                    <i class="far fa-check-square fa-lg"></i><br>Vacaciones
                                                </button>
                                            </form>                                            
                                        </div>
                                    </div>
                                </div>

                                <!-- CONTROL VEHICULAR DANGER -->
                                <div class="col-md-3 mb-4" id="divControlVehicular" style="display:none">
                                    <div class="card card-action border-left-danger shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../ControlVehicular/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioCV" id="id_usuarioCV" value="">
                                                <input type="hidden" name="nombredelusuarioCV" id="nombredelusuarioCV" value="">
                                                <input type="hidden" name="noEmpleadoCV" id="noEmpleadoCV" value="">
                                                <input type="hidden" name="correoCV" id="correoCV" value="">                                                
                                                <button type="submit" class="btn btn-outline-danger btn-block">
                                                    <i class="fas fa-car fa-lg"></i><br>Ctrl Vehicular
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- HORAS EXTRA INFO -->
                                <div class="col-md-3 mb-4" id="divHorasExtra" style="display:none">
                                    <div class="card card-action border-left-info shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../horasextra/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioHR" id="id_usuarioHR" value="">
                                                <input type="hidden" name="nombredelusuarioHR" id="nombredelusuarioHR" value="">
                                                <input type="hidden" name="noEmpleadoHR" id="noEmpleadoHR" value="">
                                                <input type="hidden" name="correoHR" id="correoHR" value="">
                                                <button type="submit" class="btn btn-outline-info btn-block">
                                                    <i class="fas fa-clock fa-lg"></i><br>Hrs Extra
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- INCIDENCIAS PRIMARY -->
                                <div class="col-md-3 mb-4" id="divIncidencias" style="display:none">
                                    <div class="card card-action border-left-primary shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../incidencias/incidencias/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioI" id="id_usuarioI" value="">
                                                <input type="hidden" name="nombredelusuarioI" id="nombredelusuarioI" value="">
                                                <input type="hidden" name="noEmpleadoI" id="noEmpleadoI" value="">
                                                <input type="hidden" name="correoI" id="correoI" value="">
                                                <input type="hidden" name="sistema" id="sistema" value="incidencias">
                                                <button type="submit" class="btn btn-outline-primary btn-block">
                                                    <i class="fas fa-list fa-lg"></i><br>Incidencias
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- PLANEACION DARK -->
                                <div class="col-md-3 mb-4" id="divPlaneacion" style="display:none">
                                    <div class="card card-action border-left-dark shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../planeacion/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioPla" id="id_usuarioPla" value="">
                                                <input type="hidden" name="nombredelusuarioPla" id="nombredelusuarioPla" value="">
                                                <input type="hidden" name="noEmpleadoPla" id="noEmpleadoPla" value="">
                                                <input type="hidden" name="correoPla" id="correoPla" value="">
                                                <button type="submit" class="btn btn-outline-dark btn-block">
                                                    <i class="fas fa-calendar fa-lg"></i><br>Planeación
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- CAPACITACION WARNING -->
                                <div class="col-md-3 mb-4" id="divCapacitacion" style="display:none">
                                    <div class="card card-action border-left-warning shadow h-100">
                                        <div class="card-body text-center">
                                            <a href="https://messbook.com.mx/capacitacion" class="btn btn-outline-warning btn-block">
                                                <i class="fas fa-list fa-lg"></i><br>Capacitación
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI'S DANGER -->
                                <div class="col-md-3 mb-4" id="divKPIs" style="display:none">
                                    <div class="card card-action border-left-danger shadow h-100">
                                        <div class="card-body text-center">
                                            <a onclick="" class="btn btn-outline-danger btn-block">
                                                <i class="fas fa-list fa-lg"></i><br>KPI's
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI'S INFO -->
                                <div class="col-md-3 mb-4" id="divTI" style="display:none">
                                    <div class="card card-action border-left-info shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="inicio">
                                                <input type="hidden" name="id_usuarioCV" id="id_usuarioCV" value="">
                                                <input type="hidden" name="nombredelusuarioCV" id="nombredelusuarioCV" value="">
                                                <input type="hidden" name="noEmpleadoCV" id="noEmpleadoCV" value="">
                                                <input type="hidden" name="correoCV" id="correoCV" value="">
                                                <button type="submit" class="btn btn-outline-info btn-block">
                                                    <i class="fas fa-laptop fa-lg"></i><br>TI 
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <!-- Tablero de avisos -->
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header bg-light text-black py-2">
                                            <h6 class="m-2 font-weight-bold">Tablero de Avisos</h6>
                                        </div>
                                        <div class="card-body">
                                            <embed id="vistaPrevia" src='https://www.mess.com.mx/wp-content/uploads/2025/10/Mural-Octubre-2025.pdf#zoom=60' type="application/pdf" width="100%" height="500px" />
                                        </div>
                                    </div>
                                </div>
                                <!-- Agenda Sala de Juntas -->
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header bg-light text-black py-2 d-flex justify-content-between align-items-center">
                                            <span class="font-weight-bold">Agenda Sala de Juntas</span>
                                            <button onclick="irSalaJuntas()" class="btn btn-success btn-sm">Ir a Sala de Juntas</button>
                                        </div>
                                        <div class="card-body">
                                            <div id="calendar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS 2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <!-- Modal Cambiar Contraseña -->
    <div class="modal fade" id="modalCambiarContrasena" tabindex="-1" role="dialog" aria-labelledby="modalCambiarContrasenaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="formCambiarContrasena" method="POST" action="cambiar_contrasena.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCambiarContrasenaLabel">Cambiar Contraseña</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="contrasena_actual">Contraseña Actual</label>
                            <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" required>
                        </div>
                        <div class="form-group">
                            <label for="nueva_contrasena">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmar_contrasena">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Scripts -->
    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src = "vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src = "vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src = "js/sb-admin-2.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src = "https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js"></script> 
    
    <script>
        $(document).ready(function () {           
            verCalendarioLogin();
            validaOpciones();
            infoEmpleado();   
            obtenerPlaca();
            
            // Asigna los valores de las cookies a los campos del formulario
            document.getElementById('id_usuario').value = getCookie('id_usuarioL');
            document.getElementById('nombredelusuario').value = getCookie('nombredelusuarioL');
            document.getElementById('noEmpleado').value = getCookie('noEmpleadoL');
            document.getElementById('correo').value = getCookie('correoL');
            
            document.getElementById('id_usuarioCV').value = getCookie('id_usuarioL');
            document.getElementById('nombredelusuarioCV').value = getCookie('nombredelusuarioL');
            document.getElementById('noEmpleadoCV').value = getCookie('noEmpleadoL');
            document.getElementById('correoCV').value = getCookie('correoL');

            document.getElementById('id_usuarioHR').value = getCookie('id_usuarioL');
            document.getElementById('nombredelusuarioHR').value = getCookie('nombredelusuarioL');
            document.getElementById('noEmpleadoHR').value = getCookie('noEmpleadoL');
            document.getElementById('correoHR').value = getCookie('correoL');

            document.getElementById('id_usuarioI').value = getCookie('id_usuarioL');
            document.getElementById('nombredelusuarioI').value = getCookie('nombredelusuarioL');
            document.getElementById('noEmpleadoI').value = getCookie('noEmpleadoL');
            document.getElementById('correoI').value = getCookie('correoL');

            document.getElementById('id_usuarioPla').value = getCookie('id_usuarioL');
            document.getElementById('nombredelusuarioPla').value = getCookie('nombredelusuarioL');
            document.getElementById('noEmpleadoPla').value = getCookie('noEmpleadoL');
            document.getElementById('correoPla').value = getCookie('correoL');

        });

    // SE TRAE INFORACION DEL EMPLEADO, DIAS DE VACACIONES, DEPARTAMENTO, JEFE, ETC.        
        function infoEmpleado(){
            $.ajax({
                url: '../incidencias/getInfoLoginMaster.php',
                type: 'POST',
                dataType: 'json',
                data: {                    
                    noEmpleado: getCookie('noEmpleadoL'),                    
                    correo: getCookie('correoL'),
                    accion: 'getInfo'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $.each(response.info, function (index, infoUsr) {
                            $('#antig').text(infoUsr.antiguedad);
                            $('#diasDisp').text(infoUsr.diasdisponibles - infoUsr.diasSol);
                            $('#lblArea').text(infoUsr.departamento);
                            $('#lblJefe').text(infoUsr.jefe);
                            $('#fechaIngreso').text(infoUsr.fechaIngreso);
                            $('#diasSol').text(infoUsr.diasSol);
                        });
                    } else {
                        console.log(response.message); // Muestra error si aplica
                    }
                }
            });
            
        }
    //PARA VALIDAR QUE SISTEMAS SE MUESTRAN EN EL PANEL DE USUARIO
        function validaOpciones() {
            $.ajax({
                url: '../incidencias/getInfoLoginMaster.php',
                type: 'POST',
                dataType: 'json',
                data: {                    
                    noEmpleado: getCookie('noEmpleadoL'),                    
                    correo: getCookie('correoL'),
                    accion: 'ValidarOpciones'
                },
                success: function(info) {
                    $.each(info, function (index, infoAccesos) {                  
                        $('#antig').text(infoAccesos.antiguedad);
                        if (infoAccesos.estatus == '1') {
                            $('#' + infoAccesos.sistema).show();
                        } else {
                            $('#' + infoAccesos.sistema).hide();
                        }
                    });
                    
                }
            });
        }
        
    //FUNCION PARA OTENER ID_USUARIO Y PLACA
        function obtenerPlaca() {
            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'getPlaca',
                    noEmpleado: getCookie('noEmpleadoL')
                },
                success: function(response) {
                    if (response.success && response.vehiculos && response.vehiculos.length > 0) {
                        let html = '';
                        if (response.vehiculos.length === 1) {
                            html = response.vehiculos[0];
                        } else {
                            html = '<ul style="list-style:none;padding-left:0;margin-bottom:0;">';
                            response.vehiculos.forEach(function(vehiculo) {
                                html += '<li>' + vehiculo + '</li>';
                            });
                            html += '</ul>';
                        }
                        $('#vehiculoAsignado').html(html);
                    } else {
                        $('#vehiculoAsignado').text('Sin vehículo asignado');
                    }
                },
                error: function() {
                    $('#vehiculoAsignado').text('Error al obtener vehículo');
                }
            });
        }

    //FUNCION PARA CARGAR EL CALENDARIO DE LA SALA DE JUNTAS
        function verCalendarioLogin() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'listWeek',
                events: '../incidencias/SalaDeJuntas/acciones_calendarioGral.php?opcion=login',
                editable: false,
                locale: 'es',
                height: 500, // Altura fija en px
                contentHeight: 400, // Altura del contenido
                aspectRatio: 2, // Relación de aspecto (ancho/alto)
                eventContent: function(info) {
                    var nombreEmpleado = info.event.title;
                    var descripcion = info.event.extendedProps.descripcion || 'Sin descripción';
                    var displayText = nombreEmpleado + '<br>' + descripcion;
                    return { html: displayText };
                }
            });
            calendar.render();
        }
    //FUNCION PARA OBTENER COOKIES
        function getCookie(name) {
            let matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
            ));
            return matches ? decodeURIComponent(matches[1]) : undefined;
        }
    </script>
</body>
</html>
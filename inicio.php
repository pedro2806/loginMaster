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
                            <div class="profile-card text-center">
                                <div class="profile-avatar mx-auto mb-1">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <h5 class="mb-1"><?php echo isset($_COOKIE['nombredelusuario']) ? htmlspecialchars($_COOKIE['nombredelusuario']) : 'Usuario'; ?></h5>
                                <small class="text-muted d-block mb-2">No. Empleado: <b><?php echo isset($_COOKIE['noEmpleado']) ? htmlspecialchars($_COOKIE['noEmpleado']) : '0000'; ?></b></small>
                                <ul class="list-group list-group-flush mb-1">
                                    <li class="list-group-item px-0 py-0 border-0"><strong>Área:</strong><p id="lblArea"></p></li>
                                    <li class="list-group-item px-0 py-0 border-0"><strong>Jefe Directo:</strong><p id="lblJefe"></p></li>
                                </ul>
                                <div class="row">                        
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box mb-1" style="background: #f6f0ffff;">
                                            <h5 id ="antig" name="antig"></h5>
                                            <p>Antigüedad</p>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box mb-1" style="background: #fffbe6;">
                                            <h5 id="fechaIngreso" name="fechaIngreso"></h5>
                                            <p>Fecha de ingreso</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                        
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box mb-1" style="background: #f6f0ffff;">
                                            <h5 id ="diasSol" name="diasSol"></h5>
                                            <p>Dias Solicitados</p>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box mb-1" style="background: #fffbe6;">
                                            <h5 id="diasDisp" name="diasDisp"></h5>
                                            <p>Días Disponibles</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="stat-box mb-1" style="background: #e6f0ff;">
                                    <h5 id ="vehiculoAsignado" name="vehiculoAsignado"></h5>
                                    <p>Vehículo asignado</p>
                                </div>
                                <div class="stat-box" style="background: #e6fff5; display:none">
                                    <h5 id ="equipoComputo" name="equipoComputo"></h5>
                                    <p>Equipo de cómputo</p>
                                </div>
                                <button class="btn btn-outline-primary btn-block mt-3" data-toggle="modal" data-target="#modalCambiarContrasena">
                                    <i class="fas fa-key"></i> Cambiar Contraseña
                                </button>
                                <a class = "btn btn-outline-danger btn-block mt-3" href = "#" data-toggle = "modal" data-target = "#logoutModalN">
                                    <i class = "fas fa-sign-out-alt fa-sm fa-fw mr-2 text-red-400"></i>
                                    Salir
                                </a>
                            </div>
                        </div>
                        <!-- Accesos rápidos y tablero -->
                        <div class="col-xl-9 col-md-8">
                            <div class="row">
                                <div class="col-md-3 mb-4" id="divVacaciones" style="display:none">
                                    <div class="card card-action border-left-warning shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../incidencias/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuario" id="id_usuario" value="">
                                                <input type="hidden" name="nombredelusuario" id="nombredelusuario" value="">
                                                <input type="hidden" name="noEmpleado" id="noEmpleado" value="">
                                                <input type="hidden" name="correo" id="correo" value="">
                                                <button type="submit" class="btn btn-outline-warning btn-block">
                                                    <i class="far fa-check-square fa-lg"></i><br>Vacaciones
                                                </button>
                                            </form>                                            
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4" id="divControlVehicular" style="display:none">
                                    <div class="card card-action border-left-danger shadow h-100">
                                        <div class="card-body text-center">
                                            <a onclick="irControlVehicular()" class="btn btn-outline-danger btn-block">
                                                <i class="fas fa-car fa-lg"></i><br>Ctrl Veh
                                            </a>
                                        </div>
                                    </div>
                                </div>                                
                                <div class="col-md-3 mb-4" id="divTI" style="display:none">
                                    <div class="card card-action border-left-primary shadow h-100">
                                        <div class="card-body text-center">
                                            <a onclick="" class="btn btn-outline-primary btn-block">
                                                <i class="fas fa-laptop fa-lg"></i><br>TI 
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4" id="divHorasExtra" style="display:none">
                                    <div class="card card-action border-left-info shadow h-100">
                                        <div class="card-body text-center">
                                            <a onclick="irHrsExtra()" class="btn btn-outline-info btn-block">
                                                <i class="fas fa-clock fa-lg"></i><br>Hrs Extra
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4" id="divIncidencias" style="display:none">
                                    <div class="card card-action border-left-info shadow h-100">
                                        <div class="card-body text-center">
                                            <a onclick="" class="btn btn-outline-info btn-block">
                                                <i class="fas fa-list fa-lg"></i><br>Incidencias
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4" id="divCapacitacion" style="display:none">
                                    <div class="card card-action border-left-primary shadow h-100">
                                        <div class="card-body text-center">
                                            <a onclick="" class="btn btn-outline-primary btn-block">
                                                <i class="fas fa-list fa-lg"></i><br>Capacitación
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4" id="divKPIs" style="display:none">
                                    <div class="card card-action border-left-danger shadow h-100">
                                        <div class="card-body text-center">
                                            <a onclick="" class="btn btn-outline-danger btn-block">
                                                <i class="fas fa-list fa-lg"></i><br>KPI's
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <!-- Tablero de avisos -->
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header bg-primary text-white py-2">
                                            <h6 class="m-2 font-weight-bold">Tablero de Avisos</h6>
                                        </div>
                                        <div class="card-body">
                                            <embed id="vistaPrevia" src='https://www.mess.com.mx/wp-content/uploads/2025/03/Marzo-2024.pdf#zoom=60' type="application/pdf" width="100%" height="400px" />
                                        </div>
                                    </div>
                                </div>
                                <!-- Agenda Sala de Juntas -->
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                                            <span class="font-weight-bold">Agenda Sala de Juntas</span>
                                            <button onclick="irSalaJuntas()" class="btn btn-light btn-sm">Ir a Sala de Juntas</button>
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

    //FUNCION PARA REDIRIGIR AL SISTEMA DE VACACIONES
        function irVacaciones() {

            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuarioL'),
                    nombredelusuario: getCookie('nombredelusuarioL'),
                    noEmpleado: getCookie('noEmpleadoL'),
                    rol: getCookie('rolL'),
                    correo: getCookie('correoL')
                },
                success: function() {
                    window.location.href = '../incidencias/inicio';
                }
            });
        }
    //FUNCION PARA REDIRIGIR AL SISTEMA DE SALA DE JUNTAS
        function irSalaJuntas() {        
            
            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuarioL'),
                    nombredelusuario: getCookie('nombredelusuarioL'),
                    noEmpleado: getCookie('noEmpleadoL'),
                    rol: getCookie('rolL'),
                    correo: getCookie('correoL')
                },
                success: function() {
                    window.location.href = '../incidencias/SalaDeJuntas';
                }
            });
        }
    
    //FUNCION PARA REDIRIGIR AL CONTROL VEHICULAR
        function irControlVehicular() {        
            
            $.ajax({
                url: '../ControlVehicular/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuario'),
                    nombredelusuario: getCookie('nombredelusuario'),
                    noEmpleado: getCookie('noEmpleado'),
                    rol: getCookie('rol'),
                    correo: getCookie('correo')
                },
                success: function() {
                    window.location.href = '../ControlVehicular/inicio';
                }
            });
        }

    //FUNCION PARA REDIRIGIR A HORAS EXTRAS
        function irHrsExtra() {        
            
            $.ajax({
                url: '../horasextra/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuarioL'),
                    nombredelusuario: getCookie('nombredelusuarioL'),
                    noEmpleado: getCookie('noEmpleadoLL'),
                    rol: getCookie('rolL'),
                    correo: getCookie('correoL')
                },
                success: function() {
                    window.location.href = '../horasextra/inicio';
                }
            });
        }

    //FUNCION PARA REDIRIGIR AL SISTEMA DE INCIDENCIAS
        function irIncidencias() {        
            
            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuarioL'),
                    nombredelusuario: getCookie('nombredelusuarioL'),
                    noEmpleado: getCookie('noEmpleadoL'),
                    rol: getCookie('rolL'),
                    correo: getCookie('correoL')
                },
                success: function() {
                    window.location.href = '../incidencias/inicio';
                }
            });
        }
    
    //FUNCION PARA REDIRIGIR A CAPACITACION
        function irCapacitacion() {

            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuarioL'),
                    nombredelusuario: getCookie('nombredelusuarioL'),
                    noEmpleado: getCookie('noEmpleadoL'),
                    rol: getCookie('rolL'),
                    correo: getCookie('correoL')
                },
                success: function() {
                    window.location.href = '../incidencias/inicio';
                }
            });
        }

    //FUNCION PARA REDIRIGIR A KPI'S
        function irKpis() {

            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuarioL'),
                    nombredelusuario: getCookie('nombredelusuarioL'),
                    noEmpleado: getCookie('noEmpleadoL'),
                    rol: getCookie('rolL'),
                    correo: getCookie('correoL')
                },
                success: function() {
                    window.location.href = '../incidencias/inicio';
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
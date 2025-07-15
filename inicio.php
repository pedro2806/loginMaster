<?php
    session_start();
    include '../ControlVehicular/conn.php';
    if(empty($_COOKIE['noEmpleado'])){
        echo '<script>window.location.assign("index")</script>';
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
    <link href="../ControlVehicular/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">    
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css" rel="stylesheet">
    <link href="../ControlVehicular/css/sb-admin-2.min.css" rel="stylesheet">
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
            border-radius: 0.5rem;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
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
                                <ul class="list-group list-group-flush mb-3">
                                    <li class="list-group-item px-0 py-1 border-0"><strong>Área:</strong><p id="lblArea"></p></li>
                                    <li class="list-group-item px-0 py-1 border-0"><strong>Jefe Directo:</strong><p id="lblJefe"></p></li>
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
                                    <h5></h5>
                                    <p>Vehículo asignado</p>
                                </div>
                                <div class="stat-box" style="background: #e6fff5;">
                                    <h5></h5>
                                    <p>Equipo de cómputo</p>
                                </div>
                                <button class="btn btn-outline-primary btn-block mt-3" data-toggle="modal" data-target="#modalCambiarContrasena">
                                    <i class="fas fa-key"></i> Cambiar Contraseña
                                </button>
                            </div>
                        </div>
                        <!-- Accesos rápidos y tablero -->
                        <div class="col-xl-9 col-md-8">
                            <div class="row">
                                <div class="col-md-3 mb-4">
                                    <div class="card card-action border-left-warning shadow h-100">
                                        <div class="card-body text-center">
                                            <a onclick="irSalaJuntas()" class="btn btn-outline-warning btn-block">
                                                <i class="far fa-check-square fa-lg"></i><br>Incidencias
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="card card-action border-left-danger shadow h-100">
                                        <div class="card-body text-center">
                                            <a href="../ControlVehicular/" class="btn btn-outline-danger btn-block">
                                                <i class="fas fa-car fa-lg"></i><br>Control Vehicular
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="card card-action border-left-info shadow h-100">
                                        <div class="card-body text-center">
                                            <a href="../ControlVehicular/" class="btn btn-outline-info btn-block">
                                                <i class="fas fa-clock fa-lg"></i><br>Horas Extra
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="card card-action border-left-primary shadow h-100">
                                        <div class="card-body text-center">
                                            <a href="../ControlVehicular/" class="btn btn-outline-primary btn-block">
                                                <i class="fas fa-laptop fa-lg"></i><br>TI (Mtto y tickets)
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
                                            <h6 class="m-0 font-weight-bold">Tablero de Avisos</h6>
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
                                            <button onclick="irIncidencias()" class="btn btn-outline-light btn-sm">Ir a Sala de Juntas</button>
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
            <form id="formCambiarContrasena" method="POST" action="#">
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
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="../ControlVehicular/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js"></script>
    <script src="../ControlVehicular/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../ControlVehicular/js/sb-admin-2.min.js"></script>
    <?php
        /*if (in_array($_COOKIE['noEmpleado'], ['276','215','183','523','19'])) {
            echo '<script src="//code.tidio.co/7gdtsrztipqfhk4odfaiekkqicwhsvxb.js"></script>';
            echo '<script src="//code.tidio.co/ehwk9fqjsinnpptkgnupmphatkinnmwi.js"></script>';
        }*/
    ?>
    <script>
        $(document).ready(function () {
            verCalendarioLogin();
            infoEmpleado();
        });

        function infoEmpleado(){
            $.ajax({
                url: '../incidencias/getInfoLoginMaster.php',
                type: 'POST',
                data: {                    
                    noEmpleado: getCookie('noEmpleado'),                    
                    correo: getCookie('correo'),
                    accion: 'getInfo'
                },
                success: function(info) {
                    $.each(info, function (index, infoUsr) {                  
                        $('#antig').text(infoUsr.antiguedad);
                        $('#diasDisp').text(infoUsr.diasdisponibles - infoUsr.diasSol);
                        $('#lblArea').text(infoUsr.departamento);
                        $('#lblJefe').text(infoUsr.jefe);
                        $('#fechaIngreso').text(infoUsr.fechaIngreso);
                        $('#diasSol').text(infoUsr.diasSol);
                    });
                    
                }
            });

        }

        function irIncidencias() {        
            
            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuario'),
                    nombredelusuario: getCookie('nombredelusuario'),
                    noEmpleado: getCookie('noEmpleado'),
                    rol: getCookie('rol'),
                    correo: getCookie('correo')
                },
                success: function() {
                    window.location.href = '../incidencias/inicio';
                }
            });
        }

        function irSalaJuntas() {        
            
            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                data: {
                    id_usuario: getCookie('id_usuario'),
                    nombredelusuario: getCookie('nombredelusuario'),
                    noEmpleado: getCookie('noEmpleado'),
                    rol: getCookie('rol'),
                    correo: getCookie('correo')
                },
                success: function() {
                    window.location.href = '../incidencias/SalaDeJuntas';
                }
            });
        }

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
        function getCookie(name) {
            let matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
            ));
            return matches ? decodeURIComponent(matches[1]) : undefined;
        }
    </script>
</body>
</html>

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
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
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
                        <div class="col-xl-3 col-md-3">
                                <div class="profile-card text-center ">
                                    <div class="card shadow-sm border-0" style="max-width: 300px; background-color: #f8f9fa;">
                                    <div class="card-body text-start p-3">                                        
                                        <div class="profile-avatar mb-1 d-block mx-auto">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
                                        
                                        <h3 class="mb-1 fw-bold" style="color: #1c83f1;">
                                            <?php echo isset($_COOKIE['nombredelusuarioL']) ? htmlspecialchars($_COOKIE['nombredelusuarioL'], ENT_QUOTES, 'UTF-8') : 'Usuario Desconocido'; ?>
                                        </h3>
                                        <p class="text-muted mb-3">
                                            No. Empleado: <?php echo isset($_COOKIE['noEmpleadoL']) ? htmlspecialchars($_COOKIE['noEmpleadoL']) : '0000'; ?>
                                        </p>

                                        <ul class="list-group list-group-flush text-start">
                                            
                                            <li class="list-group-item px-0 py-1 bg-transparent border-top-0">
                                                <small class="text-muted fw-bold d-inline-block me-2">Área:</small>
                                                <span id="lblArea" class="fw-semibold text-dark"></span>
                                            </li>
                                            
                                            <li class="list-group-item px-0 py-1 bg-transparent border-bottom-0">
                                                <small class="text-muted fw-bold d-inline-block me-2">Jefe Directo:</small>
                                                <span id="lblJefe" class="fw-semibold text-dark"></span>
                                            </li>
                                        </ul>

                                    </div>
                                </div>
                                <br>
                                <div class="row"> 
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box p-2 mb-2" style="background: #484cacff;">
                                            <h6 id="antig" name="antig" style="color:#fff; margin-bottom: 0.1rem;"></h6>
                                            <p style="color:#fff; font-size: 0.8rem; margin-bottom: 0;">Antigüedad</p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box p-2 mb-2" style="background: #0fa083ff; ">
                                            <h6 id="fechaIngreso" name="fechaIngreso" style="color:#fff; margin-bottom: 0.1rem;"></h6>
                                            <p style="color:#fff; font-size: 0.8rem; margin-bottom: 0;">Fecha de ingreso</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row"> 
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box p-2 mb-2" style="background: #484cacff;">
                                            <h6 id ="diasSol" name="diasSol" style="color:#fff; margin-bottom: 0.1rem;"></h6>
                                            <p style="color:#fff; font-size: 0.8rem; margin-bottom: 0;">Dias Solicitados</p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-6 col-md-6">
                                        <div class="stat-box p-2 mb-2" style="background: #0fa083ff;">
                                            <h6 id="diasDisp" name="diasDisp" style="color:#fff; margin-bottom: 0.1rem;"></h6>
                                            <p style="color:#fff; font-size: 0.8rem; margin-bottom: 0;">Días Disponibles</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="stat-box mb-1" style="background: #164a98ff;">
                                    <h5 id ="vehiculoAsignado" name="vehiculoAsignado" style="color:#fff; margin-bottom: 0.1rem"></h5>
                                    <p style="color:#fff;">Vehículo asignado</p>
                                </div>
                                <div class="stat-box" style="background: #e6fff5; display:none">
                                    <h5 id ="equipoComputo" name="equipoComputo"></h5>
                                    <p>Equipo de cómputo</p>
                                </div>
                                <br>
                                <button class="btn btn-outline-primary btn-block mt-3" data-toggle="modal" data-target="#modalbuzon">
                                    <i class="fas fa-envelope-open-text"></i> Buzón de Sugerencias
                                </button>
                                
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
                        <div class="col-xl-9 col-md-9">
                            <div class="row">
                                <!-- VACACIONES WARNIGN -->
                                <div class="col-md-3 mb-2" id="divVacaciones" style="display:none">
                                    <div class="card card-action border-left-warning shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../incidencias/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuario" id="id_usuario" value="">
                                                <input type="hidden" name="nombredelusuario" id="nombredelusuario" value="">
                                                <input type="hidden" name="noEmpleado" id="noEmpleado" value="">
                                                <input type="hidden" name="correo" id="correo" value="">
                                                <input type="hidden" name="sistema" id="sistema" value="vacaciones">
                                                <button type="submit" class="btn btn-outline-warning btn-block">
                                                    <i class="far fa-check-square fa-lg"></i> Vacaciones
                                                </button>                                                
                                            </form>                                            
                                        </div>
                                    </div>
                                </div>

                                <!-- CONTROL VEHICULAR DANGER -->
                                <div class="col-md-3 mb-2" id="divControlVehicular" style="display:none">
                                    <div class="card card-action border-left-danger shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../ControlVehicular/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioCV" id="id_usuarioCV" value="">
                                                <input type="hidden" name="nombredelusuarioCV" id="nombredelusuarioCV" value="">
                                                <input type="hidden" name="noEmpleadoCV" id="noEmpleadoCV" value="">
                                                <input type="hidden" name="correoCV" id="correoCV" value="">                                                
                                                <button type="submit" class="btn btn-outline-danger btn-block">
                                                    <i class="fas fa-car fa-lg"></i> Ctrl Vehicular
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- HORAS EXTRA INFO -->
                                <div class="col-md-3 mb-2" id="divHorasExtra" style="display:none">
                                    <div class="card card-action border-left-info shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../horasextra/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioHR" id="id_usuarioHR" value="">
                                                <input type="hidden" name="nombredelusuarioHR" id="nombredelusuarioHR" value="">
                                                <input type="hidden" name="noEmpleadoHR" id="noEmpleadoHR" value="">
                                                <input type="hidden" name="correoHR" id="correoHR" value="">
                                                <button type="submit" class="btn btn-outline-info btn-block">
                                                    <i class="fas fa-clock fa-lg"></i> Hrs Extra
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- INCIDENCIAS PRIMARY -->
                                <div class="col-md-3 mb-2" id="divIncidencias" style="display:none">
                                    <div class="card card-action border-left-primary shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../incidencias/incidencias/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioI" id="id_usuarioI" value="">
                                                <input type="hidden" name="nombredelusuarioI" id="nombredelusuarioI" value="">
                                                <input type="hidden" name="noEmpleadoI" id="noEmpleadoI" value="">
                                                <input type="hidden" name="correoI" id="correoI" value="">
                                                <input type="hidden" name="sistema" id="sistema" value="incidencias">
                                                <button type="submit" class="btn btn-outline-primary btn-block">
                                                    <i class="fas fa-list fa-lg"></i> Incidencias
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- PLANEACION DARK -->
                                <div class="col-md-3 mb-2" id="divPlaneacion" style="display:none">
                                    <div class="card card-action border-left-dark shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="../planeacion/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioPla" id="id_usuarioPla" value="">
                                                <input type="hidden" name="nombredelusuarioPla" id="nombredelusuarioPla" value="">
                                                <input type="hidden" name="noEmpleadoPla" id="noEmpleadoPla" value="">
                                                <input type="hidden" name="correoPla" id="correoPla" value="">
                                                <button type="submit" class="btn btn-outline-dark btn-block">
                                                    <i class="fas fa-calendar fa-lg"></i> Planeación
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- CAPACITACION WARNING -->
                                <div class="col-md-3 mb-2" id="divCapacitacion" style="display:none">
                                    <div class="card card-action border-left-warning shadow h-100">
                                        <div class="card-body text-center">
                                            <div class=" class="btn-group" role="group"">
                                                <a href="https://messbook.com.mx/capacitacion" class="btn btn-outline-warning">
                                                    <i class="fas fa-list fa-lg"></i> Capacitación
                                                </a>
                                                <a href="Manual de Usuario Capacitacion.pdf" target="_blank" class="btn btn-outline-warning">
                                                    <i class="fas fa-file-pdf fa-lg"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI'S DANGER -->
                                <div class="col-md-3 mb-2" id="divKPIs" style="display:none">
                                    <div class="card card-action border-left-danger shadow h-100">
                                        <div class="card-body text-center">
                                            <form action="../kpis_pbi/indexK.php" method="post">
                                                <input type="hidden" name="pass" id="pass" value="">
                                                <button type="submit" class="btn btn-outline-danger btn-block">
                                                    <i class="fas fa-chart-line fa-lg"></i> KPI's
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI'S INFO -->
                                <div class="col-md-3 mb-2" id="divTI" style="display:none">
                                    <div class="card card-action border-left-info shadow h-100">
                                        <div class="card-body text-center">
                                            <form method="POST" action="inicio">
                                                <input type="hidden" name="id_usuarioTI" id="id_usuarioTI" value="">
                                                <input type="hidden" name="nombredelusuarioTI" id="nombredelusuarioTI" value="">
                                                <input type="hidden" name="noEmpleadoTI" id="noEmpleadoTI" value="">
                                                <input type="hidden" name="correoTI" id="correoTI" value="">
                                                <button type="submit" class="btn btn-outline-info btn-block">
                                                    <i class="fas fa-laptop fa-lg"></i> TI 
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <!-- Formulario para Tallas -->
                                <div class="col-md-6 mb-2">
                                    <div class="card shadow h-100">
                                        <div class="card-header bg-primary text-white py-2">
                                            <h6 class="m-2 font-weight-bold">Tablero de avisos</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="post">
                                                <div class="form-group">
                                                    <div id="alertTalla" class="alert alert-danger" role="alert">
                                                        <strong>Importante:</strong> Por favor, registra tu talla de uniforme.
                                                    </div>
                                                    <label for="talla">Talla:</label>
                                                    <select class="form-control" id="talla" name="talla" required>
                                                        <option value="">Seleccione una talla de uniforme</option>  
                                                        <option value="XS">XS</option>
                                                        <option value="S">S</option>
                                                        <option value="M">M</option>
                                                        <option value="L">L</option>
                                                        <option value="XL">XL</option>
                                                    </select>
                                                    <input type="hidden" name="noEmpleadoT" id="noEmpleadoT" value="">
                                                </div>                                                
                                                <center>
                                                    <div class="btn-group" role="group" aria-label="Basic example">
                                                        <button type="button" class="btn btn-success" onclick="registraTallas()">Actualizar Talla</button>
                                                        
                                                        <?php
                                                            $usuariosRegistran = array(183, 276, 523, 403);
                                                            if (in_array($_COOKIE['noEmpleadoL'], $usuariosRegistran)) {
                                                                echo '<button onclick="VerTallas()" type="button" class="btn btn-info" data-toggle="modal" data-target="#modalResultadosTallas">Ver Tallas</button>';
                                                                echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCarrusel">Ver Fotos y Votar</button>';
                                                            }
                                                        ?>
                                                    </div>
                                                    
                                                </center>
                                            </form>
                                            <br>
                                            
                                            <embed id="vistaPrevia" src='https://www.mess.com.mx/wp-content/uploads/2025/11/Mural-Noviembre-2025.-.pdf#zoom=60' type="application/pdf" width="100%" height="300px" />
                                        </div>
                                    </div>
                                </div>
                                <!-- Tablero de avisos 
                                <div class="col-md-6 mb-2">
                                    <div class="card shadow h-100">
                                        <div class="card-header bg-light text-black py-2">
                                            <h6 class="m-2 font-weight-bold">Tablero de Avisos</h6>
                                        </div>
                                        <div class="card-body">
                                            <embed id="vistaPrevia" src='https://www.mess.com.mx/wp-content/uploads/2025/10/Mural-Octubre-2025.pdf#zoom=60' type="application/pdf" width="100%" height="500px" />
                                        </div>
                                    </div>
                                </div> -->
                                <!-- Agenda Sala de Juntas -->
                                <div class="col-md-6 mb-2">
                                    <div class="card shadow h-100">
                                        <div class="card-header bg-primary text-light py-2 d-flex justify-content-between align-items-center">
                                            <span class="font-weight-bold">Agenda Sala de Juntas</span>
                                            <form method="POST" action="../incidencias/validaLoginMaster.php">
                                                <input type="hidden" name="id_usuarioSJ" id="id_usuarioSJ" value="">
                                                <input type="hidden" name="nombredelusuarioSJ" id="nombredelusuarioSJ" value="">
                                                <input type="hidden" name="noEmpleadoSJ" id="noEmpleadoSJ" value="">
                                                <input type="hidden" name="correoSJ" id="correoSJ" value="">
                                                <input type="hidden" name="sistema" id="sistema" value="saladeJuntas">
                                                <button type="submit" class="btn btn-success btn-sm">Ir a Sala de Juntas</button>
                                            </form> 
                                            
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
    <!-- Modal Buzon de Sugerencias -->
    <div class="modal fade" id="modalbuzon" tabindex="-1" role="dialog" aria-labelledby="modalbuzonLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="formbuzon" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalbuzonLabel">Buzón de Sugerencias</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="alertTalla" class="alert alert-primary" role="alert">
                        <strong>Aviso:</strong> Buzon de sugerencias para RRHH
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <select class="form-control" id="tipo" name="tipo" required>
                                <option value="">Seleccione el tipo de comentario</option>
                                <option value="Felicitacion">Felicitación</option>
                                <option value="Sugerencia">Sugerencia</option>
                                <option value="Queja">Queja</option>
                            </select>
                            <br>
                            <label for="comentario">Escribe tu comentario:</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-info" data-dismiss="modal" onClick="verBuzon()">Ver Buzón</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" onClick="BuzonSugerencias()">Enviar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Carrusel -->
    <div class="modal fade" id="modalCarrusel" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vota por tu foto favorita</h5>
                    <hr>
                    <?php
                        $usuariosRegistran = array(183, 276, 523, 403);
                        if (in_array($_COOKIE['noEmpleadoL'], $usuariosRegistran)) {
                            echo '<button onClick="verVotos()" type="button" class="btn btn-primary" id="btnVerVotos">Ver Votos</button>';
                        }
                    ?>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Carrusel -->
                    <div id="carouselFotos" class="carousel slide" data-ride="carousel">
                        <div class="carousel-inner">
                            <!-- Foto 1 -->
                            <div class="carousel-item active">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="1">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/1.jpg" width="100%" height="500" class="d-block w-100" alt="Foto 1">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 2 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="2">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/2.jpg" width="100%" height="800" class="d-block w-100" alt="Foto 2">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 3 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="3">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/3.jpg" width="100%" height="500" class="d-block w-100" alt="Foto 3">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 4 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="4">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/4.jpg" width="100%" height="500" class="d-block w-100" alt="Foto 4">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 5 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="5">Votar por esta foto</button>
                                </center><br>  
                                <img src="concursoHallowen2025/5.jpg" width="100%" height="500" class="d-block w-100" alt="Foto 5">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 6 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="6">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/6.jpg" width="100%" height="500" class="d-block w-100" alt="Foto 6">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 7 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="7">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/7.jpg" width="100%" height="500" class="d-block w-100" alt="Foto 7">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 8 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="8">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/8.jpg" width="100%" height="800" class="d-block w-100" alt="Foto 8">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 9 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="9">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/9.jpg" width="100%" height="500" class="d-block w-100" alt="Foto 9">
                                <div class="carousel-caption"></div>
                            </div>
                            <!-- Foto 10 -->
                            <div class="carousel-item">
                                <center>    
                                    <button class="btn btn-success votar-btn" data-foto="10">Votar por esta foto</button>
                                </center><br>
                                <img src="concursoHallowen2025/10.jpg" width="100%" height="800"class="d-block w-100" alt="Foto 10">
                                <div class="carousel-caption"></div>
                            </div>
                        </div>
                        <a class="carousel-control-prev" href="#carouselFotos" role="button" data-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </a>
                        <a class="carousel-control-next" href="#carouselFotos" role="button" data-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- MODAL TABLA RESULTADOS TALLAS -->
    <div class="modal fade" id="modalResultadosTallas" tabindex="-1" role="dialog" aria-labelledby="modalResultadosTallasLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalResultadosTallasLabel">Resultados de Tallas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active btn-outline-info" type="button" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Tallas Registradas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn-outline-info" onClick="TotalTallas()" id="TotalTallasR-tab" data-toggle="tab" href="#TotalTallasR" role="tab" aria-controls="TotalTallasR" aria-selected="false">Total de Tallas</a>
                        </li>
                    </ul><br>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active in" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <!-- Tabla de Tallas Registradas-->
                            <table id="TotalTallas" class="table table-striped">
                                <button id="descargarExcelT" class="btn btn-success" onClick="descargarExcel('TotalTallas')">Descargar Excel</button>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Talla</th>
                                        <th>Sexo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los resultados se llenarán aquí mediante AJAX -->
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="TotalTallasR" role="tabpanel" aria-labelledby="TotalTallasR-tab">
                            <button id="descargarExcelR" class="btn btn-success" onClick="descargarExcel('TotalTallasRegistradas')">Descargar Excel</button>
                            <!-- Tabla de Total de Tallas -->
                            <table id="TotalTallasRegistradas" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Talla</th>
                                        <th>Sexo</th>
                                        <th>Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los resultados se llenarán aquí mediante AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--MODAL RESULTADOS VOTOS -->
    <div class="modal fade" id="modalResultadosVotos" tabindex="-1" role="dialog" aria-labelledby="modalResultadosVotosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalResultadosVotosLabel">Resultados de Votos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Tabla de Resultados de Votos -->
                    <table id="ResultadosVotos" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Votos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los resultados se llenarán aquí mediante AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!--MODAL VER SUGERENCIAS -->
    <div class="modal fade" id="modalVerSugerencias" tabindex="-1" role="dialog" aria-labelledby="modalVerSugerenciasLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerSugerenciasLabel">Sugerencias Recibidas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Tabla de Sugerencias -->
                    <table id="TablaSugerencias" class="table table-striped">
                        <button id="descargarExcelT" class="btn btn-success" onClick="descargarExcel('TablaSugerencias')">Descargar Excel</button>
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Tipo</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los resultados se llenarán aquí mediante AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
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
    <!-- Descargar Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        $(document).ready(function () {           
            verCalendarioLogin();
            validaOpciones();
            infoEmpleado();   
            obtenerPlaca();
            cargarTalla(getCookie('noEmpleadoL'));
            
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

            document.getElementById('id_usuarioSJ').value = getCookie('id_usuarioL');
            document.getElementById('nombredelusuarioSJ').value = getCookie('nombredelusuarioL');
            document.getElementById('noEmpleadoSJ').value = getCookie('noEmpleadoL');
            document.getElementById('correoSJ').value = getCookie('correoL');

            document.getElementById('pass').value = getCookie('UsrKpis');
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
                height: 450, // Altura fija en px
                contentHeight: 4550, // Altura del contenido
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

    //FUNCION ENVIAR TALLAS
        function registraTallas(){
            var accion = 'registraTallas';
            var talla = document.getElementById('talla').value;
            var noEmpleado = getCookie('noEmpleadoL');

            $.ajax({
                url: 'login.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion : accion,
                    talla: talla,
                    noEmpleado: noEmpleado,
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Talla actualizada correctamente.'
                        });
                        cargarTalla(noEmpleado); // Recarga la talla después de registrar
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo registrar la talla.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la solicitud.'
                    });
                }
            });
        }

    //FUNCION PARA CARGAR TALLA SI YA ESTA REGISTRADA
        function cargarTalla(noEmpleadoL) {
            $.ajax({
                url: 'login.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'validaTalla',
                    noEmpleado: noEmpleadoL
                },
                success: function(response) {
                    if (response.success && response.exists && response.talla) {
                        $('#alertTalla').hide(); // Oculta la alerta si la talla ya está registrada
                        $('#talla').val(response.talla); // Asigna la talla al select
                    } else {
                        $('#alertTalla').show(); // Muestra la alerta si no hay talla registrada
                    }
                },
                error: function() {
                console.error('Error al consultar la talla.');
                }
            });
        }

    //BUZON DE SUGERENCIAS
        function BuzonSugerencias() {
            var accion = 'buzon';
            var tipo = document.getElementById('tipo').value;   
            var comentario = document.getElementById('comentario').value;
            var noEmpleado = getCookie('noEmpleadoL');

            $.ajax({
                url: 'login.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion : accion,
                    tipo: tipo,
                    comentario: comentario,
                    noEmpleado: noEmpleado,
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Comentario enviado correctamente.'
                        });
                        // Limpiar el formulario después de enviar
                        $('#formbuzon')[0].reset();
                        $('#modalbuzon').modal('hide'); // Cerrar el modal
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo enviar el comentario.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la solicitud.'
                    });
                }
            });
            
        }

    //VOTAR POR FOTO
        $(document).on('click', '.votar-btn', function() {
            var id_foto = $(this).data('foto');
            var noEmpleado = getCookie('noEmpleadoL');

            $.ajax({
                url: 'login.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accionV: 'votacion',
                    id_foto: id_foto,
                    noEmpleado: noEmpleado
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Gracias por tu voto',
                            text: 'Has votado por la foto ' + id_foto 
                        });
                        $('#modalCarrusel').modal('hide'); // Cerrar el modal después de votar
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Atención',
                            text: response.message || 'Solo se permite un voto por usuario.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error en la solicitud.'
                    });
                }
            });
        });

    //FUNCION PARA VER TODAS LAS TALLAS REGISTRADAS
        function VerTallas() {
            $.ajax({
                url: 'acciones_inicio.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'ver_tallas'
                },
                success: function(response) {
                    if (response.success && response.tallas) {
                        var tablaBody = $('#TotalTallas tbody');
                        tablaBody.empty(); // Limpiar el cuerpo de la tabla antes de llenarla

                        response.tallas.forEach(function(talla) {
                            var fila = '<tr>' +
                                '<td>' + talla.noEmpleado +  '-' + talla.nombre + '</td>' +
                                '<td>' + talla.talla + '</td>' +
                                '<td>' + talla.sexo + '</td>' +
                                '</tr>';
                            tablaBody.append(fila);
                        });

                        // Mostrar el modal después de llenar la tabla
                        $('#modalResultadosTallas').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'No hay tallas registradas',
                            text: 'No se encontraron tallas en el sistema.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error en la solicitud.'
                    });
                }
            });
        }

    //VER TOTAL DE TALLAS
        function TotalTallas() {
            $.ajax({
                url: 'acciones_inicio.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'conteo_tallas'
                },
                success: function(response) {
                    if (response.success && response.tallas) {
                        var tablaBody = $('#TotalTallasRegistradas tbody');
                        tablaBody.empty(); // Limpiar el cuerpo de la tabla antes de llenarla

                        response.tallas.forEach(function(talla) {
                            var fila = '<tr>' +
                                '<td>' + talla.talla + '</td>' +
                                '<td>' + talla.sexo + '</td>' +
                                '<td>' + talla.cantidad + '</td>' +
                                '</tr>';
                            tablaBody.append(fila);
                        });

                        // Mostrar el modal después de llenar la tabla
                        $('#modalResultadosTallas').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'No hay tallas registradas',
                            text: 'No se encontraron tallas en el sistema.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error en la solicitud.'
                    });
                }
            });
        }

    //FUNCION PARA DESCARGAR EXCEL 
        function descargarExcel(tablaId) {
            var tabla = document.getElementById(tablaId);
            var filaInicio = 0; // Iniciar desde la primera fila (índice 0)
            var filaFin = tabla.rows.length - 1; // Hasta la última fila

            var wb = XLSX.utils.book_new();
            var ws_data = [];

            for (var i = filaInicio; i <= filaFin; i++) {
                var row = [];
                for (var j = 0; j < tabla.rows[i].cells.length; j++) {
                    row.push(tabla.rows[i].cells[j].innerText);
                }
                ws_data.push(row);
            }

            var ws = XLSX.utils.aoa_to_sheet(ws_data);
            XLSX.utils.book_append_sheet(wb, ws, tablaId);
            XLSX.writeFile(wb, tablaId + '.xlsx');
        }

    //VER VOTOS DEL CARRUSEL
        function verVotos() {
            $.ajax({
                url: 'acciones_inicio.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'conteo_votos'
                },
                success: function(response) {
                    if (response.success && response.votos) {
                        var tablaBody = $('#ResultadosVotos tbody');
                        tablaBody.empty(); // Limpiar el cuerpo de la tabla antes de llenarla

                        response.votos.forEach(function(voto) {
                            var imagenRuta = 'concursoHallowen2025/' + voto.id_foto + '.jpg'; // Ajusta según tu estructura
                            var fila = '<tr>' +
                                '<td>' +
                                    voto.id_foto + '<br>' +
                                    '<img src="' + imagenRuta + '" alt="Foto ' + voto.id_foto + '" style="width:70px; height:auto; border-radius:4px;">' +
                                '</td>' +
                                '<td>' + voto.cantidad + '</td>' +
                                '</tr>';
                            tablaBody.append(fila);
                        });

                        // Mostrar el modal después de llenar la tabla
                        $('#modalCarrusel').modal('hide');
                        $('#modalResultadosVotos').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'No hay votos registrados',
                            text: 'No se encontraron votos en el sistema.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error en la solicitud.'
                    });
                }
            });
        }

    //VER BUZON DE SUGERENCIAS
        function verBuzon() {
            $.ajax({
                url: 'acciones_inicio.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'ver_buzon'
                },
                success: function(response) {
                    if (response.success && response.buzon) {
                        var tablaBody = $('#TablaSugerencias tbody');
                        tablaBody.empty(); // Limpiar el cuerpo de la tabla antes de llenarla

                        response.buzon.forEach(function(buzon) {
                            var fila = '<tr>' +
                                '<td>' + buzon.noEmpleado + '-' + buzon.nombre + '</td>' +
                                '<td>' + buzon.tipo + '</td>' +
                                '<td>' + buzon.comentario + '</td>' +
                                '<td>' + buzon.fecha_registro + '</td>' +
                                '</tr>';
                            tablaBody.append(fila);
                        });

                        // Mostrar el modal después de llenar la tabla
                        $('#modalbuzon').modal('hide');
                        $('#modalVerSugerencias').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'No hay comentarios registrados',
                            text: 'No se encontraron comentarios en el sistema.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error en la solicitud.'
                    });
                }
            });
        }
</script>
</body>
</html>
<?php
session_start();
include '../incidencias/conn.php';
if (empty($_COOKIE['noEmpleadoL'])) {
    echo '<script>window.location.assign("index.php")</script>';
    exit;
}
$empleadosAdmin = [276, 403, 569, 523, 183];
$esAdmin = isset($_COOKIE['noEmpleadoL']) && in_array($_COOKIE['noEmpleadoL'], $empleadosAdmin);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Messbook - Panel de Usuario</title>
    <link rel="icon" type="image/png" href="../loginMaster/img/fav.png">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/loginMaster.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  
</head>

<body id="page-top" class="theme-light">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">
                    <div class="row">
                        <!-- ========== SIDEBAR PERFIL ========== -->
                        <div class="col-xl-3 col-md-4">
                            <div class="profile-card text-center">
                                <div class="profile-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <h4 class="mb-1 fw-bold" style="color: var(--accent);">
                                    <?php echo isset($_COOKIE['nombredelusuarioL']) ? htmlspecialchars($_COOKIE['nombredelusuarioL'], ENT_QUOTES, 'UTF-8') : 'Usuario Desconocido'; ?>
                                </h4>
                                <p class="text-muted mb-2" style="font-size:0.85rem;">
                                    No. Empleado: <?php echo isset($_COOKIE['noEmpleadoL']) ? htmlspecialchars($_COOKIE['noEmpleadoL']) : '0000'; ?>
                                </p>
                                <ul class="list-group list-group-flush text-start small mb-2" style="background:transparent;">
                                    <li class="list-group-item px-0 py-1 bg-transparent border-0">
                                        <small class="text-muted fw-bold me-1">Jefe Directo:</small>
                                        <span id="lblJefe" class="fw-semibold"></span>
                                    </li>
                                    <li class="list-group-item px-0 py-1 bg-transparent border-0">
                                        <small class="text-muted fw-bold me-1">Área:</small>
                                        <span id="lblArea" class="fw-semibold"></span>
                                    </li>
                                    <li class="list-group-item px-0 py-1 bg-transparent border-0">
                                        <small class="text-muted fw-bold me-1">Puesto:</small>
                                        <span id="lblPuesto" class="fw-semibold">—</span>
                                    </li>
                                </ul>

                                <!-- Fila 1: Fecha Ingreso (full width) -->
                                <div class="row no-gutters">
                                    <div class="col-12">
                                        <div class="stat-box">
                                            <h6 id="fechaIngreso"></h6>
                                            <p>Fecha de Ingreso</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fila 2: Días Vacaciones · Notificaciones -->
                                <div class="row no-gutters">
                                    <div class="col-7 pr-1">
                                        <div class="stat-box" title="Días de vacaciones disponibles">
                                            <h6><span id="diasDisp">0</span></h6>
                                            <p>Días Vacaciones</p>
                                        </div>
                                    </div>
                                    <div class="col-5 pl-1">
                                        <div class="stat-box d-flex flex-column justify-content-center" style="padding: 0.25rem;">
                                            <button class="btn btn-link p-0 position-relative" type="button" id="btnNotificaciones" onclick="mostrarNotificacionesFlotantes()">
                                                <i class="fas fa-bell fa-lg" style="color: var(--text);"></i>
                                                <span id="badgeNotificaciones" class="position-absolute badge rounded-pill bg-danger text-light" style="top:-4px; right:-8px; font-size:.7rem;">0</span>
                                            </button>
                                            <p style="margin-top:.15rem;">Notificaciones</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones administrativos (solo empleados permitidos) -->
                                <?php if ($esAdmin): ?>
                                    <div class="mt-3">
                                        <div class="btn-group btn-group-sm w-100 mb-2" role="group">
                                            <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#modalEvento" onclick="limpiarModalEvento()">
                                                <i class="fas fa-plus-circle"></i> Evento
                                            </button>
                                            <button type="button" class="btn btn-outline-info" onclick="cargarListaEventos()">
                                                <i class="fas fa-list"></i> Lista
                                            </button>
                                        </div>
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#modalRegistrosAsistencia" onclick="abrirModalRegistrosAsistencia('', '')">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#modalAccesosEspeciales">
                                                <i class="fas fa-user-shield"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#modalAccesoSistemas">
                                                <i class="fas fa-desktop"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Botones acción -->
                                <button class="btn btn-outline-mess-naranja btn-block mt-2" data-toggle="modal" data-target="#modalbuzon">
                                    <i class="fas fa-envelope-open-text"></i> Sugerencias
                                </button>
                                <button class="btn btn-outline-warning btn-block mt-2" data-toggle="modal" data-target="#modalCambiarContrasena">
                                    <i class="fas fa-key"></i> Password
                                </button>
                                <a class="btn btn-outline-danger btn-block mt-2" href="#" data-toggle="modal" data-target="#logoutModalN">
                                    <i class="fas fa-sign-out-alt"></i> Salir
                                </a>

                                <!-- Toggle tema claro/oscuro: [sol] [switch] [luna] -->
                                <div class="theme-toggle mt-3" id="themeToggle" role="button" tabindex="0" aria-label="Cambiar tema claro/oscuro">
                                    <i class="fas fa-sun sun"></i>
                                    <div class="switch"></div>
                                    <i class="fas fa-moon moon"></i>
                                </div>
                            </div>
                        </div>

                        <!-- ========== CONTENIDO CON TABS ========== -->
                        <div class="col-xl-9 col-md-8">
                            <ul class="nav nav-tabs nav-tabs-main" id="mainTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tabSistemas-tab" data-toggle="tab" data-target="#tabSistemas" type="button" role="tab">
                                        <i class="fas fa-th-large mr-1"></i> Sistemas
                                        <span class="tab-badge"></span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tabAgenda-tab" data-toggle="tab" data-target="#tabAgenda" type="button" role="tab">
                                        <i class="fas fa-calendar-alt mr-1"></i> Sala de Juntas
                                        <span class="tab-badge"></span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tabAvisos-tab" data-toggle="tab" data-target="#tabAvisos" type="button" role="tab">
                                        <i class="fas fa-bullhorn mr-1"></i> Avisos
                                        <span id="badgeTabAvisos" class="tab-badge"></span>
                                    </button>
                                </li>
                                <!--
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tabExpediente-tab" data-toggle="tab" data-target="#tabExpediente" type="button" role="tab">
                                        <i class="fas fa-folder-open mr-1"></i> Expediente
                                        <span class="tab-badge"></span>
                                        <span id="statusTabExpediente" class="tab-status" title="Estatus de expediente"></span>
                                    </button>
                                </li>
                                -->
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tabPersonal-tab" data-toggle="tab" data-target="#tabPersonal" type="button" role="tab">
                                        <i class="fas fa-user-cog mr-1"></i> Personal
                                        <span class="tab-badge"></span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tabVehiculo-tab" data-toggle="tab" data-target="#tabVehiculo" type="button" role="tab">
                                        <i class="fas fa-car mr-1"></i> Vehículo
                                        <span class="tab-badge"></span>
                                        <span id="statusTabVehiculo" class="tab-status" title="Estatus de vehículo"></span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tabKpis-tab" data-toggle="tab" data-target="#tabKpis" type="button" role="tab">
                                        <i class="fas fa-chart-line mr-1"></i> KPI's
                                        <span class="tab-badge"></span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tabDirectorio-tab" data-toggle="tab" data-target="#tabDirectorio" type="button" role="tab">
                                        <i class="fas fa-address-book mr-1"></i> Directorio
                                        <span class="tab-badge"></span>
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="mainTabsContent">
                                <!-- ===== TAB 1: SISTEMAS ===== -->
                                <div class="tab-pane fade show active" id="tabSistemas" role="tabpanel">
                                    <div class="row">
                                        <!-- VACACIONES -->
                                        <div class="col-md-3 mb-3" id="divVacaciones" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formVacaciones" method="POST" action="../incidencias/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuario" id="id_usuario" value="">
                                                        <input type="hidden" name="nombredelusuario" id="nombredelusuario" value="">
                                                        <input type="hidden" name="noEmpleado" id="noEmpleado" value="">
                                                        <input type="hidden" name="correo" id="correo" value="">
                                                        <input type="hidden" name="sistema" id="sistema" value="vacaciones">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="far fa-check-square fa-lg d-block mb-2"></i> Vacaciones
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CONTROL VEHICULAR -->
                                        <div class="col-md-3 mb-3" id="divControlVehicular" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formControlVehicular" method="POST" action="../ControlVehicular/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuarioCV" id="id_usuarioCV" value="">
                                                        <input type="hidden" name="nombredelusuarioCV" id="nombredelusuarioCV" value="">
                                                        <input type="hidden" name="noEmpleadoCV" id="noEmpleadoCV" value="">
                                                        <input type="hidden" name="correoCV" id="correoCV" value="">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="fas fa-car fa-lg d-block mb-2"></i> Ctrl Vehicular
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- HORAS EXTRA -->
                                        <div class="col-md-3 mb-3" id="divHorasExtra" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formHorasExtra" method="POST" action="../horasextra/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuarioHR" id="id_usuarioHR" value="">
                                                        <input type="hidden" name="nombredelusuarioHR" id="nombredelusuarioHR" value="">
                                                        <input type="hidden" name="noEmpleadoHR" id="noEmpleadoHR" value="">
                                                        <input type="hidden" name="correoHR" id="correoHR" value="">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="fas fa-clock fa-lg d-block mb-2"></i> Hrs Extra
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- INCIDENCIAS -->
                                        <div class="col-md-3 mb-3" id="divIncidencias" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formIncidencias" method="POST" action="../incidencias/incidencias/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuarioI" id="id_usuarioI" value="">
                                                        <input type="hidden" name="nombredelusuarioI" id="nombredelusuarioI" value="">
                                                        <input type="hidden" name="noEmpleadoI" id="noEmpleadoI" value="">
                                                        <input type="hidden" name="correoI" id="correoI" value="">
                                                        <input type="hidden" name="sistema" id="sistema" value="incidencias">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="fas fa-list fa-lg d-block mb-2"></i> Incidencias
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- PLANEACION -->
                                        <div class="col-md-3 mb-3" id="divPlaneacion" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formPlaneacion" method="POST" action="../planeacion/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuarioPla" id="id_usuarioPla" value="">
                                                        <input type="hidden" name="nombredelusuarioPla" id="nombredelusuarioPla" value="">
                                                        <input type="hidden" name="noEmpleadoPla" id="noEmpleadoPla" value="">
                                                        <input type="hidden" name="correoPla" id="correoPla" value="">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="fas fa-calendar fa-lg d-block mb-2"></i> Planeación
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CAPACITACION -->
                                        <div class="col-md-3 mb-3" id="divCapacitacion" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <div class="btn-group w-100" role="group">
                                                        <a href="https://messbook.com.mx/capacitacion" class="btn btn-outline-primary">
                                                            <i class="fas fa-list fa-lg d-block mb-2"></i> Capacitación
                                                        </a>
                                                        <a href="Manual de Usuario Capacitacion.pdf" target="_blank" class="btn btn-outline-primary" style="flex:0 0 auto;">
                                                            <i class="fas fa-file-pdf fa-lg d-block mb-2"></i> Manual
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ACTIVOS -->
                                        <div class="col-md-3 mb-3" id="divActivos" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formActivos" method="POST" action="../activos/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuarioAC" id="id_usuarioAC" value="">
                                                        <input type="hidden" name="nombredelusuarioAC" id="nombredelusuarioAC" value="">
                                                        <input type="hidden" name="noEmpleadoAC" id="noEmpleadoAC" value="">
                                                        <input type="hidden" name="correoAC" id="correoAC" value="">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="fas fa-box fa-lg d-block mb-2"></i> Activos
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ENTRADAS EQ -->
                                        <div class="col-md-3 mb-3" id="divEntradasEq" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formEntradasEq" method="POST" action="../planeacion/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuarioPlaEnt" id="id_usuarioPlaEnt" value="">
                                                        <input type="hidden" name="nombredelusuarioPlaEnt" id="nombredelusuarioPlaEnt" value="">
                                                        <input type="hidden" name="noEmpleadoPlaEnt" id="noEmpleadoPlaEnt" value="">
                                                        <input type="hidden" name="correoPlaEnt" id="correoPlaEnt" value="">
                                                        <input type="hidden" name="rutaredireccion" id="campoNuevoValor">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="fas fa-calendar fa-lg d-block mb-2"></i> Entradas Eq
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- PRACTICANTES -->
                                        <div class="col-md-3 mb-3" id="divPracticantes" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formPracticantes" method="POST" action="../Practicantes/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuarioPRACT" id="id_usuarioPRACT" value="">
                                                        <input type="hidden" name="nombredelusuarioPRACT" id="nombredelusuarioPRACT" value="">
                                                        <input type="hidden" name="noEmpleadoPRACT" id="noEmpleadoPRACT" value="">
                                                        <input type="hidden" name="correoPRACT" id="correoPRACT" value="">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="fas fa-user-clock fa-lg d-block mb-2"></i> Practicantes
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TICKETS BI -->
                                        <div class="col-md-3 mb-3" id="divTicketsBI" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <a href="../Tickets/" class="btn btn-outline-primary btn-block">
                                                        <i class="fas fa-ticket-alt fa-lg d-block mb-2"></i> Tickets BI
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CONTROL SGC -->
                                        <div class="col-md-3 mb-3" id="divControlSGC" style="display:none">
                                            <div class="card card-action shadow-sm h-100">
                                                <div class="card-body text-center">
                                                    <form id="formControlSGC" method="POST" action="../ControlSGC/validaLoginMaster.php">
                                                        <input type="hidden" name="id_usuarioSGC" id="id_usuarioSGC" value="">
                                                        <input type="hidden" name="nombredelusuarioSGC" id="nombredelusuarioSGC" value="">
                                                        <input type="hidden" name="noEmpleadoSGC" id="noEmpleadoSGC" value="">
                                                        <input type="hidden" name="correoSGC" id="correoSGC" value="">
                                                        <button type="submit" class="btn btn-outline-primary btn-block">
                                                            <i class="fas fa-check fa-lg d-block mb-2"></i> Control SGC
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== TAB 2: SALA DE JUNTAS ===== -->
                                <div class="tab-pane fade" id="tabAgenda" role="tabpanel">
                                    <div class="card shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center" style="background: var(--card-soft); border-color: var(--border);">
                                            <span class="font-weight-bold"><i class="fas fa-calendar-alt mr-2"></i>Agenda Sala de Juntas</span>
                                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalRegistrarLugarSJ">
                                                <i class="fas fa-plus-circle mr-1"></i> Registrar Lugar
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div id="calendar"></div>
                                            <!-- Inputs ocultos que necesitan otras partes del sistema -->
                                            <input type="hidden" id="id_usuarioSJ" value="">
                                            <input type="hidden" id="nombredelusuarioSJ" value="">
                                            <input type="hidden" id="noEmpleadoSJ" value="">
                                            <input type="hidden" id="correoSJ" value="">
                                            <input type="hidden" id="sistemaSJ" value="saladeJuntas">
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== TAB 3: AVISOS ===== -->
                                <div class="tab-pane fade" id="tabAvisos" role="tabpanel">
                                    <div class="row">
                                        <!-- Mural / Tablero (solo PDF) -->
                                        <div class="col-lg-7 mb-4">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header" style="background: var(--card-soft); border-color: var(--border);">
                                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-bullhorn mr-2"></i>Mural / Tablero de avisos</h6>
                                                </div>
                                                <div class="card-body p-2">
                                                    <embed id="vistaPrevia" src='https://www.mess.com.mx/wp-content/uploads/2026/05/MURAL-MAYO26_compressed.pdf' type="application/pdf" width="100%" height="600px" />
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Encuestas + Tallas -->
                                        <div class="col-lg-5 mb-4" id="divCapacitacionCursos">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header" style="background: var(--card-soft); border-color: var(--border);">
                                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-poll mr-2"></i>Encuestas / Votaciones</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div id="encuestasAsigandas"></div>
                                                    <hr>
                                                    <!-- Registro de Talla (movido aquí) -->
                                                    <h6 class="font-weight-bold mb-2"><i class="fas fa-tshirt mr-2"></i>Talla de uniforme</h6>
                                                    <form method="post">
                                                        <div class="form-group">
                                                            <div id="alertTalla" class="alert alert-danger py-2" role="alert" style="font-size:.85rem;">
                                                                <strong>Importante:</strong> Por favor, registra tu talla.
                                                            </div>
                                                            <label for="talla" class="mb-1">Talla:</label>
                                                            <select class="form-control form-control-sm" id="talla" name="talla" required>
                                                                <option value="">Seleccione una talla</option>
                                                                <option value="XS">XS</option>
                                                                <option value="S">S</option>
                                                                <option value="M">M</option>
                                                                <option value="L">L</option>
                                                                <option value="XL">XL</option>
                                                            </select>
                                                            <input type="hidden" name="noEmpleadoT" id="noEmpleadoT" value="">
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button type="button" class="btn btn-success" onclick="registraTallas()">Actualizar</button>
                                                                <?php
                                                                $usuariosRegistran = array(183, 276, 523, 403);
                                                                if (in_array($_COOKIE['noEmpleadoL'], $usuariosRegistran)) {
                                                                    echo '<button onclick="VerTallas()" type="button" class="btn btn-info" data-toggle="modal" data-target="#modalResultadosTallas">Ver Tallas</button>';
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== TAB 4: EXPEDIENTE (shell tentativo) ===== -->
                                <div class="tab-pane fade" id="tabExpediente" role="tabpanel">
                                    <div class="alert alert-info" style="background: var(--accent-soft); border-color: var(--border); color: var(--text);">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <strong>Expediente del empleado</strong> — Estructura tentativa. Los datos se conectarán a la base más adelante.
                                    </div>
                                    <div class="row">
                                        <!-- FUERZA -->
                                        <div class="col-lg-4 mb-3">
                                            <div class="expediente-card h-100">
                                                <div class="card-header">Fuerza</div>
                                                <div class="card-body p-0">
                                                    <table class="table table-sm mb-0 expediente-table">
                                                        <tbody>
                                                            <tr>
                                                                <td><span class="status-dot ok"></span>Archivo 1</td>
                                                                <td class="text-end">2026-04-01</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot ok"></span>Archivo 2</td>
                                                                <td class="text-end">2025-02-22</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot ok"></span>Archivo 3</td>
                                                                <td class="text-end">2026-05-03</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot danger"></span>Archivo 4</td>
                                                                <td class="text-end sin-registro">Sin Registro</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- MASA -->
                                        <div class="col-lg-4 mb-3">
                                            <div class="expediente-card h-100">
                                                <div class="card-header">Masa</div>
                                                <div class="card-body p-0">
                                                    <table class="table table-sm mb-0 expediente-table">
                                                        <tbody>
                                                            <tr>
                                                                <td><span class="status-dot ok"></span>Archivo 1</td>
                                                                <td class="text-end">2026-04-01</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot ok"></span>Archivo 2</td>
                                                                <td class="text-end">2025-02-22</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot danger"></span>Archivo 3</td>
                                                                <td class="text-end sin-registro">Sin Registro</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot danger"></span>Archivo 4</td>
                                                                <td class="text-end sin-registro">Sin Registro</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- DUREZA -->
                                        <div class="col-lg-4 mb-3">
                                            <div class="expediente-card h-100">
                                                <div class="card-header">Dureza</div>
                                                <div class="card-body p-0">
                                                    <table class="table table-sm mb-0 expediente-table">
                                                        <tbody>
                                                            <tr>
                                                                <td><span class="status-dot ok"></span>Archivo 1</td>
                                                                <td class="text-end">2026-04-01</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot ok"></span>Archivo 2</td>
                                                                <td class="text-end">2025-02-22</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot ok"></span>Archivo 3</td>
                                                                <td class="text-end">2026-05-03</td>
                                                            </tr>
                                                            <tr>
                                                                <td><span class="status-dot warn"></span>Archivo 4</td>
                                                                <td class="text-end">2024-01-01</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== TAB 5: PERSONAL ===== -->
                                <div class="tab-pane fade" id="tabPersonal" role="tabpanel">
                                    <div class="row">
                                        <!-- Notificaciones expandidas -->
                                        <div class="col-lg-5 mb-4">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header" style="background: var(--card-soft); border-color: var(--border);">
                                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-bell mr-2"></i>Notificaciones</h6>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div id="panelNotificaciones" class="notif-panel">
                                                        <div class="notif-empty"><i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>Sin notificaciones</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Calendario de Vacaciones -->
                                        <div class="col-lg-7 mb-4">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="background: var(--card-soft); border-color: var(--border);">
                                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-umbrella-beach mr-2"></i>Mis Vacaciones</h6>
                                                    <small class="text-muted"><strong class="text-dark"><span id="diasDispPanel">0</span> días disp.</strong> · <strong class="text-dark"><span id="diasSolPanel">0</span> solicitados</strong></small>
                                                </div>
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-2" style="gap: 0.75rem; font-size:.8rem;">
                                                        <span><span class="d-inline-block mr-1" style="width:.75rem; height:.75rem; background:#050D9E; border-radius:.15rem; vertical-align:middle;"></span>Yo</span>
                                                        <span id="leyendaVacacionesEquipo" class="d-none"><span class="d-inline-block mr-1" style="width:.75rem; height:.75rem; background:#F5A623; border-radius:.15rem; vertical-align:middle;"></span>Equipo</span>
                                                        <span id="leyendaVacacionesDepartamento"><span class="d-inline-block mr-1" style="width:.75rem; height:.75rem; background:#F5A623; border-radius:.15rem; vertical-align:middle;"></span>Equipo</span>
                                                    </div>
                                                    <div id="calendarVacaciones"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== TAB: VEHÍCULO ===== -->
                                <div class="tab-pane fade" id="tabVehiculo" role="tabpanel">
                                    <div id="contenedorVehiculos">
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p class="mb-0">Cargando información de vehículo...</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== TAB 6: KPI'S (frame con los KPI's segun permisos) ===== -->
                                <div class="tab-pane fade" id="tabKpis" role="tabpanel">

                                    <style>
                                        /* Solo afecta a este tab */
                                        #tabKpis {
                                            padding: 0 !important;
                                        }

                                        #tabKpis #frameKPIs {
                                            padding: 0 !important;
                                            margin: 0 !important;
                                        }

                                        /* Mata el py-5 de Bootstrap que mete 3rem de padding */
                                        #tabKpis .py-5 {
                                            padding-top: 0 !important;
                                            padding-bottom: 0 !important;
                                        }

                                        /* Quita margin del spinner si aparece */
                                        #tabKpis .text-center {
                                            margin: 0 !important;
                                        }

                                        #tabKpis .mb-2 {
                                            margin-bottom: 0 !important;
                                        }

                                        /* El iframe ocupa todo sin espacio arriba */
                                        #tabKpis iframe {
                                            display: block !important;
                                            width: 100% !important;
                                            height: calc(100vh - 140px) !important;
                                            /* Ajusta 140px según altura de tu header de tabs */
                                            border: none !important;
                                            margin: 0 !important;
                                        }
                                    </style>

                                    <div id="frameKPIs">
                                        <div class="text-center text-muted py-5">
                                            <?php
                                            $passkpis = isset($_COOKIE['UsrKpis']) ? $_COOKIE['UsrKpis'] : '';
                                            ?>
                                            <iframe src="https://messbook.com.mx/kpis_pbi/index.php?pk=<?php echo $passkpis; ?>" title="KPIs"></iframe>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== TAB 7: DIRECTORIO ===== -->
                                <div class="tab-pane fade" id="tabDirectorio" role="tabpanel">
                                    <div class="directorio-header">
                                        <div>
                                            <h4 class="mb-1" style="color: var(--accent);">Directorio</h4>
                                            <p class="text-muted mb-0 small">Busca a cualquier compañero por nombre, área, puesto o correo.</p>
                                        </div>
                                        <div class="directorio-search">
                                            <i class="fas fa-search"></i>
                                            <input type="text" id="directorioBuscar" class="form-control form-control-sm" placeholder="Buscar empleado..." autocomplete="off">
                                        </div>
                                    </div>
                                    <div id="directorioGrid" class="row">
                                        <div class="col-12 empty-state">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p class="mb-0">Cargando directorio...</p>
                                        </div>
                                    </div>
                                    <div id="directorioPaginacion" class="dir-paginacion"></div>
                                </div>












                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <footer class="sticky-footer" style="background: var(--card-bg); border-top: 1px solid var(--border);">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- ============== MODALES (sin cambios) ============== -->
    <!-- Modal Detalle Directorio (compartido; se rellena por JS al click en una card) -->
    <div class="modal fade" id="modalDirectorio" tabindex="-1" role="dialog" aria-labelledby="modalDirectorioLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--accent); color: #fff;">
                    <h5 class="modal-title" id="modalDirectorioLabel">Detalle del empleado</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar" style="text-shadow:none; opacity:.85;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div id="modalDirAvatar" class="dir-avatar-lg mb-2"></div>
                        <h5 id="modalDirNombre" class="mb-1" style="color: var(--accent);"></h5>
                        <p id="modalDirPuesto" class="text-muted mb-0"></p>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">No. Empleado</span>
                            <span id="modalDirNoEmp" class="fw-semibold">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Área</span>
                            <span id="modalDirArea" class="fw-semibold">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Nave</span>
                            <span id="modalDirNave" class="fw-semibold">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Teléfono</span>
                            <span id="modalDirTel" class="fw-semibold">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Correo</span>
                            <a id="modalDirCorreo" href="#" class="text-truncate" style="max-width:60%;">—</a>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div class="modal fade" id="modalCambiarContrasena" tabindex="-1" role="dialog" aria-labelledby="modalCambiarContrasenaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCambiarContrasenaLabel">Cambiar Contraseña</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="contrasena_actual">Contraseña actual</label>
                        <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" required>
                    </div>
                    <div class="form-group">
                        <label for="nueva_contrasena">Nueva contraseña</label>
                        <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmar_contrasena">Confirmar nueva contraseña</label>
                        <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="cambiarCont()">Guardar</button>
                </div>
            </div>
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
                    <div id="alertBuzon" class="alert alert-primary m-3" role="alert">
                        <strong>Importante:</strong> Aquí puedes dejar tus sugerencias o comentarios. Son anónimos.
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select class="form-control" id="tipo" name="tipo" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="Sugerencia">Sugerencia</option>
                                <option value="Comentario">Comentario</option>
                                <option value="Queja">Queja</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="comentario">Comentario</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="BuzonSugerencias()">Enviar</button>
                        <?php
                        if (in_array($_COOKIE['noEmpleadoL'], $usuariosRegistran)) {
                            echo '<button onclick="verBuzon()" type="button" class="btn btn-info">Ver Sugerencias</button>';
                        }
                        ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TABLA RESULTADOS TALLAS -->
    <div class="modal fade" id="modalResultadosTallas" tabindex="-1" role="dialog" aria-labelledby="modalResultadosTallasLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalResultadosTallasLabel">Resultados de Tallas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab">Tallas Registradas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="TotalTallasR-tab" data-toggle="tab" href="#TotalTallasR" role="tab" onclick="TotalTallas()">Total Tallas</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active in" id="home" role="tabpanel">
                            <table id="TotalTallas" class="table table-striped">
                                <button id="descargarExcelT" class="btn btn-success" onClick="descargarExcel('TotalTallas')">Descargar Excel</button>
                                <thead>
                                    <tr>
                                        <th>No. Empleado</th>
                                        <th>Talla</th>
                                        <th>Sexo</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="TotalTallasR" role="tabpanel">
                            <button id="descargarExcelR" class="btn btn-success" onClick="descargarExcel('TotalTallasRegistradas')">Descargar Excel</button>
                            <table id="TotalTallasRegistradas" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Talla</th>
                                        <th>Sexo</th>
                                        <th>Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL VER SUGERENCIAS -->
    <div class="modal fade" id="modalVerSugerencias" tabindex="-1" role="dialog" aria-labelledby="modalVerSugerenciasLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerSugerenciasLabel">Sugerencias Recibidas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="TablaSugerencias" class="table table-striped">
                        <button id="descargarExcelS" class="btn btn-success" onClick="descargarExcel('TablaSugerencias')">Descargar Excel</button>
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Tipo</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de encuestas -->
    <?php include 'modal.php'; ?>

    <!-- Modal Accesos Especiales -->
    <?php include 'modalAccesosEspeciales.php'; ?>

    <!-- Modal Acceso a Sistemas -->
    <?php include 'modalAccesoSistemas.php'; ?>

    <!-- ============== SCRIPTS ============== -->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Funciones Globales -->
    <script src="funcionesGlobales.js"></script>

    <script>
        // Variables globales para calendarios (lazy-init en tabs)
        var calendarSalaJuntas = null;
        var calendarVacaciones = null;
        var esJefeActual = false;
        var vehiculosDocsCargados = false;
        // Acumulador del estatus de cada vehículo para alimentar el semáforo del tab.
        // Estructura: { idVeh: { docs: {ok, total}, vals: {ok, total} } }
        var vehiculosEstado = {};

        $(document).ready(function() {
            // ===== Tema claro/oscuro =====
            const guardado = localStorage.getItem('mess_theme');
            if (guardado === 'dark') document.body.classList.replace('theme-light', 'theme-dark');
            document.getElementById('themeToggle').addEventListener('click', function() {
                if (document.body.classList.contains('theme-dark')) {
                    document.body.classList.replace('theme-dark', 'theme-light');
                    localStorage.setItem('mess_theme', 'light');
                } else {
                    document.body.classList.replace('theme-light', 'theme-dark');
                    localStorage.setItem('mess_theme', 'dark');
                }
                // Re-renderizar calendarios si están activos para refrescar colores
                if (calendarSalaJuntas) calendarSalaJuntas.render();
                if (calendarVacaciones) calendarVacaciones.render();
            });

            // ===== Inicialización original =====
            validaOpciones();
            infoEmpleado();
            verificarEsJefe();
            cargarTalla(getCookie('noEmpleadoL'));
            cargarCursosSeleccionados(getCookie('noEmpleadoL'));
            registrarNotificacionPlaneacion();
            // Pre-carga del tab Vehículo para que el semáforo se calcule sin abrirlo.
            cargarVehiculosDocs();

            // Asignar cookies a campos hidden
            const idU = getCookie('id_usuarioL');
            const nomU = getCookie('nombredelusuarioL');
            const noEmp = getCookie('noEmpleadoL');
            const corU = getCookie('correoL');

            const sets = [
                ['id_usuario', 'nombredelusuario', 'noEmpleado', 'correo'],
                ['id_usuarioCV', 'nombredelusuarioCV', 'noEmpleadoCV', 'correoCV'],
                ['id_usuarioHR', 'nombredelusuarioHR', 'noEmpleadoHR', 'correoHR'],
                ['id_usuarioI', 'nombredelusuarioI', 'noEmpleadoI', 'correoI'],
                ['id_usuarioPla', 'nombredelusuarioPla', 'noEmpleadoPla', 'correoPla'],
                ['id_usuarioPlaEnt', 'nombredelusuarioPlaEnt', 'noEmpleadoPlaEnt', 'correoPlaEnt'],
                ['id_usuarioSJ', 'nombredelusuarioSJ', 'noEmpleadoSJ', 'correoSJ'],
                ['id_usuarioAC', 'nombredelusuarioAC', 'noEmpleadoAC', 'correoAC'],
                ['id_usuarioPRACT', 'nombredelusuarioPRACT', 'noEmpleadoPRACT', 'correoPRACT'],
                ['id_usuarioSGC', 'nombredelusuarioSGC', 'noEmpleadoSGC', 'correoSGC']
            ];
            sets.forEach(function(s) {
                const [a, b, c, d] = s;
                if (document.getElementById(a)) document.getElementById(a).value = idU;
                if (document.getElementById(b)) document.getElementById(b).value = nomU;
                if (document.getElementById(c)) document.getElementById(c).value = noEmp;
                if (document.getElementById(d)) document.getElementById(d).value = corU;
            });
            if (document.getElementById('noEmpleadoT')) document.getElementById('noEmpleadoT').value = noEmp;

            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: "Escribe el nombre del empleado...",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#modalAsignacion')
            });

            cargarMisEncuestas();

            // Notificaciones
            cargarNotificaciones(false);
            setInterval(function() {
                cargarNotificaciones(false);
            }, 5400000); // 1.5h

            // Sincronizar el conteo de notificaciones con el badge del tab Avisos.
            // Observa cambios en #badgeNotificaciones (que actualiza funcionesGlobales.js)
            // y replica el valor en #badgeTabAvisos sin tocar la función global.
            (function() {
                var $src = $('#badgeNotificaciones');
                var $dst = $('#badgeTabAvisos');
                var $tab = $('#tabAvisos-tab');
                if (!$src.length || !$dst.length) return;

                function sync() {
                    var txt = ($src.text() || '').trim();
                    var n = parseInt(txt, 10);
                    if (isNaN(n) || n <= 0) {
                        $dst.text('');
                        $tab.removeClass('has-alert');
                    } else {
                        $dst.text(n > 99 ? '99+' : n);
                        $tab.addClass('has-alert');
                    }
                }

                sync(); // Estado inicial
                var obs = new MutationObserver(sync);
                obs.observe($src[0], {
                    childList: true,
                    characterData: true,
                    subtree: true
                });
            })();

            // Calendario de Sala de Juntas: lazy-init al mostrar el tab
            // (FullCalendar mide 0px si se crea con el contenedor en display:none)
            $('#tabAgenda-tab').on('shown.bs.tab', function() {
                if (!calendarSalaJuntas) {
                    initCalendarSalaJuntas();
                } else {
                    setTimeout(function() {
                        calendarSalaJuntas.updateSize();
                        calendarSalaJuntas.refetchEvents();
                    }, 50);
                }
            });
            $('#tabPersonal-tab').on('shown.bs.tab', function() {
                if (!calendarVacaciones) initCalendarVacaciones();
                setTimeout(function() {
                    if (calendarVacaciones) calendarVacaciones.updateSize();
                }, 50);
                cargarPanelNotificaciones();
            });
            $('#tabVehiculo-tab').on('shown.bs.tab', function() {
                if (!vehiculosDocsCargados) cargarVehiculosDocs();
            });
        });

        // ===== Sala de Juntas =====
        // El endpoint acciones_agendarSala.php (en /incidencias/) lee $_COOKIE['noEmpleado'],
        // pero loginMaster setea 'noEmpleadoL'. Replicamos la cookie sin sufijo antes de cada llamada.
        function syncCookieNoEmpleado() {
            var v = getCookie('noEmpleadoL');
            if (v) document.cookie = 'noEmpleado=' + encodeURIComponent(v) + '; path=/; SameSite=Lax';
        }

        // Paso 1: verifica que no haya conflicto y que finicio < ffin
        function registrarLugarSJ() {
            var finicio = document.getElementById('lugarSJ_inicio').value;
            var ffin = document.getElementById('lugarSJ_fin').value;
            var descripcion = document.getElementById('lugarSJ_descripcion').value;

            if (!finicio || !ffin || !descripcion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Faltan datos',
                    text: 'Completa fecha inicio, fin y motivo.'
                });
                return;
            }
            syncCookieNoEmpleado();

            $.ajax({
                url: '../incidencias/SalaDeJuntas/acciones_agendarSala.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    finicio: finicio,
                    ffin: ffin,
                    descripcion: descripcion,
                    accion: 'verificaReserva'
                },
                success: function(resp) {
                    if (resp && resp.success === false && finicio >= ffin) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Revisa las fechas',
                            text: 'La hora de inicio debe ser anterior a la de fin.'
                        });
                    } else if (resp && resp.success === false) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Conflicto de horario',
                            text: 'Ya existe una reserva en este horario.'
                        });
                    } else {
                        generarSolicitudSJ(finicio, ffin, descripcion);
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo verificar la reserva.'
                    });
                }
            });
        }

        // Paso 2: inserta la reserva
        function generarSolicitudSJ(finicio, ffin, descripcion) {
            $.ajax({
                url: '../incidencias/SalaDeJuntas/acciones_agendarSala.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    finicio: finicio,
                    ffin: ffin,
                    descripcion: descripcion,
                    accion: 'agregaSolicitud'
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Reserva registrada'
                    });
                    $('#modalRegistrarLugarSJ').modal('hide');
                    $('#formRegistrarLugarSJ')[0].reset();
                    if (calendarSalaJuntas) calendarSalaJuntas.refetchEvents();
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo registrar la reserva.'
                    });
                }
            });
        }

        // ===== Inicialización del calendario de Sala de Juntas =====
        function initCalendarSalaJuntas() {
            var calendarEl = document.getElementById('calendar');
            if (!calendarEl) return;
            var miNoEmp = parseInt(getCookie('noEmpleadoL'), 10) || 0;
            calendarSalaJuntas = new FullCalendar.Calendar(calendarEl, {
                initialView: 'listWeek',
                events: '../incidencias/SalaDeJuntas/acciones_calendarioGral.php?opcion=login',
                editable: false,
                locale: 'es',
                height: 450,
                contentHeight: 4550,
                aspectRatio: 2,
                eventContent: function(info) {
                    var nombreEmpleado = info.event.title;
                    var descripcion = info.event.extendedProps.descripcion || 'Sin descripción';
                    var idUsuario = parseInt(info.event.extendedProps.id_usuario, 10) || 0;
                    var idReserva = parseInt(info.event.id, 10) || 0;

                    var html = '<div>' + nombreEmpleado + '<br>' + descripcion + '</div>';
                    if (miNoEmp > 0 && idUsuario === miNoEmp && idReserva > 0) {
                        html += '<button type="button" class="btn btn-sm btn-outline-danger mt-1 py-0 px-2" ' +
                            'style="font-size:.7rem;" ' +
                            'onclick="cancelarReservaSJ(' + idReserva + ', event)">' +
                            '<i class="fas fa-times mr-1"></i>Cancelar</button>';
                    }
                    return {
                        html: html
                    };
                }
            });
            calendarSalaJuntas.render();
        }

        // Cancela una reserva propia de Sala de Juntas (estatus -> 'Cancelada').
        function cancelarReservaSJ(idReserva, evt) {
            if (evt && evt.stopPropagation) evt.stopPropagation();
            if (!idReserva) return;

            Swal.fire({
                icon: 'warning',
                title: '¿Cancelar reserva?',
                text: 'Esta acción liberará el horario en la sala.',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No',
                confirmButtonColor: '#d33'
            }).then(function(res) {
                if (!res.isConfirmed) return;
                syncCookieNoEmpleado();
                $.ajax({
                    url: '../incidencias/SalaDeJuntas/acciones_agendarSala.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        accion: 'eliminaSolicitud',
                        id: idReserva
                    }
                }).done(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Reserva cancelada',
                        timer: 1600,
                        showConfirmButton: false
                    });
                    if (calendarSalaJuntas) calendarSalaJuntas.refetchEvents();
                }).fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cancelar la reserva.'
                    });
                });
            });
        }

        // ===== Calendario de Vacaciones (Personal) =====
        function initCalendarVacaciones() {
            var el = document.getElementById('calendarVacaciones');
            if (!el) return;
            var noEmp = getCookie('noEmpleadoL') || '';
            calendarVacaciones = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 500,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                },
                eventSources: [{
                        id: 'propias',
                        color: '#050D9E',
                        borderColor: '#050D9E',
                        textColor: '#ffffff',
                        events: function(info, successCallback) {
                            $.ajax({
                                url: '../incidencias/acciones_calendario.php',
                                type: 'GET',
                                dataType: 'json',
                                data: {
                                    opcion: 'rrhh',
                                    ing: noEmp
                                }
                            }).done(function(resp) {
                                successCallback(Array.isArray(resp) ? resp : []);
                            }).fail(function() {
                                successCallback([]);
                            });
                        }
                    },
                    {
                        // Jefes: ven a los miembros de su equipo (u.jefe = yo).
                        id: 'equipo',
                        color: '#F5A623',
                        borderColor: '#F5A623',
                        textColor: '#ffffff',
                        events: function(info, successCallback) {
                            if (!esJefeActual) {
                                successCallback([]);
                                return;
                            }
                            syncCookieNoEmpleado();
                            $.ajax({
                                url: '../incidencias/acciones_calendario.php',
                                type: 'GET',
                                dataType: 'json',
                                data: {
                                    opcion: 'jefes'
                                }
                            }).done(function(resp) {
                                successCallback(Array.isArray(resp) ? resp : []);
                            }).fail(function() {
                                successCallback([]);
                            });
                        }
                    },
                    {
                        // No-jefes: ven al resto de su departamento (mismo u.departamento).
                        id: 'departamento',
                        color: '#F5A623',
                        borderColor: '#F5A623',
                        textColor: '#ffffff',
                        events: function(info, successCallback) {
                            if (esJefeActual) {
                                successCallback([]);
                                return;
                            }
                            syncCookieNoEmpleado();
                            $.ajax({
                                url: '../incidencias/acciones_calendario.php',
                                type: 'GET',
                                dataType: 'json',
                                data: {
                                    opcion: 'departamento'
                                }
                            }).done(function(resp) {
                                successCallback(Array.isArray(resp) ? resp : []);
                            }).fail(function() {
                                successCallback([]);
                            });
                        }
                    }
                ]
            });
            calendarVacaciones.render();
        }

        function cargarMisEncuestas() {
            // Usamos el ID del empleado (debería venir de tu sesión de PHP)
            const id_empleado = <?php echo $_COOKIE['noEmpleadoL']; ?>;

            $.post('acciones_eventos.php', {
                accion: 'listar_mis_actividades_completas',
                id_empleado: id_empleado
            }, function(data) {
                let html = '';

                if (data.length > 0) {
                    data.forEach(ev => {
                        // Lógica para saber si ya completó
                        // Si es asistencia, checamos 'asignado_confirmado'. Si es voto/encuesta, checamos 'respondido'.
                        let completado = (ev.tipo === 'asistencia') ? (ev.asignado_confirmado == 1) : (ev.respondido > 0);

                        let icono = 'fa-poll';
                        if (ev.tipo === 'asistencia') icono = 'fa-check-square';
                        if (ev.tipo === 'votacion') icono = 'fa-heart';

                        if (completado) {
                            // DISEÑO PARA COMPLETADAS (Deshabilitado y Verde)
                            html += `
                            <div class="mb-2">
                                <div class="card border-left-success bg-light shadow-sm py-1">
                                    <div class="card-body py-2 d-flex justify-content-between align-items-center">
                                        <span class="text-success font-weight-bold small">
                                            <i class="fas fa-check-circle mr-2"></i> ${ev.nombre}
                                            Fecha del curso: ${ev.fecha_opcion}
                                        </span>
                                        <span class="badge badge-success px-2">Completado</span>
                                    </div>
                                </div>
                            </div>`;
                        } else {
                            // DISEÑO PARA PENDIENTES (Botón azul clickable)
                            html += `
                            <div class="mb-2">
                                <a type="button" onClick="abrirModalEncuestaUsuuario(${ev.id_evento})" class="btn btn-white btn-block text-left shadow-sm py-2 border-left-primary card-btn-pendiente">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-primary font-weight-bold small">
                                            <i class="fas ${icono} mr-2"></i> ${ev.nombre}
                                        </span>
                                        <i class="fas fa-arrow-right fa-sm text-gray-400"></i>
                                    </div>
                                    <small class="d-block text-muted ml-4">Pendiente - Cierra: ${ev.fecha_fin}</small>
                                </a>
                            </div>`;
                        }
                    });
                } else {
                    html = '<div class="text-center text-muted small py-3">Sin actividades asignadas.</div>';
                }

                $('#encuestasAsigandas').html(html);
            }, 'json');
        }

        // ===== Panel de notificaciones (Tab Personal) =====
        function cargarPanelNotificaciones() {
            var $panel = $('#panelNotificaciones');

            $.ajax({
                url: 'acciones_globales.php',
                method: 'POST',
                data: {
                    accion: 'cargarNotificaciones'
                },
                dataType: 'json'
            }).done(function(resp) {
                if (!resp || !resp.success) {
                    $panel.html('<div class="notif-empty"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>No se pudieron cargar las notificaciones</div>');
                    return;
                }

                var lista = resp.notificaciones || [];

                if (!lista.length) {
                    $panel.html('<div class="notif-empty"><i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>Sin notificaciones</div>');
                    return;
                }

                var noEmpleadoL = getCookie('noEmpleadoL') || '';
                var html = '';
                lista.forEach(function(n) {
                    var id = parseInt(n.id, 10) || 0;
                    var idRegistro = parseInt(n.id_registro_referencia, 10) || 0;
                    var sistema = escapeHtml(n.sistema || 'General');
                    var archivo = escapeHtml(n.archivo || '');
                    var fecha = escapeHtml(n.fecha_actualizacion || n.fecha || '');
                    var titulo = escapeHtml(n.recordar || n.accion || 'Notificación');
                    var creadoPor = escapeHtml(n.usuario_actualiza_nombre || '');
                    var nota = escapeHtml(n.nota || '');
                    var iconoClase = obtenerIconoNotificacion(String(sistema).toLowerCase());

                    html += '<div class="notif-item" data-notificacion-id="' + id + '">';
                    html += '  <i class="' + iconoClase + ' notif-icon mt-1"></i>';
                    html += '  <div class="flex-grow-1 min-width-0">';
                    html += '      <div class="d-flex justify-content-between align-items-start">';
                    html += '          <div>';
                    html += '              <div class="font-weight-bold" style="font-size:.9rem;">' + titulo + '</div>';
                    html += (creadoPor ? '              <div class="text-muted" style="font-size:.8rem;">' + creadoPor + '</div>' : '');
                    html += (nota && nota !== titulo ? '              <div class="text-muted" style="font-size:.8rem;">' + nota + '</div>' : '');
                    html += '              <div class="text-muted" style="font-size:.75rem;"><i class="far fa-calendar-alt mr-1"></i>' + fecha + ' · <span class="text-uppercase">' + sistema + '</span></div>';
                    html += '          </div>';
                    html += '          <button type="button" class="btn btn-sm btn-light border border-success text-success px-2 py-1 ml-2" title="Marcar como leída" aria-label="Marcar como leída" onclick="marcarNotificacionLeida(' + id + ', ' + idRegistro + ', \'' + sistema + '\', \'' + archivo + '\', \'' + noEmpleadoL + '\')">';
                    html += '              <i class="fas fa-check fa-sm"></i>';
                    html += '          </button>';
                    html += '      </div>';
                    html += '  </div>';
                    html += '</div>';
                });
                $panel.html(html);
            }).fail(function() {
                $panel.html('<div class="notif-empty"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>Error de conexión</div>');
            });
        }

        // ===== INFO EMPLEADO =====
        function infoEmpleado() {
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
                            $('#diasDisp').text((infoUsr.diasdisponibles || 0) - (infoUsr.diasSol || 0));
                            $('#diasDispPanel').text((infoUsr.diasdisponibles || 0) - (infoUsr.diasSol || 0));
                            $('#diasSolPanel').text(infoUsr.diasSol || 0);
                            $('#lblArea').text(infoUsr.departamento || '—');
                            $('#lblJefe').text(infoUsr.jefe || '—');
                            $('#lblPuesto').text(infoUsr.puesto || '—');
                            $('#fechaIngreso').text(infoUsr.fechaIngreso || '—');

                            // Foto en el sidebar (cae al ícono por defecto si no hay foto).
                            var $avatar = $('.profile-avatar').first();
                            if (infoUsr.foto) {
                                $avatar.addClass('has-photo')
                                       .css('background-image', "url('" + infoUsr.foto + "')");
                            } else {
                                $avatar.removeClass('has-photo')
                                       .css('background-image', 'none');
                            }
                            // La info del jefe se resuelve con una acción dedicada en acciones_globales.php
                            // (getInfo no devuelve esJefe). Ver verificarEsJefe().
                        });
                    }
                }
            });
        }

        // ===== ¿El usuario tiene gente a su cargo? =====
        // Consulta acciones_globales.php?accion=esJefe (cuenta filas en usuarios.jefe = noEmp).
        // Si es jefe, activa la fuente "equipo" del calendario de vacaciones y muestra la leyenda.
        function verificarEsJefe() {
            var noEmp = getCookie('noEmpleadoL') || '';
            if (!noEmp) return;
            $.ajax({
                url: 'acciones_globales.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'esJefe',
                    noEmpleado: noEmp
                }
            }).done(function(resp) {
                if (resp && resp.success && resp.esJefe === true) {
                    // Es jefe: mostrar "Equipo", ocultar "Departamento" y traer su equipo.
                    esJefeActual = true;
                    $('#leyendaVacacionesEquipo').removeClass('d-none');
                    $('#leyendaVacacionesDepartamento').addClass('d-none');
                    if (calendarVacaciones) {
                        var srcEquipo = calendarVacaciones.getEventSourceById('equipo');
                        var srcDept = calendarVacaciones.getEventSourceById('departamento');
                        if (srcEquipo) srcEquipo.refetch();
                        if (srcDept) srcDept.refetch(); // queda vacía por la guarda en eventSources
                    }
                }
                // Si NO es jefe, se queda el estado por defecto:
                // leyenda "Departamento" visible, "Equipo" oculta. La fuente
                // 'departamento' ya carga sola en la primera renderización del calendario.
            });
        }

        // ===== VALIDA OPCIONES (qué sistemas se ven) =====
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
                        if (infoAccesos.estatus == '1') {
                            $('#' + infoAccesos.sistema).show();
                        } else {
                            $('#' + infoAccesos.sistema).hide();
                        }
                    });
                }
            });
        }

        // ===== Tab Vehículo: semáforo de documentación =====
        // 1) Obtiene los vehículos del usuario (getPlaca → ../incidencias/validaLoginMaster.php).
        // 2) Para cada vehículo consulta su documentación (obtenerDatosVehiculo → ../ControlVehicular/acciones_qr.php).
        // 3) Renderiza una card por vehículo con check verde / cruz roja por documento.
        function cargarVehiculosDocs() {
            var $cont = $('#contenedorVehiculos');
            var noEmp = getCookie('noEmpleadoL') || '';
            if (!noEmp) {
                $cont.html('<div class="alert alert-info">No hay sesión válida.</div>');
                return;
            }

            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'getPlaca',
                    noEmpleado: noEmp
                }
            }).done(function(resp) {
                if (!resp || !resp.success || !resp.vehiculos || resp.vehiculos.length === 0) {
                    $cont.html(
                        '<div class="card shadow-sm">' +
                        '<div class="card-body text-center text-muted py-5">' +
                        '<i class="fas fa-car-side fa-3x mb-3" style="opacity:.4;"></i>' +
                        '<h6 class="mb-1">Sin vehículo asignado</h6>' +
                        '<small>No tienes vehículos registrados a tu nombre.</small>' +
                        '</div>' +
                        '</div>'
                    );
                    vehiculosDocsCargados = true;
                    return;
                }

                // Renderizar un placeholder por cada vehículo y luego rellenar.
                // El primer vehículo se abre por defecto, los demás colapsados.
                var html = '<div class="accordion" id="accordionVehiculos">';
                resp.vehiculos.forEach(function(v, idx){
                    var idv = parseInt(v.id_vehiculo, 10) || 0;
                    html += renderCardVehiculo(idv, v.placa, v.modelo, idx === 0);
                });
                html += '</div>';
                $cont.html(html);

                // Pedir documentación + validaciones de cada vehículo.
                resp.vehiculos.forEach(function(v){
                    var idv = parseInt(v.id_vehiculo, 10) || 0;
                    if (!idv) return;
                    syncCookieNoEmpleado();

                    // Documentos
                    $.ajax({
                        url: '../ControlVehicular/acciones_qr.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            accion: 'obtenerDatosVehiculo',
                            id_vehiculo: idv
                        }
                    }).done(function(data) {
                        if (!data || data.error) {
                            $('#docsVeh-' + idv).html('<p class="text-muted small mb-0 p-3">No se pudo cargar la documentación</p>');
                            return;
                        }
                        $('#docsVeh-' + idv).html(renderListaDocs(data));
                        vehiculosEstado[idv] = vehiculosEstado[idv] || {};
                        vehiculosEstado[idv].docs = evaluarDocs(data);
                        actualizarSemaforoVehiculo();
                    }).fail(function(){
                        $('#docsVeh-' + idv).html('<p class="text-danger small mb-0 p-3">Error al obtener documentación</p>');
                    });

                    // Validaciones (checklist + mantenimiento)
                    $.ajax({
                        url: '../ControlVehicular/acciones_qr.php',
                        type: 'POST', dataType: 'json',
                        data: { accion: 'obtenerValidacionesVehiculo', id_vehiculo: idv }
                    }).done(function(data){
                        if (!data || data.error) {
                            $('#chkVeh-' + idv).html('<p class="text-muted small mb-0 p-3">No se pudo cargar el checklist</p>');
                            $('#mntVeh-' + idv).html('<p class="text-muted small mb-0 p-3">No se pudo cargar el mantenimiento</p>');
                            return;
                        }
                        $('#chkVeh-' + idv).html(renderListaChecklist(data));
                        $('#mntVeh-' + idv).html(renderListaMantenimiento(data));
                        vehiculosEstado[idv] = vehiculosEstado[idv] || {};
                        vehiculosEstado[idv].vals = evaluarValidaciones(data);
                        actualizarSemaforoVehiculo();
                    }).fail(function(){
                        $('#chkVeh-' + idv).html('<p class="text-danger small mb-0 p-3">Error</p>');
                        $('#mntVeh-' + idv).html('<p class="text-danger small mb-0 p-3">Error</p>');
                    });
                });
                vehiculosDocsCargados = true;
            }).fail(function() {
                $cont.html('<div class="alert alert-danger">No se pudo obtener la información de vehículos.</div>');
            });
        }

        function renderCardVehiculo(idVeh, placa, modelo, abierto) {
            var titulo = (placa || '') + (modelo ? ' - ' + modelo : '');
            var shown   = abierto ? ' show'    : '';
            var btnCls  = abierto ? ''          : ' collapsed';
            var spinner = '<div class="text-center text-muted p-3"><i class="fas fa-spinner fa-spin"></i></div>';

            return ''
                + '<div class="card shadow-sm mb-3" data-vehiculo="' + idVeh + '">'
                +   '<div class="card-header p-0" id="headingV-' + idVeh + '" style="background: var(--card-soft); border-color: var(--border);">'
                +     '<button class="btn btn-link w-100 text-left py-2 px-3 d-flex align-items-center justify-content-between font-weight-bold' + btnCls + '" '
                +             'style="color: var(--text); text-decoration:none;" '
                +             'type="button" data-toggle="collapse" data-target="#collapseV-' + idVeh + '" aria-expanded="' + (abierto ? 'true' : 'false') + '" aria-controls="collapseV-' + idVeh + '">'
                +       '<span><i class="fas fa-car mr-2"></i>' + titulo + '</span>'
                +       '<i class="fas fa-chevron-down"></i>'
                +     '</button>'
                +   '</div>'
                +   '<div id="collapseV-' + idVeh + '" class="collapse' + shown + '" aria-labelledby="headingV-' + idVeh + '" data-parent="#accordionVehiculos">'
                +     '<div class="card-body p-2">'
                +       '<div class="row no-gutters">'
                +         '<div class="col-md-4 px-1 mb-2">'
                +           '<div class="card h-100">'
                +             '<div class="card-header py-2 d-flex align-items-center justify-content-between" style="background: var(--card-soft); border-color: var(--border);">'
                +               '<h6 class="m-0 font-weight-bold small">Documentación</h6>'
                +               '<a href="../ControlVehicular/documentacion?v=' + idVeh + '" target="_blank" class="btn btn-warning btn-sm" title="Actualizar Docs">'
                +                 '<i class="fas fa-folder-open"></i>'
                +               '</a>'
                +             '</div>'
                +             '<div class="card-body p-0" id="docsVeh-' + idVeh + '">' + spinner + '</div>'
                +           '</div>'
                +         '</div>'
                +         '<div class="col-md-4 px-1 mb-2">'
                +           '<div class="card h-100">'
                +             '<div class="card-header py-2 d-flex align-items-center justify-content-between" style="background: var(--card-soft); border-color: var(--border);">'
                +               '<h6 class="m-0 font-weight-bold small">Checklist</h6>'
                +               '<a href="../ControlVehicular/checkVehiculo?v=' + idVeh + '" target="_blank" class="btn btn-warning btn-sm" title="Realizar Checklist">'
                +                 '<i class="fas fa-clipboard-check"></i>'
                +               '</a>'
                +             '</div>'
                +             '<div class="card-body p-0" id="chkVeh-' + idVeh + '">' + spinner + '</div>'
                +           '</div>'
                +         '</div>'
                +         '<div class="col-md-4 px-1 mb-2">'
                +           '<div class="card h-100">'
                +             '<div class="card-header py-2 d-flex align-items-center justify-content-between" style="background: var(--card-soft); border-color: var(--border);">'
                +               '<h6 class="m-0 font-weight-bold small">Mantenimiento</h6>'
                +               '<a href="../ControlVehicular/seguimiento_mantenimiento.php" target="_blank" class="btn btn-warning btn-sm" title="Ver Mantenimientos">'
                +                 '<i class="fas fa-wrench"></i>'
                +               '</a>'
                +             '</div>'
                +             '<div class="card-body p-0" id="mntVeh-' + idVeh + '">' + spinner + '</div>'
                +           '</div>'
                +         '</div>'
                +       '</div>'
                +     '</div>'
                +   '</div>'
                + '</div>';
        }

        // Renderiza solo la lista (sin envoltorio card) — la card y header se crean en renderCardVehiculo.
        function renderListaChecklist(data) {
            var subareasOrden = [
                { campo: 'asientos',          label: 'Asientos' },
                { campo: 'espejos_ventanas',  label: 'Espejos y ventanas' },
                { campo: 'estereos_aire',     label: 'Estéreos y aire' },
                { campo: 'faros',             label: 'Faros' },
                { campo: 'golpes_exterior',   label: 'Golpes exterior' },
                { campo: 'limpiaparabrisas',  label: 'Limpiaparabrisas' },
                { campo: 'limpieza',          label: 'Limpieza' },
                { campo: 'llantas',           label: 'Llantas' },
                { campo: 'placas',            label: 'Placas' },
                { campo: 'puertas_llave',     label: 'Puertas y llave' }
            ];
            var subareas = (data.checklist && data.checklist.subareas) ? data.checklist.subareas : {};
            var html = '<ul class="list-group list-group-flush">';
            subareasOrden.forEach(function(s){
                var estado = subareas[s.campo] || 'no_revisado';
                var icono;
                if (estado === 'ok')        icono = '<i class="fas fa-check-circle text-success fa-fw mr-2"></i>';
                else if (estado === 'mal')  icono = '<i class="fas fa-times-circle text-danger fa-fw mr-2"></i>';
                else                        icono = '<i class="fas fa-minus-circle text-muted fa-fw mr-2"></i>';
                html += '<li class="list-group-item d-flex align-items-center py-2">' + icono + '<span class="small">' + s.label + '</span></li>';
            });
            html += '</ul>';
            return html;
        }

        function renderListaMantenimiento(data) {
            var mt = data.mantenimiento || null;
            if (!mt) return '<p class="text-muted small mb-0 p-3">Sin mantenimiento registrado</p>';

            // REALIZADO implica que ya pasó por autorización Y se ejecutó, por eso cuenta como autorizado.
            var vobo          = (mt.VoBo_jefe || '').toUpperCase();
            var realizado     = (vobo === 'REALIZADO');
            var autorizado    = (vobo === 'AUTORIZADO' || realizado);
            var sinPendientes = (vobo && vobo !== 'PENDIENTE');

            var labelEstado;
            if (realizado)                  labelEstado = 'Último mantenimiento realizado';
            else if (vobo === 'AUTORIZADO') labelEstado = 'Mantenimiento autorizado';
            else if (vobo === 'PENDIENTE')  labelEstado = 'Mantenimiento esperando autorización';
            else                            labelEstado = 'Mantenimiento sin estatus' + (vobo ? ' (' + vobo + ')' : '');

            var alDia = false;
            var labelProx;
            if (mt.fecha_proxi) {
                var prox = new Date(mt.fecha_proxi);
                if (!isNaN(prox.getTime())) {
                    alDia = (prox >= new Date());
                    labelProx = 'Próximo mantenimiento (' + mt.fecha_proxi + ')';
                } else {
                    labelProx = 'Próximo mantenimiento (sin fecha)';
                }
            } else if (realizado) {
                // Sin próxima fecha capturada pero el último ya se realizó → al día.
                alDia = true;
                labelProx = 'Sin próximo mantenimiento programado';
            } else {
                labelProx = 'Próximo mantenimiento (sin fecha)';
            }

            var items = [
                { ok: autorizado,    label: labelEstado },
                { ok: sinPendientes, label: 'Sin solicitudes pendientes' },
                { ok: alDia,         label: labelProx }
            ];
            var html = '<ul class="list-group list-group-flush">';
            items.forEach(function(it){
                var icono = it.ok
                    ? '<i class="fas fa-check-circle text-success fa-fw mr-2"></i>'
                    : '<i class="fas fa-times-circle text-danger fa-fw mr-2"></i>';
                html += '<li class="list-group-item d-flex align-items-center py-2">' + icono + '<span class="small">' + it.label + '</span></li>';
            });
            html += '</ul>';
            return html;
        }

        function renderListaDocs(v) {
            var docs = [{
                    campo: 'licencia',
                    label: 'Licencia'
                },
                {
                    campo: 'tarjeta_circulacion',
                    label: 'T. Circulación'
                },
                {
                    campo: 'refrendo_actual',
                    label: 'Refrendo'
                },
                {
                    campo: 'seguro_vehiculo',
                    label: 'Seguro'
                },
                {
                    campo: 'verificacion_vigente',
                    label: 'Verificación'
                }
            ];
            if (!v.fecha_reg_doc) {
                return '<p class="text-muted small mb-0 p-3">Sin documentación registrada</p>';
            }
            var html = '<ul class="list-group list-group-flush">';
            docs.forEach(function(d) {
                var tiene = v[d.campo] && v[d.campo] !== 'S/R';
                var icono = tiene ?
                    '<i class="fas fa-check-circle text-success fa-fw mr-2"></i>' :
                    '<i class="fas fa-times-circle text-danger fa-fw mr-2"></i>';
                html += '<li class="list-group-item d-flex align-items-center py-2">' + icono + '<span>' + d.label + '</span></li>';
            });
            html += '</ul>';
            return html;
        }

        // ===== Semáforo del tab Vehículo =====
        // Cuenta cuántos "items" de validación están OK vs total, por vehículo.
        // El semáforo agregado determina:
        //   verde = todo OK
        //   amarillo = algo registrado pero falta o está vencido
        //   rojo = nada registrado
        //   gris (default) = sin vehículos asignados / sin datos aún
        function evaluarDocs(v) {
            var docs = ['licencia', 'tarjeta_circulacion', 'refrendo_actual', 'seguro_vehiculo', 'verificacion_vigente'];
            var total = docs.length;
            var ok = 0;
            if (v.fecha_reg_doc) {
                docs.forEach(function(c){
                    if (v[c] && v[c] !== 'S/R') ok++;
                });
            }
            return { ok: ok, total: total };
        }

        function evaluarValidaciones(data) {
            var total = 0, ok = 0;

            // Checklist (10 subáreas)
            var subareas = (data.checklist && data.checklist.subareas) ? data.checklist.subareas : {};
            ['asientos','espejos_ventanas','estereos_aire','faros','golpes_exterior',
             'limpiaparabrisas','limpieza','llantas','placas','puertas_llave'].forEach(function(k){
                total++;
                if (subareas[k] === 'ok') ok++;
            });

            // Mantenimiento (3 items: autorizado, sin pendientes, próximo al día).
            // Misma semántica que renderListaMantenimiento: REALIZADO cuenta como autorizado
            // y "sin fecha_proxi tras un REALIZADO" cuenta como al día.
            var mt = data.mantenimiento || null;
            total += 3;
            if (mt) {
                var vobo = (mt.VoBo_jefe || '').toUpperCase();
                var realizado = (vobo === 'REALIZADO');
                if (vobo === 'AUTORIZADO' || realizado) ok++;
                if (vobo && vobo !== 'PENDIENTE') ok++;
                if (mt.fecha_proxi) {
                    var prox = new Date(mt.fecha_proxi);
                    if (!isNaN(prox.getTime()) && prox >= new Date()) ok++;
                } else if (realizado) {
                    ok++;
                }
            }

            return { ok: ok, total: total };
        }

        function actualizarSemaforoVehiculo() {
            var $dot = $('#statusTabVehiculo');
            $dot.removeClass('is-green is-yellow is-red');

            var totalAll = 0, okAll = 0, n = 0;
            Object.keys(vehiculosEstado).forEach(function(idv){
                n++;
                var est = vehiculosEstado[idv];
                if (est.docs) { totalAll += est.docs.total; okAll += est.docs.ok; }
                if (est.vals) { totalAll += est.vals.total; okAll += est.vals.ok; }
            });

            if (n === 0 || totalAll === 0) return; // dejar gris por defecto

            if (okAll === totalAll)      $dot.addClass('is-green');
            else if (okAll === 0)        $dot.addClass('is-red');
            else                         $dot.addClass('is-yellow');
        }

        // ===== Cookies =====
        function getCookie(name) {
            let matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
            ));
            return matches ? decodeURIComponent(matches[1]) : undefined;
        }

        // ===== Tallas =====
        function registraTallas() {
            $.ajax({
                url: 'login.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'registraTallas',
                    talla: document.getElementById('talla').value,
                    noEmpleado: getCookie('noEmpleadoL')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Talla actualizada correctamente.'
                        });
                        cargarTalla(getCookie('noEmpleadoL'));
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
                        $('#alertTalla').hide();
                        $('#talla').val(response.talla);
                    } else {
                        $('#alertTalla').show();
                    }
                }
            });
        }

        // ===== Buzón de Sugerencias =====
        function BuzonSugerencias() {
            $.ajax({
                url: 'login.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'buzon',
                    tipo: document.getElementById('tipo').value,
                    comentario: document.getElementById('comentario').value,
                    noEmpleado: getCookie('noEmpleadoL')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Comentario enviado correctamente.'
                        });
                        $('#formbuzon')[0].reset();
                        $('#modalbuzon').modal('hide');
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

        // ===== Cursos =====
        function cargarCursosSeleccionados() {
            $.ajax({
                url: 'acciones_inicio.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'cargar_cursos',
                    noEmpleado: getCookie('noEmpleadoL')
                },
                success: function(response) {
                    if (response.success && response.cursos) {
                        response.cursos.forEach(function(cursoId) {
                            $('input[name="cursos[]"][value="' + cursoId.id_voto + '"]').prop('checked', true);
                            $('#formCursos').hide();
                            $('#cursosSeleccionados').append('<li>' + cursoId.id_voto + '</li>');
                        });
                    }
                }
            });
        }

        function guardarAsistenciaCurso() {
            var cursosSeleccionados = [];
            $('input[name="cursos[]"]:checked').each(function() {
                cursosSeleccionados.push($(this).val());
            });
            if (cursosSeleccionados.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debes seleccionar al menos un curso.'
                });
                return;
            }
            $.ajax({
                url: 'acciones_inicio.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'guardar_asistencia',
                    cursos: cursosSeleccionados,
                    noEmpleado: getCookie('noEmpleadoL')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Asistencia Guardada',
                            text: 'Tu asistencia a los cursos ha sido registrada correctamente.'
                        });
                        cargarCursosSeleccionados();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'No se pudo registrar la asistencia.'
                        });
                    }
                }
            });
        }

        // ===== Ver tallas =====
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
                        tablaBody.empty();
                        response.tallas.forEach(function(t) {
                            tablaBody.append('<tr><td>' + t.noEmpleado + '-' + t.nombre + '</td><td>' + t.talla + '</td><td>' + t.sexo + '</td></tr>');
                        });
                        $('#modalResultadosTallas').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Sin tallas',
                            text: 'No se encontraron tallas en el sistema.'
                        });
                    }
                }
            });
        }

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
                        tablaBody.empty();
                        response.tallas.forEach(function(t) {
                            tablaBody.append('<tr><td>' + t.talla + '</td><td>' + t.sexo + '</td><td>' + t.cantidad + '</td></tr>');
                        });
                        $('#modalResultadosTallas').modal('show');
                    }
                }
            });
        }

        // ===== Excel =====
        function descargarExcel(tablaId) {
            var tabla = document.getElementById(tablaId);
            var wb = XLSX.utils.book_new();
            var ws_data = [];
            for (var i = 0; i < tabla.rows.length; i++) {
                var row = [];
                for (var j = 0; j < tabla.rows[i].cells.length; j++) row.push(tabla.rows[i].cells[j].innerText);
                ws_data.push(row);
            }
            var ws = XLSX.utils.aoa_to_sheet(ws_data);
            XLSX.utils.book_append_sheet(wb, ws, tablaId);
            XLSX.writeFile(wb, tablaId + '.xlsx');
        }

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
                        tablaBody.empty();
                        response.buzon.forEach(function(b) {
                            tablaBody.append('<tr><td>' + b.noEmpleado + '-' + b.nombre + '</td><td>' + b.tipo + '</td><td>' + b.comentario + '</td><td>' + b.fecha_registro + '</td></tr>');
                        });
                        $('#modalbuzon').modal('hide');
                        $('#modalVerSugerencias').modal('show');
                    }
                }
            });
        }

        // ===== Cambiar contraseña =====
        function cambiarCont() {
            $.ajax({
                url: 'cambiar_contrasena.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'CambiarPass',
                    contrasena_actual: $('#contrasena_actual').val(),
                    nueva_contrasena: $('#nueva_contrasena').val(),
                    confirmar_contrasena: $('#confirmar_contrasena').val()
                },
                success: function(response) {
                    $('#modalCambiarContrasena').modal('hide');
                    Swal.fire({
                        title: response.message,
                        icon: response.status,
                        draggable: true
                    });
                },
                error: function() {
                    Swal.fire({
                        title: "Vuelve a intentar, hubo un problema al actualizar la contraseña!",
                        icon: "warning"
                    });
                }
            });
        }

        // ===== Directorio =====
        var directorioCache = null;       // lista completa devuelta por el backend
        var directorioVista = [];         // subset visible (filtrado por buscador o no)
        var directorioPagina = 1;
        var DIR_PAGE_SIZE = 10;

        function dirIniciales(nombre) {
            if (!nombre) return '?';
            var partes = String(nombre).trim().split(/\s+/);
            var ini = (partes[0] || '').charAt(0);
            if (partes.length > 1) ini += partes[1].charAt(0);
            return ini.toUpperCase();
        }

        function dirColor(nombre) {
            var paleta = ['#050D9E', '#1A6FB3', '#1F8A70', '#C97B0F', '#7E4FB3', '#2E8DA8', '#B23A48'];
            var s = String(nombre || '?');
            var h = 0;
            for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
            return paleta[h % paleta.length];
        }

        function dirEscape(str) {
            return String(str == null ? '' : str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function cargarDirectorio() {
            if (directorioCache !== null) return;
            $.post('../incidencias/getInfoLoginMaster.php', {
                accion: 'listarEmpleados',
                noEmpleado: 0,
                correo: ''
            }).done(function (resp) {
                try {
                    var data = typeof resp === 'string' ? JSON.parse(resp) : resp;
                    if (data && data.status === 'success' && Array.isArray(data.info)) {
                        directorioCache = data.info;
                        renderDirectorio(directorioCache);
                    } else {
                        directorioCache = [];
                        $('#directorioGrid').html('<div class="col-12 empty-state">No hay empleados activos para mostrar.</div>');
                    }
                } catch (e) {
                    $('#directorioGrid').html('<div class="col-12 empty-state text-danger">Error al procesar el directorio.</div>');
                }
            }).fail(function () {
                $('#directorioGrid').html('<div class="col-12 empty-state text-danger">No se pudo conectar al servidor.</div>');
            });
        }

        function renderDirectorio(lista) {
            directorioVista = lista || [];
            directorioPagina = 1;
            pintarPaginaDirectorio();
        }

        function pintarPaginaDirectorio() {
            var total = directorioVista.length;
            if (!total) {
                $('#directorioGrid').html('<div class="col-12 empty-state">No se encontraron resultados.</div>');
                $('#directorioPaginacion').empty();
                return;
            }
            var totalPaginas = Math.max(1, Math.ceil(total / DIR_PAGE_SIZE));
            if (directorioPagina > totalPaginas) directorioPagina = totalPaginas;
            if (directorioPagina < 1) directorioPagina = 1;
            var start = (directorioPagina - 1) * DIR_PAGE_SIZE;
            var end   = Math.min(start + DIR_PAGE_SIZE, total);

            var html = '';
            for (var i = start; i < end; i++) {
                var e = directorioVista[i];
                var ini = dirIniciales(e.nombre);
                var col = dirColor(e.nombre);
                var nombre = dirEscape(e.nombre || 'Sin nombre');
                var nave   = dirEscape(e.nave   || '—');
                var area   = dirEscape(e.area   || '—');
                var puesto = dirEscape(e.puesto || '—');
                var correo = dirEscape(e.correo || '');
                var avatar = e.foto
                    ? '<div class="dir-avatar has-photo" style="background-image:url(\'' + dirEscape(e.foto) + '\'); background-color:' + col + '">' + ini + '</div>'
                    : '<div class="dir-avatar" style="background:' + col + '">' + ini + '</div>';
                html += '<div class="col-md-6 mb-3">'
                     +    '<div class="directorio-card" data-noemp="' + dirEscape(e.noEmpleado) + '">'
                     +      avatar
                     +      '<div class="dir-info">'
                     +        '<div class="dir-name">' + nombre + '</div>'
                     +        '<div class="dir-meta">' + nave + '</div>'
                     +        '<div class="dir-meta">' + area + '</div>'
                     +        '<div class="dir-meta">' + puesto + '</div>'
                     +        (correo ? '<span class="dir-mail">' + correo + '</span>' : '')
                     +      '</div>'
                     +    '</div>'
                     +  '</div>';
            }
            $('#directorioGrid').html(html);

            var prevDisabled = directorioPagina === 1 ? 'disabled' : '';
            var nextDisabled = directorioPagina === totalPaginas ? 'disabled' : '';
            var ctrl = ''
                + '<div class="dir-pag-info">Mostrando ' + (start + 1) + '–' + end + ' de ' + total + '</div>'
                + '<div class="dir-pag-nav">'
                +   '<button type="button" class="btn btn-sm btn-outline-secondary" id="dirPagPrev" ' + prevDisabled + '>'
                +     '<i class="fas fa-chevron-left"></i>'
                +   '</button>'
                +   '<span class="dir-pag-actual">Página ' + directorioPagina + ' de ' + totalPaginas + '</span>'
                +   '<button type="button" class="btn btn-sm btn-outline-secondary" id="dirPagNext" ' + nextDisabled + '>'
                +     '<i class="fas fa-chevron-right"></i>'
                +   '</button>'
                + '</div>';
            $('#directorioPaginacion').html(ctrl);
        }

        $(document).on('click', '#dirPagPrev', function () {
            if (directorioPagina > 1) { directorioPagina--; pintarPaginaDirectorio(); }
        });
        $(document).on('click', '#dirPagNext', function () {
            directorioPagina++; pintarPaginaDirectorio();
        });

        function abrirModalDirectorio(noEmp) {
            if (!directorioCache) return;
            var emp = null;
            for (var i = 0; i < directorioCache.length; i++) {
                if (String(directorioCache[i].noEmpleado) === String(noEmp)) { emp = directorioCache[i]; break; }
            }
            if (!emp) return;
            var ini = dirIniciales(emp.nombre);
            var col = dirColor(emp.nombre);
            var $avatar = $('#modalDirAvatar').text(ini);
            if (emp.foto) {
                $avatar.addClass('has-photo')
                       .css({ 'background-color': col, 'background-image': "url('" + emp.foto + "')" });
            } else {
                $avatar.removeClass('has-photo')
                       .css({ 'background-color': col, 'background-image': 'none' });
            }
            $('#modalDirNombre').text(emp.nombre || '—');
            $('#modalDirPuesto').text(emp.puesto || '—');
            $('#modalDirNoEmp').text(emp.noEmpleado || '—');
            $('#modalDirArea').text(emp.area || '—');
            $('#modalDirNave').text(emp.nave || '—');

            // Teléfono(s): viene ya formateado del backend ("4422908635 ext. 817 / 4423942739").
            $('#modalDirTel').text(emp.telefono || '—');

            if (emp.correo) {
                $('#modalDirCorreo').text(emp.correo).attr('href', 'mailto:' + emp.correo);
            } else {
                $('#modalDirCorreo').text('—').attr('href', '#');
            }
            $('#modalDirectorio').modal('show');
        }

        $(document).on('click', '.directorio-card', function () {
            abrirModalDirectorio($(this).data('noemp'));
        });

        $(document).on('input', '#directorioBuscar', function () {
            if (!directorioCache) return;
            var q = $(this).val().toLowerCase().trim();
            if (!q) { renderDirectorio(directorioCache); return; }
            var filtrado = directorioCache.filter(function (e) {
                return (e.nombre  || '').toLowerCase().indexOf(q) !== -1
                    || (e.area    || '').toLowerCase().indexOf(q) !== -1
                    || (e.nave    || '').toLowerCase().indexOf(q) !== -1
                    || (e.puesto  || '').toLowerCase().indexOf(q) !== -1
                    || (e.correo  || '').toLowerCase().indexOf(q) !== -1
                    || String(e.noEmpleado || '').indexOf(q) !== -1;
            });
            renderDirectorio(filtrado);
        });

        // Soltar el foco antes del cierre para evitar el warning de aria-hidden en Chrome.
        $('#modalDirectorio').on('hide.bs.modal', function () {
            if (document.activeElement) document.activeElement.blur();
        });

        // Carga lazy: la primera vez que se abre el tab.
        $(document).on('shown.bs.tab', '#tabDirectorio-tab', cargarDirectorio);
    </script>
</body>

</html>
<?php
    session_start();
    include '../ControlVehicular/conn.php';
    if($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null){
        echo '<script>window.location.assign("index")</script>';
    }
?>
<!DOCTYPE html>
<html lang = "en">

<head>

    <meta charset = "utf-8">
    <meta http-equiv = "X-UA-Compatible" content = "IE = edge">
    <meta name = "viewport" content = "width = device-width, initial-scale = 1, shrink-to-fit = no">
    <meta name = "description" content = "">
    <meta name = "author" content = "">

    <title>MESS</title>

    <!-- Custom fonts for this template-->
    <link href = "../ControlVehicular/vendor/fontawesome-free/css/all.min.css" rel = "stylesheet" type = "text/css">
    <link href = "https://fonts.googleapis.com/css?family = Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel = "stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css">
    <!-- Custom styles for this template-->
    <link href = "../ControlVehicular/css/sb-admin-2.min.css" rel = "stylesheet">

</head>

<body id = "page-top">

    <!-- Page Wrapper -->
    <div id = "wrapper">        
        <!-- Content Wrapper -->
        <div id = "content-wrapper" class = "d-flex flex-column">

            <!-- Main Content -->
            <div id = "content">            
                <?php                    
                    include 'encabezado.php';
                ?>                
                <!-- Begin Page Content -->
                <div class = "container-fluid">                    
                    <div class = "row">
                        <!-- Earnings (Monthly) Card Example -->
                        <div class = "col-xl-4 col-md-6 ">
                            <div class = "card border-left-primary shadow h-60 py-0">
                                <div class="card-head">
                                    <div class = "card-header py-3">
                                        <h6 class = "m-0 font-weight-bold text-primary">Tablero de avisos</h6>
                                    </div>
                                </div>
                                <div class = "card-body">
                                    <embed id="vistaPrevia" src='https://www.mess.com.mx/wp-content/uploads/2025/03/Marzo-2024.pdf#zoom=60' type="application/pdf" width="100%" height="380">
                                </div>
                            </div>
                        </div>                        

                        <div class = "col-xl-4 col-md-6 ">
                            <div class = "card border-left-success shadow h-60 py-0">
                                <div class="card-head">
                                    <div class = "card-header ">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-success mb-0">Agenda Sala de Juntas</h6>
                                            <a href="../ControlVehicular/" class="btn btn-outline-success btn-sm ml-2">Ir a Sala de Juntas</a>
                                        </div>
                                    </div>
                                </div>
                                <div class = "card-body">
                                    <div class="text-center" id="calendar"></div>
                                </div>
                            </div>
                        </div>

                        <div class = "col-xl-4 col-md-6 ">
                            <div class = "card border-left-info shadow h-60 py-0">
                                <div class="card-head">
                                    <div class = "card-header py-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-info mb-0">Horas Extra</h6>
                                            <a href="../ControlVehicular/" class="btn btn-outline-info btn-sm ml-2">Ir a Horas Extra</a>
                                        </div>
                                    </div>
                                </div>
                                <div class = "card-body">
                                    
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class = "sticky-footer bg-white">
                <div class = "container my-auto">
                    <div class = "copyright text-center my-auto">
                        <span>Copyright &copy; MESS 2025</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class = "scroll-to-top rounded" href = "#page-top">
        <i class = "fas fa-angle-up"></i>
    </a>


    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src = "../ControlVehicular/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src = "https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js"></script>
    <!-- Core plugin JavaScript-->
    <script src = "../ControlVehicular/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src = "../ControlVehicular/js/sb-admin-2.min.js"></script>
    
    <?php
        if ($_COOKIE['noEmpleado'] == '276' || $_COOKIE['noEmpleado'] == '215' || $_COOKIE['noEmpleado'] == '183' || $_COOKIE['noEmpleado'] == '523' || $_COOKIE['noEmpleado'] == '19') {
            // Mostrar el chat de Tidio solo para ciertos empleados
            echo '<script src="//code.tidio.co/7gdtsrztipqfhk4odfaiekkqicwhsvxb.js"></script>';
            echo '<script src="//code.tidio.co/ehwk9fqjsinnpptkgnupmphatkinnmwi.js"></script>';
        }
    ?>
    
    <script>
        $(document).ready(function () {
            verCalendarioLogin();
        });

        function verCalendarioLogin() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {        
                initialView: 'listWeek', // Cambiar a vista diaria
                events: '../Incidencias/SalaDeJuntas/acciones_calendarioGral.php?opcion=login', // Aquí llamas a tu PHP que devuelve las vacaciones en JSON
                editable: false,
                locale: 'es',
                eventContent: function(info) {
                    // Personalizar el contenido del evento
                    var nombreEmpleado = info.event.title;
                    var fechaInicio = info.event.start;
                    var fechaFin = info.event.end;
                    var descripcion = info.event.extendedProps.descripcion || 'Sin descripción'; // Obtener la descripción del evento
                    var displayText = nombreEmpleado + '<br>' + descripcion;

                    return { html: displayText };
                }
            });
            calendar.render();
        } 
    </script>
</body>

</html>
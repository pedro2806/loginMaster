    <!DOCTYPE html>
<html lang = "sp">
<head>
    <meta charset = "utf-8">
    <meta http-equiv = "X-UA-Compatible" content = "IE = edge">
    <meta name = "viewport" content = "width = device-width, initial-scale = 1, shrink-to-fit = no">
    <meta name = "description" content = "">
    <meta name = "author" content = "">

    <title>APPS MESS</title>

    <!-- Custom fonts for this template-->
    <link href = "../ControlVehicular/vendor/fontawesome-free/css/all.min.css" rel = "stylesheet" type = "text/css">    
    <!-- Custom styles for this template-->
    <link href = "../ControlVehicular/css/sb-admin-2.min.css" rel = "stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css">
</head>
<body style="background-image:url(fondo.png); background-size: cover; background-repeat: no-repeat; background-attachment: fixed;">
    <div class = "container">
        <div class = "row justify-content-center">
            <div class = "col-xl-10 col-lg-12 col-md-9">
                <div class = "card o-hidden border-0 shadow-lg my-5">
                    <div class = "card-body p-0">
                        <div class = "row justify-content-center">                                                        
                            <div class = "p-0 text-center">                                    
                                <br><img src = "../ControlVehicular/img/MESS_05_Imagotipo.svg" alt = "Logo MESS" width = "350px">
                            </div>                            
                        </div>
                        <div class = "row">
                            <div class = "col-sm-1"></div>
                            <div class = "col-sm-10">
                                <div class = "text-center">
                                    <b class = "h5 text-gray-900 mb-4">Sistemas MESS</b>
                                    <b class = "h5 text-gray-900 mb-4">Bienvenido</b>
                                </div>
                            </div>
                        </div>
                        <div class = "row">
                            <!--LOGIN-->                            
                            <div class = "col-sm-6 d-flex flex-column align-items-center">
                                <div class = "card p-5 w-100">
                                    <center> Login sistemas MESS</center>
                                    <br>
                                    <form onsubmit="validaSesion(); return false;">
                                        <div class = "form-group">
                                            <input type = "text" class = "form-control form-control-user" id = "InputEmail" name = "InputEmail" aria-describedby = "emailHelp" placeholder = "Usuario">
                                            <span>@mess.com.mx</span>
                                        </div>
                                        <div class = "form-group">
                                            <input type = "password" class = "form-control form-control-user" id = "InputPassword" name = "InputPassword" placeholder = "Contraseña">
                                        </div>
                                        <div class = "form-group">
                                            <div class = "custom-control custom-checkbox small">
                                                <input type = "checkbox" class = "custom-control-input" id = "customCheck">
                                                <label class = "custom-control-label" for = "customCheck">Recordar usuario y contraseña</label>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <input class = "btn btn-primary btn-md" type = "submit" name = "btningresar" value = "   Acceder   "/>
                                        </div>
                                    </form>    </div>
                            </div>
                            <!--LOGIN-->
                            <!--IMAGEN-->
                            <div class = "col-sm-6">
                                <div class="p-4">
                                    <div class="text-center" id="calendar"></div>
                                </div>    
                            </div>
                        </div>
                        <br>                        
                        <!--BARRA DE SOPORTE-->
                        <div class="row">  
                            <div class = "col-lg-6 mx-auto">
                                <center><b style="font-size: 0.9em;">Login centralizado para todos los sistemas de MESS, permitiendo acceso unificado y gestión de credenciales desde un solo punto.</b></center>                            
                            </div>          
                            <div class = "col-lg-6 mx-auto">
                                <center>
                                    <p class="alert alert-info" style="font-size: 0.9em;">
                                        Soporte del sistema:                                        
                                        <a href="mailto:pedro.martinez@mess.com.mx">pedro.martinez@mess.com.mx</a>
                                    </p>
                                </center>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src = "../ControlVehicular/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src = "../ControlVehicular/vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src = "../ControlVehicular/js/sb-admin-2.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src = "https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js"></script>    
    <script>
    $(document).ready(function () {
        verCalendarioLogin();
    });
    function validaSesion() {
        var usuario = $('#InputEmail').val(); 
        var password = $('#InputPassword').val();
        if (usuario === '' || password === '') {
            // Mostrar alerta de campos vacíos
            Swal.fire({
                icon: 'warning',
                title: 'Campos vacíos',
                text: 'Por favor, completa todos los campos.',
                confirmButtonText: 'Aceptar'
            });
            return false;
        } else {
            $.ajax({
                type: 'POST',
                url: 'login.php',
                data: {
                    InputEmail: usuario,
                    InputPassword: password,
                    btningresar: 'Ingresar'
                },
                success: function (response) {
                    var dataArray = [];
                    try {
                        dataArray = typeof response === "string" ? JSON.parse(response) : response;
                    } catch (e) {
                        dataArray = [];
                        Swal.fire({
                            title: "Error",
                            text: "Respuesta inválida del servidor.",
                            icon: "error"
                        });
                        return;
                    }

                    if (!dataArray || dataArray.length === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Credenciales incorrectas',
                            text: 'Usuario o contraseña inválidos.',
                            confirmButtonText: 'Aceptar'
                        });
                        return;
                    }

                    dataArray.forEach(function (data) {
                        const expires = new Date(Date.now() + 99900000).toUTCString();
                        document.cookie = `id_usuarioL=${encodeURIComponent(data.id)}; expires=${expires}; SameSite=Lax; path=/;`;
                        document.cookie = `nombredelusuarioL=${encodeURIComponent(data.nombre)}; expires=${expires}; SameSite=Lax; path=/;`;
                        document.cookie = `noEmpleadoL=${encodeURIComponent(data.noEmpleado)}; expires=${expires}; SameSite=Lax; path=/;`;
                        document.cookie = `rolL=${encodeURIComponent(data.rol)}; expires=${expires}; SameSite=Lax; path=/;`;
                        document.cookie = `correoL=${encodeURIComponent(data.usuario)}; expires=${expires}; SameSite=Lax; path=/;`;
                        document.cookie = `UsrKpis=${encodeURIComponent(data.kpis)}; expires=${expires}; SameSite=Lax; path=/;`;
                    });

                    window.location.href = 'inicio';
                },    error: function (xhr, status, error) {
                    // ... (tu código de manejo de errores)
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al procesar tu solicitud. Inténtalo de nuevo más tarde.',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        }
    }
    function verCalendarioLogin() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {        
            initialView: 'listWeek', // Cambiar a vista diaria
            events: '../incidencias/SalaDeJuntas/acciones_calendarioGral.php?opcion=login', // Aquí llamas a tu PHP que devuelve las vacaciones en JSON
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
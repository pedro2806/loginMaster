<!-- Topbar -->
<nav class = "navbar navbar-expand navbar-light bg-white topbar mb-2 static-top shadow">
<!-- Enlace a Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Enlace a Bootstrap JS (necesario para el funcionamiento del modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Enlace a FontAwesome para los íconos (si usas íconos) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<!-- Sidebar Toggle (Topbar) -->
<button id = "sidebarToggleTop" class = "btn btn-link d-md-none rounded-circle mr-3">
    <i class = "fa fa-bars"></i>
</button>

<!-- Topbar Navbar -->
<ul class = "navbar-nav ml-auto">    
    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
    <li class = "nav-item dropdown no-arrow d-sm-none">
        <a class = "nav-link dropdown-toggle" href = "#" id = "searchDropdown" role = "button"
            data-toggle = "dropdown" aria-haspopup = "true" aria-expanded = "false">
            <i class = "fas fa-search fa-fw"></i>
        </a>
        <!-- Dropdown - Messages -->
        <div class = "dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
            aria-labelledby = "searchDropdown">
            <form class = "form-inline mr-auto w-100 navbar-search">
                <div class = "input-group">
                    <input type = "text" class = "form-control bg-light border-0 small"
                        placeholder = "Search for..." aria-label = "Search"
                        aria-describedby = "basic-addon2">
                    <div class = "input-group-append">
                        <button class = "btn btn-primary" type = "button">
                            <i class = "fas fa-search fa-sm"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </li>    
    <div class = "topbar-divider d-none d-sm-block"></div>
    <!-- Nav Item - User Information -->    
    <li class = "nav-item dropdown no-arrow">
        <a class = "nav-link dropdown-toggle" href = "#" id = "userDropdown" role = "button"
            data-toggle = "dropdown" aria-haspopup = "true" aria-expanded = "false">
            <span class = "mr-4 text-gray-600">
                <?php echo $_COOKIE['nombredelusuario']?>
            </span>
            
            <i class="fas fa-user-circle fa-2x text-gray-300"></i>
            
            
        </a>
        <!-- Dropdown - User Information -->
        <div class = "dropdown-menu dropdown-menu-right shadow animated--grow-in"   aria-labelledby = "userDropdown">
            
            <div class = "dropdown-divider"></div>
            <a class = "dropdown-item" href = "#" data-toggle = "modal" data-target = "#logoutModalN">
                <i class = "fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                Salir
            </a>
        </div>
    </li>

</ul>

    <!-- Logout Modal-->
    <div class = "modal fade" id = "logoutModalN" tabindex = "-1" role = "dialog" aria-labelledby = "exampleModalLabel"aria-hidden = "true">
        <div class = "modal-dialog" role = "document">
            <div class = "modal-content border-left-danger">
                <div class = "modal-header">
                    <h4 class = "modal-title" id = "exampleModalLabel"> Cerrar sesión </h4>
                    <button class = "close" type = "button" data-dismiss = "modal" aria-label = "Close">
                        <span aria-hidden = "true">X</span>
                    </button>
                </div>
                <div class = "modal-body"><h5><b>¿Estas seguro?</b></h5></div>
                <div class = "modal-footer">
                    <button class = "btn btn-warning" type = "button" data-dismiss = "modal">Cancelar</button>
                    <a class = "btn btn-danger" href = "logout">Salir</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>

        $(document).ready(function () {
            verCalendarioLogin();
        });

        function verCalendarioLogin() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {        
                initialView: 'listWeek', // Cambiar a vista diaria
                events: '../ControlVehicular/SalaDeJuntas/acciones_calendarioGral.php?opcion=login', // Aquí llamas a tu PHP que devuelve las vacaciones en JSON
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
    
    //Funcion para leer cookies
    function getCookie(name) {
        let value = "; " + document.cookie;
        let parts = value.split("; " + name + "=");
        if (parts.length === 2) return parts.pop().split(";").shift();
        return null; // Si no encuentra la cookie, retorna null
    }
    // Asignar el valor de la cookie al input
    window.onload = function() {
        var cookieValue = getCookie("noEmpleado"); // Aquí "noEmpleadoCookie" es el nombre de la cookie
    
        // Verificar si la cookie existe y asignar el valor al input
        if (cookieValue) {
            document.getElementById("noEmpleado").value = cookieValue;
        }
    };
    </script>
</nav>
<!-- End of Topbar -->
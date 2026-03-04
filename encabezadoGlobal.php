<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white mb-2 static-top shadow">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-1">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto" style="height:20px; align-items:center;">
        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow" style="height:20px;">
            <a class="nav-link dropdown-toggle py-1" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="height:20px; display:flex; align-items:center;">
                <span class="mr-0 text-gray-600" style="font-size:15px; line-height:1;">
                    Acceso a los sistemas de MESS Servicios Metrológicos
                </span>
            </a>
        </li>
    </ul>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModalN" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-left-danger">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel">Cerrar sesión</h4>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5><b>¿Estas seguro?</b></h5>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-warning" type="button" data-dismiss="modal">Cancelar</button>
                    <a class="btn btn-danger" href="logout">Salir</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    </script>
</nav>
<!-- End of Topbar -->

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        cargarNotificaciones(false); // Carga inicial de notificaciones sin mostrar flotantes
        setInterval(function() {
            cargarNotificaciones(false);
        }, 30000);
    });

    // Función para leer cookies
    function getCookie(name) {
        let value = "; " + document.cookie;
        let parts = value.split("; " + name + "=");
        if (parts.length === 2) return parts.pop().split(";").shift();
        return null; // Si no encuentra la cookie, retorna null
    }

    // Función para escapar caracteres especiales en HTML
    function escapeHtml(texto) {
        return String(texto || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Función para limpiar el stack de notificaciones flotantes
    function limpiarStackNotificaciones() {
        $('#notificationStack').empty();
    }

    // Función para renderizar una notificación flotante
    function renderNotificacionFlotante(notificacion) {
        var stack = $('#notificationStack');
        var iniciales = escapeHtml(notificacion.iniciales || 'NA');
        var nota = escapeHtml(notificacion.nota || notificacion.mensaje || 'Sin nota');
        var accion = escapeHtml(notificacion.accion || '');
        var fecha = escapeHtml(notificacion.fecha_actualizacion || notificacion.fecha || '');
        var id = parseInt(notificacion.id, 10) || 0;
        var idRegistro = parseInt(notificacion.id_registro_referencia, 10) || 0;

        var html = '';
        html += '<div class="toast show border-0 shadow-sm mb-2" data-notificacion-id="' + id + '" role="alert" aria-live="assertive" aria-atomic="true">';
        html += '  <div class="toast-body p-2">';
        html += '      <div class="d-flex justify-content-between align-items-start gap-4">';
        html += '          <div class="d-flex align-items-start gap-4">';
        html += '              <span class="badge rounded-pill bg-primary mt-1">' + iniciales + ' - ' + notificacion.accion + '</span>';
        html += '              <div>';
        html += '                  <div class="small text-dark fw-semibold">' + nota + '</div>';
        html += '                  <div class="small text-muted">' + fecha + '</div>';
        html += '              </div>';
        html += '          </div>';
        html += '          <button class="btn btn-sm btn-outline-success" title="Marcar como leída" aria-label="Marcar como leída" onclick="marcarNotificacionLeida(' + id + ', ' + idRegistro + ')">';
        html += '              <i class="fas fa-check"></i>';
        html += '          </button>';
        html += '      </div>';
        html += '  </div>';
        html += '</div>';

        var toast = $(html);
        stack.append(toast);

        setTimeout(function() {
            toast.fadeOut(10000, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Función para mostrar notificaciones flotantes al hacer clic en el botón
    function mostrarNotificacionesFlotantes() {
        cargarNotificaciones(true);
    }

    // Función para cargar notificaciones desde el servidor
    function cargarNotificaciones(mostrarFlotantes) {
        var badge = $('#badgeNotificaciones');
        $.ajax({
            url: 'acciones_globales.php',
            method: 'POST',
            data: { accion: 'cargarNotificaciones' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var notificaciones = response.notificaciones;
                    var total = parseInt(response.total || 0, 10);

                    if (total > 0) {
                        badge.removeClass('d-none').text(total > 99 ? '99+' : total);
                    } else {
                        badge.addClass('d-none').text('0');
                    }

                    if (mostrarFlotantes === true) {
                        limpiarStackNotificaciones();
                        if (notificaciones.length > 0) {
                            notificaciones.forEach(function(notificacion) {
                                renderNotificacionFlotante(notificacion);
                            });
                        } else {
                            renderNotificacionFlotante({
                                id: 0,
                                iniciales: 'OK',
                                nota: 'No tienes nuevas notificaciones.',
                                fecha_actualizacion: ''
                            });
                        }
                    }
                }
            }
        });
    }

    // Función para marcar una notificación como leída
    function marcarNotificacionLeida(idNotificacion, idRegistro) {
        $.ajax({
            url: 'acciones_globales.php',
            method: 'POST',
            dataType: 'json',
            data: {
                accion: 'marcarLeida',
                idNotificacion: idNotificacion
            },
            success: function(response) {
                if (response.success) {
                    $('[data-notificacion-id="' + idNotificacion + '"]').fadeOut(200, function() {
                        $(this).remove();
                    });
                    cargarNotificaciones(false);
                    if (parseInt(idRegistro, 10) > 0) {
                        window.location.href = 'entradaTareas.php?id=' + parseInt(idRegistro, 10);
                    }
                }
            }
        });
    }
    </script>
</nav>
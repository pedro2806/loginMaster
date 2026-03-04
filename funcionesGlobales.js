async function validaOpciones(sistema, opcion) {
    try {
        const info = await $.ajax({
            url: '../loginMaster/acciones_globales.php',
            type: 'POST',
            dataType: 'json',
            data: {
                noEmpleado: getCookie('noEmpleado'),
                sistema: sistema,
                opcion: opcion,
                accion: 'ValidarPermisos'
            }
        });

        return info; // Ahora sí espera a tener el valor
    } catch (error) {        
        return 0;
    }
}

// Función para renderizar una notificación flotante
function renderNotificacionFlotante(notificacion) {
    var stack = $('#notificationStack');
    var iniciales = escapeHtml(notificacion.iniciales || 'NA');
    var nota = escapeHtml(notificacion.nota || notificacion.mensaje || 'Sin nota');
    var accion = escapeHtml(notificacion.accion || '');
    var sistema = escapeHtml(notificacion.sistema || accion || 'General');
    var fecha = escapeHtml(notificacion.fecha_actualizacion || notificacion.fecha || '');
    var iconoSistema = obtenerIconoNotificacion(sistema.toLowerCase());
    var id = parseInt(notificacion.id, 10) || 0;
    var idRegistro = parseInt(notificacion.id_registro_referencia, 10) || 0;
    var sistema = escapeHtml(notificacion.sistema || 'General');
    var archivo = escapeHtml(notificacion.archivo || '');

    var html = '';
    html += '<div class="toast show border-0 shadow-sm mb-3" data-notificacion-id="' + id + '" role="alert" aria-live="assertive" aria-atomic="true">';
    html += '  <div class="toast-body p-2">';
    html += '      <div class="d-flex justify-content-between align-items-center">';
    html += '          <div class="d-flex align-items-center flex-wrap">';
    html += '              <span class="badge rounded-pill bg-primary text-white px-3 py-2 mr-2 mb-1">';
    html += '                  <i class="' + iconoSistema + ' mr-2"></i>' + iniciales + ' - ' + sistema;
    html += '              </span>';
    html += '              <div class="mb-1">';
    html += '                  <span class="text-dark font-weight-bold mr-3" style="font-size: .95rem; line-height:1.1;">' + nota + '</span>';
    html += '                  <span class="text-muted" style="font-size: .90rem; white-space: nowrap;"><i class="far fa-calendar-alt mr-1"></i>' + fecha + '</span>';
    html += '              </div>';
    html += '          </div>';
    html += '          <button class="btn btn-sm btn-light border border-success text-success px-2 py-1" title="Marcar como leída" aria-label="Marcar como leída" onclick="marcarNotificacionLeida(' + id + ', ' + idRegistro + ', \'' + sistema + '\', \'' + archivo + '\', \'' + getCookie('noEmpleado') + '\')">';
    html += '              <i class="fas fa-check fa-sm"></i>';
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

// Función para determinar el ícono de la notificación según el sistema
function obtenerIconoNotificacion(sistema) {
    if (sistema.indexOf('incidencia') !== -1) {
        return 'fas fa-exclamation-triangle';
    }
    if (sistema.indexOf('ctrlVehicular') !== -1) {
        return 'fas fa-car';
    }
    if (sistema.indexOf('entradasEq') !== -1) {
        return 'fas fa-users';
    }
    if (sistema.indexOf('planeacion') !== -1) {
        return 'fas fa-calendar-alt';
    }
    if (sistema.indexOf('activos') !== -1) {
        return 'fas fa-box';
    }
    return 'fas fa-bell';
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
function marcarNotificacionLeida(idNotificacion, idRegistro, sistema, archivo) {
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
                var urlDestino = construirUrlNotificacion(sistema, archivo, idRegistro);
                if (urlDestino !== '') {
                    window.location.href = urlDestino;
                }
            }
        }
    });
}

// Función para escapar caracteres HTML
function limpiarStackNotificaciones() {
        $('#notificationStack').empty();
    }

// Función para escapar caracteres HTML
function escapeHtml(texto) {
    return String(texto || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Función para construir la URL de destino según el sistema y archivo
function construirUrlNotificacion(sistema, archivo, idRegistro) {
    $.ajax({
        url: '../planeacion/validaLoginNot.php',
        type: 'POST',
        dataType: 'json',
        data: {
            noEmpleado: getCookie('noEmpleadoL'),
            sistema: sistema,
            archivo: archivo,            
            idRegistro: idRegistro,
        },
        success: function(response) {
            
                    alert('Redirigiendo a la sección correspondiente...');
                    window.location.href = '/planeacion/entradaDetalleEntradas.php?id=' + idRegistro;
            
            
        }
    });
    /*var urlBase = 'https://messbook.com.mx/';
    var urlBase = 'http://localhost/';
    
    //INCIDENCIAS
    if (sistema.toLowerCase().indexOf('incidencia') !== -1) {
        return urlBase + 'incidencias/entradaTareas.php?id=' + idRegistro;
    }
    
    //CTRL VEHICULAR
    if (sistema.toLowerCase().indexOf('ctrlvehicular') !== -1) {
        return urlBase + '/ControlVehicular/autorizar_prestamo.php'
    } 

    //ENTRADAS EQUIPOS
    if (sistema.toLowerCase().indexOf('entradaseq') !== -1) {
        return urlBase + '/planeacion/entradaDetalleEntradas.php?id=' + idRegistro;
    }

    //PLANEACION
    if (sistema.toLowerCase().indexOf('planeacion') !== -1) {
        return urlBase + '/planeacion/seguimiento_actividades.php?id=' + idRegistro;
    }

    //ACTIVOS
    if (sistema.toLowerCase().indexOf('activos') !== -1) {
        return urlBase + '/activos/detalleActivo.php?id=' + idRegistro;
    }*/
    return '';
}
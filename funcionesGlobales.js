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

async function validaOpcionesSistema(sistema, opcion) {
    try {
        const info = await $.ajax({
            url: '../loginMaster/acciones_globales.php',
            type: 'POST',
            dataType: 'json',
            data: {                
                sistema: sistema,
                opcion: opcion,
                accion: 'ValidarPermisosSistema'
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
    var sistema = escapeHtml(notificacion.sistema || accion || 'General');
    var fecha = escapeHtml(notificacion.fecha_actualizacion || notificacion.fecha || '');
    var iconoSistema = obtenerIconoNotificacion(sistema.toLowerCase());
    var id = parseInt(notificacion.id, 10) || 0;
    var idRegistro = parseInt(notificacion.id_registro_referencia, 10) || 0;
    var sistema = escapeHtml(notificacion.sistema || 'General');
    var archivo = escapeHtml(notificacion.archivo || '');
    var recordar = escapeHtml(notificacion.recordar || '');
    var creadoPor = escapeHtml(notificacion.usuario_actualiza_nombre || notificacion.id_usuario_actualiza || '');

    var html = '';
    html += '<div class="toast show border-0 shadow-sm mb-3" data-notificacion-id="' + id + '" role="alert" aria-live="assertive" aria-atomic="true">';
    html += '  <div class="toast-body p-2">';
    html += '      <div class="d-flex justify-content-between align-items-center">';
    html += '          <div class="d-flex align-items-center flex-wrap">';
    html += '              <span class="badge rounded-pill bg-primary text-white px-3 py-2 mr-2 mb-1">';
    html += '                  <i class="' + iconoSistema + ' mr-2"></i>' + sistema;
    html += '              </span>';
    html += '              <div class="mb-1">';
    html += '                  <span class="text-dark font-weight-bold mr-3" style="font-size: .95rem; line-height:1.1;">' + creadoPor + ' - ' + recordar + '</span>';
    html += '                  <span class="text-muted" style="font-size: .90rem; white-space: nowrap;"><i class="far fa-calendar-alt mr-1"></i>' + fecha + '</span>';
    html += '              </div>';
    html += '          </div>';
    html += '          <button class="btn btn-sm btn-light border border-success text-success px-2 py-1" title="Marcar como leída" aria-label="Marcar como leída" onclick="marcarNotificacionLeida(' + id + ', ' + idRegistro + ', \'' + sistema + '\', \'' + archivo + '\', \'' + getCookie('noEmpleadoL') + '\')">';
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
function marcarNotificacionLeida(idNotificacion, idRegistro, sistema, archivo, noEmpleado) {
    $("#campoNuevoValor").val(archivo);
    $.ajax({
        url: 'acciones_globales.php',
        method: 'POST',
        dataType: 'json',
        data: {
            accion: 'marcarLeida',
            idNotificacion: idNotificacion,
            noEmpleado: noEmpleado
        },
        success: function(response) {
            if (response.success) {
                $('[data-notificacion-id="' + idNotificacion + '"]').fadeOut(200, function() {
                    $(this).remove();
                });
                cargarNotificaciones(false);
                construirUrlNotificacion(idNotificacion, sistema, archivo, idRegistro);                
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

// Función para pasar de texto a JSON
function parsearRespuestaNotificacion(rawResponse) {
    var response = rawResponse;
    if (typeof rawResponse !== 'string') {
        return response;
    }

    var texto = rawResponse.trim();
    try {
        response = JSON.parse(texto);
    } catch (e1) {
        // Algunos entornos retornan scripts + JSON al final; tomamos el ultimo bloque JSON.
        var inicioJson = texto.lastIndexOf('{');
        if (inicioJson === -1) {
            console.error('No se encontro JSON en la respuesta:', texto);
            return null;
        }

        try {
            response = JSON.parse(texto.substring(inicioJson));
        } catch (e2) {
            console.error('Respuesta no es JSON valido:', texto);
            return null;
        }
    }

    return response;
}

// Función para procesar la redirección después de marcar la notificación como leída
function procesarRedireccionNotificacion(response, idNotificacion, sistema, archivo, idRegistro) {
    var idRegistroInt = parseInt(idRegistro || 0, 10);
    var idNotificacionInt = parseInt(idNotificacion || 0, 10);
    var idFormulario = null;
    var urlDestino = String((response && response.urlDestino) || '').trim();

    if (urlDestino === '') {
        console.error('No se recibio urlDestino para el sistema:', sistema, 'archivo:', archivo);
        alert('No se pudo determinar la pantalla destino para esta notificacion.');
        return;
    }

    // Caso especial: entradasEq redirige directo con URL Query
    if (sistema === 'entradasEq' && idRegistroInt > 0) {
        var separadorId = urlDestino.indexOf('?') === -1 ? '?' : '&';
        window.location.href = urlDestino + separadorId + 'id=' + idRegistroInt;
        return;
    }

    // Identificar el formulario según el sistema
    if (sistema === 'incidencias') {
        idFormulario = 'formIncidencias';
    } else if (sistema === 'ctrlVehicular') {
        idFormulario = 'formControlVehicular';
    } else if (sistema === 'entradasEq') {
        idFormulario = 'formEntradasEq';
    } else if (sistema === 'planeacion') {
        idFormulario = 'formPlaneacion';
    } else if (sistema === 'activos') {
        idFormulario = 'formActivos';
    } else if (sistema === 'vacaciones') {
        idFormulario = 'formVacaciones';
    } else if (sistema === 'horas') {
        idFormulario = 'formHorasExtra';
    } else if (sistema === 'practicantes') {
        idFormulario = 'formPracticantes';
    }

    var formulario = idFormulario ? document.getElementById(idFormulario) : null;

    if (formulario) {
        formulario.action = urlDestino;

        // Rellenar campos hidden existentes con datos del usuario
        var idUsuario = getCookie('id_usuarioL') || '';
        var nombre = getCookie('nombredelusuarioL') || '';
        var noEmpleado = getCookie('noEmpleadoL') || '';
        var correo = getCookie('correoL') || '';

        formulario.querySelectorAll('input[type="hidden"]').forEach(function(input) {
            var nombreCampo = input.name || '';
            if (nombreCampo === 'id_usuario' || nombreCampo === 'idUsuario') {
                input.value = idUsuario;
            } else if (nombreCampo === 'nombredelusuario') {
                input.value = nombre;
            } else if (nombreCampo === 'noEmpleado') {
                input.value = noEmpleado;
            } else if (nombreCampo === 'correo') {
                input.value = correo;
            } else if (nombreCampo === 'id') {
                input.value = idNotificacionInt;
            } else if (nombreCampo === 'idRegistro') {
                input.value = idRegistroInt;
            } else if (nombreCampo === 'archivo') {
                input.value = archivo;
            } else if (nombreCampo === 'sistema') {
                input.value = sistema;
            }
        });

        formulario.submit();
        return;
    }

    // Si no existe formulario, redirigir vía URL con query parameters
    var separador = urlDestino.indexOf('?') === -1 ? '?' : '&';
    var queryParams = 'id=' + idNotificacionInt + '&idRegistro=' + idRegistroInt + '&archivo=' + encodeURIComponent(archivo) + '&sistema=' + encodeURIComponent(sistema);

    // Redirigir a la URL construida
    window.location.href = urlDestino + separador + queryParams;
}

// Función para construir la URL de destino según el sistema y archivo
function construirUrlNotificacion(idNotificacion, sistema, archivo, idRegistro) {
    var endpointsPorSistema = {
        entradasEq: '/planeacion/validaLoginNot.php',
        planeacion: '/planeacion/validaLoginNot.php',

        incidencias: '/incidencias/validaLoginNot.php',
        vacaciones: '/incidencias/validaLoginNot.php',
        
        ctrlVehicular: '/ctrlVehicular/validaLoginNot.php',
        activos: '/activos/validaLoginNot.php',
        horas: '/horas/validaLoginNot.php',
        practicantes: '/practicantes/validaLoginNot.php'
    };
    var endpointValidaLogin = endpointsPorSistema[sistema];

    $.ajax({
        url: endpointValidaLogin,
        type: 'POST',
        dataType: 'text',
        data: {
            noEmpleado: getCookie('noEmpleadoL'),
            sistema: sistema,
            archivo: archivo,            
            idRegistro: idRegistro
        },
        success: function(rawResponse) {
            var response = parsearRespuestaNotificacion(rawResponse);
            if (!response) {
                return;
            }

            if (response.status === 'success' || response.success === true) {
                procesarRedireccionNotificacion(response, idNotificacion, sistema, archivo, idRegistro);
            } else {
                // Manejar error de validación (ej. sesión expirada o sin permisos)
                alert('Error: ' + (response.mensaje || 'No tienes permisos para acceder.'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en la petición:', error, 'status:', xhr.status, 'response:', xhr.responseText);
            alert('Hubo un error de conexión con el servidor.');
        }
    });
}
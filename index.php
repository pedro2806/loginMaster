<?php
// ===== BLOQUE RECUPERAR CONTRASEÑA CON PHPMailer =====
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requires también van arriba
require __DIR__ . '/libs/PHPMailer/src/Exception.php';
require __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/libs/PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'recover_password') {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Correo inválido']);
        exit;
    }
    
    include '../incidencias/conn.php';
    
    if (!isset($conn) || $conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a BD']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT password_restaurar FROM usuarios WHERE correo = ? AND estatus ='1' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'El correo no está registrado o el usuario esta dado de baja']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $row = $result->fetch_assoc();
    $passwordRecuperar = trim($row['password_restaurar'] ?? '');
    
    if (empty($passwordRecuperar)) {
        echo json_encode(['success' => false, 'message' => 'No hay contraseña de recuperación asignada. Contacta a soporte de Messbook']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $mail =  new PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->SMTPDebug = 0; // PONER EN 0 SI NO QUIERES QUE SALGA EL LOG EN LA PANTALLA
                          //PONER EN 2 PARA DEPURACION DETALLADA
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mess.metrologia@gmail.com';
        $mail->Password   = 'hglidvwsxcbbefhe';
        $mail->SMTPSecure =  PHPMailer::ENCRYPTION_SMTPS;//PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 465; //desarrollo 587, produccion 465 con SSL
        $mail->CharSet    = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom('mess.metrologia@gmail.com', 'Messbook');
        $mail->addAddress($email);
        $mail->addReplyTo('sebastian.gutierrez@mess.com.mx', 'Soporte Messbook');
        
        // Contenido HTML - DISEÑO CORPORATIVO TIPO FACEBOOK
       $mail->isHTML(true);
$mail->Subject = 'Recuperación de contraseña - Messbook';

$mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f0f2f5; font-family: Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f2f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    <!-- Header azul sólido -->
                    <tr>
                        <td bgcolor="#1e3a8a" style="background-color: #1e3a8a; padding: 40px 30px; text-align: center; border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; letter-spacing: -0.5px; font-family: Helvetica, Arial, sans-serif;">messbook</h1>
                            <p style="margin: 8px 0 0 0; color: #e4e6eb; font-size: 14px; font-family: Helvetica, Arial, sans-serif;">Central Identity and Access Management System</p>
                        </td>
                    </tr>

                    <!-- Card blanca -->
                    <tr>
                        <td bgcolor="#ffffff" style="background-color: #ffffff; padding: 40px 30px; border-radius: 0 0 12px 12px;">
                            <h2 style="margin: 0 0 20px 0; color: #1c1e21; font-size: 20px; font-weight: 600; font-family: Helvetica, Arial, sans-serif;">Recuperación de contraseña</h2>

                            <p style="margin: 0 0 24px 0; color: #1c1e21; font-size: 15px; line-height: 1.5; font-family: Helvetica, Arial, sans-serif;">
                                Hola,<br>
                              Recibimos una solicitud para recuperar tu contraseña de Messbook.
                            </p>

                            <!-- Box de contraseña -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f2f5; border: 1.5px solid #dddfe2; border-radius: 8px; margin: 24px 0;">
                                <tr>
                                    <td style="padding: 20px; text-align: center;">
                                        <p style="margin: 0 0 8px 0; color: #65676b; font-size: 13px; font-weight: 600; text-transform: uppercase; font-family: Helvetica, Arial, sans-serif;">Tu contraseña de recuperación</p>
                                        <p style="margin: 0; color: #1877f2; font-size: 24px; font-weight: 700; letter-spacing: 2px; font-family: Courier, monospace;">'.$passwordRecuperar.'</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 24px 0; color: #1c1e21; font-size: 15px; line-height: 1.5; font-family: Helvetica, Arial, sans-serif;">
                                Ingresa con esta contraseña y cámbiala inmediatamente desde tu perfil para mantener tu cuenta segura.
                            </p>

                            <!-- Botón azul sólido -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="https://messbook.com.mx/loginMaster" style="display: inline-block; background-color: #1877f2; color: #ffffff; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-size: 16px; font-weight: 700; font-family: Helvetica, Arial, sans-serif;">
                                            Iniciar sesión en Messbook
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #dadde1; margin-top: 30px; padding-top: 20px;">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0 0 8px 0; color: #1c1e21; font-size: 13px; font-weight: 600; font-family: Helvetica, Arial, sans-serif;">Si tienes problemas para acceder a Messbook contacta a:</p>
                                        <p style="margin: 0; color: #65676b; font-size: 13px; line-height: 1.6; font-family: Helvetica, Arial, sans-serif;">
                                            <a href="mailto:pedro.martinez@mess.com.mx" style="color: #1877f2; text-decoration: none;">pedro.martinez@mess.com.mx</a><br>
                                            <a href="mailto:sebastian.gutierrez@mess.com.mx" style="color: #1877f2; text-decoration: none;">sebastian.gutierrez@mess.com.mx</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; text-align: center;">
                            <p style="margin: 0; color: #8a8d91; font-size: 11px; line-height: 1.5; font-family: Helvetica, Arial, sans-serif;">
                                Este correo fue enviado por Messbook.<br>
                                Business Intelligence | Messbook ©️ '.date("Y").'
                            </p>
                            <p style="margin: 12px 0 0 0; color: #8a8d91; font-size: 11px; font-family: Helvetica, Arial, sans-serif;">
                                Si no solicitaste este correo, ignóralo de forma segura.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

// Versión texto plano
$mail->AltBody = "Hola,\n\nTu contraseña de recuperación es: $passwordRecuperar\n\nIngresa con esta contraseña y cámbiala desde tu perfil.\n\nSaludos,\nMessbook Development Team ©️ 2026";
        
     
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Contraseña enviada a tu correo']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al enviar correo: ' . $mail->ErrorInfo]);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}
// ===== FIN BLOQUE RECUPERAR CONTRASEÑA =====
?>
<!DOCTYPE html>
<html lang="sp">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Messbook</title>

    <link rel="icon" type="image/png" href="../loginMaster/img/fav.png">

    <!-- Custom fonts for this template-->
    <link href="../ControlVehicular/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">    
    <!-- Custom styles for this template-->
    <link href="../ControlVehicular/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css">
    
    <link href="css/login.css" rel="stylesheet">
</head>
<body>
    <div class="fb-container">
        <div class="fb-content">
            <div class="fb-inner">
                <!--IZQUIERDA: BRANDING-->
                <div class="fb-left">
                    <img src="../loginMaster/img/messbook_logo3.png" alt="Messbook" class="fb-logo-img">
                    <h2 class="fb-tagline" style="font-size: 18px !important;">
                        Messbook es el sistema central de gestión de identidades y accesos de la empresa.
                        Acceso seguro a todo tu ecosistema corporativo.
                    </h2>
                </div>
                
                <!--DERECHA: LOGIN CARD-->
                <div class="fb-right">
                    <div class="fb-card">
                        <div class="fb-card-body">
                            <div class="fb-card-title">Iniciar sesión en Messbook</div>
                            <form onsubmit="validaSesion(); return false;">
                                <div class="fb-input-group">
                                    <i class="fas fa-user fb-input-icon"></i>
                                    <input type="text" class="fb-input" id="InputEmail" name="InputEmail" aria-describedby="emailHelp" placeholder="Correo electrónico">
                                    <div class="fb-domain-text"></div>
                                </div>
                                
                                <div class="fb-input-group">
                                    <i class="fas fa-lock fb-input-icon"></i>
                                    <input type="password" class="fb-input" id="InputPassword" name="InputPassword" placeholder="Contraseña">
                                </div>
                                
                                <div class="fb-check-wrap">
                                    <div class="custom-control custom-checkbox small">
                                        <input type="checkbox" class="custom-control-input" id="customCheck">
                                        <label class="custom-control-label" for="customCheck">Recordar mis datos</label>
                                    </div>
                                </div>
                                
                                <input class="fb-login-btn" type="submit" name="btningresar" value="Iniciar sesión"/>
                                <a id="forgotPasswordLink" name="forgotPasswordLink" href="#" style="display: block; text-align: center; margin-top: 12px; font-size: 14px; color: #1877f2; text-decoration: none;">¿Olvidaste tu contraseña?</a>
                            </form>
                            
                            <div class="fb-divider"></div>
                            
                            <div class="fb-support-text">
                                <strong>Messbook es desarrollado por el equipo de BI</strong><br>
                                <a href="mailto:pedro.martinez@mess.com.mx">pedro.martinez@mess.com.mx</a><br>
                                <a href="mailto:sebastian.gutierrez@mess.com.mx">sebastian.gutierrez@mess.com.mx</a>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 28px; text-align: center; font-size: 14px;">
                      
                    </div>
                </div>
            </div>
        </div>
        
        <div class="fb-footer">
            <div class="fb-footer-inner">
               
                
                <img src="../loginMaster/img/mess-desarrollo-b1.png" alt="Grupo Mess" class="fb-footer-logo">
                
                <div class="fb-footer-links">
                   Business Intelligence | Messbook ©️ <?php echo date("Y"); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL RECUPERAR CONTRASEÑA ===== -->
    <div id="recoverModal" class="recover-modal">
        <div class="recover-modal-content">
            <div class="recover-modal-title">Recuperar contraseña</div>
            <div class="recover-modal-text">Ingresa tu correo de mess y te enviaremos tu contraseña de recuperación.</div>
            <input type="email" id="recoverEmail" class="recover-modal-input" placeholder="usuario@mess.com.mx">
            <div class="recover-modal-btns">
                <button class="recover-modal-btn recover-modal-btn-secondary" onclick="closeRecoverModal()">Cancelar</button>
                <button class="recover-modal-btn recover-modal-btn-primary" onclick="sendRecoverEmail()">Enviar</button>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="../ControlVehicular/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="../ControlVehicular/vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="../ControlVehicular/js/sb-admin-2.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.js"></script>    
    <script>
    $(document).ready(function () {
        verCalendarioLogin();
        
        // ===== MODAL RECUPERAR CONTRASEÑA =====
        $('#forgotPasswordLink').on('click', function(e) {
            e.preventDefault();
            $('#recoverModal').fadeIn(200);
        });
        
        // Cerrar modal al hacer click fuera
        $(window).on('click', function(e) {
            if ($(e.target).is('#recoverModal')) {
                closeRecoverModal();
            }
        });
    });
    
    function closeRecoverModal() {
        $('#recoverModal').fadeOut(200);
        $('#recoverEmail').val('');
    }
    
    function sendRecoverEmail() {
        var email = $('#recoverEmail').val().trim();
        
        if (!email) {
            Swal.fire('Error', 'Ingresa tu correo', 'warning');
            return;
        }
        
        // CERRAR MODAL INMEDIATAMENTE AL DAR CLICK - ARREGLADO
        closeRecoverModal();
        
        Swal.fire({
            title: 'Enviando...',
            text: 'Buscando tu contraseña',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            type: 'POST',
            url: window.location.href,
            data: {
                action: 'recover_password',
                email: email
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('¡Listo!', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'No se pudo procesar la solicitud. Revisa la consola.', 'error');
                console.log(xhr.responseText);
            }
        });
    }
    // ===== FIN MODAL RECUPERAR CONTRASEÑA =====
    function validaSesion() {
        var usuario = $('#InputEmail').val(); 
        var password = $('#InputPassword').val();
        if (usuario === '' || password === '') {
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
                        document.cookie = `fotoL=${encodeURIComponent(data.foto)}; expires=${expires}; SameSite=Lax; path=/;`;
                        document.cookie = `UsrKpis=${encodeURIComponent(data.kpis)}; expires=${expires}; SameSite=Lax; path=/;`;

                        // Cookies para Tickets BI (mismo valor que las *L, sufijo BI).
                        // Acotadas a path=/Tickets para que solo existan dentro del sistema de tickets BI.
                        document.cookie = `id_usuarioBI=${encodeURIComponent(data.id)}; expires=${expires}; SameSite=Lax; path=/Tickets;`;
                        document.cookie = `nombredelusuarioBI=${encodeURIComponent(data.nombre)}; expires=${expires}; SameSite=Lax; path=/Tickets;`;
                        document.cookie = `noEmpleadoBI=${encodeURIComponent(data.noEmpleado)}; expires=${expires}; SameSite=Lax; path=/Tickets;`;
                        document.cookie = `rolBI=${encodeURIComponent(data.rol)}; expires=${expires}; SameSite=Lax; path=/Tickets;`;
                        document.cookie = `correoBI=${encodeURIComponent(data.usuario)}; expires=${expires}; SameSite=Lax; path=/Tickets;`;
                        document.cookie = `fotoBI=${encodeURIComponent(data.foto)}; expires=${expires}; SameSite=Lax; path=/Tickets;`;
                    });

                    window.location.href = 'inicio';
                },    error: function (xhr, status, error) {
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
        if (!calendarEl) return;
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
    </script>
    
</body>
</html>
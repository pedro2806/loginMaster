<?php
require_once '../incidencias/conn.php';

// 1. Obtener ID del evento y del empleado (Simulado o de Sesión)
$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;

$id_empleado = $_COOKIE['noEmpleadoL'] ?? null;

if ($id_evento <= 0) die("Acceso no válido.");

// 2. Consultar datos del evento
$res_ev = $conn->query("SELECT * FROM enc_eventos WHERE id_evento = $id_evento AND estatus = 1");
$evento = $res_ev->fetch_assoc();

if (!$evento) die("El evento no existe o ha finalizado.");

// 3. VALIDACIÓN CRÍTICA: ¿Está el empleado invitado/asignado?
$sql_asig = "SELECT confirmado FROM enc_eventos_asignados WHERE id_evento = $id_evento AND id_empleado = $id_empleado";

$res_asig = $conn->query($sql_asig);
$asignacion = $res_asig->fetch_assoc();

// Bandera para saber si ya participó
$ya_participo = ($asignacion && $asignacion['confirmado'] == 1) ? true : false;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $evento['nombre']; ?></title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .card-dinamica { transition: 0.3s; border-radius: 15px; overflow: hidden; }
        .card-dinamica:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .badge-info-custom { background: #4e73df; color: white; padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    
    <div class="row justify-content-center mb-4">
        <div class="col-md-10 text-center">
            <h1 class="h2 font-weight-bold text-primary"><?php echo $evento['nombre']; ?></h1>
            <p class="text-muted"><?php echo $evento['descripcion']; ?></p>
        </div>
    </div>

    <?php if (!$asignacion): ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-left-danger shadow h-100 py-4">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
                        <h4 class="font-weight-bold">Acceso Restringido</h4>
                        <p>Lo sentimos, no te encuentras en la lista de convocados para esta actividad.</p>
                        <a href="index.php" class="btn btn-secondary btn-sm">Regresar al Inicio</a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($ya_participo): ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-left-success shadow h-100 py-4">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4 class="font-weight-bold">¡Participación Registrada!</h4>
                        <p>Gracias por confirmar tu asistencia o voto. Tu respuesta ha sido guardada correctamente.</p>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <form id="formParticipacion">
            <input type="hidden" name="id_evento" value="<?php echo $id_evento; ?>">
            <input type="hidden" name="id_empleado" value="<?php echo $id_empleado; ?>">

            <div class="row justify-content-center">
                <?php
                $opciones = $conn->query("SELECT * FROM enc_eventos_opciones WHERE id_evento = $id_evento ORDER BY pregunta ASC");
                
                while ($op = $opciones->fetch_assoc()): ?>
                    
                    <?php if ($evento['tipo'] == 'votacion'): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card card-dinamica shadow h-100 border-0">
                                <img src="<?php echo $op['ruta_imagen']; ?>" class="card-img-top" style="height: 350px; object-fit: cover;">
                                <div class="card-body text-center">
                                    <h5 class="font-weight-bold text-dark mb-3"><?php echo $op['titulo']; ?></h5>
                                    <button type="button" class="btn btn-primary btn-block rounded-pill" onclick="enviarRespuesta(<?php echo $op['id_opcion']; ?>)">
                                        <i class="fas fa-vote-yea"></i> Votar
                                    </button>
                                </div>
                            </div>
                        </div>

                    <?php else: // ASISTENCIA O ENCUESTA ?>
                        <div class="col-md-10 mb-3">
                            <div class="card card-dinamica shadow-sm border-left-info py-2">
                                <div class="card-body d-flex justify-content-between align-items-center px-4">
                                    <div>
                                        <h6 class="m-1 font-weight-bold text-primary text-bold"><?php echo $op['titulo']; ?></h6>
                                        <div class="mt-1">
                                            <?php if($op['grupo']): ?>
                                                <span class="badge-info-custom text-white">Gpo: <?php echo $op['grupo']; ?></span>
                                            <?php endif; ?>
                                            <?php if($op['fecha_opcion']): ?>
                                                <b>Fecha del curso:<small class="text-muted ml-3"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($op['fecha_opcion'])); ?></small></b>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="custom-control <?php echo ($evento['tipo'] == 'asistencia' ? 'custom-checkbox' : 'custom-radio'); ?> custom-control-lg">
                                        <input type="<?php echo ($evento['tipo'] == 'asistencia' ? 'checkbox' : 'radio'); ?>" 
                                                name="opcion_val[]" 
                                                class="custom-control-input chk-participacion" 
                                                id="opt<?php echo $op['id_opcion']; ?>" 
                                                value="<?php echo $op['id_opcion']; ?>">
                                        <label class="custom-control-label" for="opt<?php echo $op['id_opcion']; ?>"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endwhile; ?>
            </div>

            <?php if ($evento['tipo'] != 'votacion'): ?>
                <div class="text-center mt-5">
                    <button type="button" class="btn btn-success btn-lg px-5 shadow rounded-pill" onclick="guardarMultiple()">
                        <i class="fas fa-check-double"></i> Confirmar Participación
                    </button>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Para Votaciones (una sola opción)
function enviarRespuesta(id_op) {
    ejecutarEnvio([id_op]);
}

// Para Asistencias o Encuestas (múltiples o selección)
function guardarMultiple() {
    let seleccionados = $('.chk-participacion:checked').map(function(){ return $(this).val(); }).get();
    if(seleccionados.length === 0) return Swal.fire('Atención', 'Selecciona al menos una opción.', 'warning');
    ejecutarEnvio(seleccionados);
}

function ejecutarEnvio(opciones_array) {
    $.post('acciones_eventos.php', {
        accion: 'registrar_participacion_final',
        id_evento: <?php echo $id_evento; ?>,
        id_empleado: <?php echo $id_empleado; ?>,
        opciones: opciones_array
    }, function(res) {
        if(res.status === 'success') {
            Swal.fire('¡Éxito!', 'Tu participación ha sido guardada.', 'success').then(() => { location.reload(); });
        } else {
            Swal.fire('Atención', res.msg, 'info');
        }
    }, 'json');
}
</script>
</body>
</html>
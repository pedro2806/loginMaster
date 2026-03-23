<?php
require_once '../incidencias/conn.php';

// Validamos el ID del evento
$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_evento <= 0) {
    die("ID de evento no válido.");
}

// 1. Consultar datos del evento
$sql_ev = "SELECT * FROM enc_eventos WHERE id_evento = $id_evento AND estatus = 1";
$res_ev = $conn->query($sql_ev);
$evento = $res_ev->fetch_assoc();

if (!$evento) {
    die("El evento no existe o ya no está disponible.");
}

// Simulamos el ID del empleado (Normalmente de $_SESSION)
$id_empleado = 1; 
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
        .card-hover:hover { transform: scale(1.01); transition: 0.2s; cursor: pointer; }
        .custom-control-lg .custom-control-label::before, 
        .custom-control-lg .custom-control-label::after {
            top: 0.1rem !important; left: -2rem !important;
            width: 1.5rem !important; height: 1.5rem !important;
        }
        .custom-control-lg .custom-control-label {
            margin-left: 0.5rem !important; padding-top: 0.2rem !important;
            cursor: pointer; font-size: 1.1rem;
        }
        .pregunta-header { background-color: #f8f9fc; border-bottom: 2px solid #4e73df; }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="card shadow border-left-primary mb-4 text-center">
        <div class="card-body">
            <h1 class="h3 font-weight-bold text-primary"><?php echo $evento['nombre']; ?></h1>
            <p class="mb-2 text-dark"><?php echo $evento['descripcion']; ?></p>
            <span class="badge badge-pill badge-info px-3">
                <i class="far fa-clock"></i> Cierra: <?php echo date("d/m/Y H:i", strtotime($evento['fecha_fin'])); ?>
            </span>
        </div>
    </div>

    <form id="formParticipacion">
        <input type="hidden" name="id_evento" value="<?php echo $id_evento; ?>">
        <input type="hidden" name="id_empleado" value="<?php echo $id_empleado; ?>">

        <div class="row justify-content-center">
            <?php
            // Consultamos las opciones
            $res_op = $conn->query("SELECT * FROM enc_eventos_opciones WHERE id_evento = $id_evento");
            
            // --- CASO A: VOTACIÓN (GALERÍA DE FOTOS) ---
            if ($evento['tipo'] == 'votacion'): 
                while ($op = $res_op->fetch_assoc()): ?>
                    <div class="col-sm-6 col-md-4 mb-4">
                        <div class="card h-100 shadow border-bottom-primary card-hover">
                            <img src="<?php echo $op['ruta_imagen']; ?>" class="card-img-top" style="height: 220px; object-fit: cover;">
                            <div class="card-body text-center">
                                <h5 class="font-weight-bold"><?php echo $op['titulo']; ?></h5>
                                <button type="button" class="btn btn-primary btn-block rounded-pill" onclick="enviarVotoUnico(<?php echo $op['id_opcion']; ?>)">
                                    <i class="fas fa-heart"></i> Votar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

            // --- CASO B: ASISTENCIA (CHECKLIST MÚLTIPLE) ---
            <?php elseif ($evento['tipo'] == 'asistencia'): 
                while ($op = $res_op->fetch_assoc()): ?>
                    <div class="col-md-10 col-lg-8 mb-2">
                        <div class="card shadow-sm border-left-success py-2 card-hover" onclick="$('#chk<?php echo $op['id_opcion']; ?>').click()">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-dark"><?php echo $op['titulo']; ?></h6>
                                <div class="custom-control custom-checkbox custom-control-lg">
                                    <input type="checkbox" class="custom-control-input chk-asistencia" id="chk<?php echo $op['id_opcion']; ?>" value="<?php echo $op['id_opcion']; ?>" onclick="event.stopPropagation()">
                                    <label class="custom-control-label" for="chk<?php echo $op['id_opcion']; ?>"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <div class="col-12 text-center mt-4">
                    <button type="button" class="btn btn-success btn-lg shadow rounded-pill px-5" onclick="guardarAsistencias()">
                        <i class="fas fa-check-double"></i> Confirmar Mi Asistencia
                    </button>
                </div>

            // --- CASO C: ENCUESTA (MÚLTIPLES PREGUNTAS) ---
            <?php elseif ($evento['tipo'] == 'encuesta'): 
                // Agrupamos opciones por pregunta en un array de PHP
                
                $preguntas = [];
                while($row = $res_op->fetch_assoc()) {
                    $titulo_p = !empty($row['pregunta']) ? $row['pregunta'] : 'Pregunta General';
                    $preguntas[$titulo_p][] = $row;
                }
                
                $idx = 0;
                foreach ($preguntas as $pregunta_texto => $items): $idx++; ?>
                    <div class="col-md-10 col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header pregunta-header">
                                <h6 class="m-0 font-weight-bold text-primary"><?php echo $idx; ?>. <?php echo $pregunta_texto; ?></h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($items as $op): ?>
                                    <div class="custom-control custom-radio custom-control-lg mb-3">
                                        <input type="radio" name="preg_<?php echo $idx; ?>" class="custom-control-input rad-encuesta" 
                                                id="rad<?php echo $op['id_opcion']; ?>" value="<?php echo $op['id_opcion']; ?>">
                                        <label class="custom-control-label text-dark" for="rad<?php echo $op['id_opcion']; ?>">
                                            <?php echo $op['titulo']; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-12 text-center mt-2">
                    <button type="button" class="btn btn-info btn-lg shadow rounded-pill px-5" onclick="enviarEncuestaCompleta()">
                        <i class="fas fa-paper-plane"></i> Enviar Encuesta
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// A. VOTO ÚNICO (Votación)
function enviarVotoUnico(id_op) {
    ejecutarRegistro([id_op], 'Tu voto ha sido registrado.');
}

// B. ASISTENCIAS (Múltiples)
function guardarAsistencias() {
    let seleccionados = $('.chk-asistencia:checked').map(function(){ return $(this).val(); }).get();
    if(seleccionados.length === 0) return Swal.fire('Atención', 'Selecciona al menos un ítem', 'warning');
    ejecutarRegistro(seleccionados, 'Asistencia confirmada.');
}

// C. ENCUESTA (Múltiples preguntas)
function enviarEncuestaCompleta() {
    let seleccionados = [];
    let grupos = [...new Set($('.rad-encuesta').map(function(){ return $(this).attr('name'); }).get())];
    let incompleto = false;

    grupos.forEach(nombre => {
        let valor = $(`input[name="${nombre}"]:checked`).val();
        if(!valor) incompleto = true;
        else seleccionados.push(valor);
    });

    if(incompleto) return Swal.fire('Atención', 'Por favor responde todas las preguntas.', 'warning');
    ejecutarRegistro(seleccionados, 'Encuesta enviada con éxito.');
}

// FUNCIÓN DE ENVÍO AL BACKEND
function ejecutarRegistro(opciones_ids, mensajeExito) {
    $.post('acciones_eventos.php', {
        accion: 'registrar_asistencia_multiple', // Usamos este case para procesar arrays
        id_evento: <?php echo $id_evento; ?>,
        id_empleado: getCookie('noEmpleadoL'), // Intentamos obtener de cookie, si no usamos el hardcodeado
        opciones: opciones_ids
    }, function(res) {
        if(res.status === 'success') {
            Swal.fire('¡Gracias!', mensajeExito, 'success').then(() => { location.reload(); });
        } else {
            Swal.fire('Atención', res.msg, 'info');
        }
    }, 'json');
}

function getCookie(name) {
    let value = "; " + document.cookie;
    let parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null; // Si no encuentra la cookie, retorna null
}
</script>
</body>
</html>
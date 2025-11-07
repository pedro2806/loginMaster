<?php
include '../incidencias/conn.php';

$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$noEmpleado = isset($_POST['noEmpleado']) ? $_POST['noEmpleado'] : '';

//MOSTRAR TALLAS
if ($accion == 'ver_tallas') {

    $sqlTallas ="  SELECT ta.noEmpleado, ta.talla, ta.prenda, ta.fecha_captura, us.sexo, us.nombre
                    FROM tallas ta
                    INNER JOIN usuarios us ON ta.noEmpleado= us.noEmpleado 
                    ORDER BY noEmpleado ASC";
    $result = $conn->query($sqlTallas);

    $tallasData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tallasData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'tallas' => $tallasData]);
    $conn->close();
    exit;
}

//CONTEO DE TALLAS
if ($accion == 'conteo_tallas') {

    $sqlTotalTallas = "  SELECT ta.talla, us.sexo, COUNT(*) AS cantidad
                    FROM tallas ta
                    INNER JOIN usuarios us ON ta.noEmpleado = us.noEmpleado
                    WHERE ta.talla IN ('XS', 'S', 'M', 'L', 'XL')
                    GROUP BY ta.talla, us.sexo
                    ORDER BY us.sexo ASC, 
                             FIELD(ta.talla, 'XS', 'S', 'M', 'L', 'XL')";
    $result = $conn->query($sqlTotalTallas);

    $tallasData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tallasData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'tallas' => $tallasData]);
    $conn->close();
    exit;
}

//VER VOTOS DEL CARRUSEL
if ($accion == 'conteo_votos') {
    $sqlTotalVotos = "  SELECT id_foto, COUNT(*) AS cantidad
                    FROM votos_fotos
                    GROUP BY id_foto";
    $result = $conn->query($sqlTotalVotos);

    $votosData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $votosData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'votos' => $votosData]);
    $conn->close();
    exit;
}

//VER BUZON DE SUGERENCIAS
if ($accion == 'ver_buzon') {

    $sqlBuzon ="  SELECT noEmpleado, nombre, departamento, mensaje, fecha_captura
                    FROM buzonsugerencias
                    ORDER BY fecha_captura DESC";
    $result = $conn->query($sqlBuzon);

    $buzonData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $buzonData[] = $row;
        }
    }
    echo json_encode(['success' => true, 'buzon' => $buzonData]);
    $conn->close();
    exit;
}

?>
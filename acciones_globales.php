<?php
header('Content-Type: application/json');
include '../incidencias/conn.php';

$accion = $_POST["accion"];
$noEmpleado = $_POST["noEmpleado"];
$sistema = $_POST["sistema"];
$opcion = $_POST["opcion"];

if($accion == "ValidarPermisos"){
    // Usamos sentencias preparadas para evitar inyecciones SQL
    $stmt = $conn->prepare("SELECT COUNT(*) AS cuantos FROM accesos_especiales 
                            WHERE noEmpleado = ? AND sistema = ? AND opcion = ? AND estatus = 1");
    $stmt->bind_param("iss", $noEmpleado, $sistema, $opcion);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $row = $result->fetch_assoc();
        // Devolvemos una estructura más simple para facilitar el JS
        echo json_encode([
            'status' => 'success', 
            'data' => [['cuantos' => $row['cuantos']]] 
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error en la consulta']);
    }
    $stmt->close();
}

?>
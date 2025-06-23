<?php
include '../ControlVehicular/conn.php';

$email = $_POST['InputEmail'];
$password = $_POST['InputPassword'];
$accion = $_POST['btningresar'];

if ($accion == 'Ingresar') {
    $datosUsr = [];        

    $sql = "SELECT  * FROM usuarios WHERE usuario = '$email' AND password = '$password'";                
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $vehiculos = [];
        while ($row = $result->fetch_assoc()) {
            $datosUsr[] = $row;
        }
        echo json_encode($datosUsr);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraro el usuario.']);
    }
    exit;
    
}

<?php
// acciones_eventos.php
require_once '../incidencias/conn.php'; // Tu conexión actual

$accion = $_POST['accion'] ?? '';

if ($_POST['accion'] == 'guardar_evento') {
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $inicio = $_POST['fecha_inicio'];
    $fin = $_POST['fecha_fin'];
    $desc = $_POST['descripcion'];

    $sql = "INSERT INTO enc_eventos (nombre, descripcion, tipo, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nombre, $desc, $tipo, $inicio, $fin);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => $conn->error]);
    }
    exit;
}
?>
<?php
/* ============================================================
 * poliza.php — Sirve el PDF de la póliza de un vehículo por placa.
 *
 * Los PDFs viven en POLIZAS_2026/ con el formato
 *   "POLIZA <numero> <placa>.pdf"  (ej. "POLIZA 5424708 UMZ106E.pdf")
 * Como el número de póliza varía, no hay enlace directo predecible:
 * se localiza el archivo por la placa y se entrega en línea (inline).
 *
 * Uso:  poliza.php?placa=UMZ106E
 * ============================================================ */

// Solo alfanumérico → evita path traversal y normaliza la placa.
$placa = isset($_GET['placa']) ? preg_replace('/[^A-Za-z0-9]/', '', $_GET['placa']) : '';

if ($placa === '') {
    http_response_code(400);
    exit('Placa no especificada.');
}

$dir = __DIR__ . DIRECTORY_SEPARATOR . 'POLIZAS_2026' . DIRECTORY_SEPARATOR;

// Busca cualquier archivo cuyo nombre termine con la placa.
$matches = glob($dir . '*' . $placa . '.pdf');

if (!$matches || !is_file($matches[0])) {
    http_response_code(404);
    exit('No se encontró la póliza para la placa ' . htmlspecialchars($placa) . '.');
}

$archivo = $matches[0];

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="poliza_' . $placa . '.pdf"');
header('Content-Length: ' . filesize($archivo));
readfile($archivo);
exit;

<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/kobold_control.php';
$kobold = new KoboldControl();

$input = json_decode(file_get_contents('php://input'), true);
$accion = isset($input['accion']) ? $input['accion'] : '';

$response = ['success' => false, 'message' => 'Acción no válida', 'activo' => false];

if ($accion === 'iniciar') {
    // Dispara el ejecutable y evalúa el puerto tras el tiempo de gracia interno
    $resultado = $kobold->iniciar();
    $response = [
        'success' => true, 
        'activo' => $resultado, 
        'message' => $resultado ? 'Motor iniciado y respondiendo.' : 'Orden enviada, esperando disponibilidad del puerto.'
    ];
} elseif ($accion === 'detener') {
    // Mata los procesos definidos en la clase
    $kobold->detener();
    $response = [
        'success' => true, 
        'activo' => false, 
        'message' => 'Señal de detención ejecutada.'
    ];
} elseif ($accion === 'estado') {
    // Consulta limpia mediante fsockopen al puerto 5001
    $activo = $kobold->estaActivo();
    $response = [
        'success' => true, 
        'activo' => $activo
    ];
}

echo json_encode($response);
exit;
?>
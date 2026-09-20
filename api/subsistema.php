<?php
// api/subsistema.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/kobold_control.php';

$kobold = new KoboldControl();
$accion = $_GET['accion'] ?? null;

// 1. Petición de estado desde el script (api/subsistema.php?accion=estado)
if ($accion === 'estado') {
    echo json_encode(['activo' => $kobold->estaActivo()]);
    exit;
}

// 2. Petición de inicio desde el script (api/subsistema.php?accion=iniciar)
if ($accion === 'iniciar') {
    echo json_encode($kobold->iniciar());
    exit;
}

// 3. Petición de parada desde el script (api/subsistema.php?accion=detener)
if ($accion === 'detener') {
    echo json_encode($kobold->detener());
    exit;
}

// 4. Envío de mensajes del chat
if (!$kobold->estaActivo()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Asegúrate de que KoboldCpp esté ejecutándose en el puerto 5001.'
    ]);
    exit;
}

$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true);

if (!isset($data['messages']) || !is_array($data['messages'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Estructura de mensajes no válida.'
    ]);
    exit;
}

$payload = [
    'model' => 'qwen2.5-coder-1.5b-instruct',
    'messages' => array_slice($data['messages'], -8),
    'temperature' => 0.7,
    'max_tokens' => 512,
    'stream' => false
];

$ch = curl_init('http://127.0.0.1:5001/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $resData = json_decode($response, true);
    $reply = $resData['choices'][0]['message']['content'] ?? 'Sin respuesta del motor.';
    
    echo json_encode([
        'status' => 'ok',
        'response' => $reply
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Asegúrate de que KoboldCpp esté ejecutándose en el puerto 5001.'
    ]);
}
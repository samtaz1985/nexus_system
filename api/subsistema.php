<?php
// api/subsistema.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/logger.php';

$dbActiva = false;
$iaActiva = false;

// 1. Validar conexión a Base de Datos
if (isset($pdo)) {
    try {
        $pdo->query("SELECT 1");
        $dbActiva = true;
    } catch (Exception $e) {
        $dbActiva = false;
    }
}

// 2. Validar conexión a KoboldCpp (probando puerto local)
$ch = curl_init('http://127.0.0.1:5001/api/v1/model');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Si responde 200 OK, el servicio está activo
if ($httpCode === 200 && !$curlError) {
    $iaActiva = true;
}

// Respuesta compatible tanto con el formato 'activo' como con 'servicios'
$respuesta = [
    'status'    => ($dbActiva && $iaActiva) ? 'ok' : 'warning',
    'timestamp' => date('Y-m-d H:i:s'),
    'activo'    => $iaActiva,
    'db_activa' => $dbActiva,
    'servicios' => [
        'database'  => $dbActiva,
        'koboldcpp' => $iaActiva
    ]
];

echo json_encode($respuesta, JSON_PRETTY_PRINT);
exit;
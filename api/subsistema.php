<?php
// api/subsistema.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/logger.php';
require_once __DIR__ . '/../config/kobold_control.php';

$kobold = new KoboldControl();
$dbActiva = false;

// 1. Manejo de Acciones (Iniciar / Detener via POST o GET)
$accion = $_REQUEST['accion'] ?? null;

if ($accion === 'iniciar') {
    $res = $kobold->iniciar();
    echo json_encode($res, JSON_PRETTY_PRINT);
    exit;
}

if ($accion === 'detener') {
    $res = $kobold->detener();
    echo json_encode($res, JSON_PRETTY_PRINT);
    exit;
}

// 2. Validar conexión a Base de Datos
if (isset($pdo)) {
    try {
        $pdo->query("SELECT 1");
        $dbActiva = true;
    } catch (Exception $e) {
        $dbActiva = false;
    }
}

// 3. Validar estado del motor IA (KoboldCpp)
$iaActiva = $kobold->estaActivo();

// 4. Respuesta de Monitoreo
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
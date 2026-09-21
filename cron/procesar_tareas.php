<?php
// cron/procesar_tareas.php
error_reporting(0);
ini_set('display_errors', 0);

if (php_sapi_name() !== 'cli') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/logger.php';

if (!isset($pdo)) {
    registrarLog('cron_tareas', 'error_conexion', 'No hay conexion a la base de datos', 'error');
    echo json_encode(['status' => 'error', 'mensaje' => 'Sin conexión a la base de datos.']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, texto FROM tareas WHERE completada = 0 AND (resultado_ia IS NULL OR resultado_ia = '') ORDER BY id ASC LIMIT 1");
    $tarea = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tarea) {
        registrarLog('cron_tareas', 'sin_tareas', 'No hay tareas pendientes por procesar', 'info');
        echo json_encode(['status' => 'ok', 'mensaje' => 'No hay tareas pendientes por procesar.']);
        exit;
    }
} catch (Exception $e) {
    registrarLog('cron_tareas', 'error_query', $e->getMessage(), 'error');
    echo json_encode(['status' => 'error', 'mensaje' => 'Error al consultar tareas: ' . $e->getMessage()]);
    exit;
}

// Consultar memorias activas
$contextoMemoria = "";
try {
    $stmtMem = $pdo->query("SELECT tipo, clave, valor FROM nexus_memoria ORDER BY relevancia DESC LIMIT 5");
    $memorias = $stmtMem->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($memorias)) {
        $mLista = [];
        foreach ($memorias as $m) {
            $mLista[] = "- " . strtoupper($m['tipo']) . " (" . $m['clave'] . "): " . $m['valor'];
        }
        $contextoMemoria = implode("\n", $mLista);
    }
} catch (Exception $e) {}

$systemPrompt = "Eres NIAH, el motor autónomo de Nexus System.\n"
              . "Antes de responder, analiza la solicitud dentro de las etiquetas <pensamiento>...</pensamiento> guiándote por estas reglas:\n"
              . "1. Evalúa el objetivo de la tarea técnica.\n"
              . "2. Genera una solución o plan de acción directo en texto plano (NO utilices JSON ni estructuras complejas).\n"
              . "3. Formula el resultado final fuera de las etiquetas de forma técnica, limpia y en español.\n\n"
              . "=== REGLAS Y MEMORIAS DEL SISTEMA ===\n" . $contextoMemoria;

$promptCompleto = "### System:\n" . $systemPrompt . "\n\n"
                . "### User:\nAnaliza y propone la solución técnica para la tarea ID [" . $tarea['id'] . "]: " . $tarea['texto'] . "\n\n"
                . "### Assistant:\n";

$payload = json_encode([
    'prompt' => $promptCompleto,
    'max_context_length' => 2048,
    'max_length' => 512,
    'temperature' => 0.16,
    'top_p' => 0.85,
    'rep_pen' => 1.18,
    'stop_sequence' => ["### User:", "### System:", "### Assistant:", "=== REGLAS", "=== END", "=== FIN"]
]);

$ch = curl_init('http://127.0.0.1:5001/api/v1/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    registrarLog('cron_tareas', 'error_curl', $curl_error, 'error');
    echo json_encode(['status' => 'error', 'mensaje' => 'Error cURL: ' . $curl_error]);
    exit;
}

$data = json_decode($response, true);

if (is_array($data) && isset($data['results'][0]['text'])) {
    $resultadoIA = trim($data['results'][0]['text']);

    // Extracción y log del pensamiento interno de NIAH
    if (preg_match('/<pensamiento>(.*?)<\/pensamiento>/s', $resultadoIA, $matches)) {
        $pensamientoInterno = trim($matches[1]);
        if (function_exists('registrarLog')) {
            registrarLog('niah_reasoning', 'pensamiento_cron', "Tarea ID [{$tarea['id']}]: " . $pensamientoInterno, 'info');
        }
        $resultadoIA = preg_replace('/<pensamiento>(.*?)<\/pensamiento>/s', '', $resultadoIA);
    }

    // Limpieza profunda contra bucles de cierre (=== FIN DE... ===, === REGLAS... ===, etc.)
    $resultadoIA = preg_replace('/===\s*(FIN|REGLAS|END).*$/is', '', $resultadoIA);
    $resultadoIA = preg_replace('/###\s*(User|Assistant|System):?/i', '', $resultadoIA);

    $resultadoIA = trim($resultadoIA);

    try {
        $update = $pdo->prepare("UPDATE tareas SET resultado_ia = ?, fecha_procesado = NOW() WHERE id = ?");
        $update->execute([$resultadoIA, $tarea['id']]);

        // Registrar éxito en logs
        registrarLog('cron_tareas', 'tarea_procesada', "Tarea ID [{$tarea['id']}] analizada con éxito por NIAH.", 'success');

        echo json_encode([
            'status' => 'ok',
            'tarea_id' => $tarea['id'],
            'analisis' => $resultadoIA
        ]);
    } catch (Exception $e) {
        registrarLog('cron_tareas', 'error_bd_update', $e->getMessage(), 'error');
        echo json_encode(['status' => 'error', 'mensaje' => 'Error al actualizar BD: ' . $e->getMessage()]);
    }
} else {
    registrarLog('cron_tareas', 'error_respuesta_ia', 'Respuesta inválida o vacía de KoboldCpp', 'error');
    echo json_encode(['status' => 'error', 'mensaje' => 'Respuesta no válida de KoboldCpp.']);
}
exit;